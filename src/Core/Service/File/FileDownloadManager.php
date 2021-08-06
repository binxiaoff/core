<?php

declare(strict_types=1);

namespace Unilend\Core\Service\File;

use Exception;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Unilend\Core\Entity\FileVersion;
use Unilend\Core\Service\FileSystem\FileSystemHelper;

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
}
