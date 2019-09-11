<?php

declare(strict_types=1);

namespace Unilend\Service\FileSystem;

use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use RuntimeException;

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
}
