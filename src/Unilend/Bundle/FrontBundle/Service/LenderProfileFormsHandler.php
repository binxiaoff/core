<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\{
    FormError, FormInterface
};
use Symfony\Component\HttpFoundation\{
    File\UploadedFile, FileBag
};
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Attachment, AttachmentType, BankAccount, ClientAddress, Clients, ClientsStatus, Companies, PaysV2, Users, WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Service\{
    AddressManager, AttachmentManager, BankAccountManager, ClientAuditer, ClientDataHistoryManager, ClientStatusManager
};
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class LenderProfileFormsHandler
{
    /** @var EntityManager */
    private $entityManager;
    /** @var AttachmentManager */
    private $attachmentManager;
    /** @var ClientStatusManager */
    private $clientStatusManager;
    /** @var ClientAuditer */
    private $clientAuditer;
    /** @var ClientDataHistoryManager */
    private $clientDataHistoryManager;
    /** @var AddressManager */
    private $addressManager;
    /** @var BankAccountManager */
    private $bankAccountManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManager            $entityManager
     * @param AttachmentManager        $attachmentManager
     * @param ClientStatusManager      $clientStatusManager
     * @param ClientAuditer            $clientAuditer
     * @param ClientDataHistoryManager $clientDataHistoryManager
     * @param AddressManager           $addressManager
     * @param BankAccountManager       $bankAccountManager
     * @param TranslatorInterface      $translator
     * @param TemplateMessageProvider  $messageProvider
     * @param \Swift_Mailer            $mailer
     * @param LoggerInterface          $logger
     */
    public function __construct(
        EntityManager $entityManager,
        AttachmentManager $attachmentManager,
        ClientStatusManager $clientStatusManager,
        ClientAuditer $clientAuditer,
        ClientDataHistoryManager $clientDataHistoryManager,
        AddressManager $addressManager,
        BankAccountManager $bankAccountManager,
        TranslatorInterface $translator,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        LoggerInterface $logger
    )
    {
        $this->entityManager            = $entityManager;
        $this->attachmentManager        = $attachmentManager;
        $this->clientStatusManager      = $clientStatusManager;
        $this->clientAuditer            = $clientAuditer;
        $this->clientDataHistoryManager = $clientDataHistoryManager;
        $this->addressManager           = $addressManager;
        $this->bankAccountManager       = $bankAccountManager;
        $this->translator               = $translator;
        $this->messageProvider          = $messageProvider;
        $this->mailer                   = $mailer;
        $this->logger                   = $logger;
    }

    /**
     * @param Clients       $unattachedClient
     * @param Clients       $client
     * @param FormInterface $form
     * @param FileBag       $fileBag
     *
     * @return bool
     * @throws \Exception
     */
    public function handlePersonIdentity(Clients $unattachedClient, Clients $client, FormInterface $form, FileBag $fileBag): bool
    {
        $isRectoUploaded = false;
        $files           = [
            AttachmentType::CNI_PASSPORTE       => $fileBag->get('id_recto'),
            AttachmentType::CNI_PASSPORTE_VERSO => $fileBag->get('id_verso')
        ];
        $newAttachments  = [];

        foreach ($files as $attachmentTypeId => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $newAttachments[] = $this->upload($client, $attachmentTypeId, $file);

                    if (AttachmentType::CNI_PASSPORTE === $attachmentTypeId) {
                        $isRectoUploaded = true;
                    }
                } catch (\Exception $exception) {
                    $form->get('client')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));
                    $this->logger->error('An error occurred while uploading attachment type id: ' . $attachmentTypeId . '. Error message: ' . $exception->getMessage(), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'method'    => __METHOD__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine()
                    ]);
                }
            }
        }

        if (
            false === $isRectoUploaded
            && (
                $unattachedClient->getIdNationalite() !== $client->getIdNationalite()
                || $unattachedClient->getCivilite() !== $client->getCivilite()
            )
        ) {
            $form->get('client')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-change-ID-warning-message')));
        }

        if ($form->isValid()) {
            $this->clientStatusManager->changeClientStatusTriggeredByClientAction($client, null, false, false, $newAttachments);
            $clientChanges = $this->logClientChanges($client);

            if ($isRectoUploaded || false === empty($clientChanges)) {
                $this->clientDataHistoryManager->sendAccountModificationEmail($client, $clientChanges, $newAttachments);
            }

            return true;
        }

        return false;
    }

    /**
     * @param Clients       $client
     * @param Companies     $company
     * @param FormInterface $form
     * @param FileBag       $fileBag
     *
     * @return bool
     * @throws \Exception
     */
    public function handleCompanyIdentity(Clients $client, Companies $company, FormInterface $form, FileBag $fileBag): bool
    {
        $isFileUploaded = false;

        if (Companies::CLIENT_STATUS_MANAGER != $company->getStatusClient()) {
            if (
                Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT === $company->getStatusClient()
                && empty($company->getStatusConseilExterneEntreprise())
            ) {
                $form->get('company')->get('statusConseilExterneEntreprise')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-external-counsel-error-message')));
            }

            if (
                Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT === $company->getStatusClient()
                && Companies::CLIENT_STATUS_EXTERNAL_COUNSEL_OTHER === $company->getStatusConseilExterneEntreprise()
                && empty($company->getPreciserConseilExterneEntreprise())
            ) {
                $form->get('company')->get('preciserConseilExterneEntreprise')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-external-counsel-error-message')));
            }

            if (empty($company->getCiviliteDirigeant())) {
                $form->get('company')->get('civiliteDirigeant')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-director-form-of-address-missing')));
            }

            if (empty($company->getNomDirigeant())) {
                $form->get('company')->get('nomDirigeant')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-director-name-missing')));
            }

            if (empty($company->getPrenomDirigeant())) {
                $form->get('company')->get('prenomDirigeant')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-director-first-name-missing')));
            }

            if (empty($company->getFonctionDirigeant())) {
                $form->get('company')->get('fonctionDirigeant')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-director-position-missing')));
            }

            if (empty($company->getPhoneDirigeant())) {
                $form->get('company')->get('phoneDirigeant')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-director-phone-missing')));
            }

            if (empty($company->getEmailDirigeant())) {
                $form->get('company')->get('emailDirigeant')->addError(new FormError($this->translator->trans('common-validator_email-address-invalid')));
            }
        }

        $files = [
            AttachmentType::CNI_PASSPORTE_DIRIGEANT => $fileBag->get('id_recto'),
            AttachmentType::CNI_PASSPORTE_VERSO     => $fileBag->get('id_verso'),
            AttachmentType::KBIS                    => $fileBag->get('company-registration'),
        ];

        if ($company->getStatusClient() > Companies::CLIENT_STATUS_MANAGER) {
            $files[AttachmentType::DELEGATION_POUVOIR] = $fileBag->get('delegation-of-authority');
        }
        $newAttachments = [];

        foreach ($files as $attachmentTypeId => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $newAttachments[] = $this->upload($client, $attachmentTypeId, $file);
                    $isFileUploaded   = true;
                } catch (\Exception $exception) {
                    $form->get('company')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));
                    $this->logger->error('An error occurred while uploading attachment type id: ' . $attachmentTypeId . '. Error message: ' . $exception->getMessage(), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'method'    => __METHOD__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine()
                    ]);
                }
            }
        }

        if ($form->isValid()) {
            $this->clientStatusManager->changeClientStatusTriggeredByClientAction($client, $company, false, false, $newAttachments);

            $clientChanges = $this->logClientChanges($client);

            $classMetaData = $this->entityManager->getClassMetadata(Companies::class);
            $unitOfWork    = $this->entityManager->getUnitOfWork();
            $unitOfWork->computeChangeSet($classMetaData, $company);
            $modifiedDataCompany = $unitOfWork->getEntityChangeSet($company);
            unset($modifiedDataCompany['updated']);

            $this->entityManager->flush($company);

            if ($isFileUploaded || false === empty($clientChanges) || false === empty($modifiedDataCompany)) {
                $this->clientDataHistoryManager->sendAccountModificationEmail($client, $clientChanges, $newAttachments);
            }

            return true;
        }

        return false;
    }

    /**
     * @param Clients            $client
     * @param FormInterface      $form
     * @param FileBag            $fileBag
     * @param string             $type
     * @param ClientAddress|null $address
     *
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function handlePersonAddress(Clients $client, FormInterface $form, FileBag $fileBag, string $type, ?ClientAddress $address): bool
    {
        $housingCertificate = null;
        $newAttachments     = [];
        $modifiedData       = [];

        $zip       = $form->get('zip')->getData();
        $countryId = $form->get('idCountry')->getData();

        switch ($type) {
            case AddressType::TYPE_MAIN_ADDRESS:
                $modifiedData = [
                    ClientDataHistoryManager::MAIN_ADDRESS_FORM_LABEL => ClientDataHistoryManager::MAIN_ADDRESS_FORM_LABEL
                ];
                if (PaysV2::COUNTRY_FRANCE == $countryId && null === $this->entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $zip])) {
                    $form->get('zip')->addError(new FormError($this->translator->trans('lender-profile_information-tab-fiscal-address-section-unknown-zip-code-error-message')));
                }

                $form->get('noUsPerson')->getData() ? $client->setUsPerson(false) : $client->setUsPerson(true);

                $files[AttachmentType::JUSTIFICATIF_DOMICILE] = $fileBag->get('housing-certificate');
                if ($countryId !== PaysV2::COUNTRY_FRANCE) {
                    $files[AttachmentType::JUSTIFICATIF_FISCAL] = $fileBag->get('tax-certificate');
                }
                if ($form->get('housedByThirdPerson')->getData()) {
                    $files[AttachmentType::ATTESTATION_HEBERGEMENT_TIERS] = $fileBag->get('housed-by-third-person-declaration');
                    $files[AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT] = $fileBag->get('id-third-person-housing');
                }

                if (
                    AddressType::TYPE_MAIN_ADDRESS === $type
                    && (
                        null === $address
                        || (
                            $address->getAddress() !== $form->get('address')->getData()
                            || $address->getZip() !== $form->get('zip')->getData()
                            || $address->getCity() !== $form->get('city')->getData()
                            || $address->getIdCountry()->getIdPays() !== $form->get('idCountry')->getData()
                        )
                    )
                    && empty($files[AttachmentType::JUSTIFICATIF_DOMICILE])
                ) {
                    $form->addError(new FormError($this->translator->trans('lender-profile_information-tab-fiscal-address-change-message')));
                }

                foreach ($files as $attachmentTypeId => $file) {
                    if ($file instanceof UploadedFile) {
                        try {
                            $newAttachments[] = $attachement = $this->upload($client, $attachmentTypeId, $file);

                            if (AttachmentType::JUSTIFICATIF_DOMICILE === $attachmentTypeId) {
                                $housingCertificate = $attachement;
                            }
                        } catch (\Exception $exception) {
                            $form->addError(new FormError($this->translator->trans('lender-profile_information-tab-fiscal-address-section-upload-files-error-message')));
                            $this->logger->error('An error occurred while uploading attachment type id: ' . $attachmentTypeId . '. Error message: ' . $exception->getMessage(), [
                                'id_client' => $client->getIdClient(),
                                'class'     => __CLASS__,
                                'method'    => __METHOD__,
                                'file'      => $exception->getFile(),
                                'line'      => $exception->getLine()
                            ]);
                        }
                    } else {
                        switch ($attachmentTypeId) {
                            case AttachmentType::JUSTIFICATIF_FISCAL :
                                $error = $this->translator->trans('lender-profile_information-tab-fiscal-address-section-missing-tax-certificate');
                                break;
                            case AttachmentType::ATTESTATION_HEBERGEMENT_TIERS :
                                $error = $this->translator->trans('lender-profile_information-tab-fiscal-address-missing-housed-by-third-person-declaration');
                                break;
                            case AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT :
                                $error = $this->translator->trans('lender-profile_information-tab-fiscal-address-missing-id-third-person-housing');
                                break;
                            default :
                                continue 2;
                        }
                        $form->addError(new FormError($error));
                    }
                }
                break;
            case AddressType::TYPE_POSTAL_ADDRESS:
                $modifiedData = [
                    ClientDataHistoryManager::POSTAL_ADDRESS_FORM_LABEL => ClientDataHistoryManager::POSTAL_ADDRESS_FORM_LABEL
                ];
                break;
            default:
                break;
        }
        $addressModification = false;

        if ($form->isValid()) {
            if ($form->has('samePostalAddress') && $form->get('samePostalAddress')->getData()) {
                $this->addressManager->clientPostalAddressSameAsMainAddress($client);
                $addressModification = true;
            } elseif (
                false === $form->has('samePostalAddress')
                || $form->has('samePostalAddress') && empty($form->get('samePostalAddress')->getData())
            ) {
                $this->addressManager->saveClientAddress(
                    $form->get('address')->getData(),
                    $form->get('zip')->getData(),
                    $form->get('city')->getData(),
                    $form->get('idCountry')->getData(),
                    $client,
                    $type
                );
                $addressModification = true;
            }

            if (AddressType::TYPE_MAIN_ADDRESS === $type) {
                if (null !== $housingCertificate) {
                    $address = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
                    $this->addressManager->linkAttachmentToAddress($address, $housingCertificate);
                    $addressModification = true;
                } else {
                    $this->logger->error('Lender main address has no attachment.', [
                        'class'     => __CLASS__,
                        'line'      => __LINE__,
                        'id_client' => $client->getIdClient()
                    ]);
                }
            }
            $this->clientStatusManager->changeClientStatusTriggeredByClientAction($client, null, $addressModification, false, $newAttachments);

            $modifiedData += $this->logClientChanges($client);

            $this->clientDataHistoryManager->sendAccountModificationEmail($client, $modifiedData, $newAttachments);

            return true;
        }

        return false;
    }

    /**
     * @param Companies     $company
     * @param FormInterface $form
     * @param string        $type
     *
     * @return bool
     * @throws \Exception
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function handleCompanyAddress(Companies $company, FormInterface $form, string $type): bool
    {
        $zip          = $form->get('zip')->getData();
        $countryId    = $form->get('idCountry')->getData();
        $modifiedData = [];

        switch ($type) {
            case AddressType::TYPE_MAIN_ADDRESS:
                $modifiedData = [
                    ClientDataHistoryManager::MAIN_ADDRESS_FORM_LABEL => ClientDataHistoryManager::MAIN_ADDRESS_FORM_LABEL
                ];

                if (
                    false === empty($zip) && false === empty($countryId)
                    && PaysV2::COUNTRY_FRANCE == $countryId
                    && null === $this->entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $zip])
                ) {
                    $form->get('zip')->addError(new FormError($this->translator->trans('lender-profile_information-tab-fiscal-address-section-unknown-zip-code-error-message')));
                }
                break;
            case AddressType::TYPE_POSTAL_ADDRESS:
                $modifiedData = [
                    ClientDataHistoryManager::POSTAL_ADDRESS_FORM_LABEL => ClientDataHistoryManager::POSTAL_ADDRESS_FORM_LABEL
                ];
                break;
            default:
                $this->logger->error('Unknown address type requested. Type: ' . $type . ' is not supported in lender profile', [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__
                ]);
                break;
        }
        $addressModification = false;

        if ($form->isValid()) {
            if ($form->has('samePostalAddress') && $form->get('samePostalAddress')->getData()) {
                $this->addressManager->companyPostalAddressSameAsMainAddress($company);
                $addressModification = true;
            } elseif (
                false === $form->has('samePostalAddress')
                || $form->has('samePostalAddress') && empty($form->get('samePostalAddress')->getData())
            ) {
                $this->addressManager->saveCompanyAddress(
                    $form->get('address')->getData(),
                    $form->get('zip')->getData(),
                    $form->get('city')->getData(),
                    $form->get('idCountry')->getData(),
                    $company,
                    $type
                );
                $addressModification = true;
            }

            $this->clientStatusManager->changeClientStatusTriggeredByClientAction($company->getIdClientOwner(), null, $addressModification);
            $this->clientDataHistoryManager->sendAccountModificationEmail($company->getIdClientOwner(), $modifiedData);

            return true;
        }

        return false;
    }

    /**
     * @param FormInterface    $form
     * @param FileBag          $fileBag
     * @param BankAccount|null $unattachedBankAccount
     *
     * @return bool
     * @throws \Exception
     */
    public function handleBankDetailsForm(FormInterface $form, FileBag $fileBag, ?BankAccount $unattachedBankAccount): bool
    {
        $iban                = $form->get('bankAccount')->get('iban')->getData();
        $bic                 = $form->get('bankAccount')->get('bic')->getData();
        $client              = $form->get('client')->getData();
        $bankAccountDocument = null;

        if (false === in_array(strtoupper(substr($iban, 0, 2)), PaysV2::EEA_COUNTRIES_ISO)) {
            $form->get('bankAccount')->get('iban')->addError(new FormError($this->translator->trans('lender-subscription_documents-iban-not-european-error-message')));
        }

        if (
            null === $unattachedBankAccount && (false === empty($iban) || false === empty($bic))
            || null !== $unattachedBankAccount && ($unattachedBankAccount->getIban() !== $iban || $unattachedBankAccount->getBic() !== $bic)
        ) {
            $file = $fileBag->get('iban-certificate');

            if (false === $file instanceof UploadedFile) {
                $form->get('bankAccount')->addError(new FormError($this->translator->trans('lender-profile_rib-file-mandatory')));
            } else {
                try {
                    $bankAccountDocument = $this->upload($client, AttachmentType::RIB, $file);
                } catch (\Exception $exception) {
                    $form->addError(new FormError($this->translator->trans('lender-profile_fiscal-tab-rib-file-error')));
                    $this->logger->error('An error occurred while uploading IBAN attachment type id: ' . AttachmentType::RIB . '. Error message: ' . $exception->getMessage(), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'method'    => __METHOD__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine()
                    ]);
                }
            }
        }

        if ($form->isValid() && $bankAccountDocument) {
            $clientChanges = $this->logClientChanges($client);
            $clientChanges += [
                ClientDataHistoryManager::BANK_ACCOUNT_FORM_LABEL => ClientDataHistoryManager::BANK_ACCOUNT_FORM_LABEL
            ];

            $this->bankAccountManager->saveBankInformation($client, $bic, $iban, $bankAccountDocument);
            $this->clientStatusManager->changeClientStatusTriggeredByClientAction($client, null, false, true, [$bankAccountDocument]);
            $this->clientDataHistoryManager->sendAccountModificationEmail($client, $clientChanges, [$bankAccountDocument]);

            return true;
        }

        return false;
    }

    /**
     * @param Clients       $client
     * @param FormInterface $form
     *
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function handleEmailForm(Clients $client, FormInterface $form): bool
    {
        if (false === empty($this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findByEmailAndStatus($client->getEmail(), ClientsStatus::GRANTED_LOGIN))) {
            $form->addError(new FormError($this->translator->trans('lender-profile_security-identification-error-existing-email')));
        }

        if ($form->isValid()) {
            $this->clientStatusManager->changeClientStatusTriggeredByClientAction($client);
            $clientChanges = $this->logClientChanges($client);

            if (false === empty($clientChanges)) {
                $this->clientDataHistoryManager->sendAccountModificationEmail($client, $clientChanges);
            }

            return true;
        }

        return false;
    }

    /**
     * @param Clients $client
     *
     * @return bool
     * @throws OptimisticLockException
     */
    public function handlePhoneForm(Clients $client): bool
    {
        $this->clientStatusManager->changeClientStatusTriggeredByClientAction($client);
        $clientChanges = $this->logClientChanges($client);

        if (false === empty($clientChanges)) {
            $this->clientDataHistoryManager->sendAccountModificationEmail($client, $clientChanges);
        }

        return true;
    }

    /**
     * Changes should only be logged when client is actually updated meaning form should be valid
     * Thus this method should only be called once form is fully validated, including attachments
     *
     * @param Clients $client
     *
     * @return array
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function logClientChanges(Clients $client): array
    {
        $frontUser     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_FRONT);
        $clientChanges = $this->clientAuditer->logChanges($client, $frontUser);

        if (false === empty($clientChanges['email'][0])) {
            $this->notifyEmailChangeToOldAddress($client, $clientChanges['email'][0]);
        }

        $this->entityManager->flush($client);

        return $clientChanges;
    }

    /**
     * @param Clients $client
     * @param string  $oldEmail
     */
    private function notifyEmailChangeToOldAddress(Clients $client, string $oldEmail): void
    {
        $walletRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $wallet           = $walletRepository->getWalletByType($client, WalletType::LENDER);

        if (null === $wallet) {
            $this->logger->error('Could not notify email modification to old email address. Unable to find lender wallet.', [
                'id_client' => $client,
                'class'     => __CLASS__,
                'function'  => __FUNCTION__
            ]);

            return;
        }

        $message = $this->messageProvider->newMessage('alerte-changement-email-preteur', [
            'firstName'     => $client->getPrenom(),
            'lastName'      => $client->getNom(),
            'lenderPattern' => $wallet->getWireTransferPattern()
        ]);

        try {
            $message->setTo($oldEmail);
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->error('Could not send email modification alert to the previous lender email. Error: ' . $exception->getMessage(), [
                'id_client'   => $client->getIdClient(),
                'template_id' => $message->getTemplateId(),
                'class'       => __CLASS__,
                'function'    => __FUNCTION__,
                'file'        => $exception->getFile(),
                'line'        => $exception->getLine()
            ]);
        }
    }

    /**
     * @param Clients      $client
     * @param int          $attachmentTypeId
     * @param UploadedFile $file
     *
     * @return Attachment
     * @throws \Exception
     */
    private function upload(Clients $client, int $attachmentTypeId, UploadedFile $file): Attachment
    {
        $attachmentType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find($attachmentTypeId);
        $attachment     = $this->attachmentManager->upload($client, $attachmentType, $file);

        if (false === $attachment instanceof Attachment) {
            throw new \Exception();
        }

        return $attachment;
    }
}
