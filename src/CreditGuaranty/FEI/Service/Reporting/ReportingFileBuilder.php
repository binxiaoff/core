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
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Repository\FinancingObjectRepository;
use Symfony\Component\Filesystem\Filesystem;

class ReportingFileBuilder
{
    private FinancingObjectRepository $financingObjectRepository;
    private ReportingQueryGenerator $reportingQueryGenerator;
    private string $directoryTemporary;
    private Filesystem $fileSystem;

    public function __construct(
        FinancingObjectRepository $financingObjectRepository,
        ReportingQueryGenerator $reportingQueryGenerator,
        Filesystem $filesystem,
        string $directoryTemporary
    ) {
        $this->financingObjectRepository = $financingObjectRepository;
        $this->reportingQueryGenerator   = $reportingQueryGenerator;
        $this->directoryTemporary        = $directoryTemporary;
        $this->fileSystem                = $filesystem;
    }

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     * @throws \Exception
     */
    public function createFile(ReportingTemplate $reportingTemplate, array $filters): string
    {
        $headerRow = $this->buildHeader($reportingTemplate);

        if (false === $this->fileSystem->exists($this->directoryTemporary)) {
            $this->fileSystem->mkdir($this->directoryTemporary, 0700);
        }

        $tmpfname = $this->fileSystem->tempnam($this->directoryTemporary, '', '.xlsx');
        $writer   = WriterFactory::createFromType(Type::XLSX);
        $writer->openToFile($tmpfname);
        $writer->addRow($headerRow);

        $rowStyle = (new StyleBuilder())->build();
        $offset   = 0;
        $limit    = 1000;

        $query = $this->reportingQueryGenerator->generate($filters, $reportingTemplate);
        while (
            $reporting = $this->financingObjectRepository->findByReportingFilters(
                $reportingTemplate->getProgram(),
                $query,
                $offset,
                $limit
            )
        ) {
            foreach ($reporting as $item) {
                unset($item['id_financing_object']);
                $writer->addRow(WriterEntityFactory::createRowFromArray(\array_values($item), $rowStyle));
            }
            unset($reporting);
            $offset += $limit;
        }

        $writer->close();

        return $tmpfname;
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
}
