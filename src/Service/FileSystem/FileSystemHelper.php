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
     * @param string              $temporaryFilePath
     * @param FilesystemInterface $filesystem
     * @param string              $filesystemDestPath
     *
     * @throws FileExistsException
     */
    public function writeTempFileToFileSystem(string $temporaryFilePath, FilesystemInterface $filesystem, string $filesystemDestPath): void
    {
        $fileResource = fopen($temporaryFilePath, 'r+b');

        if (is_resource($fileResource)) {
            $result = $filesystem->writeStream($filesystemDestPath, $fileResource);
            fclose($fileResource);

            if (false === $result) {
                throw new RuntimeException(sprintf('Could not write file "%s"', $temporaryFilePath));
            }
        }

        @unlink($temporaryFilePath);
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
