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
use Unilend\Entity\{Attachment, AttachmentType, Clients, Companies, ProjectAttachment};
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

    /**
     * @param EntityManagerInterface $entityManager
     * @param FilesystemInterface    $userAttachmentFilesystem
     * @param FileUploadManager      $fileUploadManager
     * @param RealUserFinder         $realUserFinder
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        FilesystemInterface $userAttachmentFilesystem,
        FileUploadManager $fileUploadManager,
        RealUserFinder $realUserFinder
    ) {
        $this->entityManager            = $entityManager;
        $this->userAttachmentFilesystem = $userAttachmentFilesystem;
        $this->realUserFinder           = $realUserFinder;
        $this->fileUploadManager        = $fileUploadManager;
    }

    /**
     * @param Clients|null    $clientOwner
     * @param Companies|null  $companyOwner
     * @param Clients         $uploader
     * @param AttachmentType  $attachmentType
     * @param Attachment|null $attachment
     * @param UploadedFile    $uploadedFile
     * @param bool            $archivePreviousAttachments
     * @param string|null     $description
     *
     * @throws Exception
     *
     * @return Attachment
     */
    public function upload(
        ?Clients $clientOwner,
        ?Companies $companyOwner,
        Clients $uploader,
        ?AttachmentType $attachmentType,
        ?Attachment $attachment,
        UploadedFile $uploadedFile,
        bool $archivePreviousAttachments = true,
        ?string $description = null
    ): Attachment {
        $relativeUploadedPath = $this->fileUploadManager
            ->uploadFile($uploadedFile, $this->userAttachmentFilesystem, '/', $this->getClientDirectory($clientOwner ?? $uploader))
        ;

        if ($archivePreviousAttachments && ($attachmentType || $attachment)) {
            $this->archiveAttachments($clientOwner, $attachmentType ?? $attachment->getType());
        }

        if (null === $attachment) {
            $attachment = new Attachment();
        }

        $attachment
            ->setPath($relativeUploadedPath)
            ->setClientOwner($clientOwner)
            ->setCompanyOwner($companyOwner)
            ->setAddedByValue($this->realUserFinder)
            ->setOriginalName($uploadedFile->getClientOriginalName())
        ;

        if ($attachmentType) {
            $attachment->setType($attachmentType);
        }

        if ($description) {
            $attachment->setDescription($description);
        }

        $this->entityManager->persist($attachment);
        $this->entityManager->flush($attachment);

        return $attachment;
    }

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    public function isModifiedAttachment(Attachment $attachment): bool
    {
        try {
            $previousAttachment = $this->entityManager->getRepository(Attachment::class)
                ->findPreviousNotArchivedAttachment($attachment)
            ;
        } catch (Exception $exception) {
            $previousAttachment = null;
        }

        return null !== $previousAttachment;
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
     * @param bool       $save
     *
     * @throws Exception
     */
    public function archive(Attachment $attachment, bool $save = true): void
    {
        $attachment
            ->setArchived(new DateTimeImmutable())
            ->setArchivedByValue($this->realUserFinder)
        ;

        if ($save) {
            $this->entityManager->getRepository(Attachment::class)->save($attachment);
        }
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
     * @param Attachment $attachment
     *
     * @throws FileNotFoundException
     *
     * @return false|resource
     */
    public function readStream(Attachment $attachment)
    {
        return $this->userAttachmentFilesystem->readStream($attachment->getPath());
    }

    /**
     * @param Attachment $attachment
     *
     * @throws FileNotFoundException
     *
     * @return false|string
     */
    public function getMimeType(Attachment $attachment)
    {
        return $this->userAttachmentFilesystem->getMimeType($attachment->getPath());
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
        $this->entityManager->getRepository(Attachment::class)->save($attachment);
    }

    /**
     * @param Clients|null   $clientOwner
     * @param AttachmentType $attachmentType
     *
     * @throws Exception
     */
    private function archiveAttachments(?Clients $clientOwner, AttachmentType $attachmentType): void
    {
        $attachmentsToArchive = [];
        if ($clientOwner) {
            $attachmentsToArchive = $this->entityManager->getRepository(Attachment::class)
                ->findBy([
                    'owner'    => $clientOwner,
                    'type'     => $attachmentType,
                    'archived' => null,
                ])
            ;

            foreach ($attachmentsToArchive as $attachment) {
                $this->archive($attachment, false);
            }
        }

        $this->entityManager->flush($attachmentsToArchive);
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
