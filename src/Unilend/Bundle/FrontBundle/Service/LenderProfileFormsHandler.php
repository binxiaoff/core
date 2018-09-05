<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\{FormError, FormInterface};
use Symfony\Component\HttpFoundation\{File\UploadedFile, FileBag};
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{AddressType, Attachment, AttachmentType, BankAccount, ClientAddress, Clients, ClientsStatus, Companies, CompanyAddress, Pays, Users, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\{AddressManager, AttachmentManager, BankAccountManager, ClientAuditer, ClientDataHistoryManager, ClientStatusManager};
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
     * @param Clients       $client
     * @param Clients       $unattachedClient
     * @param FormInterface $form
     * @param FileBag       $fileBag
     *
     * @return bool
     * @throws \Exception
     */
    public function handlePersonIdentity(Clients $client, Clients $unattachedClient, FormInterface $form, FileBag $fileBag): bool
    {
        $newAttachments = $this->uploadPersonalIdentityDocuments($client, $unattachedClient, $form, $fileBag);

        if ($this->isFormValid($form)) {
            $this->saveAndNotifyChanges($client, $unattachedClient, null, null, $newAttachments);

            return true;
        }

        return false;
    }

    /**
     * @param Clients       $client
     * @param Clients       $unattachedClient
     * @param FormInterface $form
     * @param FileBag       $fileBag
     *
     * @return array
     */
    private function uploadPersonalIdentityDocuments(Clients $client, Clients $unattachedClient, FormInterface $form, FileBag $fileBag): array
    {
        $newAttachments = [];

        $files = [
            AttachmentType::CNI_PASSPORTE       => $fileBag->get('id_recto'),
            AttachmentType::CNI_PASSPORTE_VERSO => $fileBag->get('id_verso')
        ];

        foreach ($files as $attachmentTypeId => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $newAttachments[$attachmentTypeId] = $this->upload($client, $attachmentTypeId, $file);
                } catch (\Exception $exception) {
                    $form->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));

                    $this->logger->error('An error occurred while uploading attachment type id: ' . $attachmentTypeId . '. Error message: ' . $exception->getMessage(), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine()
                    ]);
                }
            }
        }

        if (
            false === in_array(AttachmentType::CNI_PASSPORTE, array_keys($newAttachments))
            && (
                $unattachedClient->getIdNationalite() !== $client->getIdNationalite()
                || $unattachedClient->getCivilite() !== $client->getCivilite()
                || $unattachedClient->getPrenom() !== $client->getPrenom()
            )
        ) {
            $form->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-change-ID-warning-message')));
        }

        return $newAttachments;
    }

    /**
     * @param Clients       $client
     * @param Clients       $unattachedClient
     * @param Companies     $company
     * @param Companies     $unattachedCompany
     * @param FormInterface $form
     * @param FileBag       $fileBag
     *
     * @return bool
     * @throws \Exception
     */
    public function handleCompanyIdentity(Clients $client, Clients $unattachedClient, Companies $company, Companies $unattachedCompany, FormInterface $form, FileBag $fileBag): bool
    {
        $this->checkCompanyIdentityForm($company, $form);

        $newAttachments = $this->uploadCompanyIdentityDocuments($client, $company, $form, $fileBag);

        if ($this->isFormValid($form)) {
            $this->saveAndNotifyChanges($client, $unattachedClient, $company, $unattachedCompany, $newAttachments);

            return true;
        }

        return false;
    }

    /**
     * @param Companies     $company
     * @param FormInterface $companyForm
     */
    private function checkCompanyIdentityForm(Companies $company, FormInterface $companyForm): void
    {
        if (Companies::CLIENT_STATUS_MANAGER != $company->getStatusClient()) {
            if (
                Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT === $company->getStatusClient()
                && empty($company->getStatusConseilExterneEntreprise())
            ) {
                $companyForm
                    ->get('statusConseilExterneEntreprise')
                    ->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-external-counsel-error-message')));
            }

            if (
                Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT === $company->getStatusClient()
                && Companies::CLIENT_STATUS_EXTERNAL_COUNSEL_OTHER === $company->getStatusConseilExterneEntreprise()
                && empty($company->getPreciserConseilExterneEntreprise())
            ) {
                $companyForm
                    ->get('preciserConseilExterneEntreprise')
                    ->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-external-counsel-error-message')));
            }

            if (empty($company->getCiviliteDirigeant())) {
                $companyForm
                    ->get('civiliteDirigeant')
                    ->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-director-form-of-address-missing')));
            }

            if (empty($company->getNomDirigeant())) {
                $companyForm->get('nomDirigeant')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-director-name-missing')));
            }

            if (empty($company->getPrenomDirigeant())) {
                $companyForm->get('prenomDirigeant')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-director-first-name-missing')));
            }

            if (empty($company->getFonctionDirigeant())) {
                $companyForm->get('fonctionDirigeant')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-director-position-missing')));
            }

            if (empty($company->getPhoneDirigeant())) {
                $companyForm->get('phoneDirigeant')->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-company-director-phone-missing')));
            }

            if (empty($company->getEmailDirigeant())) {
                $companyForm->get('emailDirigeant')->addError(new FormError($this->translator->trans('common-validator_email-address-invalid')));
            }
        }
    }

    /**
     * @param Clients       $client
     * @param Companies     $company
     * @param FormInterface $form
     * @param FileBag       $fileBag
     *
     * @return array
     */
    private function uploadCompanyIdentityDocuments(Clients $client, Companies $company, FormInterface $form, FileBag $fileBag): array
    {
        $newAttachments = [];

        $files = [
            AttachmentType::CNI_PASSPORTE_DIRIGEANT => $fileBag->get('id_recto'),
            AttachmentType::CNI_PASSPORTE_VERSO     => $fileBag->get('id_verso'),
            AttachmentType::KBIS                    => $fileBag->get('company-registration'),
        ];

        if ($company->getStatusClient() > Companies::CLIENT_STATUS_MANAGER) {
            $files[AttachmentType::DELEGATION_POUVOIR] = $fileBag->get('delegation-of-authority');
        }

        foreach ($files as $attachmentTypeId => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $newAttachments[$attachmentTypeId] = $this->upload($client, $attachmentTypeId, $file);
                } catch (\Exception $exception) {
                    $form->addError(new FormError($this->translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));

                    $this->logger->error('An error occurred while uploading attachment type id: ' . $attachmentTypeId . '. Error message: ' . $exception->getMessage(), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine()
                    ]);
                }
            }
        }

        return $newAttachments;
    }

    /**
     * @param Clients            $client
     * @param Clients            $unattachedClient
     * @param FormInterface      $form
     * @param FileBag            $fileBag
     * @param string             $type
     * @param null|ClientAddress $clientAddress
     *
     * @return bool
     * @throws OptimisticLockException
     */
    public function handlePersonAddress(Clients $client, Clients $unattachedClient, FormInterface $form, FileBag $fileBag, string $type, ?ClientAddress $clientAddress): bool
    {
        $addressModified = $this->isAddressModified($form, $clientAddress);
        if (false === $addressModified) {
            return true;
        }

        $this->checkAddressForm($form, $type);

        $newAttachments = $this->uploadPersonAddressDocument($client, $form, $fileBag, $type);

        if ($this->isFormValid($form)) {
            $this->savePersonAddress($client, $form, $type, $newAttachments);

            if ($form->has('noUsPerson')) {
                $form->get('noUsPerson')->getData() ? $client->setUsPerson(false) : $client->setUsPerson(true);
            }

            $this->saveAndNotifyChanges($client, $unattachedClient, null, null, $newAttachments, $type);

            return true;
        }

        return false;
    }

    /**
     * @param FormInterface $form
     * @param string        $type
     */
    private function checkAddressForm(FormInterface $form, string $type): void
    {
        if (AddressType::TYPE_POSTAL_ADDRESS === $type) {
            return;
        }

        $zip       = $form->get('zip')->getData();
        $countryId = $form->get('idCountry')->getData();

        if (Pays::COUNTRY_FRANCE == $countryId && null === $this->entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $zip])) {
            $form->get('zip')->addError(new FormError($this->translator->trans('lender-profile_information-tab-fiscal-address-section-unknown-zip-code-error-message')));
        }
    }

    /**
     * @param FormInterface                     $form
     * @param ClientAddress|CompanyAddress|null $address
     *
     * @return bool
     */
    private function isAddressModified(FormInterface $form, $address): bool
    {
        if (
            null === $address
            || $address->getAddress() !== $form->get('address')->getData()
            || $address->getZip() !== $form->get('zip')->getData()
            || $address->getCity() !== $form->get('city')->getData()
            || $address->getIdCountry()->getIdPays() !== $form->get('idCountry')->getData()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param Clients       $client
     * @param FormInterface $form
     * @param FileBag       $fileBag
     * @param string        $type
     *
     * @return array
     */
    private function uploadPersonAddressDocument(Clients $client, FormInterface $form, FileBag $fileBag, string $type)
    {
        $newAttachments = [];

        if (AddressType::TYPE_POSTAL_ADDRESS === $type) {
            return $newAttachments;
        }

        $countryId = $form->get('idCountry')->getData();

        $files[AttachmentType::JUSTIFICATIF_DOMICILE] = $fileBag->get('housing-certificate');
        if ($countryId !== Pays::COUNTRY_FRANCE) {
            $files[AttachmentType::JUSTIFICATIF_FISCAL] = $fileBag->get('tax-certificate');
        }
        if ($form->get('housedByThirdPerson')->getData()) {
            $files[AttachmentType::ATTESTATION_HEBERGEMENT_TIERS] = $fileBag->get('housed-by-third-person-declaration');
            $files[AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT] = $fileBag->get('id-third-person-housing');
        }

        foreach ($files as $attachmentTypeId => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $newAttachments[$attachmentTypeId] = $attachement = $this->upload($client, $attachmentTypeId, $file);
                } catch (\Exception $exception) {
                    $form->addError(new FormError($this->translator->trans('lender-profile_information-tab-fiscal-address-section-upload-files-error-message')));
                    $this->logger->error('An error occurred while uploading attachment type id: ' . $attachmentTypeId . '. Error message: ' . $exception->getMessage(), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
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

        if (false === isset($newAttachments[AttachmentType::JUSTIFICATIF_DOMICILE]) || false === $newAttachments[AttachmentType::JUSTIFICATIF_DOMICILE] instanceof Attachment) {
            $form->addError(new FormError($this->translator->trans('lender-profile_information-tab-fiscal-address-change-message')));
        }

        return $newAttachments;
    }

    /**
     * @param Clients       $client
     * @param FormInterface $form
     * @param string        $type
     * @param array         $newAttachments
     *
     * @throws OptimisticLockException
     * @throws \Exception
     */
    private function savePersonAddress(Clients $client, FormInterface $form, string $type, array $newAttachments): void
    {
        if ($form->has('samePostalAddress') && $form->get('samePostalAddress')->getData()) {
            $this->addressManager->clientPostalAddressSameAsMainAddress($client);
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
        }

        $housingCertificate = null;
        if (isset($newAttachments[AttachmentType::JUSTIFICATIF_DOMICILE])) {
            $housingCertificate = $newAttachments[AttachmentType::JUSTIFICATIF_DOMICILE];
        }

        if (AddressType::TYPE_MAIN_ADDRESS === $type && $housingCertificate) {
            $lastModifiedAddress = $this->entityManager
                ->getRepository('UnilendCoreBusinessBundle:ClientAddress')
                ->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);

            $this->addressManager->linkAttachmentToAddress($lastModifiedAddress, $housingCertificate);
        }
    }

    /**
     * @param Companies           $company
     * @param FormInterface       $form
     * @param string              $addressType
     * @param CompanyAddress|null $companyAddress
     *
     * @return bool
     * @throws \Exception
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function handleCompanyAddress(Companies $company, FormInterface $form, string $addressType, ?CompanyAddress $companyAddress): bool
    {
        $addressModified = $this->isAddressModified($form, $companyAddress);
        if (false === $addressModified) {
            return true;
        }

        $this->checkAddressForm($form, $addressType);

        if ($form->isValid()) {
            $this->saveCompanyAddress($company, $form, $addressType);

            $this->saveAndNotifyChanges($company->getIdClientOwner(), $company->getIdClientOwner(), null, null, [], $addressType);

            return true;
        }

        return false;
    }

    /**
     * @param Companies     $company
     * @param FormInterface $form
     * @param string        $addressType
     *
     * @throws \Exception
     */
    private function saveCompanyAddress(Companies $company, FormInterface $form, string $addressType)
    {
        if ($form->has('samePostalAddress') && $form->get('samePostalAddress')->getData()) {
            $this->addressManager->companyPostalAddressSameAsMainAddress($company);
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
                $addressType
            );
        }
    }

    /**
     * @param Clients       $client
     * @param Clients       $unattachedClient
     * @param FormInterface $form
     * @param FileBag       $fileBag
     *
     * @return bool
     * @throws \Exception
     */
    public function handleBankDetailsForm(Clients $client, Clients $unattachedClient, FormInterface $form, FileBag $fileBag): bool
    {
        $this->checkBankDetailsForm($form);
        $bankAccount         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($client);
        $bankAccountDocument = $this->uploadBankDocument($client, $form, $fileBag, $bankAccount);

        if ($this->isFormValid($form)) {
            $newAttachments      = [];
            $bankAccountModified = false;

            if ($bankAccountDocument) {
                $iban = $form->get('iban')->getData();
                $bic  = $form->get('bic')->getData();
                $this->bankAccountManager->saveBankInformation($client, $bic, $iban, $bankAccountDocument);
                $bankAccountModified = true;
                $newAttachments      = [$bankAccountDocument];
            }

            $this->saveAndNotifyChanges($client, $unattachedClient, null, null, $newAttachments, null, $bankAccountModified);

            return true;
        }

        return false;
    }

    /**
     * @param FormInterface $form
     */
    private function checkBankDetailsForm(FormInterface $form): void
    {
        if (false === in_array(strtoupper(substr($form->get('iban')->getData(), 0, 2)), Pays::EEA_COUNTRIES_ISO)) {
            $form->get('iban')->addError(new FormError($this->translator->trans('lender-subscription_documents-iban-not-european-error-message')));
        }
    }

    /**
     * @param Clients          $client
     * @param FormInterface    $form
     * @param FileBag          $fileBag
     * @param null|BankAccount $unattachedBankAccount
     *
     * @return null|Attachment
     */
    private function uploadBankDocument(Clients $client, FormInterface $form, FileBag $fileBag, ?BankAccount $unattachedBankAccount): ?Attachment
    {
        $bankAccountDocument = null;

        if ($this->isBankAccountModified($form, $unattachedBankAccount)) {
            $file = $fileBag->get('iban-certificate');

            if (false === $file instanceof UploadedFile) {
                $form->addError(new FormError($this->translator->trans('lender-profile_rib-file-mandatory')));
            } else {
                try {
                    $bankAccountDocument = $this->upload($client, AttachmentType::RIB, $file);
                } catch (\Exception $exception) {
                    $form->addError(new FormError($this->translator->trans('lender-profile_fiscal-tab-rib-file-error')));
                    $this->logger->error('An error occurred while uploading IBAN attachment type id: ' . AttachmentType::RIB . '. Error message: ' . $exception->getMessage(), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine()
                    ]);
                }
            }
        }

        return $bankAccountDocument;
    }

    /**
     * @param Clients       $client
     * @param Clients       $unattachedClient
     * @param FormInterface $form
     *
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function handleEmailForm(Clients $client, Clients $unattachedClient, FormInterface $form): bool
    {
        if (false === empty($this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findGrantedLoginAccountsByEmail($client->getEmail()))) {
            $form->addError(new FormError($this->translator->trans('lender-profile_security-identification-error-existing-email')));
        }

        if ($form->isValid()) {
            $this->saveAndNotifyChanges($client, $unattachedClient);

            return true;
        }

        return false;
    }

    /**
     * @param Clients $client
     * @param Clients $unattachedClient
     *
     * @return bool
     * @throws OptimisticLockException
     */
    public function handlePhoneForm(Clients $client, Clients $unattachedClient): bool
    {
        $this->saveAndNotifyChanges($client, $unattachedClient);

        return true;
    }

    /**
     * @param FormInterface $form
     *
     * @return bool
     */
    private function isFormValid(FormInterface $form): bool
    {
        return (null !== $form->getParent() && $form->getParent()->isValid()) || (null === $form->getParent() && $form->isValid());
    }

    /**
     * @param Clients        $modifiedClient
     * @param Clients        $unattachedClient
     * @param Companies|null $modifiedCompany
     * @param Companies|null $unattachedCompany
     * @param array          $newAttachments
     * @param string|null    $modifiedAddressType
     * @param bool           $isBankAccountModified
     *
     * @throws OptimisticLockException
     */
    private function saveAndNotifyChanges(
        Clients $modifiedClient,
        Clients $unattachedClient,
        ?Companies $modifiedCompany = null,
        ?Companies $unattachedCompany = null,
        array $newAttachments = [],
        ?string $modifiedAddressType = null,
        bool $isBankAccountModified = false
    ): void
    {
        if ($modifiedCompany) {
            $this->entityManager->flush($modifiedCompany);
        }
        $clientChanges  = $this->clientStatusManager->getClientChangeSet($modifiedClient, $unattachedClient);
        $companyChanges = $this->clientStatusManager->getCompanyChangeSet($modifiedCompany, $unattachedCompany);

        if (0 < count($clientChanges)) {
            $this->logAndSaveClientChanges($modifiedClient);
        }
        $isAddressModified = in_array($modifiedAddressType, [AddressType::TYPE_MAIN_ADDRESS, AddressType::TYPE_POSTAL_ADDRESS]);

        $this->clientStatusManager->changeClientStatusTriggeredByClientAction($modifiedClient, $unattachedClient, $modifiedCompany, $unattachedCompany, $isAddressModified, $isBankAccountModified,
            $newAttachments);
        $this->clientDataHistoryManager->sendLenderProfileModificationEmail($modifiedClient, $clientChanges, $companyChanges, $newAttachments, $modifiedAddressType, $isBankAccountModified);
    }

    /**
     * Changes should only be logged when client is actually updated, meaning the form should be valid
     * Thus this method should only be called once form is fully validated, including attachments
     *
     * @param Clients $client
     *
     * @return array
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function logAndSaveClientChanges(Clients $client): array
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

        $message = $this->messageProvider->newMessage('notification-changement-email-preteur', [
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

    /**
     * @param Clients       $client
     * @param Clients       $unattachedClient
     * @param ClientAddress $clientAddress
     * @param string        $addressType
     * @param FormInterface $form
     * @param FileBag       $fileBag
     *
     * @throws \Exception
     */
    public function handleAllPersonalData(
        Clients $client,
        Clients $unattachedClient,
        ClientAddress $clientAddress,
        string $addressType,
        FormInterface $form,
        FileBag $fileBag
    ): void
    {
        $clientForm          = $form->get('client');
        $addressForm         = $form->get($addressType);
        $bankForm            = $form->get('bankAccount');
        $modifiedAddressType = null;
        $newAttachments      = [];

        // Identity
        $identityAttachments = $this->uploadPersonalIdentityDocuments($client, $unattachedClient, $clientForm, $fileBag);
        $newAttachments      = $identityAttachments + $newAttachments;

        // Address
        $isAddressModified = $this->isAddressModified($addressForm, $clientAddress);
        if ($isAddressModified) {
            $this->checkAddressForm($addressForm, $addressType);
            $housingAttachments  = $this->uploadPersonAddressDocument($client, $addressForm, $fileBag, $addressType);
            $newAttachments      = $housingAttachments + $newAttachments;
            $modifiedAddressType = $addressType;
        }

        //Bank Account
        $isBankAccountModified = false;
        $bankAccount           = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($client);
        $bankAccountAttachment = $this->uploadBankDocument($client, $bankForm, $fileBag, $bankAccount);
        if ($bankAccountAttachment) {
            $this->checkBankDetailsForm($bankForm);
            $newAttachments[AttachmentType::RIB] = $bankAccountAttachment;
            $isBankAccountModified               = true;
        }

        if ($form->isValid()) {
            if ($isAddressModified) {
                $this->savePersonAddress($client, $addressForm, $addressType, $newAttachments);
            }

            $addressForm->get('noUsPerson')->getData() ? $client->setUsPerson(false) : $client->setUsPerson(true);

            if ($isBankAccountModified) {
                $iban = $bankForm->get('iban')->getData();
                $bic  = $bankForm->get('bic')->getData();
                $this->bankAccountManager->saveBankInformation($client, $bic, $iban, $newAttachments[AttachmentType::RIB]);
            }

            $client->setPersonalDataUpdated();

            $this->saveAndNotifyChanges($client, $unattachedClient, null, null, $newAttachments, $modifiedAddressType, $isBankAccountModified);
        }
    }

    /**
     * @param FormInterface    $bankForm
     * @param BankAccount|null $unattachedBankAccount
     *
     * @return bool
     */
    private function isBankAccountModified(FormInterface $bankForm, ?BankAccount $unattachedBankAccount): bool
    {
        $iban = $bankForm->get('iban')->getData();
        $bic  = $bankForm->get('bic')->getData();

        if (
            null === $unattachedBankAccount && (false === empty($iban) || false === empty($bic))
            || null !== $unattachedBankAccount && ($unattachedBankAccount->getIban() !== $iban || $unattachedBankAccount->getBic() !== $bic)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param Clients        $client
     * @param Clients        $unattachedClient
     * @param Companies      $company
     * @param Companies      $unattachedCompany
     * @param CompanyAddress $companyAddress
     * @param string         $addressType
     * @param FormInterface  $form
     * @param FileBag        $fileBag
     *
     * @throws \Exception
     */
    public function handleAllLegalEntityData(
        Clients $client,
        Clients $unattachedClient,
        Companies $company,
        Companies $unattachedCompany,
        CompanyAddress $companyAddress,
        string $addressType,
        FormInterface $form,
        FileBag $fileBag
    ): void
    {
        $companyForm         = $form->get('company');
        $addressForm         = $form->get($addressType);
        $bankForm            = $form->get('bankAccount');
        $modifiedAddressType = null;

        // Identity
        $this->checkCompanyIdentityForm($company, $companyForm);

        $newAttachments = $this->uploadCompanyIdentityDocuments($client, $company, $companyForm, $fileBag);

        // Address
        $isAddressModified = $this->isAddressModified($addressForm, $companyAddress);
        if ($isAddressModified) {
            $this->checkAddressForm($addressForm, $addressType);
            $modifiedAddressType = $addressType;
        }

        //Bank Account
        $isBankAccountModified = false;
        $bankAccount           = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($client);
        $bankAccountAttachment = $this->uploadBankDocument($client, $bankForm, $fileBag, $bankAccount);
        if ($bankAccountAttachment) {
            $this->checkBankDetailsForm($bankForm);
            $newAttachments[AttachmentType::RIB] = $bankAccountAttachment;
            $isBankAccountModified               = true;
        }

        if ($form->isValid()) {
            if ($isAddressModified) {
                $this->saveCompanyAddress($company, $addressForm, $addressType);
            }

            if ($isBankAccountModified) {
                $iban = $bankForm->get('iban')->getData();
                $bic  = $bankForm->get('bic')->getData();
                $this->bankAccountManager->saveBankInformation($client, $bic, $iban, $newAttachments[AttachmentType::RIB]);
            }

            $client->setPersonalDataUpdated();

            $this->saveAndNotifyChanges($client, $unattachedClient, $company, $unattachedCompany, $newAttachments, $modifiedAddressType, $isBankAccountModified);
        }
    }
}
