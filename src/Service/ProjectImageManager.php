<?php

declare(strict_types=1);

namespace Unilend\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\Project;
use Unilend\Traits\GenerateFileNameTrait;

class ProjectImageManager
{
    use GenerateFileNameTrait;

    public const PROJECT_IMAGE_DIRECTORY = 'images/projects/';

    /**
     * @var string
     */
    private $userUploadDirectory;

    /**
     * ProjectImageManager constructor.
     *
     * @param string $userUploadDirectory
     */
    public function __construct(string $userUploadDirectory)
    {
        $this->userUploadDirectory = $userUploadDirectory;
    }

    /**
     * @param UploadedFile $file
     *
     * @return string
     */
    public function uploadImage(UploadedFile $file)
    {
        $hash      = hash('sha256', uniqid());
        $subfolder = $hash[0] . DIRECTORY_SEPARATOR . $hash[1];

        $imageFolder = $this->getProjectImageRootDirectory() . DIRECTORY_SEPARATOR . $subfolder;

        $filename = $this->generateFileName($file, $imageFolder);

        $file->move($imageFolder, $filename);

        return self::PROJECT_IMAGE_DIRECTORY . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * @param Project $project
     */
    public function removeImage(Project $project)
    {
        if (!$project->getImage()) {
            return;
        }

        $filepath = $this->userUploadDirectory . DIRECTORY_SEPARATOR . $project->getImage();

        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    /**
     * @param Project           $project
     * @param UploadedFile|null $file
     */
    public function setImage(Project $project, ?UploadedFile $file)
    {
        if ($project->getImage()) {
            $this->removeImage($project);
            $project->setImage(null);
        }

        if ($file) {
            $relativeFilePath = $this->uploadImage($file);
            $project->setImage($relativeFilePath);
        }
    }

    /**
     * @return string
     */
    private function getProjectImageRootDirectory()
    {
        return $this->userUploadDirectory . DIRECTORY_SEPARATOR . self::PROJECT_IMAGE_DIRECTORY;
    }
}
