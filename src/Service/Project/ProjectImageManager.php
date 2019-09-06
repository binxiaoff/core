<?php

declare(strict_types=1);

namespace Unilend\Service\Project;

use League\Flysystem\{FileExistsException, FileNotFoundException, FilesystemInterface};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\Project;
use Unilend\Service\FileSystem\FileUploadManager;

class ProjectImageManager
{
    public const PROJECT_IMAGE_DIRECTORY = 'image/project/';

    /** @var FileUploadManager */
    private $uploadManager;
    /** @var FilesystemInterface */
    private $userUploadFilesystem;

    /**
     * ProjectImageManager constructor.
     *
     * @param FileUploadManager   $uploadManager
     * @param FilesystemInterface $userUploadFilesystem
     */
    public function __construct(
        FileUploadManager $uploadManager,
        FilesystemInterface $userUploadFilesystem
    ) {
        $this->uploadManager        = $uploadManager;
        $this->userUploadFilesystem = $userUploadFilesystem;
    }

    /**
     * @param Project           $project
     * @param UploadedFile|null $file
     *
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    public function setImage(Project $project, ?UploadedFile $file): void
    {
        if ($project->getImage()) {
            $this->removeImage($project);
            $project->setImage(null);
        }

        if ($file) {
            $relativeFilePath = $this->uploadImage($project, $file);
            $project->setImage($relativeFilePath);
        }
    }

    /**
     * @param Project      $project
     * @param UploadedFile $file
     *
     * @throws FileExistsException
     *
     * @return string
     */
    private function uploadImage(Project $project, UploadedFile $file): string
    {
        return $this->uploadManager->uploadFile($file, $this->userUploadFilesystem, self::PROJECT_IMAGE_DIRECTORY, $project->getId() ? (string) $project->getId() : null);
    }

    /**
     * @param Project $project
     *
     * @throws FileNotFoundException
     */
    private function removeImage(Project $project): void
    {
        if (!$project->getImage()) {
            return;
        }

        if ($this->userUploadFilesystem->has($project->getImage())) {
            $this->userUploadFilesystem->delete($project->getImage());
        }
    }
}
