<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Attachment, AttachmentType, Clients, ProjectAttachment, Projects, Transfer, TransferAttachment
};

class AttachmentManager
{
    /**  @var EntityManager */
    private $entityManager;

    /** @var string */
    private $uploadRootDir;

    /** @var Filesystem */
    private $filesystem;

    /**
     * AttachmentManager constructor.
     *
     * @param EntityManager $entityManager
     * @param Filesystem    $filesystem
     * @param               $uploadRootDir
     */
    public function __construct(EntityManager $entityManager, Filesystem $filesystem, $uploadRootDir)
    {
        $this->entityManager = $entityManager;
        $this->filesystem    = $filesystem;
        $this->uploadRootDir = $uploadRootDir;
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
        $rootDir = realpath($this->uploadRootDir);

        return substr($rootDir, -1) === DIRECTORY_SEPARATOR ? substr($rootDir, 0, -1) : $rootDir;
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
        $attached                    = $projectAttachmentRepository->getAttachedAttachments($project, $attachment->getType());
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
    public function getAllTypesForProjects($includeOthers = true)
    {
        $types = [
            AttachmentType::KBIS,
            AttachmentType::RIB,
            AttachmentType::CNI_PASSPORTE_DIRIGEANT,
            AttachmentType::CNI_PASSPORTE_VERSO,
            AttachmentType::DERNIERE_LIASSE_FISCAL,
            AttachmentType::LIASSE_FISCAL_N_1,
            AttachmentType::LIASSE_FISCAL_N_2,
            AttachmentType::RELEVE_BANCAIRE_MOIS_N,
            AttachmentType::RELEVE_BANCAIRE_MOIS_N_1,
            AttachmentType::RELEVE_BANCAIRE_MOIS_N_2,
            AttachmentType::DEBTS_STATEMENT,
            AttachmentType::ETAT_PRIVILEGES_NANTISSEMENTS,
            AttachmentType::CGV,
            AttachmentType::RAPPORT_CAC,
            AttachmentType::STATUTS,
            AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_1,
            AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_VERSO_1,
            AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_2,
            AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_VERSO_2,
            AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_3,
            AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_VERSO_3,
            AttachmentType::DERNIERE_LIASSE_FISCAL_HOLDING,
            AttachmentType::KBIS_HOLDING,
            AttachmentType::ETAT_ENDETTEMENT,
            AttachmentType::PREVISIONNEL,
            AttachmentType::SITUATION_COMPTABLE_INTERMEDIAIRE,
            AttachmentType::DERNIERS_COMPTES_CONSOLIDES,
            AttachmentType::BALANCE_CLIENT,
            AttachmentType::BALANCE_FOURNISSEUR,
            AttachmentType::PHOTOS_ACTIVITE,
            AttachmentType::PRESENTATION_PROJET,
            AttachmentType::PRESENTATION_ENTRERPISE
        ];

        if ($includeOthers) {
            $types = array_merge($types, [
                AttachmentType::AUTRE1,
                AttachmentType::AUTRE2,
                AttachmentType::AUTRE3,
                AttachmentType::AUTRE4
            ]);
        }

        $sortedTypes = [];
        /** @var AttachmentType $attachmentType */
        foreach ($this->entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->findTypesIn($types) as $attachmentType) {
            $index               = array_search($attachmentType->getId(), $types, true);
            $sortedTypes[$index] = $attachmentType;
        }
        ksort($sortedTypes);

        return $sortedTypes;
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
