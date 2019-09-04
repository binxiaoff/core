<?php

declare(strict_types=1);

namespace Unilend\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\Project;

class ProjectImageManager
{
    public const PROJECT_IMAGE_DIRECTORY = 'images/projects/';

    /**
     * @var string
     */
    private $userUploadDirectory;

    /**
     * @var FileUploadManager
     */
    private $uploadManager;

    /**
     * ProjectImageManager constructor.
     *
     * @param string            $userUploadDirectory
     * @param FileUploadManager $uploadManager
     */
    public function __construct(
        string $userUploadDirectory,
        FileUploadManager $uploadManager
    ) {
        $this->userUploadDirectory = $userUploadDirectory;
        $this->uploadManager       = $uploadManager;
    }

    /**
     * @param Project           $project
     * @param UploadedFile|null $file
     */
    public function setImage(Project $project, ?UploadedFile $file): void
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
     * @param UploadedFile $file
     *
     * @return string
     */
    private function uploadImage(UploadedFile $file): string
    {
        $relativeFilePath = $this->uploadManager->uploadFile($file, $this->getProjectImageRootDirectory());

        return self::PROJECT_IMAGE_DIRECTORY . $relativeFilePath;
    }

    /**
     * @param Project $project
     */
    private function removeImage(Project $project): void
    {
        if (!$project->getImage()) {
            return;
        }

        $filepath = $this->userUploadDirectory . $project->getImage();

        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    /**
     * @return string
     */
    private function getProjectImageRootDirectory(): string
    {
        return $this->userUploadDirectory . self::PROJECT_IMAGE_DIRECTORY;
    }
}
