<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Attachment, AttachmentType, GreenpointAttachment, GreenpointAttachmentDetail};

class GreenpointManager
{
    const NOT_VERIFIED                   = 0;
    const OUT_OF_BOUNDS                  = 1;
    const FALSIFIED_OR_MINOR             = 2;
    const ILLEGIBLE                      = 3;
    const VERSO_MISSING                  = 4;
    const NAME_SURNAME_INVERSION         = 5;
    const INCOHERENT_OTHER_ERROR         = 6;
    const EXPIRED                        = 7;
    const CONFORM_COHERENT_NOT_QUALIFIED = 8;
    const CONFORM_COHERENT_QUALIFIED     = 9;

    const TYPE_IDENTITY_DOCUMENT   = 1;
    const TYPE_RIB                 = 2;
    const TYPE_HOUSING_CERTIFICATE = 3;

    const ID_CONTROL_STATUS_LABEL = [
        self::NOT_VERIFIED                   => 'Non vérifié',
        self::OUT_OF_BOUNDS                  => 'Hors périmètre (pas un document d\'identité)',
        self::FALSIFIED_OR_MINOR             => 'Falsifiée ou mineur',
        self::ILLEGIBLE                      => 'Illisible / coupée',
        self::VERSO_MISSING                  => 'Verso seul : recto manquant',
        self::NAME_SURNAME_INVERSION         => 'Non cohérent / données : inversion nom - prénom',
        self::INCOHERENT_OTHER_ERROR         => 'Non cohérent / données : autre erreur',
        self::EXPIRED                        => 'Expiré',
        self::CONFORM_COHERENT_NOT_QUALIFIED => 'Conforme, cohérent et valide mais non labellisable',
        self::CONFORM_COHERENT_QUALIFIED     => 'Conforme, cohérent et valide + label GREENPOINT IDCONTROL'
    ];

    const IBAN_FLASH_STATUS_LABEL = [
        self::NOT_VERIFIED                   => 'Non vérifié',
        self::OUT_OF_BOUNDS                  => 'Hors périmètre (pas un RIB)',
        self::FALSIFIED_OR_MINOR             => 'Falsifié',
        self::ILLEGIBLE                      => 'Illisible / coupé',
        self::VERSO_MISSING                  => 'Banque hors périmètre',
        self::NAME_SURNAME_INVERSION         => 'Non cohérent / données : inversion nom - prénom',
        self::INCOHERENT_OTHER_ERROR         => 'Non cohérent / données : autre erreur',
        self::EXPIRED                        => '-',
        self::CONFORM_COHERENT_NOT_QUALIFIED => 'Vérifié sauf prénom du titulaire non vérifié',
        self::CONFORM_COHERENT_QUALIFIED     => 'Conforme, cohérent et valide'
    ];

    const ADDRESS_CONTROL_STATUS_LABEL = [
        self::NOT_VERIFIED                   => 'Non vérifié',
        self::OUT_OF_BOUNDS                  => 'Hors périmètre (pas un justificatif de domicile)',
        self::FALSIFIED_OR_MINOR             => 'Falsifié',
        self::ILLEGIBLE                      => 'Illisible / coupé',
        self::VERSO_MISSING                  => 'Fournisseur hors périmètre',
        self::NAME_SURNAME_INVERSION         => 'Non cohérent / données : erreur sur le titulaire',
        self::INCOHERENT_OTHER_ERROR         => 'Non cohérent / données : erreur sur l\'adresse',
        self::EXPIRED                        => '-',
        self::CONFORM_COHERENT_NOT_QUALIFIED => 'Vérifié sauf prénom du titulaire non vérifié',
        self::CONFORM_COHERENT_QUALIFIED     => 'Conforme, cohérent et valide'
    ];

    /** @var EntityManager */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var AttachmentManager */
    private $attachmentManager;
    /** @var AddressManager */
    private $addressManager;
    /** @var BankAccountManager */
    private $bankAccountManager;

    /**
     * @param EntityManager      $entityManager
     * @param LoggerInterface    $logger
     * @param AttachmentManager  $attachmentManager
     * @param AddressManager     $addressManager
     * @param BankAccountManager $bankAccountManager
     */
    public function __construct(
        EntityManager $entityManager,
        LoggerInterface $logger,
        AttachmentManager $attachmentManager,
        AddressManager $addressManager,
        BankAccountManager $bankAccountManager
    )
    {
        $this->entityManager      = $entityManager;
        $this->logger             = $logger;
        $this->attachmentManager  = $attachmentManager;
        $this->addressManager     = $addressManager;
        $this->bankAccountManager = $bankAccountManager;
    }

