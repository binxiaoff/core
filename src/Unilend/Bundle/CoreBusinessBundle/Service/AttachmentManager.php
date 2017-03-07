<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAttachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Transfer;
use Unilend\Bundle\CoreBusinessBundle\Entity\TransferAttachment;

class AttachmentManager
{

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var string
     */
    private $uploadRootDir;
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(EntityManager $entityManager, Filesystem $filesystem, $uploadRootDir)
    {
        $this->entityManager = $entityManager;
        $this->filesystem    = $filesystem;
        $this->uploadRootDir = $uploadRootDir;
    }

    /**
     * @param Clients        $client
     * @param AttachmentType $attachmentType
     *
     * @return bool|int
     */
    public function attachmentExists(Clients $client, AttachmentType $attachmentType)
    {
        $attachment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findOneBy(['idClient' => $client, 'idType' => $attachmentType]);

        if ($attachment && empty($attachment->getPath())) {
            return $attachment->getId();
        } else {
            return false;
        }
    }

    /**
     * @param Clients        $client
     * @param AttachmentType $attachmentType
     * @param UploadedFile   $file
     * @param null           $name
     *
     * @return Attachment
     */
    public function upload(Clients $client, AttachmentType $attachmentType, UploadedFile $file, $name = null)
    {
        $destination = $this->getUploadDestination($client);
        $fileName    = ($name === null ? md5(uniqid()) : $name) . '.' . $file->guessExtension();
        if (file_exists($destination . DIRECTORY_SEPARATOR . $fileName)) {
            $fileName = md5(uniqid()) . $fileName;
        }
        $file = $file->move($destination, $fileName);
        $path = ltrim($file->getPathname(), $this->getUploadRootDir());

        $attachmentsToArchive = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findBy(['idClient' => $client, 'idType' => $attachmentType, 'archived' => null]);
        foreach ($attachmentsToArchive as $toArchive) {
            $toArchive->setArchived(new \DateTime());
        }

        $attachment = new Attachment();
        $attachment->setPath($path)
                   ->setClient($client)
                   ->setType($attachmentType);
        $this->entityManager->persist($attachment);
        $this->entityManager->flush();

        return $attachment;
    }

    /**
     * @param Attachment $attachment
     *
     * @return string
     */
    public function getFullPath(Attachment $attachment)
    {
        return $this->getUploadRootDir() . $attachment->getPath();
    }

    /**
     * @return string
     */
    private function getUploadRootDir()
    {
        return $this->uploadRootDir . (substr($this->uploadRootDir, -1) === '/' ? '' : '/');
    }

    /**
     * @param Attachment $attachment
     * @param Projects   $project
     *
     * @return ProjectAttachment
     */
    public function attachToProject(Attachment $attachment, Projects $project)
    {
        $projectAttachmentRepo = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachment');
        $attached = $projectAttachmentRepo->getAttachedAttachments($project, $attachment->getType());

        foreach ($attached as $attachmentToDetach) {
            $this->entityManager->remove($attachmentToDetach);
            $this->entityManager->flush($attachmentToDetach);
        }
        $projectAttachment = $projectAttachmentRepo->findOneBy(['idAttachment' => $attachment, 'idProject' => $project]);
        if (null === $projectAttachment) {
            $projectAttachment = new ProjectAttachment();
            $projectAttachment->setProject($project)
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
     */
    public function attachToTransfer(Attachment $attachment, Transfer $transfer)
    {
        $transferAttachment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TransferAttachment')->findOneBy(['idAttachment' => $attachment, 'idTransfer' => $transfer]);
        if (null === $transferAttachment) {
            $projectAttachment = new TransferAttachment();
            $projectAttachment->setTransfer($transfer)
                              ->setAttachment($attachment);
            $this->entityManager->persist($transferAttachment);
            $this->entityManager->flush($transferAttachment);
        }

        return $transferAttachment;
    }

    /**
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
        $destination = $this->getUploadRootDir() . $hash[0] . DIRECTORY_SEPARATOR . $hash[1] . DIRECTORY_SEPARATOR . $client->getIdClient();

        if (false === is_dir($destination)) {
            $this->filesystem->mkdir($destination);
        }

        return $destination;
    }

    /**
     * @param bool $bIncludeOthers
     *
     * @return AttachmentType[]
     */
    public function getAllTypesForProjects($bIncludeOthers = true)
    {
        $aTypes = array(
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
            AttachmentType::ETAT_PRIVILEGES_NANTISSEMENTS,
            AttachmentType::CGV,
            AttachmentType::RAPPORT_CAC,
            AttachmentType::STATUTS,
            AttachmentType::DEBTS_STATEMENT,
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
        );

        if ($bIncludeOthers) {
            $aTypes = array_merge($aTypes, [
                AttachmentType::AUTRE1,
                AttachmentType::AUTRE2,
                AttachmentType::AUTRE3,
                AttachmentType::AUTRE4
            ]);
        }

        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->findTypesIn($aTypes);
    }

    /**
     * @param bool $bIncludeOthers
     *
     * @return AttachmentType[]
     */
    public function getAllTypesForLender($bIncludeOthers = true)
    {
        $aTypes = array(
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
        );

        if ($bIncludeOthers) {
            $aTypes = array_merge($aTypes, [
                AttachmentType::AUTRE1,
                AttachmentType::AUTRE2,
                AttachmentType::AUTRE3,
                AttachmentType::AUTRE4
            ]);
        }

        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->findTypesIn($aTypes);
    }

    /**
     * @param Attachment $attachment
     */
    public function archive(Attachment $attachment)
    {
        $attachment->setArchived(new \DateTime());
        $this->entityManager->flush($attachment);
    }

    public function findUploadedFile()
    {
        foreach ($_FILES as $file) {

        }
    }
}
