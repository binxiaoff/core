<?php

declare(strict_types=1);

namespace Unilend\Service\FileSystem;

use League\Flysystem\{FileExistsException, FileNotFoundException, FilesystemInterface};
use RuntimeException;
use Symfony\Component\HttpFoundation\{ResponseHeaderBag, StreamedResponse};
use URLify;

class FileSystemHelper
{
    /**
     * @param string              $srcFilePath
     * @param string              $destFilePath
     * @param FilesystemInterface $filesystem
     *
     * @throws FileExistsException
     */
    public function writeStreamToFileSystem(string $srcFilePath, string $destFilePath, FilesystemInterface $filesystem): void
    {
        $fileResource = fopen($srcFilePath, 'r+b');

        if (is_resource($fileResource)) {
            $result = $filesystem->writeStream($destFilePath, $fileResource);
            if (false === $result) {
                throw new RuntimeException(sprintf('Could not write file "%s"', $srcFilePath));
            }

            fclose($fileResource);
        }

        unlink($srcFilePath);
    }

    /**
     * @param FilesystemInterface $filesystem
     * @param string              $filePath
     * @param string|null         $fileName
     *
     * @throws FileNotFoundException
     *
     * @return StreamedResponse
     */
    public function download(FilesystemInterface $filesystem, string $filePath, ?string $fileName = null): StreamedResponse
    {
        $response = new StreamedResponse(static function () use ($filePath, $filesystem) {
            stream_copy_to_stream($filesystem->readStream($filePath), fopen('php://output', 'w+b'));
        });

        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, URLify::downcode($fileName ?? pathinfo($filePath, PATHINFO_FILENAME)));
        $response->headers->set('Content-Disposition', $contentDisposition);
        $response->headers->set('Content-Type', $filesystem->getMimetype($filePath) ?: 'application/octet-stream');

        return $response;
    }
}
