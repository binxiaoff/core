<?php

declare(strict_types=1);

namespace Unilend\Service\Attachment;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use InvalidArgumentException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\{Attachment, Clients, ProjectAttachment};
use Unilend\Repository\AttachmentRepository;
use Unilend\Service\FileSystem\FileUploadManager;
use Unilend\Service\User\RealUserFinder;

class AttachmentManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var RealUserFinder */
    private $realUserFinder;
    /** @var FileUploadManager */
    private $fileUploadManager;
    /** @var FilesystemInterface */
    private $userAttachmentFilesystem;
    /** @var AttachmentRepository $attachmentRepository */
    private $attachmentRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param FilesystemInterface    $userAttachmentFilesystem
     * @param FileUploadManager      $fileUploadManager
     * @param RealUserFinder         $realUserFinder
     * @param AttachmentRepository   $attachmentRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        FilesystemInterface $userAttachmentFilesystem,
        FileUploadManager $fileUploadManager,
        RealUserFinder $realUserFinder,
        AttachmentRepository $attachmentRepository
    ) {
        $this->entityManager            = $entityManager;
        $this->userAttachmentFilesystem = $userAttachmentFilesystem;
        $this->realUserFinder           = $realUserFinder;
        $this->fileUploadManager        = $fileUploadManager;
        $this->attachmentRepository     = $attachmentRepository;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param Clients      $uploader
     *
     * @throws Exception
     *
     * @return Attachment
     */
    public function upload(
        UploadedFile $uploadedFile,
        Clients $uploader
    ): Attachment {
        $relativeUploadedPath = $this->fileUploadManager
            ->uploadFile($uploadedFile, $this->userAttachmentFilesystem, '/', $this->getClientDirectory($uploader))
        ;

        $attachment = new Attachment($relativeUploadedPath, $uploader);

        $attachment
            ->setOriginalName($uploadedFile->getClientOriginalName())
        ;

        return $attachment;
    }

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    public function isOrphan(Attachment $attachment): bool
    {
        $attachedAttachments = $this->entityManager->getRepository(ProjectAttachment::class)->findBy(['attachment' => $attachment]);

        return 0 === count($attachedAttachments);
    }

    /**
     * @param Attachment $attachment
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Attachment $attachment): void
    {
        $this->attachmentRepository->save($attachment);
    }

    /**
     * @param Attachment $attachment
     *
     * @throws Exception
     */
    public function archive(Attachment $attachment): void
    {
        $userFinder = $this->realUserFinder;
        $attachment->archive($userFinder());
        $this->attachmentRepository->save($attachment);
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
