<?php

declare(strict_types=1);

namespace Unilend\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use URLify;

class FileUploadManager
{
    /**
     * @param UploadedFile $file
     * @param string       $uploadRootFolder
     * @param string|null  $subdirectory
     *
     * @return string
     */
    public function uploadFile(UploadedFile $file, string $uploadRootFolder, string $subdirectory = null): string
    {
        $hash         = hash('sha256', $subdirectory ?? uniqid());
        $subdirectory = $hash[0] . DIRECTORY_SEPARATOR . $hash[1] . ($subdirectory ? DIRECTORY_SEPARATOR . $subdirectory : '');

        $uploadRootFolder = $this->normalizePath($uploadRootFolder);
        $uploadFolder     = $uploadRootFolder . DIRECTORY_SEPARATOR . $subdirectory;

        $filename = $this->generateFileName($file, $uploadFolder);

        $file->move($uploadFolder, $filename);

        return $subdirectory . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string       $uploadAbsolutePath
     *
     * @return string
     */
    private function generateFileName(UploadedFile $uploadedFile, string $uploadAbsolutePath): string
    {
        $originalFilename      = URLify::filter(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName              = $originalFilename . '-' . md5(uniqid());
        $fileExtension         = $uploadedFile->guessExtension() ?? $uploadedFile->getClientOriginalExtension();
        $fileNameWithExtension = $fileName . '.' . $fileExtension;

        if (file_exists($uploadAbsolutePath . DIRECTORY_SEPARATOR . $fileNameWithExtension)) {
            $fileNameWithExtension = $this->generateFileName($uploadedFile, $uploadAbsolutePath);
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
        $path = realpath($path);

        return DIRECTORY_SEPARATOR === mb_substr($path, -1) ? mb_substr($path, 0, -1) : $path;
    }
}
