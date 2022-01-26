<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Controller\Reporting;

use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterFactory;
use KLS\Core\Service\File\FileDownloadManager;
use KLS\CreditGuaranty\FEI\Service\FinancingObjectUpdater;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Download
{
    private const FILE_NAME = 'kls_credit-and-guaranty_import-file_1.0.xlsx';

    private FileDownloadManager $fileDownloadManager;

    public function __construct(FileDownloadManager $fileDownloadManager)
    {
        $this->fileDownloadManager = $fileDownloadManager;
    }

    /**
     * @throws UnsupportedTypeException
     */
    public function __invoke(): StreamedResponse
    {
        $style = (new StyleBuilder())->build();

        $writer = WriterFactory::createFromType(Type::XLSX);
        $row    = WriterEntityFactory::createRowFromArray(FinancingObjectUpdater::IMPORT_FILE_COLUMNS, $style);

        return $this->fileDownloadManager->downloadNewXlsxFile($writer, [$row], self::FILE_NAME);
    }
}
