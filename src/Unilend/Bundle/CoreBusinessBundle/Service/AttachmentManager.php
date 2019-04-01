<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\{Exception as PhpSpreadsheetException, IOFactory as PhpSpreadsheetIOFactory, Writer\Pdf\Mpdf as PhpSpreadsheetMpdf};
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\{Exception\FileNotFoundException, Filesystem};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Entity\{Attachment, AttachmentType, Clients, ProjectAttachment, ProjectAttachmentType, Projects, Transfer, TransferAttachment};

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

    /**
     * @param EntityManagerInterface $entityManager
     * @param Filesystem             $filesystem
     * @param string                 $uploadRootDirectory
     * @param string                 $tmpDirectory
     * @param string                 $rootDirectory
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, Filesystem $filesystem, string $uploadRootDirectory, string $tmpDirectory, string $rootDirectory, LoggerInterface $logger)
    {
        $this->entityManager       = $entityManager;
        $this->filesystem          = $filesystem;
        $this->uploadRootDirectory = $uploadRootDirectory;
        $this->tmpDirectory        = $tmpDirectory;
        $this->rootDirectory       = $rootDirectory;
        $this->logger              = $logger;
    }

    /**
     * @param Clients        $client
     * @param AttachmentType $attachmentType
     * @param UploadedFile   $file
     * @param bool           $archivePreviousAttachments
     *
     * @return Attachment
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function upload(Clients $client, AttachmentType $attachmentType, UploadedFile $file, bool $archivePreviousAttachments = true): Attachment
    {
        $destinationRelative = $this->getUploadDestination($client);
        $destinationAbsolute = $this->getUploadRootDir() . DIRECTORY_SEPARATOR . $destinationRelative;

        if (false === is_dir($destinationAbsolute)) {
            $this->filesystem->mkdir($destinationAbsolute);
        }

        $fileName     = md5(uniqid());
        $fileFullName = $fileName . '.' . $file->getClientOriginalExtension();

        if (file_exists($destinationAbsolute . DIRECTORY_SEPARATOR . $fileFullName)) {
            $fileFullName = $fileName . '_' . md5(uniqid()) . '.' . $file->getClientOriginalExtension();
        }

        $file->move($destinationAbsolute, $fileFullName);

        $attachmentsToArchive = [];
        if ($archivePreviousAttachments) {
            $attachmentsToArchive = $this->entityManager->getRepository(Attachment::class)
                ->findBy([
                    'idClient' => $client,
                    'idType'   => $attachmentType,
                    'archived' => null
                ]);

            foreach ($attachmentsToArchive as $toArchive) {
                $toArchive->setArchived(new \DateTime());
            }
        }

        $attachment = new Attachment();
        $attachment
            ->setPath($destinationRelative . DIRECTORY_SEPARATOR . $fileFullName)
            ->setClient($client)
            ->setType($attachmentType)
            ->setOriginalName($file->getClientOriginalName());

        $this->entityManager->persist($attachment);

        // Only flush impacted entities otherwise it may flush data that should not (see LenderProfileController)
        $this->entityManager->flush(array_merge($attachmentsToArchive, [$attachment]));

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
     * @return string
     */
    private function getUploadRootDir()
    {
        $rootDir = realpath($this->uploadRootDirectory);

        return substr($rootDir, -1) === DIRECTORY_SEPARATOR ? substr($rootDir, 0, -1) : $rootDir;
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
                'id_client'     => $attachment->getClient()->getIdClient(),
                'class'         => __CLASS__,
                'function'      => __FUNCTION__,
                'file'          => $exception->getFile(),
                'line'          => $exception->getLine()
            ]);
        }
    }

    /**
     * @param Attachment $attachment
     * @param Projects   $project
     *
     * @return ProjectAttachment
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function attachToProject(Attachment $attachment, Projects $project)
    {
        $projectAttachmentRepository = $this->entityManager->getRepository(ProjectAttachment::class);
        $attached                    = $projectAttachmentRepository->getAttachedAttachmentsByType($project, $attachment->getType());
        $projectAttachmentType       = $this->entityManager->getRepository(ProjectAttachmentType::class)->findOneBy([
            'idType' => $attachment->getType()
        ]);

        foreach ($attached as $index => $attachmentToDetach) {
            if (null === $projectAttachmentType->getMaxItems() || $index < $projectAttachmentType->getMaxItems() - 1) {
                continue;
            }

            $attachmentToDetach->getAttachment()->setArchived(new \DateTime('now'));

            $this->entityManager->remove($attachmentToDetach);
            $this->entityManager->flush([$attachmentToDetach->getAttachment(), $attachmentToDetach]);
        }

        $projectAttachment = $projectAttachmentRepository->findOneBy(['idAttachment' => $attachment, 'idProject' => $project]);
        if (null === $projectAttachment) {
            $projectAttachment = new ProjectAttachment();
            $projectAttachment
                ->setProject($project)
                ->setAttachment($attachment);

            $this->entityManager->persist($projectAttachment);
            $this->entityManager->flush($projectAttachment);
        }

        return $projectAttachment;
    }

    /**
     * @param Attachment $attachment
     * @param Transfer   $transfer
     *
     * @return TransferAttachment
     * @throws \Doctrine\ORM\OptimisticLockException
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
                               ->setAttachment($attachment);
            $this->entityManager->persist($transferAttachment);
            $this->entityManager->flush($transferAttachment);
        }

        return $transferAttachment;
    }

    /**
     * Get relative client attachment path
     *
     * @param Clients $client
     *
     * @return string
     */
    private function getUploadDestination(Clients $client)
    {
        if (empty($client->getIdClient())) {
            throw new \InvalidArgumentException('Cannot find the upload destination. The client id is empty.');
        }
        $hash        = hash('sha256', $client->getIdClient());
        $destination = $hash[0] . DIRECTORY_SEPARATOR . $hash[1] . DIRECTORY_SEPARATOR . $client->getIdClient();

        return $destination;
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
            AttachmentType::DISPENSE_PRELEVEMENT_2017
        ];

        if ($includeOthers) {
            $types = array_merge($types, [
                AttachmentType::AUTRE1,
                AttachmentType::AUTRE2,
                AttachmentType::AUTRE3,
                AttachmentType::AUTRE4
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
                ->findPreviousNotArchivedAttachment($attachment);
        } catch (\Exception $exception) {
            $previousAttachment = null;
        }

        return null !== $previousAttachment;
    }
}
