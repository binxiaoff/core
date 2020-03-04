<?php

declare(strict_types=1);

namespace Unilend\Service\Attachment;

use Exception;
use InvalidArgumentException;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\{Attachment, Clients, Project, Staff};
use Unilend\Service\FileSystem\FileUploadManager;

class AttachmentManager
{
    /** @var FileUploadManager */
    private $fileUploadManager;
    /** @var FilesystemInterface */
    private $userAttachmentFilesystem;

    /**
     * @param FilesystemInterface $userAttachmentFilesystem
     * @param FileUploadManager   $fileUploadManager
     */
    public function __construct(
        FilesystemInterface $userAttachmentFilesystem,
        FileUploadManager $fileUploadManager
    ) {
        $this->userAttachmentFilesystem = $userAttachmentFilesystem;
        $this->fileUploadManager        = $fileUploadManager;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param Staff        $uploader
     * @param string       $type
     * @param Project      $project
     * @param string|null  $description
     *
     * @throws Exception
     * @throws FileExistsException
     *
     * @return Attachment
     */
    public function upload(
        UploadedFile $uploadedFile,
        Staff $uploader,
        string $type,
        Project $project,
        ?string $description = null
    ): Attachment {
        $relativeUploadedPath = $this->fileUploadManager
            ->uploadFile($uploadedFile, $this->userAttachmentFilesystem, '/', $this->getClientDirectory($uploader->getClient()))
        ;

        $attachment = new Attachment($relativeUploadedPath, $type, $uploader, $project);

        $attachment
            ->setOriginalName($uploadedFile->getClientOriginalName())
            ->setProject($project)
            ->setDescription($description)
            ->setSize($this->userAttachmentFilesystem->getSize($relativeUploadedPath))
        ;

        return $attachment;
    }

    /**
     * @param Attachment $attachment
     *
     * @throws FileNotFoundException
     *
     * @return false|resource
     */
    public function read(Attachment $attachment)
    {
        return $this->userAttachmentFilesystem->read($attachment->getPath());
    }

    /**
     * @param Clients $client
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    private function getClientDirectory(Clients $client): string
    {
        if (empty($client->getId())) {
            throw new InvalidArgumentException('Cannot find the upload destination. The client id is empty.');
        }

        return (string) $client->getId();
    }
}
