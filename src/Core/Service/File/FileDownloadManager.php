<?php

declare(strict_types=1);

namespace KLS\Core\Service\File;

use Box\Spout\Writer\XLSX\Writer;
use Exception;
use KLS\Core\Entity\FileVersion;
use KLS\Core\Service\FileSystem\FileSystemHelper;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDownloadManager
{
    /** @var FileSystemHelper */
    private $fileSystemHelper;

    public function __construct(FileSystemHelper $fileSystemHelper)
    {
        $this->fileSystemHelper = $fileSystemHelper;
    }

    /**
     * @throws Exception
     */
    public function download(FileVersion $fileVersion): StreamedResponse
    {
        $filePath         = $fileVersion->getPath();
        $fileSystemHelper = $this->fileSystemHelper;
        $response         = new StreamedResponse(static function () use ($fileVersion, $fileSystemHelper) {
            \stream_copy_to_stream($fileSystemHelper->readStream($fileVersion), \fopen('php://output', 'w+b'));
        });

        $fileName         = $this->fileSystemHelper->normalizeFileName($fileVersion->getOriginalName() ?? \pathinfo($filePath, PATHINFO_FILENAME));
        $fileNameFallback = \preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $fileName);

        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName, $fileNameFallback);
        $response->headers->set('Content-Disposition', $contentDisposition);
        $response->headers->set('Content-Type', $fileVersion->getMimetype() ?: 'application/octet-stream');
        $response->headers->set('Content-Length', $fileVersion->getSize());

        return $response;
    }

    public function downloadNewXlsxFile(Writer $writer, array $rows, string $fileName): StreamedResponse
    {
        $response = new StreamedResponse(static function () use ($writer, $rows) {
            $writer->openToFile('php://output');
            foreach ($rows as $row) {
                $writer->addRow($row);
            }
            $writer->close();
        });

        $fileNameFallback = \preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $fileName);

        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName, $fileNameFallback);
        $response->headers->set('Content-Disposition', $contentDisposition);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        return $response;
    }
}
