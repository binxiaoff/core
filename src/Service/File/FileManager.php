<?php

declare(strict_types=1);

namespace Unilend\Service\File;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use InvalidArgumentException;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\{Clients, File, FileVersion, Project, Staff};
use Unilend\Repository\FileRepository;
use Unilend\Service\FileSystem\FileUploadManager;

class FileManager
{
    /** @var FileUploadManager */
    private $fileUploadManager;
    /** @var FilesystemInterface */
    private $userAttachmentFilesystem;
    /** @var FileRepository */
    private $fileRepository;

    /**
     * @param FilesystemInterface $userAttachmentFilesystem
     * @param FileUploadManager   $fileUploadManager
     * @param FileRepository      $fileRepository
     */
    public function __construct(
        FilesystemInterface $userAttachmentFilesystem,
        FileUploadManager $fileUploadManager,
        FileRepository $fileRepository
    ) {
        $this->userAttachmentFilesystem = $userAttachmentFilesystem;
        $this->fileUploadManager        = $fileUploadManager;
        $this->fileRepository           = $fileRepository;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param Staff        $uploader
     * @param string|null  $description
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     * @throws FileExistsException
     *
     * @return FileVersion
     */
    public function upload(
        UploadedFile $uploadedFile,
        Staff $uploader,
        ?string $description = null
    ): FileVersion {
        $mineType                               = $uploadedFile->getMimeType();
        [$relativeUploadedPath, $encryptionKey] = $this->fileUploadManager
            ->uploadFile($uploadedFile, $this->userAttachmentFilesystem, '/', $this->getClientDirectory($uploader->getClient()))
        ;

        $file        = new File();
        $fileVersion = new FileVersion($relativeUploadedPath, $uploader, $file, $encryptionKey, $mineType);
        $fileVersion->setFileSystem(FileVersion::FILE_SYSTEM_USER_ATTACHMENT)
            ->setOriginalName($uploadedFile->getClientOriginalName())
            ->setSize($uploadedFile->getSize())
        ;

        $file->setCurrentFileVersion($fileVersion);

        $this->fileRepository->save($file);

        return $file;
    }

    /**
     * @param File         $file
     * @param UploadedFile $uploadedFile
     * @param Staff        $uploader
     *
     * @throws FileExistsException
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return FileVersion
     */
    public function uploadFile(
        File $file,
        UploadedFile $uploadedFile,
        Staff $uploader
    ): FileVersion {
        $relativeUploadedPath = $this->fileUploadManager
            ->uploadFile($uploadedFile, $this->userAttachmentFilesystem, '/', $this->getClientDirectory($uploader->getClient()))
        ;

        if (null === $file) {
            $file = new File();
        }

        $fileVersion = new FileVersion($relativeUploadedPath, $uploader, $file);
        $fileVersion->setFileSystem(FileVersion::FILE_SYSTEM_USER_ATTACHMENT)
            ->setOriginalName($uploadedFile->getClientOriginalName())
            ->setSize($uploadedFile->getSize())
        ;

        $file->setCurrentFileVersion($fileVersion);

        $this->fileRepository->save($file);

        return $file;
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
