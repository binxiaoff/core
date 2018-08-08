<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{AddressType, Attachment, AttachmentType, Clients, GreenpointAttachment, GreenpointAttachmentDetail};
use Unilend\Bundle\WSClientBundle\Entity\Greenpoint\{HousingCertificate, Identity, Rib};
use Unilend\Bundle\WSClientBundle\Service\GreenPointManager;

class GreenPointDataManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var AttachmentManager */
    private $attachmentManager;

    /**
     * @param EntityManager      $entityManager
     * @param LoggerInterface    $logger
     * @param AttachmentManager  $attachmentManager
     */
    public function __construct(
        EntityManager $entityManager,
        LoggerInterface $logger,
        AttachmentManager $attachmentManager
    )
    {
        $this->entityManager      = $entityManager;
        $this->logger             = $logger;
        $this->attachmentManager  = $attachmentManager;
    }

    /**
     * @param Attachment $attachment
     *
     * @return array
     * @throws \Exception
     */
    public function getGreenPointData(Attachment $attachment): array
    {
        switch ($attachment->getType()->getId()) {
            case AttachmentType::CNI_PASSPORTE:
            case AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT:
            case AttachmentType::CNI_PASSPORTE_DIRIGEANT:
                return $this->getIdentityData($attachment->getClient(), $attachment);
            case AttachmentType::RIB:
                return $this->getBankAccountData($attachment->getClient(), $attachment);
            case AttachmentType::JUSTIFICATIF_DOMICILE:
            case AttachmentType::ATTESTATION_HEBERGEMENT_TIERS:
                return $this->getAddressData($attachment->getClient(), $attachment);
            default :
                return [];
        }
    }

    /**
     * @param Clients    $client
     * @param Attachment $attachment
     *
     * @return array
     */
    private function getIdentityData(Clients $client, Attachment $attachment)
    {
        return array_merge($this->getCommonClientData($client, $attachment), ['date_naissance' => $client->getNaissance()->format('d/m/Y')]);
    }

    /**
     * @param Clients    $client
     * @param Attachment $attachment
     *
     * @return array
     */
    private function getBankAccountData(Clients $client, Attachment $attachment)
    {
        $bankAccount = $attachment->getBankAccount();
        if (null === $bankAccount) {
            throw new \InvalidArgumentException('Attachment has no bank account');
        }

        return array_merge(
            $this->getCommonClientData($client, $attachment), [
            'iban' => $bankAccount->getIban(),
            'bic'  => $bankAccount->getBic()
        ]);
    }

    /**
     * @param Clients    $client
     * @param Attachment $attachment
     *
     * @return array
     * @throws \Exception
     */
    private function getAddressData(Clients $client, Attachment $attachment)
    {
        if ($client->isNaturalPerson()) {
            $address = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
        } else {
            $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
            $address = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
        }

        if (null === $address) {
            throw new \Exception('Client/Company has no last modified address');
        }

        return array_merge(
            $this->getCommonClientData($client, $attachment), [
            'adresse'     => $address->getAddress(),
            'code_postal' => $address->getZip(),
            'ville'       => $address->getCity(),
            'pays'        => strtoupper($address->getIdCountry()->getFr())
        ]);
    }

    /**
     * @param Clients    $client
     * @param Attachment $attachment
     *
     * @return array
     */
    private function getCommonClientData(Clients $client, Attachment $attachment): array
    {
        return [
            'files'    => fopen($this->attachmentManager->getFullPath($attachment), 'r'),
            'dossier'  => $client->getIdClient(),
            'document' => $attachment->getId(),
            'detail'   => GreenPointManager::DETAIL_TRUE,
            'nom'      => $client->getNom() . ($client->getNomUsage() ? '|' . $client->getNomUsage() : ''),
            'prenom'   => $client->getPrenom()
        ];
    }

    /**
     * @param Attachment $attachment
     *
     * @return GreenpointAttachment
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createGreenpointAttachment(Attachment $attachment): GreenpointAttachment
    {
        $greenPointAttachment = new GreenpointAttachment();
        $greenPointAttachment->setIdAttachment($attachment);

        $this->entityManager->persist($greenPointAttachment);
        $this->entityManager->flush($greenPointAttachment);

        return $greenPointAttachment;
    }

    /**
     * @param Attachment                      $attachment
     * @param Identity|Rib|HousingCertificate $response
     *
     * @return GreenpointAttachment
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateGreenpointAttachment(Attachment $attachment, $response): GreenpointAttachment
    {
        $greenPointAttachment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:GreenpointAttachment')->findOneBy(['idAttachment' => $attachment->getId()]);

        if (null === $greenPointAttachment) {
            $greenPointAttachment = $this->createGreenpointAttachment($attachment);
        }

        if ($response instanceof Identity) {
            $greenPointAttachment->setValidationCode($response->getCode());
        }

        $greenPointAttachment
            ->setValidationStatus($response->getStatus())
            ->setValidationStatusLabel($response->getStatusLabel());

        $this->entityManager->flush($greenPointAttachment);

        return $greenPointAttachment;
    }

    /**
     * @param GreenpointAttachment            $greenPointAttachment
     * @param Identity|Rib|HousingCertificate $response
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateGreenPointAttachmentDetail(GreenpointAttachment $greenPointAttachment, $response): void
    {
        $greenPointAttachmentDetail = $greenPointAttachment->getGreenpointAttachmentDetail();

        if (null === $greenPointAttachmentDetail) {
            $greenPointAttachmentDetail = new GreenpointAttachmentDetail();
            $greenPointAttachmentDetail->setIdGreenpointAttachment($greenPointAttachment);
            $this->entityManager->persist($greenPointAttachmentDetail);
        }

        if ($response instanceof Identity) {
            $this->setIdentityData($greenPointAttachmentDetail, $response);
        }

        if ($response instanceof Rib) {
           $this->setRibDetail($greenPointAttachmentDetail, $response);
        }

        if ($response instanceof HousingCertificate){
            $this->setHousingCertificateDetail($greenPointAttachmentDetail, $response);
        }

        $this->entityManager->flush($greenPointAttachmentDetail);
    }

    /**
     * @param GreenpointAttachmentDetail $greenPointAttachmentDetail
     * @param Identity                   $identity
     */
    private function setIdentityData(GreenpointAttachmentDetail $greenPointAttachmentDetail, Identity $identity): void
    {
        $greenPointAttachmentDetail
            ->setDocumentType(GreenPointManager::TYPE_IDENTITY_DOCUMENT)
            ->setIdentityName($identity->getName())
            ->setIdentitySurname($identity->getFirstName())
            ->setIdentityMrz1($identity->getMrz1())
            ->setIdentityMrz2($identity->getMrz2())
            ->setIdentityMrz3($identity->getMrz3())
            ->setIdentityNationality($identity->getNationality())
            ->setIdentityIssuingCountry($identity->getIssuingCountry())
            ->setIdentityIssuingAuthority($identity->getIssuingAuthority())
            ->setIdentityExpirationDate($identity->getExpirationDate())
            ->setIdentityBirthdate($identity->getBirthdate())
            ->setIdentityDocumentNumber($identity->getDocumentNumber())
            ->setIdentityDocumentTypeId($identity->getType())
            ->setIdentityCivility($identity->getGender());
    }

    /**
     * @param GreenpointAttachmentDetail $greenPointAttachmentDetail
     * @param Rib                        $rib
     */
    private function setRibDetail(GreenpointAttachmentDetail $greenPointAttachmentDetail, Rib $rib): void
    {
        $greenPointAttachmentDetail
            ->setDocumentType(GreenPointManager::TYPE_RIB)
            ->setBankDetailsIban($rib->getIban())
            ->setBankDetailsBic($rib->getBic())
            ->setBankDetailsUrl($rib->getUrl());
    }

    /**
     * @param GreenpointAttachmentDetail $greenPointAttachmentDetail
     * @param HousingCertificate         $housingCertificate
     */
    private function setHousingCertificateDetail(GreenpointAttachmentDetail $greenPointAttachmentDetail, HousingCertificate $housingCertificate): void
    {
        $greenPointAttachmentDetail
            ->setDocumentType(GreenPointManager::TYPE_HOUSING_CERTIFICATE)
            ->setAddressAddress($housingCertificate->getAddress())
            ->setAddressPostalCode($housingCertificate->getZip())
            ->setAddressCity($housingCertificate->getCity())
            ->setAddressCountry($housingCertificate->getCountry());
    }
}
