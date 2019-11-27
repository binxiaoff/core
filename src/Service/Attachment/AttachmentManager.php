<?php

declare(strict_types=1);

namespace Unilend\Service\Attachment;

use DateTimeImmutable;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use InvalidArgumentException;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\{Attachment, Clients, Companies};
use Unilend\Repository\AttachmentRepository;
use Unilend\Service\FileSystem\FileUploadManager;

class AttachmentManager
{
    /** @var FileUploadManager */
    private $fileUploadManager;
    /** @var FilesystemInterface */
    private $userAttachmentFilesystem;
    /** @var AttachmentRepository $attachmentRepository */
    private $attachmentRepository;

    /**
     * @param FilesystemInterface  $userAttachmentFilesystem
     * @param FileUploadManager    $fileUploadManager
     * @param AttachmentRepository $attachmentRepository
     */
    public function __construct(
        FilesystemInterface $userAttachmentFilesystem,
        FileUploadManager $fileUploadManager,
        AttachmentRepository $attachmentRepository
    ) {
        $this->userAttachmentFilesystem = $userAttachmentFilesystem;
        $this->fileUploadManager        = $fileUploadManager;
        $this->attachmentRepository     = $attachmentRepository;
    }

    /**
     * @param UploadedFile   $uploadedFile
     * @param Clients        $uploader
     * @param string         $type
     * @param Companies|null $companyOwner
     * @param string|null    $description
     *
     * @throws Exception
     * @throws FileExistsException
     *
     * @return Attachment
     */
    public function upload(
        UploadedFile $uploadedFile,
        Clients $uploader,
        string $type,
        ?Companies $companyOwner = null,
        ?string $description = null
    ): Attachment {
        $relativeUploadedPath = $this->fileUploadManager
            ->uploadFile($uploadedFile, $this->userAttachmentFilesystem, '/', $this->getClientDirectory($uploader))
        ;

        $attachment = new Attachment($relativeUploadedPath, $type, $uploader);

        $attachment
            ->setOriginalName($uploadedFile->getClientOriginalName())
            ->setCompanyOwner($companyOwner)
            ->setDescription($description)
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
        return $this->getFileSystem()->read($attachment->getPath());
    }

    /**
     * @return FilesystemInterface
     */
    public function getFileSystem(): FilesystemInterface
    {
        return $this->userAttachmentFilesystem;
    }

    /**
     * @param Attachment $attachment
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function logDownload(Attachment $attachment): void
    {
        $attachment->setDownloaded(new DateTimeImmutable());
        $this->attachmentRepository->save($attachment);
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
        if (empty($client->getIdClient())) {
            throw new InvalidArgumentException('Cannot find the upload destination. The client id is empty.');
        }

        return (string) $client->getIdClient();
    }
}
