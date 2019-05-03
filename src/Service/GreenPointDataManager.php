<?php

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Entity\External\GreenPoint\{HousingCertificate, Identity, Rib};
use Unilend\Entity\{AddressType, Attachment, AttachmentType, ClientAddress, Companies, CompanyAddress, GreenpointAttachment, GreenpointAttachmentDetail};
use Unilend\Service\WebServiceClient\GreenPointManager;

class GreenPointDataManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var AttachmentManager */
    private $attachmentManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     * @param AttachmentManager      $attachmentManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        AttachmentManager $attachmentManager
    ) {
        $this->entityManager     = $entityManager;
        $this->logger            = $logger;
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * @param Attachment $attachment
     *
     * @throws \Exception
     *
     * @return array
     */
    public function getGreenPointData(Attachment $attachment): array
    {
        switch ($attachment->getType()->getId()) {
            case AttachmentType::CNI_PASSPORTE:
            case AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT:
            case AttachmentType::CNI_PASSPORTE_DIRIGEANT:
                return $this->getIdentityData($attachment);
            case AttachmentType::RIB:
                return $this->getBankAccountData($attachment);
            case AttachmentType::JUSTIFICATIF_DOMICILE:
            case AttachmentType::ATTESTATION_HEBERGEMENT_TIERS:
                return $this->getAddressData($attachment);
            default:
                return [];
        }
    }

    /**
     * @param Attachment                      $attachment
     * @param Identity|Rib|HousingCertificate $response
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return GreenpointAttachment
     */
    public function updateGreenPointData(Attachment $attachment, $response): GreenpointAttachment
    {
        if (false === $response instanceof Identity && false === $response instanceof Rib && false === $response instanceof HousingCertificate) {
            throw new \InvalidArgumentException('Response has not the right type.');
        }

        $greenPointAttachment = $this->updateGreenpointAttachment($attachment, $response);

        $this->updateGreenPointAttachmentDetail($greenPointAttachment, $response);

        return $greenPointAttachment;
    }

    /**
     * @param Attachment $attachment
     *
     * @return array
     */
    private function getIdentityData(Attachment $attachment): array
    {
        return array_merge($this->getCommonClientData($attachment), ['date_naissance' => $attachment->getOwner()->getDateOfBirth()->format('d/m/Y')]);
    }

    /**
     * @param Attachment $attachment
     *
     * @return array
     */
    private function getBankAccountData(Attachment $attachment): array
    {
        $bankAccount = $attachment->getBankAccount();
        if (null === $bankAccount) {
            throw new \InvalidArgumentException('Attachment has no bank account');
        }

        return array_merge(
            $this->getCommonClientData($attachment),
            [
                'iban' => $bankAccount->getIban(),
                'bic'  => $bankAccount->getBic(),
            ]
        );
    }

    /**
     * @param Attachment $attachment
     *
     * @throws \Exception
     *
     * @return array
     */
    private function getAddressData(Attachment $attachment): array
    {
        if ($attachment->getOwner()->isNaturalPerson()) {
            $address = $this->entityManager->getRepository(ClientAddress::class)->findLastModifiedNotArchivedAddressByType($attachment->getOwner(), AddressType::TYPE_MAIN_ADDRESS);
        } else {
            $company = $this->entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $attachment->getOwner()]);
            $address = $this->entityManager->getRepository(CompanyAddress::class)->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
        }

        if (null === $address) {
            throw new \Exception('Client/Company has no last modified address');
        }

        return array_merge(
            $this->getCommonClientData($attachment),
            [
                'adresse'     => $address->getAddress(),
                'code_postal' => $address->getZip(),
                'ville'       => $address->getCity(),
                'pays'        => mb_strtoupper($address->getIdCountry()->getFr()),
            ]
        );
    }

    /**
     * @param Attachment $attachment
     *
     * @return array
     */
    private function getCommonClientData(Attachment $attachment): array
    {
        return [
            'files'    => fopen($this->attachmentManager->getFullPath($attachment), 'rb'),
            'dossier'  => $attachment->getOwner()->getIdClient(),
            'document' => $attachment->getId(),
            'detail'   => GreenPointManager::DETAIL_TRUE,
            'nom'      => $attachment->getOwner()->getLastName() . ($attachment->getOwner()->getPreferredName() ? '|' . $attachment->getOwner()->getPreferredName() : ''),
            'prenom'   => $attachment->getOwner()->getFirstName(),
        ];
    }

    /**
     * @param Attachment $attachment
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return GreenpointAttachment
     */
    private function createGreenpointAttachment(Attachment $attachment): GreenpointAttachment
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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return GreenpointAttachment
     */
    private function updateGreenpointAttachment(Attachment $attachment, $response): GreenpointAttachment
    {
        $greenPointAttachment = $this->entityManager->getRepository(GreenpointAttachment::class)->findOneBy(['idAttachment' => $attachment->getId()]);

        if (null === $greenPointAttachment) {
            $greenPointAttachment = $this->createGreenpointAttachment($attachment);
        }

        if ($response instanceof Identity) {
            $greenPointAttachment->setValidationCode($response->getCode());
        }

        $greenPointAttachment
            ->setValidationStatus($response->getStatus())
            ->setValidationStatusLabel($response->getStatusLabel())
        ;

        $this->entityManager->flush($greenPointAttachment);

        return $greenPointAttachment;
    }

    /**
     * @param GreenpointAttachment            $greenPointAttachment
     * @param Identity|Rib|HousingCertificate $response
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function updateGreenPointAttachmentDetail(GreenpointAttachment $greenPointAttachment, $response): void
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

        if ($response instanceof HousingCertificate) {
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
            ->setIdentityBirthdate($identity->getBirthday())
            ->setIdentityDocumentNumber($identity->getDocumentNumber())
            ->setIdentityDocumentTypeId($identity->getType())
            ->setIdentityCivility($identity->getGender())
        ;
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
            ->setBankDetailsUrl($rib->getUrl())
        ;
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
            ->setAddressCountry($housingCertificate->getCountry())
        ;
    }
}
