<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\Reporting;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Entity\Style\Border;
use Box\Spout\Common\Entity\Style\CellAlignment;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Common\Entity\Style\Style;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\Style\BorderBuilder;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterFactory;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\IOException as CryptoIOException;
use Exception;
use InvalidArgumentException;
use KLS\Core\Entity\File;
use KLS\Core\Entity\FileVersion;
use KLS\Core\Entity\User;
use KLS\Core\Service\FileSystem\FileSystemHelper;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Repository\FinancingObjectRepository;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;

class ReportingFileBuilder
{
    private const XLSX_EXTENSION = 'xlsx';
    private const ROOT_DIRECTORY = 'reporting_fei';

    private FinancingObjectRepository $financingObjectRepository;
    private ReportingQueryGenerator $reportingQueryGenerator;
    private string $temporaryDirectory;
    private Filesystem $fileSystem;
    private FilesystemOperator $generatedDocumentFilesystem;
    private FileSystemHelper $fileSystemHelper;

    public function __construct(
        FinancingObjectRepository $financingObjectRepository,
        ReportingQueryGenerator $reportingQueryGenerator,
        Filesystem $filesystem,
        string $temporaryDirectory,
        FilesystemOperator $generatedDocumentFilesystem,
        FileSystemHelper $fileSystemHelper
    ) {
        $this->financingObjectRepository   = $financingObjectRepository;
        $this->reportingQueryGenerator     = $reportingQueryGenerator;
        $this->temporaryDirectory          = $temporaryDirectory;
        $this->fileSystem                  = $filesystem;
        $this->generatedDocumentFilesystem = $generatedDocumentFilesystem;
        $this->fileSystemHelper            = $fileSystemHelper;
    }

    /**
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     * @throws FilesystemException
     * @throws IOException
     * @throws CryptoIOException
     * @throws EnvironmentIsBrokenException
     */
    public function build(ReportingTemplate $reportingTemplate, array $filters, User $builtBy): File
    {
        $filePath = $this->createFile($reportingTemplate, $filters);

        return $this->writeToFileSystem($filePath, $builtBy, $reportingTemplate);
    }

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     * @throws Exception
     */
    private function createFile(ReportingTemplate $reportingTemplate, array $filters): string
    {
        $headerRow = $this->buildHeader($reportingTemplate);

        if (false === $this->fileSystem->exists($this->temporaryDirectory)) {
            $this->fileSystem->mkdir($this->temporaryDirectory, 0700);
        }

        $tmpName = $this->fileSystem->tempnam($this->temporaryDirectory, '', '.xlsx');
        $writer  = WriterFactory::createFromType(Type::XLSX);
        $writer->openToFile($tmpName);
        $writer->addRow($headerRow);

        $rowStyle = (new StyleBuilder())->build();
        $offset   = 0;
        $limit    = 1000;

        $query = $this->reportingQueryGenerator->generate($filters, $reportingTemplate);
        while (
            $reportingData = $this->financingObjectRepository->findByReportingFilters(
                $reportingTemplate->getProgram(),
                $query,
                $offset,
                $limit
            )
        ) {
            foreach ($reportingData as $item) {
                unset($item['id_financing_object']);
                $writer->addRow(WriterEntityFactory::createRowFromArray(\array_values($item), $rowStyle));
            }
            unset($reportingData);
            $offset += $limit;
        }

        $writer->close();

        return $tmpName;
    }

    /**
     * @throws FilesystemException
     * @throws EnvironmentIsBrokenException
     * @throws CryptoIOException
     * @throws Exception
     */
    private function writeToFileSystem(string $tmpName, User $builtBy, ReportingTemplate $reportingTemplate): File
    {
        $uploadDirectory = $this->fileSystemHelper->normalizeDirectory(
            self::ROOT_DIRECTORY,
            $this->getUserDirectory($builtBy)
        );
        $destFilePath = $uploadDirectory
            . DIRECTORY_SEPARATOR
            . $this->generateFileName($reportingTemplate, $uploadDirectory);
        $encryptionKey = $this->fileSystemHelper->writeTempFileToFileSystem(
            $tmpName,
            $this->generatedDocumentFilesystem,
            $destFilePath
        );

        $file = $this->mapToFile(
            $destFilePath,
            MimeTypes::getDefault()->guessMimeType($tmpName),
            $encryptionKey,
            $builtBy
        );

        $this->fileSystem->remove($tmpName);

        return $file;
    }

    /**
     * @throws Exception
     */
    private function mapToFile(string $filePath, string $mimeType, string $encryptionKey, User $builtBy): File
    {
        $fileName    = \pathinfo($filePath)['basename'];
        $file        = new File($fileName);
        $fileVersion = new FileVersion(
            $filePath,
            $builtBy,
            $file,
            FileVersion::FILE_SYSTEM_GENERATED_DOCUMENT,
            $encryptionKey,
            $mimeType
        );
        $fileVersion->setOriginalName($fileName);
        $file->setCurrentFileVersion($fileVersion);

        return $file;
    }

    private function buildHeader(ReportingTemplate $reportingTemplate): Row
    {
        $reportingTemplateFields = $reportingTemplate->getOrderedFields(true);
        $orderedFields           = \array_merge(
            \array_keys(FieldAlias::MAPPING_REPORTING_DATES),
            $reportingTemplateFields
        );

        return WriterEntityFactory::createRowFromArray($orderedFields, $this->headerStyle());
    }

    private function headerStyle(): Style
    {
        $border = (new BorderBuilder())
            ->setBorderBottom(Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)
            ->build()
        ;

        return (new StyleBuilder())
            ->setFontBold()
            ->setCellAlignment(CellAlignment::CENTER)
            ->setBorder($border)
            ->build()
        ;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getUserDirectory(User $user): string
    {
        if (null === $user->getId()) {
            throw new InvalidArgumentException('Cannot find the upload destination. The user id is empty.');
        }

        return (string) $user->getId();
    }

    /**
     * @throws FilesystemException
     */
    private function generateFileName(ReportingTemplate $reportingTemplate, string $uploadDirectory): string
    {
        $date                  = new \DateTimeImmutable();
        $fileNameWithExtension = $reportingTemplate->getProgram()->getName() . '_' .
            $reportingTemplate->getName() . '_' . $date->getTimestamp() . '.' . self::XLSX_EXTENSION;

        if (
            $this->generatedDocumentFilesystem->fileExists(
                $uploadDirectory . DIRECTORY_SEPARATOR . $fileNameWithExtension
            )
        ) {
            $fileNameWithExtension = $this->generateFileName($reportingTemplate, $uploadDirectory);
        }

        return $fileNameWithExtension;
    }
}
