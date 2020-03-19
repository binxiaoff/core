<?php

declare(strict_types=1);

namespace Unilend\Service\File;

use Exception;
use InvalidArgumentException;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\{Clients, FileVersion, Project, Staff};
use Unilend\Repository\FileVersionRepository;
use Unilend\Service\FileSystem\FileUploadManager;

class FileManager
{
    /** @var FileUploadManager */
    private $fileUploadManager;
    /** @var FilesystemInterface */
    private $userAttachmentFilesystem;
    /** @var FileVersionRepository */
    private $fileVersionRepository;

    /**
     * @param FilesystemInterface   $userAttachmentFilesystem
     * @param FileUploadManager     $fileUploadManager
     * @param FileVersionRepository $fileVersionRepository
     */
    public function __construct(
        FilesystemInterface $userAttachmentFilesystem,
        FileUploadManager $fileUploadManager,
        FileVersionRepository $fileVersionRepository
    ) {
        $this->userAttachmentFilesystem = $userAttachmentFilesystem;
        $this->fileUploadManager        = $fileUploadManager;
        $this->fileVersionRepository    = $fileVersionRepository;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param Staff        $uploader
     * @param string       $type
     * @param Project      $project
     * @param string|null  $description
     *
     *@throws FileExistsException
     * @throws Exception
     *
     * @return FileVersion
     */
    public function upload(
        UploadedFile $uploadedFile,
        Staff $uploader,
        string $type,
        Project $project,
        ?string $description = null
    ): FileVersion {
        $mineType                               = $uploadedFile->getMimeType();
        [$relativeUploadedPath, $encryptionKey] = $this->fileUploadManager
            ->uploadFile($uploadedFile, $this->userAttachmentFilesystem, '/', $this->getClientDirectory($uploader->getClient()))
        ;

        $attachment = new FileVersion($relativeUploadedPath, $uploader, $encryptionKey, $mineType);

        //@todo change that
        $attachment
            ->setOriginalName($uploadedFile->getClientOriginalName())
            ->setSize($this->userAttachmentFilesystem->getSize($relativeUploadedPath))
        ;

        $this->fileVersionRepository->save($attachment);

        return $attachment;
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