    /**
     * @param int        $type
     * @param Attachment $attachment
     * @param array      $data
     *
     * @throws \Exception
     */
    public function handleAsynchronousFeedback(int $type, Attachment $attachment, array $data): void
    {
        switch ($type) {
            case self::TYPE_IDENTITY_DOCUMENT:
                $greenPointAttachment = $this->updateGreenpointAttachment($attachment, $data);
                $this->updateGreenPointAttachmentDetail($greenPointAttachment, $data);
                break;
            case self::TYPE_RIB:
                $greenPointAttachment = $this->updateGreenpointAttachment($attachment, $data);
                $this->handleRibReturn($attachment, $greenPointAttachment);
                break;
            case self::TYPE_HOUSING_CERTIFICATE:
                $greenPointAttachment = $this->updateGreenpointAttachment($attachment, $data);
                $this->handleHousingCertificateReturn($attachment, $greenPointAttachment);
                break;
            default:
                $this->logger->error(
                    'Greenpoint returned feedback for unknown type', [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__,
                    'type'     => $type
                    ]);
        }
    }

    /**
     * @param array    $data
     * @param int|null $attachmentTypeId
     * @param int|null $code
     *
     * @return array
     */
    public function getGreenPointAttachementDetail(array $data): array
    {
        $attachmentDetail['document_type']              = $data['type'] ?? null;
        $attachmentDetail['identity_civility']          = $data['sexe'] ?? null;
        $attachmentDetail['identity_name']              = $data['prenom'] ?? null;
        $attachmentDetail['identity_surname']           = $data['nom'] ?? null;
        $attachmentDetail['identity_expiration_date']   = empty($data['expirationdate']) ? null : \DateTime::createFromFormat('d/m/Y', $data['expirationdate']);
        $attachmentDetail['identity_birthdate']         = empty($data['date_naissance']) ? null : \DateTime::createFromFormat('d/m/Y', $data['date_naissance']);
        $attachmentDetail['identity_mrz1']              = $data['mrz1'] ?? null;
        $attachmentDetail['identity_mrz2']              = $data['mrz2'] ?? null;
        $attachmentDetail['identity_mrz3']              = $data['mrz3'] ?? null;
        $attachmentDetail['identity_nationality']       = $data['nationalite'] ?? null;
        $attachmentDetail['identity_issuing_country']   = $data['pays_emetteur'] ?? null;
        $attachmentDetail['identity_issuing_authority'] = $data['autorite_emettrice'] ?? null;
        $attachmentDetail['identity_document_number']   = $data['numero'] ?? null;
        $attachmentDetail['identity_document_type_id']  = $data['type_id'] ?? null;
        $attachmentDetail['bank_details_iban']          = $data['iban'] ?? null;
        $attachmentDetail['bank_details_bic']           = $data['bic'] ?? null;
        $attachmentDetail['bank_details_url']           = $data['url'] ?? null;
        $attachmentDetail['address_address']            = $data['adresse'] ?? null;
        $attachmentDetail['address_postal_code']        = $data['code_postal'] ?? null;
        $attachmentDetail['address_city']               = $data['ville'] ?? null;
        $attachmentDetail['address_country']            = $data['pays'] ?? null;

        return $attachmentDetail;
    }

    /**
     * @param array    $response
     * @param int|null $attachmentTypeId
     * @param int|null $code
     *
     * @return array
     */
    private function getAttachmentData(array $response, ?int $attachmentTypeId = null, ?int $code = null): array
    {
        $attachment['validation_code']   = $code ?? $response['code'] ?? null;
        $attachment['validation_status'] = $response['statut_verification'] ?? null;
        $attachment['agency']            = null;

        switch ($attachmentTypeId) {
            case AttachmentType::CNI_PASSPORTE:
            case AttachmentType::CNI_PASSPORTE_VERSO:
            case AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT:
            case AttachmentType::CNI_PASSPORTE_DIRIGEANT:
                $attachment['validation_status_label'] = self::ID_CONTROL_STATUS_LABEL[$attachment['validation_status']];
                break;
            case AttachmentType::RIB:
                $attachment['validation_status_label'] = self::IBAN_FLASH_STATUS_LABEL[$attachment['validation_status']];
                break;
            case AttachmentType::JUSTIFICATIF_DOMICILE:
            case AttachmentType::ATTESTATION_HEBERGEMENT_TIERS:
                $attachment['validation_status_label'] = self::ADDRESS_CONTROL_STATUS_LABEL[$attachment['validation_status']];
                break;
        }

        return $attachment;
    }

