<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Attachment, AttachmentType, Clients, ClientsStatus, GreenpointAttachment, GreenpointKyc};
use Unilend\Bundle\WSClientBundle\Entity\GreenPoint\{HousingCertificate, Identity, Rib};
use Unilend\Bundle\WSClientBundle\Service\GreenPointManager;

class GreenPointValidationManager
{
    const ATTACHMENT_TYPE_TO_VALIDATE = [
        AttachmentType::CNI_PASSPORTE,
        AttachmentType::JUSTIFICATIF_DOMICILE,
        AttachmentType::ATTESTATION_HEBERGEMENT_TIERS,
        AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT,
        AttachmentType::CNI_PASSPORTE_DIRIGEANT,
        AttachmentType::RIB,
    ];

    const STATUS_TO_CHECK = [
        ClientsStatus::STATUS_TO_BE_CHECKED,
        ClientsStatus::STATUS_COMPLETENESS_REPLY,
        ClientsStatus::STATUS_MODIFICATION,
        ClientsStatus::STATUS_SUSPENDED
    ];

    /** @var EntityManager */
    private $entityManager;
    /** @var AttachmentManager */
    private $attachmentManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var AddressManager */
    private $addressManager;
    /** @var BankAccountManager */
    private $bankAccountManager;
    /** @var GreenPointDataManager  */
    private $dataManager;
    /** @var GreenPointManager  */
    private $wsManager;

    /**
     * @param EntityManager         $entityManager
     * @param AttachmentManager     $attachmentManager
     * @param LoggerInterface       $logger
     * @param AddressManager        $addressManager
     * @param BankAccountManager    $bankAccountManager
     * @param GreenPointDataManager $dataManager
     * @param GreenPointManager     $wsManager
     */
    public function __construct(
        EntityManager $entityManager,
        AttachmentManager $attachmentManager,
        LoggerInterface $logger,
        AddressManager $addressManager,
        BankAccountManager $bankAccountManager,
        GreenPointDataManager $dataManager,
        GreenPointManager $wsManager
    )
    {
        $this->entityManager      = $entityManager;
        $this->attachmentManager  = $attachmentManager;
        $this->logger             = $logger;
        $this->addressManager     = $addressManager;
        $this->bankAccountManager = $bankAccountManager;
        $this->dataManager        = $dataManager;
        $this->wsManager          = $wsManager;
    }

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    public function isEligibleForValidation(Attachment $attachment): bool
    {
        if (false === in_array($attachment->getType()->getId(), self::ATTACHMENT_TYPE_TO_VALIDATE)) {
            return false;
        }

        $clientStatusHistoryRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory');

        try {
            $validationCount = $clientStatusHistoryRepository->getValidationsCount($attachment->getClient()->getIdClient());
        } catch (\Exception $exception) {
            $validationCount = 0;
            $this->logger->warning('Could not check the validation count on id_client: ' . $attachment->getClient()->getIdClient() . ' - Error: ' . $exception->getMessage(),[
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'id_client' => $attachment->getClient()->getIdClient()
            ]);
        }

        if ($validationCount > 0 && false === $this->attachmentManager->isModifiedAttachment($attachment)) {
            return false;
        }

        if (false == file_exists(realpath($this->attachmentManager->getFullPath($attachment)))) {
            $this->logger->error(
                'Attachment file not found (ID ' . $attachment->getId() . ')', [
                'class'         => __CLASS__,
                'function'      => __FUNCTION__,
                'id_attachment' => $attachment->getId()
            ]);

            return false;
        }

        if (null !== $attachment->getGreenpointAttachment() && null !== $attachment->getGreenpointAttachment()->getValidationStatus()) {
           return false;
        }

        return true;
    }

    /**
     * @param Attachment $attachment
     *
     * @throws \Exception
     */
    public function validateAttachement(Attachment $attachment)
    {
        $greenPointData = $this->dataManager->getGreenPointData($attachment);

        switch ($attachment->getType()->getId()) {
            case AttachmentType::CNI_PASSPORTE:
            case AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT:
            case AttachmentType::CNI_PASSPORTE_DIRIGEANT:
                $response = $this->wsManager->checkIdentity($greenPointData);
                break;
            case AttachmentType::RIB:
                $response = $this->wsManager->checkIban($greenPointData);
                break;
            case AttachmentType::JUSTIFICATIF_DOMICILE:
            case AttachmentType::ATTESTATION_HEBERGEMENT_TIERS:
                $response = $this->wsManager->checkAddress($greenPointData);
                break;
            default :
                throw new \InvalidArgumentException('Unsupported attachment type. No GreenPoint resource found');
        }

        $this->dataManager->createGreenPointAttachment($attachment);

        $this->handleGreenPointResponse($response, $attachment);
    }

