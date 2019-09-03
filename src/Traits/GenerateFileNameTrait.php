<?php

declare(strict_types=1);

namespace Unilend\Traits;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use URLify;

trait GenerateFileNameTrait
{
    /**
     * @param UploadedFile $uploadedFile
     * @param string       $uploadAbsolutePath
     *
     * @return string
     */
    private function generateFileName(UploadedFile $uploadedFile, string $uploadAbsolutePath)
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
}