    /**
     * @param Attachment $attachment
     * @param array      $data
     *
     * @return GreenpointAttachment
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function updateGreenpointAttachment(Attachment $attachment, array $data): GreenpointAttachment
    {
        $attachmentData       = $this->getAttachmentData($data, $attachment->getType()->getId());
        $greenPointAttachment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:GreenpointAttachment')->findOneBy(['idAttachment' => $attachment->getId()]);

        if (null === $greenPointAttachment) {
            $greenPointAttachment = new GreenpointAttachment();
            $greenPointAttachment->setIdAttachment($attachment);

            $this->entityManager->persist($greenPointAttachment);
        }

        $greenPointAttachment
            ->setValidationCode($attachmentData['validation_code'])
            ->setValidationStatus($attachmentData['validation_status'])
            ->setValidationStatusLabel($attachmentData['validation_status_label']);

        $this->entityManager->flush($greenPointAttachment);

        $this->updateGreenPointAttachmentDetail($greenPointAttachment, $data);

        return $greenPointAttachment;
    }

    /**
     * @param GreenpointAttachment $greenPointAttachment
     * @param array                $data
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function updateGreenPointAttachmentDetail(GreenpointAttachment $greenPointAttachment, array $data)
    {
        $greenpointAttachementDetail = $this->getGreenPointAttachementDetail($data);

        $greenPointAttachmentDetails = $greenPointAttachment->getGreenpointAttachmentDetail();
        if (null === $greenPointAttachmentDetails) {
            $greenPointAttachmentDetails = new GreenpointAttachmentDetail();
            $greenPointAttachmentDetails->setIdGreenpointAttachment($greenPointAttachment);
            $this-> entityManager->persist($greenPointAttachmentDetails);
        }

        $greenPointAttachmentDetails
            ->setDocumentType($greenpointAttachementDetail['document_type'])
            ->setIdentityCivility($greenpointAttachementDetail['identity_civility'])
            ->setIdentityName($greenpointAttachementDetail['identity_name'])
            ->setIdentitySurname($greenpointAttachementDetail['identity_surname'])
            ->setIdentityExpirationDate($greenpointAttachementDetail['identity_expiration_date'])
            ->setIdentityBirthdate($greenpointAttachementDetail['identity_birthdate'])
            ->setIdentityMrz1($greenpointAttachementDetail['identity_mrz1'])
            ->setIdentityMrz2($greenpointAttachementDetail['identity_mrz2'])
            ->setIdentityMrz3($greenpointAttachementDetail['identity_mrz3'])
            ->setIdentityNationality($greenpointAttachementDetail['identity_nationality'])
            ->setIdentityIssuingCountry($greenpointAttachementDetail['identity_issuing_country'])
            ->setIdentityIssuingAuthority($greenpointAttachementDetail['identity_issuing_authority'])
            ->setIdentityDocumentNumber($greenpointAttachementDetail['identity_document_number'])
            ->setIdentityDocumentTypeId($greenpointAttachementDetail['identity_document_type_id'])
            ->setBankDetailsIban($greenpointAttachementDetail['bank_details_iban'])
            ->setBankDetailsBic($greenpointAttachementDetail['bank_details_bic'])
            ->setBankDetailsUrl($greenpointAttachementDetail['bank_details_url'])
            ->setAddressAddress($greenpointAttachementDetail['address_address'])
            ->setAddressPostalCode($greenpointAttachementDetail['address_postal_code'])
            ->setAddressCity($greenpointAttachementDetail['address_city'])
            ->setAddressCountry($greenpointAttachementDetail['address_country']);

        $this->entityManager->flush($greenPointAttachmentDetails);
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
                $this->logger->error('Lender housing certificate has no associated address - Client: ' . $attachment->getClient()->getIdClient(), [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__
                ]);
            } else {
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
                $this->logger->error('Lender has no associated bank account - Client: ' . $attachment->getClient()->getIdClient(), [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__,
                    'id_client' => $attachment->getClient()->getIdClient()
                ]);
            } else {
                $this->bankAccountManager->validateBankAccount($bankAccountToValidate);
            }
        }
    }

}
