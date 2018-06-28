<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use PhpOffice\PhpSpreadsheet\{
    Exception as PhpSpreadsheetException, IOFactory as PhpSpreadsheetIOFactory, Writer\Pdf\Mpdf as PhpSpreadsheetMpdf
};
use PhpOffice\PhpWord\{
    Exception\Exception as PhpWordException, IOFactory as PhpWordIOFactory, Writer\PDF\DomPDF as PhpWordDomPDF
};
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\{
    Exception\FileNotFoundException, Filesystem
};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Attachment, AttachmentType, Clients, ProjectAttachment, Projects, Transfer, TransferAttachment
};

class AttachmentManager
{
    const PHPOFFICE_TEMPORARY_DIR = '/tmp/phpoffice';

    /** @var EntityManager */
    private $entityManager;
    /** @var Filesystem */
    private $filesystem;
    /** @var string */
    private $uploadRootDirectory;
    /** @var string */
    private $rootDirectory;
    /** @var LoggerInterface */
    private $logger;

    /**
     * AttachmentManager constructor.
     *
     * @param EntityManager   $entityManager
     * @param Filesystem      $filesystem
     * @param string          $uploadRootDirectory
     * @param string          $rootDirectory
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $entityManager, Filesystem $filesystem, string $uploadRootDirectory, string $rootDirectory, LoggerInterface $logger)
    {
        $this->entityManager       = $entityManager;
        $this->filesystem          = $filesystem;
        $this->uploadRootDirectory = $uploadRootDirectory;
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
            $attachmentsToArchive = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')
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
     */
    public function output(Attachment $attachment): void
    {
        $path = $this->getFullPath($attachment);

        if (false === file_exists($path)) {
            throw new FileNotFoundException(null, 0, null, $path);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'csv':
            case 'xls':
            case 'xlsx':
                $this->outputExcel($attachment);
                break;
            case 'doc':
            case 'docx':
                $this->outputWord($attachment);
                break;
            default:
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($attachment->getPath()) . '";');
                header('Content-Length: '. filesize($path));

                echo file_get_contents($path);
                break;
        }
    }

    /**
     * @param Attachment $attachment
     */
    private function outputExcel(Attachment $attachment): void
    {
        try {
            $path     = $this->getFullPath($attachment);
            $document = PhpSpreadsheetIOFactory::load($path);

            /** @var PhpSpreadsheetMpdf $writer */
            $writer = PhpSpreadsheetIOFactory::createWriter($document, 'Mpdf');
            $this->outputOffice($attachment, $writer);
        } catch (PhpSpreadsheetException $exception) {
            $this->logger->error('Unable to convert Excel file to PDF. Message: ' . $exception->getMessage(), [
                'id_client' => $attachment->getClient()->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }
    }

    /**
     * @param Attachment $attachment
     */
    private function outputWord(Attachment $attachment): void
    {
        try {
            \PhpOffice\PhpWord\Settings::setPdfRendererPath($this->rootDirectory . '/../vendor/dompdf/dompdf');
            \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');

            $path     = $this->getFullPath($attachment);
            $document = PhpWordIOFactory::load($path);

            /** @var PhpWordDomPDF $writer */
            $writer = PhpWordIOFactory::createWriter($document, 'PDF');
            $this->outputOffice($attachment, $writer);
        } catch (PhpWordException $exception) {
            $this->logger->error('Unable to load Word file to PDF. Message: ' . $exception->getMessage(), [
                'id_client' => $attachment->getClient()->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }
    }

    /**
     * @param Attachment                       $attachment
     * @param PhpSpreadsheetMpdf|PhpWordDomPDF $writer
     */
    private function outputOffice(Attachment $attachment, $writer): void
    {
        try {
            if (false === $this->filesystem->exists(self::PHPOFFICE_TEMPORARY_DIR)) {
                $this->filesystem->mkdir(self::PHPOFFICE_TEMPORARY_DIR);
            }

            $temporaryPath = self::PHPOFFICE_TEMPORARY_DIR . '/' . uniqid() . '.pdf';
            $path          = $this->getFullPath($attachment);

            /** @var PhpSpreadsheetMpdf|PhpWordDomPDF $writer */
            $writer->setTempDir(self::PHPOFFICE_TEMPORARY_DIR);
            $writer->save($temporaryPath);

            $fileName = pathinfo($path, PATHINFO_FILENAME) . '.pdf';
            $fileSize = filesize($temporaryPath);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Transfer-Encoding: binary');
            header('Content-Disposition: attachment; filename="' . $fileName . '";');
            header('Content-Length: ' . $fileSize);
            readfile($temporaryPath);

            $this->filesystem->remove($temporaryPath);
        } catch (\Exception $exception) {
            $this->logger->error('Unable to convert Office file to PDF. Message: ' . $exception->getMessage(), [
                'id_client' => $attachment->getClient()->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
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
        $projectAttachmentRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachment');
        $attached                    = $projectAttachmentRepository->getAttachedAttachmentsByType($project, $attachment->getType());
        $projectAttachmentType       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachmentType')->findOneBy([
            'idType' => $attachment->getType()
        ]);

        foreach ($attached as $index => $attachmentToDetach) {
            if ($index < $projectAttachmentType->getMaxItems() - 1) {
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
        $transferAttachmentRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TransferAttachment');
        $attached                     = $transferAttachmentRepository->getAttachedAttachments($transfer, $attachment->getType());

        foreach ($attached as $attachmentToDetach) {
            $this->entityManager->remove($attachmentToDetach);
            $this->entityManager->flush($attachmentToDetach);
        }

        $transferAttachment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TransferAttachment')->findOneBy(['idAttachment' => $attachment, 'idTransfer' => $transfer]);
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

        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->findTypesIn($types);
    }

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    public function isModifiedAttachment(Attachment $attachment)
    {
        try {
            $previousAttachment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')
                ->findPreviousNotArchivedAttachment($attachment);
        } catch (\Exception $exception) {
            $previousAttachment = null;
        }

        return null !== $previousAttachment;
    }
}
