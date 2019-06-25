<?php

declare(strict_types=1);

namespace Unilend\Service\Attachment;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\IOFactory as PhpSpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf as PhpSpreadsheetMpdf;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\{Exception\FileNotFoundException, Filesystem};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\{Attachment, AttachmentType, ClientAddressAttachment, Clients, Companies, GreenpointAttachment, ProjectAttachment, Transfer,
    TransferAttachment};
use Unilend\Service\User\RealUserFinder;
use URLify;

class AttachmentManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var Filesystem */
    private $filesystem;
    /** @var string */
    private $uploadRootDirectory;
    /** @var string */
    private $tmpDirectory;
    /** @var string */
    private $rootDirectory;
    /** @var LoggerInterface */
    private $logger;
    /** @var RealUserFinder */
    private $realUserFinder;

    /**
     * @param EntityManagerInterface $entityManager
     * @param Filesystem             $filesystem
     * @param string                 $uploadRootDirectory
     * @param string                 $tmpDirectory
     * @param string                 $rootDirectory
     * @param LoggerInterface        $logger
     * @param RealUserFinder         $realUserFinder
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Filesystem $filesystem,
        string $uploadRootDirectory,
        string $tmpDirectory,
        string $rootDirectory,
        LoggerInterface $logger,
        RealUserFinder $realUserFinder
    ) {
        $this->entityManager       = $entityManager;
        $this->filesystem          = $filesystem;
        $this->uploadRootDirectory = $uploadRootDirectory;
        $this->tmpDirectory        = $tmpDirectory;
        $this->rootDirectory       = $rootDirectory;
        $this->logger              = $logger;
        $this->realUserFinder      = $realUserFinder;
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
        $uploadPathAndName         = $this->uploadFile($uploadedFile, $clientOwner, $uploader);
        $relativeUploadPathAndName = str_replace($this->getUploadRootDir() . DIRECTORY_SEPARATOR, '', $uploadPathAndName);

        if ($archivePreviousAttachments) {
            $this->archiveAttachments($clientOwner, $attachmentType ?? $attachment->getType());
        }

        if (null === $attachment) {
            $attachment = new Attachment();
        }

        $attachment
            ->setPath($relativeUploadPathAndName)
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

        // Only flush impacted entities otherwise it may flush data that should not (see LenderProfileController)
        $this->entityManager->flush($attachment);

        return $attachment;
    }

    /**
     * @param Attachment $attachment
     *
     * @return string
     */
    public function getFullPath(Attachment $attachment)
    {
        return $this->getUploadRootDir() . DIRECTORY_SEPARATOR . $attachment->getPath();
    }

    /**
     * @param Attachment $attachment
     * @param bool       $convert
     *
     * @throws FileNotFoundException
     */
    public function output(Attachment $attachment, bool $convert): void
    {
        $path = $this->getFullPath($attachment);

        if (false === $this->filesystem->exists($path)) {
            throw new FileNotFoundException(null, 0, null, $path);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($convert) {
            switch ($extension) {
                case 'csv':
                case 'xls':
                case 'xlsx':
                    $this->outputExcel($attachment);

                    return;
            }
        }

        $fileName = $attachment->getOriginalName() ?? basename($attachment->getPath());

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '";');
        header('Content-Length: ' . filesize($path));

        echo file_get_contents($path);
    }

    /**
     * @param Attachment $attachment
     * @param Transfer   $transfer
     *
     * @return TransferAttachment
     */
    public function attachToTransfer(Attachment $attachment, Transfer $transfer)
    {
        $transferAttachmentRepository = $this->entityManager->getRepository(TransferAttachment::class);
        $attached                     = $transferAttachmentRepository->getAttachedAttachments($transfer, $attachment->getType());

        foreach ($attached as $attachmentToDetach) {
            $this->entityManager->remove($attachmentToDetach);
            $this->entityManager->flush($attachmentToDetach);
        }

        $transferAttachment = $this->entityManager->getRepository(TransferAttachment::class)->findOneBy(['idAttachment' => $attachment, 'idTransfer' => $transfer]);
        if (null === $transferAttachment) {
            $transferAttachment = new TransferAttachment();
            $transferAttachment->setTransfer($transfer)
                ->setAttachment($attachment)
            ;
            $this->entityManager->persist($transferAttachment);
            $this->entityManager->flush($transferAttachment);
        }

        return $transferAttachment;
    }

    /**
     * @param bool $includeOthers
     *
     * @return AttachmentType[]
     */
    public function getAllTypesForLender($includeOthers = true)
    {
        $types = [
            AttachmentType::CNI_PASSPORTE,
            AttachmentType::CNI_PASSPORTE_VERSO,
            AttachmentType::JUSTIFICATIF_DOMICILE,
            AttachmentType::RIB,
            AttachmentType::ATTESTATION_HEBERGEMENT_TIERS,
            AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT,
            AttachmentType::CNI_PASSPORTE_DIRIGEANT,
            AttachmentType::DELEGATION_POUVOIR,
            AttachmentType::KBIS,
            AttachmentType::JUSTIFICATIF_FISCAL,
            AttachmentType::DISPENSE_PRELEVEMENT_2014,
            AttachmentType::DISPENSE_PRELEVEMENT_2015,
            AttachmentType::DISPENSE_PRELEVEMENT_2016,
            AttachmentType::DISPENSE_PRELEVEMENT_2017,
        ];

        if ($includeOthers) {
            $types = array_merge($types, [
                AttachmentType::AUTRE1,
                AttachmentType::AUTRE2,
                AttachmentType::AUTRE3,
                AttachmentType::AUTRE4,
            ]);
        }

        return $this->entityManager->getRepository(AttachmentType::class)->findTypesIn($types);
    }

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    public function isModifiedAttachment(Attachment $attachment)
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

        if (count($attachedAttachments) > 0) {
            return false;
        }

        $attachedAttachments = $this->entityManager->getRepository(ClientAddressAttachment::class)->findBy(['idAttachment' => $attachment]);

        if (count($attachedAttachments) > 0) {
            return false;
        }

        $attachedAttachments = $this->entityManager->getRepository(TransferAttachment::class)->findBy(['idAttachment' => $attachment]);

        if (count($attachedAttachments) > 0) {
            return false;
        }

        $attachedAttachments = $this->entityManager->getRepository(GreenpointAttachment::class)->findBy(['idAttachment' => $attachment]);

        if (count($attachedAttachments) > 0) {
            return false;
        }

        return true;
    }

    /**
     * @param Attachment $attachment
     * @param bool       $save
     *
     * @throws ORMException
     * @throws OptimisticLockException
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
     * @return string
     */
    private function getUploadRootDir()
    {
        $rootDir = realpath($this->uploadRootDirectory);

        return DIRECTORY_SEPARATOR === mb_substr($rootDir, -1) ? mb_substr($rootDir, 0, -1) : $rootDir;
    }

    /**
     * @param Attachment $attachment
     */
    private function outputExcel(Attachment $attachment): void
    {
        // Higher value for big files
        ini_set('pcre.backtrack_limit', '10000000');

        try {
            $path          = $this->getFullPath($attachment);
            $temporaryPath = $this->tmpDirectory . '/' . uniqid() . '.pdf';
            $document      = PhpSpreadsheetIOFactory::load($path);

            /** @var PhpSpreadsheetMpdf $writer */
            $writer = PhpSpreadsheetIOFactory::createWriter($document, 'Mpdf');
            $writer->writeAllSheets();
            $writer->setTempDir($this->tmpDirectory);
            $writer->save($temporaryPath);

            $fileName = $attachment->getOriginalName() ?? basename($attachment->getPath());
            $fileName = pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';
            $fileSize = filesize($temporaryPath);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Transfer-Encoding: binary');
            header('Content-Disposition: attachment; filename="' . $fileName . '";');
            header('Content-Length: ' . $fileSize);
            readfile($temporaryPath);

            $this->filesystem->remove($temporaryPath);
        } catch (PhpSpreadsheetException $exception) {
            $this->logger->error('Unable to convert Excel file to PDF. Message: ' . $exception->getMessage(), [
                'id_attachment' => $attachment->getId(),
                'id_client'     => $attachment->getClientOwner()->getIdClient(),
                'class'         => __CLASS__,
                'function'      => __FUNCTION__,
                'file'          => $exception->getFile(),
                'line'          => $exception->getLine(),
            ]);
        }
    }

    /**
     * Get relative client attachment path.
     *
     * @param Clients $client
     *
     * @return string
     */
    private function getUploadRelativePath(Clients $client)
    {
        if (empty($client->getIdClient())) {
            throw new InvalidArgumentException('Cannot find the upload destination. The client id is empty.');
        }
        $hash = hash('sha256', (string) $client->getIdClient());

        return $hash[0] . DIRECTORY_SEPARATOR . $hash[1] . DIRECTORY_SEPARATOR . $client->getIdClient();
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string       $uploadAbsolutePath
     *
     * @return string
     */
    private function generateFileName(UploadedFile $uploadedFile, string $uploadAbsolutePath)
    {
        $originalFilename      = URLify::filter(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName              = $originalFilename . '-' . md5(uniqid());
        $fileExtension         = $uploadedFile->guessExtension() ?? $uploadedFile->getClientOriginalExtension();
        $fileNameWithExtension = $fileName . '.' . $fileExtension;

        if (file_exists($uploadAbsolutePath . DIRECTORY_SEPARATOR . $fileNameWithExtension)) {
            $fileNameWithExtension = $this->generateFileName($uploadedFile, $uploadAbsolutePath);
        }

        return $fileNameWithExtension;
    }

    /**
     * @param Clients|null $owner
     * @param Clients      $uploader
     *
     * @return string
     */
    private function getUploadAbsolutePath(?Clients $owner, Clients $uploader)
    {
        $relativePath = $this->getUploadRelativePath($owner ?? $uploader);
        $absolutePath = $this->getUploadRootDir() . DIRECTORY_SEPARATOR . $relativePath;

        if (false === is_dir($absolutePath)) {
            $this->filesystem->mkdir($absolutePath);
        }

        return $absolutePath;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param Clients|null $owner
     * @param Clients      $uploader
     *
     * @return string
     */
    private function uploadFile(UploadedFile $uploadedFile, ?Clients $owner, Clients $uploader)
    {
        $uploadPath = $this->getUploadAbsolutePath($owner, $uploader);
        $fileName   = $this->generateFileName($uploadedFile, $uploadPath);

        $uploadedFile->move($uploadPath, $fileName);

        return $uploadPath . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * @param Clients|null   $clientOwner
     * @param AttachmentType $attachmentType
     *
     * @throws Exception
     */
    private function archiveAttachments(?Clients $clientOwner, AttachmentType $attachmentType)
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
}
