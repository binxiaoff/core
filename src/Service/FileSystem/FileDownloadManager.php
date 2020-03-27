<?php

declare(strict_types=1);

namespace Unilend\Service\FileSystem;

use Exception;
use Symfony\Component\HttpFoundation\{ResponseHeaderBag, StreamedResponse};
use Unilend\Entity\Attachment;
use URLify;

class FileDownloadManager
{
    /** @var FileSystemHelper */
    private $fileSystemHelper;

    /**
     * @param FileSystemHelper $fileSystemHelper
     */
    public function __construct(FileSystemHelper $fileSystemHelper)
    {
        $this->fileSystemHelper = $fileSystemHelper;
    }

    /**
     * @param Attachment $attachment
     *
     * @throws Exception
     *
     * @return StreamedResponse
     */
    public function download(Attachment $attachment): StreamedResponse
    {
        $filePath         = $attachment->getPath();
        $fileSystemHelper = $this->fileSystemHelper;
        $response         = new StreamedResponse(static function () use ($attachment, $fileSystemHelper) {
            stream_copy_to_stream($fileSystemHelper->readStream($attachment), fopen('php://output', 'w+b'));
        });

        $fileName         = URLify::downcode($attachment->getOriginalName() ?? pathinfo($filePath, PATHINFO_FILENAME));
        $fileNameFallback = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $fileName);

        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName, $fileNameFallback);
        $response->headers->set('Content-Disposition', $contentDisposition);
        $response->headers->set('Content-Type', $attachment->getMimetype() ?: 'application/octet-stream');

        return $response;
    }
}
