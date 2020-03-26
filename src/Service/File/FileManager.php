<?php

declare(strict_types=1);

namespace Unilend\Service\File;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use InvalidArgumentException;
use League\Flysystem\{FileExistsException, FileNotFoundException, FilesystemInterface};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\{Clients, File, FileVersion, Staff};
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
    public function __construct(FilesystemInterface $userAttachmentFilesystem, FileUploadManager $fileUploadManager, FileRepository $fileRepository)
    {
        $this->userAttachmentFilesystem = $userAttachmentFilesystem;
        $this->fileUploadManager        = $fileUploadManager;
        $this->fileRepository           = $fileRepository;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param Staff        $uploader
     * @param File|null    $file
     * @param string|null  $description
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Exception
     * @throws FileExistsException
     *
     * @return File
     */
    public function upload(UploadedFile $uploadedFile, Staff $uploader, ?File $file = null, string $description = null): File
    {
        $mineType                               = $uploadedFile->getMimeType();
        [$relativeUploadedPath, $encryptionKey] = $this->fileUploadManager
            ->uploadFile($uploadedFile, $this->userAttachmentFilesystem, '/', $this->getClientDirectory($uploader->getClient()))
        ;

        if (null === $file) {
            $file = new File();
        }

        $fileVersion = new FileVersion($relativeUploadedPath, $uploader, $file, FileVersion::FILE_SYSTEM_USER_ATTACHMENT, $encryptionKey, $mineType);
        $fileVersion->setOriginalName($uploadedFile->getClientOriginalName())
            ->setSize($uploadedFile->getSize())
        ;

        $file->setCurrentFileVersion($fileVersion)->setDescription($description);

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
