<?php

declare(strict_types=1);

namespace Unilend\Service\FileSystem;

use League\Flysystem\{FileExistsException, FilesystemInterface};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use URLify;

class FileUploadManager
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
     * @param UploadedFile        $file
     * @param FilesystemInterface $filesystem
     * @param string              $uploadRootDirectory
     * @param string|null         $subdirectory
     *
     * @throws FileExistsException
     *
     * @return string
     */
    public function uploadFile(UploadedFile $file, FilesystemInterface $filesystem, string $uploadRootDirectory, ?string $subdirectory): string
    {
        $hash         = hash('sha256', $subdirectory ?? uniqid('', true));
        $subdirectory = $hash[0] . DIRECTORY_SEPARATOR . $hash[1] . ($subdirectory ? DIRECTORY_SEPARATOR . $subdirectory : '');

        $uploadRootDirectory = $this->normalizePath($uploadRootDirectory);
        $uploadDirectory     = $uploadRootDirectory . DIRECTORY_SEPARATOR . $subdirectory;

        $filename = $this->generateFileName($file, $filesystem, $uploadDirectory);
        $filePath = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

        $this->fileSystemHelper->writeStreamToFileSystem($file->getPathname(), $filePath, $filesystem);

        return $filePath;
    }

    /**
     * @param UploadedFile        $uploadedFile
     * @param FilesystemInterface $filesystem
     * @param string              $uploadDirectory
     *
     * @return string
     */
    private function generateFileName(UploadedFile $uploadedFile, FilesystemInterface $filesystem, string $uploadDirectory): string
    {
        $originalFilename      = URLify::filter(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));
        $fileNameWithExtension = $originalFilename . '-' . uniqid('', true) . '.' . $uploadedFile->guessExtension() ?? $uploadedFile->getClientOriginalExtension();

        if ($filesystem->has($uploadDirectory . DIRECTORY_SEPARATOR . $fileNameWithExtension)) {
            $fileNameWithExtension = $this->generateFileName($uploadedFile, $filesystem, $uploadDirectory);
        }

        return $fileNameWithExtension;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function normalizePath(string $path): string
    {
        return DIRECTORY_SEPARATOR === mb_substr($path, -1) ? mb_substr($path, 0, -1) : $path;
    }
}
