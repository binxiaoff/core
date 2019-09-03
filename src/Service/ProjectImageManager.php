<?php

declare(strict_types=1);

namespace Unilend\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Traits\GenerateFileNameTrait;

class ProjectImageManager
{
    use GenerateFileNameTrait;

    /**
     * @var string
     */
    private $uploadRootFolder;

    /**
     * ProjectImageManager constructor.
     *
     * @param string $uploadRootFolder
     */
    public function __construct(
        string $uploadRootFolder
    ) {
        $this->uploadRootFolder = $uploadRootFolder;
    }

    /**
     * @param UploadedFile $file
     *
     * @return string
     */
    public function upload(UploadedFile $file)
    {
        $filename = $this->generateFileName($file, $this->uploadRootFolder);

        $hash      = hash('sha256', uniqid());
        $subfolder = $hash[0] . DIRECTORY_SEPARATOR . $hash[1];

        $file->move($this->uploadRootFolder . DIRECTORY_SEPARATOR . $subfolder, $filename);

        return $subfolder . DIRECTORY_SEPARATOR . $filename;
    }
}