    /**
     * @param Identity|Rib|HousingCertificate $response
     * @param Attachment                      $attachment
     *
     * @throws \Exception
     */
    private function handleGreenPointResponse($response, Attachment $attachment)
    {
        $greenPointAttachment = $this->dataManager->updateGreenPointAttachment($attachment, $response);

        $this->dataManager->updateGreenPointAttachmentDetail($greenPointAttachment, $response);

        if (
            $attachment->getType()->getId() === AttachmentType::RIB
            && $response instanceof Rib
        ) {
            $this->handleRibReturn($attachment, $greenPointAttachment);
        }

        if (
            in_array($attachment->getType()->getId(), [AttachmentType::JUSTIFICATIF_DOMICILE, AttachmentType::ATTESTATION_HEBERGEMENT_TIERS])
            && $response instanceof HousingCertificate
        ) {
            $this->handleHousingCertificateReturn($attachment, $greenPointAttachment);
        }
    }

    /**
     * @param Clients $client
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveClientKycStatus(Clients $client)
    {
        $kycInfo = $this->wsManager->getClientKYCStatus($client);

        $clientKyc = $this->entityManager->getRepository('UnilendCoreBusinessBundle:GreenPointKyc')->findOneBy(['idClient' => $client->getIdClient()]);
        if (null === $clientKyc) {
            $clientKyc = new GreenpointKyc();
            $clientKyc->setCreationDate($kycInfo->getCreated());

            $this->entityManager->persist($clientKyc);
        }

        $clientKyc
            ->setStatus($kycInfo->getStatus())
            ->setLastUpdate(($kycInfo->getLastModified()));

        $this->entityManager->flush($clientKyc);
    }

    /**
     * @param Identity|Rib|HousingCertificate $response
     * @param Attachment                      $attachment
     *
     * @throws \Exception
     */
    public function handleAsynchronousFeedback($response, Attachment $attachment): void
    {
        $this->handleGreenPointResponse($response, $attachment);

        $this->saveClientKycStatus($attachment->getClient());
    }

    /**
     * @param Attachment           $attachment
     * @param GreenpointAttachment $greenPointAttachment
     *
     * @throws \Exception
     */
    private function handleHousingCertificateReturn(Attachment $attachment, GreenpointAttachment $greenPointAttachment)
    {
        if (AttachmentType::JUSTIFICATIF_DOMICILE === $attachment->getType()->getId() && GreenpointAttachment::STATUS_VALIDATION_VALID === $greenPointAttachment->getValidationStatus()) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ClientAddressAttachment $addressAttachment */
            $addressAttachment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddressAttachment')->findOneBy(['idAttachment' => $attachment]);
            if (null === $addressAttachment || null === $addressAttachment->getIdClientAddress()) {
                $this->logger->error(
                    'Lender housing certificate has no associated address - Client: ' . $attachment->getClient()->getIdClient(), [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__
                ]);
            }
            else {
                $this->addressManager->validateLenderAddress($addressAttachment->getIdClientAddress());
            }
        }
    }

    /**
     * @param Attachment           $attachment
     * @param GreenpointAttachment $greenPointAttachment
     *
     * @throws \Exception
     */
    private function handleRibReturn(Attachment $attachment, GreenpointAttachment $greenPointAttachment)
    {
        if (AttachmentType::RIB === $attachment->getType()->getId() && GreenpointAttachment::STATUS_VALIDATION_VALID === $greenPointAttachment->getValidationStatus()) {
            $bankAccountToValidate = $attachment->getBankAccount();
            if (null === $bankAccountToValidate) {
                $this->logger->error(
                    'Lender has no associated bank account - Client: ' . $attachment->getClient()->getIdClient(), [
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'id_client' => $attachment->getClient()->getIdClient()
                ]);
            }
            else {
                $this->bankAccountManager->validateBankAccount($bankAccountToValidate);
            }
        }
    }
}
