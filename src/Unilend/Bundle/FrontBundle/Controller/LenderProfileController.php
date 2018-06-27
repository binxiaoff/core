<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\{
    Method, Security
};
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\{
    Extension\Core\Type\CheckboxType, FormError, FormInterface
};
use Symfony\Component\HttpFoundation\{
    File\UploadedFile, FileBag, JsonResponse, RedirectResponse, Request, Response
};
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Attachment, AttachmentType, BankAccount, ClientAddress, Clients, ClientsGestionTypeNotif, ClientsHistoryActions, ClientsStatus, Companies, GreenpointAttachment, Ifu, LenderTaxExemption, PaysV2, TaxType, Users, Wallet, WalletBalanceHistory, WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Service\{
    ClientAuditer, LocationManager, NewsletterManager
};
use Unilend\Bundle\FrontBundle\Form\ClientPasswordType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\{
    BankAccountType, ClientEmailType, CompanyIdentityType, LegalEntityProfileType, OriginOfFundsType, PersonPhoneType, PersonProfileType, SecurityQuestionType
};
use Unilend\Bundle\FrontBundle\Security\User\UserLender;

class LenderProfileController extends Controller
{
    /**
     * @Route("/profile", name="lender_profile")
     * @Route("/profile/info-perso", name="lender_profile_personal_information")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function personalInformationAction(Request $request): Response
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->redirectToRoute('home');
        }

        $entityManager            = $this->get('doctrine.orm.entity_manager');
        $formManager              = $this->get('unilend.frontbundle.service.form_manager');
        $companyAddressRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress');
        $clientAddressRepository  = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress');
        $client                   = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());
        $unattachedClient         = clone $client;

        $phoneForm = $this->createForm(PersonPhoneType::class, $client);

        if ($client->isNaturalPerson()) {
            $lastModifiedMainAddress = $clientAddressRepository->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
            $postalAddress           = $client->getIdPostalAddress();

            $identityFormBuilder = $this->createFormBuilder()
                ->add('client', PersonProfileType::class, ['data' => $client]);
            $mainAddressForm     = $formManager->getClientAddressForm($lastModifiedMainAddress, AddressType::TYPE_MAIN_ADDRESS);
            $mainAddressForm
                ->add('noUsPerson', CheckboxType::class, ['required' => false])
                ->add('housedByThirdPerson', CheckboxType::class, ['required' => false]);

            $postalAddressForm   = $formManager->getClientAddressForm($postalAddress, AddressType::TYPE_POSTAL_ADDRESS);
            $hasPostalAddress    = null === $postalAddress;
            $postalAddressForm->add('samePostalAddress', CheckboxType::class, ['data' => $hasPostalAddress, 'required' => false]);
        } else {
            $company                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
            $unattachedCompany       = clone $company;
            $lastModifiedMainAddress = $companyAddressRepository->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
            $postalAddress           = $company->getIdPostalAddress();

            $identityFormBuilder = $this->createFormBuilder()
                ->add('client', LegalEntityProfileType::class, ['data' => $client])
                ->add('company', CompanyIdentityType::class, ['data' => $company]);
            $identityFormBuilder->get('company')->remove('siren');

            $mainAddressForm   = $formManager->getCompanyAddressForm($lastModifiedMainAddress, AddressType::TYPE_MAIN_ADDRESS);
            $postalAddressForm = $formManager->getCompanyAddressForm($postalAddress, AddressType::TYPE_POSTAL_ADDRESS);
            $hasPostalAddress  = null === $postalAddress;
            $postalAddressForm->add('samePostalAddress', CheckboxType::class, ['data' => $hasPostalAddress, 'required' => false]);
        }

        $identityForm = $identityFormBuilder->getForm();

        if ($request->isMethod(Request::METHOD_POST)) {
            $isValid = false;

            $identityForm->handleRequest($request);
            if ($identityForm->isSubmitted() && $identityForm->isValid()) {
                if ($client->isNaturalPerson()) {
                    $isValid = $this->handlePersonIdentity($unattachedClient, $client, $identityForm, $request->files);
                } else {
                    $isValid = $this->handleCompanyIdentity($client, $unattachedCompany, $company, $identityForm, $request->files);
                }
            }

            $mainAddressForm->handleRequest($request);
            if ($mainAddressForm->isSubmitted() && $mainAddressForm->isValid()) {
                if ($client->isNaturalPerson()) {
                    $isValid = $this->handlePersonAddress($client, $mainAddressForm, $request->files, AddressType::TYPE_MAIN_ADDRESS, $lastModifiedMainAddress);
                } else {
                    $isValid = $this->handleCompanyAddress($company, $mainAddressForm, AddressType::TYPE_MAIN_ADDRESS);
                }
            }

            $postalAddressForm->handleRequest($request);
            if ($postalAddressForm->isSubmitted() && $postalAddressForm->isValid()) {
                if ($client->isNaturalPerson()) {
                    $isValid = $this->handlePersonAddress($client, $postalAddressForm, $request->files, AddressType::TYPE_POSTAL_ADDRESS, $postalAddress);
                } else {
                    $isValid = $this->handleCompanyAddress($company, $postalAddressForm, AddressType::TYPE_POSTAL_ADDRESS);
                }
            }

            $phoneForm->handleRequest($request);
            if ($phoneForm->isSubmitted() && $phoneForm->isValid()) {
                $this->addFlash('phoneSuccess', $this->get('translator')->trans('lender-profile_information-tab-phone-form-success-message'));
                $this->logClientChanges($client);

                $isValid = true;
            }

            if ($isValid) {
                return $this->redirectToRoute('lender_profile_personal_information');
            }
        }

        $templateData = [
            'client'               => $client,
            'clientMainAddress'    => $lastModifiedMainAddress,
            'clientPostalAddress'  => $postalAddress,
            'company'              => isset($company) ? $company : null,
            'companyMainAddress'   => $lastModifiedMainAddress,
            'companyPostalAddress' => $postalAddress,
            'isCIPActive'          => $this->isCIPActive(),
            'forms'                => [
                'identity'      => $identityForm->createView(),
                'mainAddress'   => $mainAddressForm->createView(),
                'postalAddress' => $postalAddressForm->createView(),
                'phone'         => $phoneForm->createView()
            ],
            'isLivingAbroad'       => $lastModifiedMainAddress ? ($lastModifiedMainAddress->getIdCountry()->getIdPays() !== PaysV2::COUNTRY_FRANCE) : false
        ];

        $setting                             = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Liste deroulante conseil externe de l\'entreprise']);
        $templateData['externalCounselList'] = json_decode($setting->getValue(), true);
        $attachmentRepository                = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment');
        if (false === empty($company)) {
            $templateData['companyIdAttachments'][AttachmentType::CNI_PASSPORTE_DIRIGEANT] = $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::CNI_PASSPORTE_DIRIGEANT);
            $templateData['companyIdAttachments'][AttachmentType::CNI_PASSPORTE_VERSO]     = $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::CNI_PASSPORTE_VERSO);
            $templateData['companyOtherAttachments'][AttachmentType::KBIS]                 = $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::KBIS);
            $templateData['companyOtherAttachments'][AttachmentType::DELEGATION_POUVOIR]   = $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::DELEGATION_POUVOIR);
        } else {
            $templateData['identityAttachments'][AttachmentType::CNI_PASSPORTE]                  = $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::CNI_PASSPORTE);
            $templateData['identityAttachments'][AttachmentType::CNI_PASSPORTE_VERSO]            = $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::CNI_PASSPORTE_VERSO);
            $templateData['residenceAttachments'][AttachmentType::JUSTIFICATIF_DOMICILE]         = $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::JUSTIFICATIF_DOMICILE);
            $templateData['residenceAttachments'][AttachmentType::ATTESTATION_HEBERGEMENT_TIERS] = $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::ATTESTATION_HEBERGEMENT_TIERS);
            $templateData['residenceAttachments'][AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT] = $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT);
            $templateData['residenceAttachments'][AttachmentType::JUSTIFICATIF_FISCAL]           = $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::JUSTIFICATIF_FISCAL);
        }

        return $this->render('lender_profile/personal_information.html.twig', $templateData);
    }

    /**
     * @param Clients       $unattachedClient
     * @param Clients       $client
     * @param FormInterface $form
     * @param FileBag       $fileBag
     *
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function handlePersonIdentity(Clients $unattachedClient, Clients $client, FormInterface $form, FileBag $fileBag): bool
    {
        $translator      = $this->get('translator');
        $isRectoUploaded = false;
        $files           = [
            AttachmentType::CNI_PASSPORTE       => $fileBag->get('id_recto'),
            AttachmentType::CNI_PASSPORTE_VERSO => $fileBag->get('id_verso')
        ];

        foreach ($files as $attachmentTypeId => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $this->upload($client, $attachmentTypeId, $file);

                    if (AttachmentType::CNI_PASSPORTE === $attachmentTypeId) {
                        $isRectoUploaded = true;
                    }
                } catch (\Exception $exception) {
                    $form->get('client')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));
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
            $form->get('client')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-change-ID-warning-message')));
        }

        if ($form->isValid()) {
            $this->addFlash('identitySuccess', $translator->trans('lender-profile_information-tab-identity-section-files-update-success-message'));

            $clientChanges = $this->logClientChanges($client);

            if ($isRectoUploaded || false === empty($clientChanges)) {
                $this->updateClientStatusAndNotifyClient($client);
            }

            return true;
        }

        return false;
    }

    /**
     * @param Clients       $client
     * @param Companies     $unattachedCompany
     * @param Companies     $company
     * @param FormInterface $form
     * @param FileBag       $fileBag
     *
     * @return bool
     * @throws \Exception
     */
    private function handleCompanyIdentity(Clients $client, Companies $unattachedCompany, Companies $company, FormInterface $form, FileBag $fileBag): bool
    {
        $isFileUploaded = false;
        $translator     = $this->get('translator');

        if ($company->getStatusClient() > Companies::CLIENT_STATUS_MANAGER) {
            if (
                Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT === $company->getStatusClient()
                && empty($company->getStatusConseilExterneEntreprise())
            ) {
                $form->get('company')->get('statusConseilExterneEntreprise')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-external-counsel-error-message')));
            }

            if (
                Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT === $company->getStatusClient()
                && Companies::CLIENT_STATUS_EXTERNAL_COUNSEL_OTHER === $company->getStatusConseilExterneEntreprise()
                && empty($company->getPreciserConseilExterneEntreprise())
            ) {
                $form->get('company')->get('preciserConseilExterneEntreprise')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-external-counsel-error-message')));
            }

            if (empty($company->getCiviliteDirigeant())) {
                $form->get('company')->get('civiliteDirigeant')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-director-form-of-address-missing')));
            }

            if (empty($company->getNomDirigeant())) {
                $form->get('company')->get('nomDirigeant')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-director-name-missing')));
            }

            if (empty($company->getPrenomDirigeant())) {
                $form->get('company')->get('prenomDirigeant')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-director-first-name-missing')));
            }

            if (empty($company->getFonctionDirigeant())) {
                $form->get('company')->get('fonctionDirigeant')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-director-position-missing')));
            }

            if (empty($company->getPhoneDirigeant())) {
                $form->get('company')->get('phoneDirigeant')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-director-phone-missing')));
            }

            if (empty($company->getEmailDirigeant())) {
                $form->get('company')->get('emailDirigeant')->addError(new FormError($translator->trans('common-validator_email-address-invalid')));
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

        foreach ($files as $attachmentTypeId => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $this->upload($client, $attachmentTypeId, $file);
                    $isFileUploaded = true;
                } catch (\Exception $exception) {
                    $form->get('company')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));
                }
            }
        }

        if ($form->isValid()) {
            $this->addFlash('identitySuccess', $translator->trans('lender-profile_information-tab-identity-section-files-update-success-message'));

            // Only for company related data
            $formManager         = $this->get('unilend.frontbundle.service.form_manager');
            $clientChanges       = $this->logClientChanges($client);
            $modifiedDataCompany = $formManager->getModifiedContent($unattachedCompany, $company) ?? null;
            $this->get('doctrine.orm.entity_manager')->flush($company);

            if ($isFileUploaded || false === empty($clientChanges) || false === empty($modifiedDataCompany)) {
                $this->updateClientStatusAndNotifyClient($client, $modifiedDataCompany);
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
    private function handlePersonAddress(Clients $client, FormInterface $form, FileBag $fileBag, string $type, ?ClientAddress $address): bool
    {
        $entityManager      = $this->get('doctrine.orm.entity_manager');
        $translator         = $this->get('translator');
        $addressManager     = $this->get('unilend.service.address_manager');
        $housingCertificate = null;

        $zip       = $form->get('zip')->getData();
        $countryId = $form->get('idCountry')->getData();

        switch ($type) {
            case AddressType::TYPE_MAIN_ADDRESS:
                if (PaysV2::COUNTRY_FRANCE == $countryId && null === $entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $zip])) {
                    $form->get('zip')->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-section-unknown-zip-code-error-message')));
                }

                $form->get('noUsPerson')->getData() ? $client->setUsPerson(false) : $client->setUsPerson(true);
                $this->logClientChanges($client);

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
                    $form->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-change-message')));
                }

                foreach ($files as $attachmentTypeId => $file) {
                    if ($file instanceof UploadedFile) {
                        try {
                            $attachement = $this->upload($client, $attachmentTypeId, $file);

                            if (AttachmentType::JUSTIFICATIF_DOMICILE === $attachmentTypeId) {
                                $housingCertificate = $attachement;
                            }
                        } catch (\Exception $exception) {
                            $form->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-section-upload-files-error-message')));
                        }
                    } else {
                        switch ($attachmentTypeId) {
                            case AttachmentType::JUSTIFICATIF_FISCAL :
                                $error = $translator->trans('lender-profile_information-tab-fiscal-address-section-missing-tax-certificate');
                                break;
                            case AttachmentType::ATTESTATION_HEBERGEMENT_TIERS :
                                $error = $translator->trans('lender-profile_information-tab-fiscal-address-missing-housed-by-third-person-declaration');
                                break;
                            case AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT :
                                $error = $translator->trans('lender-profile_information-tab-fiscal-address-missing-id-third-person-housing');
                                break;
                            default :
                                continue 2;
                        }
                        $form->addError(new FormError($error));
                    }
                }

                $success     = 'mainAddressSuccess';
                $translation = $translator->trans('lender-profile_information-tab-fiscal-address-form-success-message');
                break;
            case AddressType::TYPE_POSTAL_ADDRESS:
                $success     = 'postalAddressSuccess';
                $translation = $translator->trans('lender-profile_information-tab-postal-address-form-success-message');
                break;
            default:
                break;
        }

        if ($form->isValid()) {
            if ($form->has('samePostalAddress') && $form->get('samePostalAddress')->getData()) {
                $addressManager->clientPostalAddressSameAsMainAddress($client);
            } elseif (
                false === $form->has('samePostalAddress')
                || $form->has('samePostalAddress') && empty($form->get('samePostalAddress')->getData())
            ) {
                $addressManager->saveClientAddress(
                    $form->get('address')->getData(),
                    $form->get('zip')->getData(),
                    $form->get('city')->getData(),
                    $form->get('idCountry')->getData(),
                    $client,
                    $type
                );
            }

            if (AddressType::TYPE_MAIN_ADDRESS === $type) {
                if (null !== $housingCertificate) {
                    $address = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
                    $addressManager->linkAttachmentToAddress($address, $housingCertificate);
                } else {
                    $this->get('logger')->error('Lender main address has no attachment.', [
                        'class'     => __CLASS__,
                        'line'      => __LINE__,
                        'id_client' => $client->getIdClient()
                    ]);
                }
            }

            $this->updateClientStatusAndNotifyClient($client);

            $this->addFlash($success, $translation);

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
    private function handleCompanyAddress(Companies $company, FormInterface $form, string $type): bool
    {
        $translator     = $this->get('translator');
        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $addressManager = $this->get('unilend.service.address_manager');

        $zip       = $form->get('zip')->getData();
        $countryId = $form->get('idCountry')->getData();

        switch ($type) {
            case AddressType::TYPE_MAIN_ADDRESS:
                if (
                    false === empty($zip) && false === empty($countryId)
                    && PaysV2::COUNTRY_FRANCE == $countryId
                    && null === $entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $zip])
                ) {
                    $form->get('zip')->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-section-unknown-zip-code-error-message')));
                }

                $success     = 'mainAddressSuccess';
                $translation = $translator->trans('lender-profile_information-tab-fiscal-address-form-success-message');
                break;
            case AddressType::TYPE_POSTAL_ADDRESS:
                $success     = 'postalAddressSuccess';
                $translation = $translator->trans('lender-profile_information-tab-postal-address-form-success-message');
                break;
            default:
                $this->get('logger')->error('Unknown address type requested. Type: ' . $type . ' is not supported in lender subscription', [
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
                break;
        }

        if ($form->isValid()) {
            if ($form->has('samePostalAddress') && $form->get('samePostalAddress')->getData()) {
                $addressManager->companyPostalAddressSameAsMainAddress($company);
            } elseif (
                false === $form->has('samePostalAddress')
                || $form->has('samePostalAddress') && empty($form->get('samePostalAddress')->getData())
            ) {
                $addressManager->saveCompanyAddress(
                    $form->get('address')->getData(),
                    $form->get('zip')->getData(),
                    $form->get('city')->getData(),
                    $form->get('idCountry')->getData(),
                    $company,
                    $type
                );
            }

            $this->updateClientStatusAndNotifyClient($company->getIdClientOwner());

            $this->addFlash($success, $translation);

            return true;
        }

        return false;
    }

    /**
     * @Route("/profile/info-fiscal", name="lender_profile_fiscal_information")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function fiscalInformationAction(Request $request): Response
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->redirectToRoute('home');
        }

        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $clientAddressRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress');
        $client                  = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());
        $bankAccount             = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($client);
        $wallet                  = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        $form = $this->createFormBuilder()
            ->add('client', OriginOfFundsType::class, ['data' => $client])
            ->add('bankAccount', BankAccountType::class)
            ->getForm();

        if (null !== $bankAccount) {
            $form->get('bankAccount')->get('iban')->setData($bankAccount->getIban());
            $form->get('bankAccount')->get('bic')->setData($bankAccount->getBic());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isValid = $this->handleBankDetailsForm($form, $request->files, $bankAccount);
            if ($isValid) {
                return $this->redirectToRoute('lender_profile_fiscal_information');
            }
        }

        $ifuRepository         = $entityManager->getRepository('UnilendCoreBusinessBundle:Ifu');
        $taxType               = $entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_STATUTORY_CONTRIBUTIONS);
        $taxExemptionDateRange = $this->getTaxExemptionDateRange();
        $taxExemptionHistory   = $this->getExemptionHistory($wallet);
        $isEligible            = $this->getTaxExemptionEligibility($wallet);

        $templateData = [
            'client'      => $client,
            'bankAccount' => $bankAccount,
            'isCIPActive' => $this->isCIPActive(),
            'bankForm'    => $form->createView(),
            'lender'      => [
                'fiscal_info' => [
                    'documents'   => $ifuRepository->findBy(['idClient' => $client->getIdClient(), 'statut' => Ifu::STATUS_ACTIVE], ['annee' => 'DESC']),
                    'amounts'     => $this->getFiscalBalanceAndOwedCapital($client),
                    'rib'         => $bankAccount ? $bankAccount->getAttachment() : '',
                    'fundsOrigin' => $this->getFundsOrigin($client->getType())
                ]
            ],
            'clientAddress'                => $clientAddressRepository->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS),
            'currentYear'                  => date('Y'),
            'lastYear'                     => date('Y') - 1,
            'nextYear'                     => date('Y') + 1,
            'taxExemptionRequestLimitDate' => strftime('%d %B %Y', $taxExemptionDateRange['taxExemptionRequestLimitDate']->getTimestamp()),
            'rateOfTaxDeductionAtSource'   => $taxType->getRate(),
            'exemptions'                   => $taxExemptionHistory,
            'taxExemptionEligibility'      => $isEligible,
            'declarationIsPossible'        => $this->checkIfTaxExemptionIsPossible($taxExemptionHistory, $taxExemptionDateRange, $isEligible)
        ];

        return $this->render('lender_profile/fiscal_information.html.twig', $templateData);
    }

    /**
     * @Route("/profile/securite", name="lender_profile_security")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function securityAction(Request $request): Response
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->redirectToRoute('home');
        }

        $entityManager = $this->get('doctrine.orm.entity_manager');
        $client        = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());
        $emailForm     = $this->createForm(ClientEmailType::class, $client);
        $passwordForm  = $this->createForm(ClientPasswordType::class);
        $questionForm  = $this->createForm(SecurityQuestionType::class, $client);

        if ($request->isMethod(Request::METHOD_POST)) {
            $isValid = false;

            $emailForm->handleRequest($request);
            if ($emailForm->isSubmitted() && $emailForm->isValid()) {
                $isValid = $this->handleEmailForm($client, $emailForm);
            }

            $passwordForm->handleRequest($request);
            if ($passwordForm->isSubmitted()) {
                $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::CHANGE_PASSWORD);

                if ($passwordForm->isValid()) {
                    $isValid = $this->handlePasswordForm($client, $passwordForm);
                }
            }

            $questionForm->handleRequest($request);
            if ($questionForm->isSubmitted()) {
                $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::LENDER_PROFILE_SECURITY_QUESTION);

                if ($questionForm->isValid()) {
                    $entityManager->flush($client);

                    $this->addFlash('securitySecretQuestionSuccess', $this->get('translator')->trans('lender-profile_security-secret-question-section-form-success-message'));

                    $isValid = true;
                }
            }

            if ($isValid) {
                return $this->redirectToRoute('lender_profile_security');
            }
        }

        $templateData = [
            'client'        => $client,
            'isCIPActive'   => $this->isCIPActive(),
            'forms'         => [
                'securityEmail'    => $emailForm->createView(),
                'securityPwd'      => $passwordForm->createView(),
                'securityQuestion' => $questionForm->createView()
            ]
        ];

        return $this->render('lender_profile/security.html.twig', $templateData);
    }

    /**
     * @Route("/profile/alertes", name="lender_profile_notifications")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function notificationsAction(Request $request): Response
    {
        /** @var UserLender $user */
        $user = $this->getUser();

        if (false === in_array($user->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->redirectToRoute('home');
        }

        $clientRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients');
        $client           = $clientRepository->find($user->getClientId());

        $templateData = [
            'isCIPActive'       => $this->isCIPActive(),
            'newsletterConsent' => $client->getOptin1(),
            'siteUrl'           => $request->getSchemeAndHttpHost()
        ];

        $this->addNotificationSettingsTemplate($templateData);

        return $this->render('lender_profile/notifications.html.twig', $templateData);
    }

    /**
     * @param array $templateData
     */
    private function addNotificationSettingsTemplate(array &$templateData): void
    {
        /** @var \clients_gestion_notifications $notificationSettings */
        $notificationSettings = $this->get('unilend.service.entity_manager')->getRepository('clients_gestion_notifications');
        $notificationSetting  = $notificationSettings->getNotifs($this->getUser()->getClientId());

        if (empty($notificationSetting) || ClientsGestionTypeNotif::NUMBER_NOTIFICATION_TYPES > count($notificationSetting)) {
            $this->get('unilend.service.notification_manager')->generateDefaultNotificationSettings($this->getUser()->getClientId());
            $notificationSetting = $notificationSettings->getNotifs($this->getUser()->getClientId());
        }

        $templateData['notification_settings']['immediate'] = [
            ClientsGestionTypeNotif::TYPE_NEW_PROJECT                   => $notificationSetting[ClientsGestionTypeNotif::TYPE_NEW_PROJECT][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_BID_PLACED                    => $notificationSetting[ClientsGestionTypeNotif::TYPE_BID_PLACED][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_BID_REJECTED                  => $notificationSetting[ClientsGestionTypeNotif::TYPE_BID_REJECTED][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED                 => $notificationSetting[ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_PROJECT_PROBLEM               => $notificationSetting[ClientsGestionTypeNotif::TYPE_PROJECT_PROBLEM][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID => $notificationSetting[ClientsGestionTypeNotif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_REPAYMENT                     => $notificationSetting[ClientsGestionTypeNotif::TYPE_REPAYMENT][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_BANK_TRANSFER_CREDIT          => $notificationSetting[ClientsGestionTypeNotif::TYPE_BANK_TRANSFER_CREDIT][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_CREDIT_CARD_CREDIT            => $notificationSetting[ClientsGestionTypeNotif::TYPE_CREDIT_CARD_CREDIT][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_DEBIT                         => $notificationSetting[ClientsGestionTypeNotif::TYPE_DEBIT][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
        ];

        $templateData['notification_settings']['daily'] = [
            ClientsGestionTypeNotif::TYPE_NEW_PROJECT   => $notificationSetting[ClientsGestionTypeNotif::TYPE_NEW_PROJECT][\clients_gestion_notifications::TYPE_NOTIFICATION_DAILY],
            ClientsGestionTypeNotif::TYPE_BID_PLACED    => $notificationSetting[ClientsGestionTypeNotif::TYPE_BID_PLACED][\clients_gestion_notifications::TYPE_NOTIFICATION_DAILY],
            ClientsGestionTypeNotif::TYPE_BID_REJECTED  => $notificationSetting[ClientsGestionTypeNotif::TYPE_BID_REJECTED][\clients_gestion_notifications::TYPE_NOTIFICATION_DAILY],
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED => $notificationSetting[ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED][\clients_gestion_notifications::TYPE_NOTIFICATION_DAILY],
            ClientsGestionTypeNotif::TYPE_REPAYMENT     => $notificationSetting[ClientsGestionTypeNotif::TYPE_REPAYMENT][\clients_gestion_notifications::TYPE_NOTIFICATION_DAILY]
        ];

        $templateData['notification_settings']['weekly'] = [
            ClientsGestionTypeNotif::TYPE_NEW_PROJECT   => $notificationSetting[ClientsGestionTypeNotif::TYPE_NEW_PROJECT][\clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY],
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED => $notificationSetting[ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED][\clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY],
            ClientsGestionTypeNotif::TYPE_REPAYMENT     => $notificationSetting[ClientsGestionTypeNotif::TYPE_REPAYMENT][\clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY]
        ];

        $templateData['notification_settings']['monthly'] = [
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED => $notificationSetting[ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED][\clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY],
            ClientsGestionTypeNotif::TYPE_REPAYMENT     => $notificationSetting[ClientsGestionTypeNotif::TYPE_REPAYMENT][\clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY]
        ];
    }

    /**
     * @Route("/profile/notification", name="lender_profile_notification", condition="request.isXmlHttpRequest()")
     * @Method("POST")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateNotificationAction(Request $request): JsonResponse
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->json('ko');
        }

        $sendingPeriod = $request->request->filter('period', FILTER_SANITIZE_STRING);
        $active        = $request->request->getBoolean('active');

        switch ($sendingPeriod) {
            case 'newsletter':
                return $this->updateNewsletterConsent($active, $request->getClientIp());
            case 'immediate':
            case 'daily':
            case 'weekly':
            case 'monthly':
                return $this->updateNotificationSettings($sendingPeriod, $active, $request->request->getInt('type_id'));
            default:
                return $this->json('ko');
        }
    }

    /**
     * @param bool        $active
     * @param null|string $ipAddress
     *
     * @return JsonResponse
     */
    private function updateNewsletterConsent(bool $active, ?string $ipAddress): JsonResponse
    {
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $clientRepository  = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $client            = $clientRepository->find($this->getUser()->getClientId());
        $newsletterManager = $this->get(NewsletterManager::class);

        if ($active) {
            $response = $newsletterManager->subscribeNewsletter($client, $ipAddress);
        } else {
            $response = $newsletterManager->unsubscribeNewsletter($client, $ipAddress);
        }

        $response = $response ? 'ok' : 'ko';
        return $this->json($response);
    }

    /**
     * @param string   $sendingPeriod
     * @param bool     $active
     * @param int|null $typeId
     *
     * @return JsonResponse
     */
    private function updateNotificationSettings(string $sendingPeriod, bool $active, ?int $typeId): JsonResponse
    {
        $type = null;

        /* Put it temporary here, because we don't need it after the project refectory notification  */
        $immediateTypes = [
            ClientsGestionTypeNotif::TYPE_NEW_PROJECT,
            ClientsGestionTypeNotif::TYPE_BID_PLACED,
            ClientsGestionTypeNotif::TYPE_BID_REJECTED,
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED,
            ClientsGestionTypeNotif::TYPE_PROJECT_PROBLEM,
            ClientsGestionTypeNotif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID,
            ClientsGestionTypeNotif::TYPE_REPAYMENT,
            ClientsGestionTypeNotif::TYPE_BANK_TRANSFER_CREDIT,
            ClientsGestionTypeNotif::TYPE_CREDIT_CARD_CREDIT,
            ClientsGestionTypeNotif::TYPE_DEBIT
        ];
        $dailyTypes     = [
            ClientsGestionTypeNotif::TYPE_NEW_PROJECT,
            ClientsGestionTypeNotif::TYPE_BID_PLACED,
            ClientsGestionTypeNotif::TYPE_BID_REJECTED,
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED,
            ClientsGestionTypeNotif::TYPE_REPAYMENT
        ];
        $weeklyTypes    = [
            ClientsGestionTypeNotif::TYPE_NEW_PROJECT,
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED,
            ClientsGestionTypeNotif::TYPE_REPAYMENT
        ];

        $monthlyTypes = [
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED,
            ClientsGestionTypeNotif::TYPE_REPAYMENT
        ];

        switch ($sendingPeriod) {
            case 'immediate':
                $type = \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE;
                if (false === in_array($typeId, $immediateTypes, true)) {
                    return $this->json('ko');
                }
                break;
            case 'daily':
                $type = \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY;
                if (false === in_array($typeId, $dailyTypes, true)) {
                    return $this->json('ko');
                }
                break;
            case 'weekly':
                $type = \clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY;
                if (false === in_array($typeId, $weeklyTypes, true)) {
                    return $this->json('ko');
                }
                break;
            case 'monthly':
                $type = \clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY;
                if (false === in_array($typeId, $monthlyTypes, true)) {
                    return $this->json('ko');
                }
                break;
            default:
                return $this->json('ko');
        }

        try {
            $entityManager                  = $this->get('doctrine.orm.entity_manager');
            $notificationSettingsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsGestionNotifications');
            $notificationSettings           = $notificationSettingsRepository->findOneBy([
                'idClient' => $this->getUser()->getClientId(),
                'idNotif'  => $typeId
            ]);

            if (null === $notificationSettings) {
                $this->get('unilend.service.notification_manager')->generateDefaultNotificationSettings($this->getUser()->getClientId());
                $this->get('logger')->warning('Setting of frequency ' . $type . ' for type ' . $typeId . ' did not exist for client ' . $this->getUser()->getClientId(), [
                    'id_client' => $this->getUser()->getClientId(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__
                ]);
                $notificationSettings = $notificationSettingsRepository->findOneBy([
                    'idClient' => $this->getUser()->getClientId(),
                    'idNotif'  => $typeId
                ]);
            }

            $notificationSettings->{'set' . ucfirst($type)}($active);

            $entityManager->flush($notificationSettings);
        } catch (\Exception $exception) {
            $this->get('logger')->error('Could not update lender notifications. Error: ' . $exception->getMessage(), [
                'id_client' => $this->getUser()->getClientId(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);

            return $this->json('ko');
        }

        return $this->json('ok');
    }

    /**
     * @Route("/profile/documents", name="lender_completeness")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @return Response
     */
    public function lenderCompletenessAction(): Response
    {
        if (false === in_array($this->getUser()->getClientStatus(), [ClientsStatus::STATUS_COMPLETENESS, ClientsStatus::STATUS_COMPLETENESS_REMINDER])) {
            return $this->redirectToRoute('lender_dashboard');
        }

        $attachmentManager = $this->get('unilend.service.attachment_manager');
        $entityManager     = $this->get('doctrine.orm.entity_manager');

        $template = [
            'attachmentTypes' => $attachmentManager->getAllTypesForLender(),
            'attachmentsList' => '',
            'bankForm'        => null
        ];

        $ribAttachment   = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findOneClientAttachmentByType($this->getUser()->getClientId(), AttachmentType::RIB);
        $bankAccount     = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($this->getUser()->getClientId());
        $bankAccountForm = $this->createFormBuilder()
            ->add('bankAccount', BankAccountType::class)
            ->getForm();

        $bankAccountForm->get('bankAccount')->get('iban')->setData($bankAccount->getIban());
        $bankAccountForm->get('bankAccount')->get('bic')->setData($bankAccount->getBic());
        $template['bankForm'] = $bankAccountForm->createView();

        $completenessRequest = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory')->findOneBy(
            ['idClient' => $this->getUser()->getClientId(), 'idStatus' => ClientsStatus::STATUS_COMPLETENESS],
            ['added' => 'DESC', 'id' => 'DESC']
        );

        if (null !== $completenessRequest) {
            $domElement = new \DOMDocument();
            $domElement->loadHTML($completenessRequest->getContent());
            $list = $domElement->getElementsByTagName('ul');
            if ($list->length > 0 && $list->item(0)->childNodes->length > 0) {
                $template['attachmentsList'] = $list->item(0)->C14N();
            }
        } elseif (
            null !== $ribAttachment
            && $ribAttachment->getGreenpointAttachment() instanceof GreenpointAttachment
            && $ribAttachment->getGreenpointAttachment()->getValidationStatus() < 8
        ) {
            $template['attachmentsList'] = '<ul><li>' . $ribAttachment->getType()->getLabel() . '</li></ul>';
        }

        return $this->render('lender_profile/lender_completeness.html.twig', $template);
    }

    /**
     * @Route("/profile/documents/submit", name="lender_completeness_submit")
     * @Method("POST")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function lenderCompletenessFormAction(Request $request): RedirectResponse
    {
        if (false === in_array($this->getUser()->getClientStatus(), [ClientsStatus::STATUS_COMPLETENESS, ClientsStatus::STATUS_COMPLETENESS_REMINDER])) {
            return $this->redirectToRoute('lender_dashboard');
        }

        $translator     = $this->get('translator');
        $isFileUploaded = false;
        $error          = '';
        $files          = $request->request->get('files', []);
        $client         = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());

        foreach ($request->files->all() as $fileName => $file) {
            if ($file instanceof UploadedFile && false === empty($files[$fileName])) {
                try {
                    $document       = $this->upload($client, $files[$fileName], $file);
                    $isFileUploaded = true;

                    if (AttachmentType::RIB === $document->getType()->getId()) {
                        $form               = $request->request->get('form', ['bankAccount' => ['bic' => '', 'iban' => '']]);
                        $iban               = $form['bankAccount']['iban'];
                        $bic                = $form['bankAccount']['bic'];

                        if (in_array(strtoupper(substr($iban, 0, 2)), PaysV2::EEA_COUNTRIES_ISO)) {
                            $bankAccountManager = $this->get('unilend.service.bank_account_manager');
                            $bankAccountManager->saveBankInformation($client, $bic, $iban, $document);
                        } else {
                            $error = $translator->trans('lender-subscription_documents-iban-not-european-error-message');
                        }
                    }
                } catch (\Exception $exception) {
                    $error = $translator->trans('lender-profile_completeness-form-error-message');
                }
            }
        }

        if (empty($error) && $isFileUploaded) {
            $this->updateClientStatusAndNotifyClient($client);

            $this->addFlash('completenessSuccess', $translator->trans('lender-profile_completeness-form-success-message'));
        } elseif (false === empty($error)) {
            $this->addFlash('completenessError', $error);
        }

        return $this->redirectToRoute('lender_completeness');
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
        $attachmentManager = $this->get('unilend.service.attachment_manager');
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $attachmentType    = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find($attachmentTypeId);
        $attachment        = $attachmentManager->upload($client, $attachmentType, $file);

        if (false === $attachment instanceof Attachment) {
            throw new \Exception();
        }

        return $attachment;
    }

    /**
     * @param Clients $client
     * @param Request $request
     * @param string  $formName
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveClientHistoryAction(Clients $client, Request $request, string $formName): void
    {
        $formManager = $this->get('unilend.frontbundle.service.form_manager');
        $post        = $formManager->cleanPostData($request->request->all());
        $files       = $request->files;

        if (false === empty($files)) {
            $post = array_merge($post, $formManager->getNamesOfFiles($files));
        }

        $formManager->saveFormSubmission($client, $formName, serialize(['id_client' => $client->getIdClient(), 'post' => $post]), $request->getClientIp());
    }

    /**
     * @param Clients    $client
     * @param array|null $modifiedData
     */
    private function updateClientStatusAndNotifyClient(Clients $client, ?array $modifiedData = null): void
    {
        // Data in $modifiedData should only be data not historized in tables `bank_account`, `attachment`, `*_address` or `client_data_history`
        $historyContent      = $this->formatArrayToUnorderedList($modifiedData);
        $clientStatusManager = $this->get('unilend.service.client_status_manager');

        if ($client->getUsPerson()) {
            $clientStatusManager->addClientStatus($client, Users::USER_ID_FRONT, ClientsStatus::STATUS_SUSPENDED);
        } else {
            $clientStatusManager->changeClientStatusTriggeredByClientAction($client, $historyContent);
        }
        $this->sendAccountModificationEmail($client);
    }

    /**
     * @Route("/profile/ajax/zip", name="lender_profile_ajax_zip")
     * @Method("GET")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getZipAction(Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            /** @var LocationManager $locationManager */
            $locationManager = $this->get('unilend.service.location_manager');
            return new JsonResponse($locationManager->getCities($request->query->get('zip')));
        }

        return new Response('not an ajax request');
    }

    /**
     * @Route("/profile/ifu", name="get_ifu")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function downloadIFUAction(Request $request): Response
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->redirectToRoute('home');
        }

        /** @var \ifu $ifu */
        $ifu        = $this->get('unilend.service.entity_manager')->getRepository('ifu');
        $client     = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());
        $translator = $this->get('translator');

        if ($client->getHash() === $request->query->filter('hash', FILTER_SANITIZE_STRING)) {
            if (
                $ifu->get($this->getUser()->getClientId(), 'annee = ' . $request->query->getInt('year', 0) . ' AND statut = ' . Ifu::STATUS_ACTIVE . ' AND id_client')
                && file_exists($this->get('kernel')->getRootDir() . '/../' . $ifu->chemin)
            ) {
                return new Response(
                    @file_get_contents($this->get('kernel')->getRootDir() . '/../' . $ifu->chemin),
                    Response::HTTP_OK,
                    [
                        'Content-Description' => 'File Transfer',
                        'Content-type'        => 'application/force-download;',
                        'content-disposition' => 'attachment; filename="' . basename($ifu->chemin) . '";'
                    ]
                );
            } else {
                $errorTitle = $translator->trans('lender-error-page_file-not-found');
                $status     = Response::HTTP_NOT_FOUND;
            }
        } else {
            $errorTitle = $translator->trans('lender-error-page_access-denied');
            $status     = Response::HTTP_FORBIDDEN;
        }

        return $this->render('exception/error.html.twig', ['errorTitle' => $errorTitle])->setStatusCode($status);
    }

    /**
     * @param FormInterface    $form
     * @param FileBag          $fileBag
     * @param BankAccount|null $unattachedBankAccount
     *
     * @return bool
     * @throws \Exception
     */
    private function handleBankDetailsForm(FormInterface $form, FileBag $fileBag, ?BankAccount $unattachedBankAccount): bool
    {
        $translator          = $this->get('translator');
        $iban                = $form->get('bankAccount')->get('iban')->getData();
        $bic                 = $form->get('bankAccount')->get('bic')->getData();
        $client              = $form->get('client')->getData();
        $bankAccountDocument = null;

        if (false === in_array(strtoupper(substr($iban, 0, 2)), PaysV2::EEA_COUNTRIES_ISO)) {
            $form->get('bankAccount')->get('iban')->addError(new FormError($translator->trans('lender-subscription_documents-iban-not-european-error-message')));
        }

        if (
            null === $unattachedBankAccount && (false === empty($iban) || false === empty($bic))
            || null !== $unattachedBankAccount && ($unattachedBankAccount->getIban() !== $iban || $unattachedBankAccount->getBic() !== $bic)
        ) {
            $file = $fileBag->get('iban-certificate');

            if (false === $file instanceof UploadedFile) {
                $form->get('bankAccount')->addError(new FormError($translator->trans('lender-profile_rib-file-mandatory')));
            } else {
               try {
                   $bankAccountDocument = $this->upload($client, AttachmentType::RIB, $file);
               } catch (\Exception $exception) {
                   $form->addError(new FormError($translator->trans('lender-profile_fiscal-tab-rib-file-error')));
               }
           }
        }

        if ($form->isValid() && $bankAccountDocument) {
            $this->addFlash('bankInfoUpdateSuccess', $translator->trans('lender-profile_fiscal-tab-bank-info-update-ok'));
            $this->logClientChanges($client);
            $this->updateClientStatusAndNotifyClient($client);

            $bankAccountManager = $this->get('unilend.service.bank_account_manager');
            $bankAccountManager->saveBankInformation($client, $bic, $iban, $bankAccountDocument);

            return true;
        }

        return false;
    }

    /**
     * @param Clients $client
     *
     * @return array
     * @throws \Doctrine\ORM\ORMException
     */
    private function getFiscalBalanceAndOwedCapital(Clients $client): array
    {
        $entityManager                  = $this->get('doctrine.orm.entity_manager');
        $walletRepository               = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $walletBalanceHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');
        $operationRepository            = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        $lastYear = new \DateTime('Last day of december last year');
        $wallet   = $walletRepository->getWalletByType($client->getIdClient(), WalletType::LENDER);
        /** @var WalletBalanceHistory $history */
        $history = $walletBalanceHistoryRepository->getBalanceOfTheDay($wallet, $lastYear);

        return [
            'balance'     => null !== $history ? bcadd($history->getAvailableBalance(), $history->getCommittedBalance(), 2) : 0,
            'owedCapital' => $operationRepository->getRemainingDueCapitalAtDate($client->getIdClient(), $lastYear)
        ];
    }

    /**
     * @param int $clientType
     *
     * @return array
     */
    private function getFundsOrigin(int $clientType): array
    {
        switch ($clientType) {
            case Clients::TYPE_PERSON:
            case Clients::TYPE_PERSON_FOREIGNER:
                $settingName = 'Liste deroulante origine des fonds';
                break;
            default:
                $settingName = 'Liste deroulante origine des fonds societe';
                break;
        }

        $fundsOriginList = $this->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:Settings')
            ->findOneBy(['type' => $settingName])
            ->getValue();
        $fundsOriginList = explode(';', $fundsOriginList);

        return array_combine(range(1, count($fundsOriginList)), array_values($fundsOriginList));
    }

    /**
     * @param Clients       $client
     * @param FormInterface $form
     *
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function handleEmailForm(Clients $client, FormInterface $form): bool
    {
        $translator    = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (false === empty($entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findByEmailAndStatus($client->getEmail(), ClientsStatus::GRANTED_LOGIN))) {
            $form->addError(new FormError($translator->trans('lender-profile_security-identification-error-existing-email')));
        }

        if ($form->isValid()) {
            $this->addFlash('securityIdentificationSuccess', $translator->trans('lender-profile_security-identification-form-success-message'));
            $this->logClientChanges($client);

            return true;
        }

        return false;
    }

    /**
     * @param Clients       $client
     * @param FormInterface $form
     *
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function handlePasswordForm(Clients $client, FormInterface $form): bool
    {
        $translator              = $this->get('translator');
        $securityPasswordEncoder = $this->get('security.password_encoder');
        $entityManager           = $this->get('doctrine.orm.entity_manager');

        if (false === $securityPasswordEncoder->isPasswordValid($this->getUser(), $form->get('formerPassword')->getData())) {
            $form->get('formerPassword')->addError(new FormError($translator->trans('lender-profile_security-password-section-error-wrong-former-password')));
        }

        $encodedPassword = '';
        try {
            $encodedPassword = $securityPasswordEncoder->encodePassword($this->getUser(), $form->get('password')->getData());
        } catch (\Exception $exception) {
            $form->get('password')->addError(new FormError($translator->trans('common-validator_password-invalid')));
        }

        if ($form->isValid()) {
            $client->setPassword($encodedPassword);
            $entityManager->flush($client);

            $this->sendPasswordModificationEmail($client);
            $this->addFlash('securityPasswordSuccess', $translator->trans('lender-profile_security-password-section-form-success-message'));

            return true;
        }

        return false;
    }

    /**
     * @param Clients $client
     */
    private function sendPasswordModificationEmail(Clients $client): void
    {
        $keywords = [
            'firstName'     => $client->getPrenom(),
            'password'      => '',
            'lenderPattern' => $this->get('doctrine.orm.entity_manager')
                ->getRepository('UnilendCoreBusinessBundle:Wallet')
                ->getWalletByType($client->getIdClient(), WalletType::LENDER)
                ->getWireTransferPattern()
        ];

        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('generation-mot-de-passe', $keywords);

        try {
            $message->setTo($client->getEmail());
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->warning('Could not send email: generation-mot-de-passe - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'id_client'        => $client->getIdClient(),
                'class'            => __CLASS__,
                'function'         => __METHOD__,
                'file'             => $exception->getFile(),
                'line'             => $exception->getLine()
            ]);
        }
    }

    /**
     * @param Clients $client
     */
    private function sendAccountModificationEmail(Clients $client): void
    {
        $keywords = [
            'firstName'     => $client->getPrenom(),
            'lenderPattern' => $this->get('doctrine.orm.entity_manager')
                ->getRepository('UnilendCoreBusinessBundle:Wallet')
                ->getWalletByType($client->getIdClient(), WalletType::LENDER)
                ->getWireTransferPattern()
        ];

        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-modification-compte', $keywords);

        try {
            $message->setTo($client->getEmail());
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->warning(
                'Could not send email: preteur-modification-compte - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }

    /**
     * @return bool
     */
    private function isCIPActive(): bool
    {
        /** @var \lender_evaluation_log $evaluationLog */
        $evaluationLog = $this->get('unilend.service.entity_manager')->getRepository('lender_evaluation_log');
        $wallet        = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);

        return $evaluationLog->hasLenderLog($wallet->getId());
    }

    /**
     * @Route("/profile/request-tax-exemption", name="profile_fiscal_information_tax_exemption")
     * @Method("POST")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function requestTaxExemptionAction(Request $request): Response
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->redirectToRoute('home');
        }

        $entityManager                = $this->get('doctrine.orm.entity_manager');
        $translator                   = $this->get('translator');
        $logger                       = $this->get('logger');
        $lenderTaxExemptionRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:LenderTaxExemption');
        $wallet                       = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $year                         = date('Y') + 1;

        $post = $request->request->all();

        try {
            $taxExemptionDateRange = $this->getTaxExemptionDateRange();
            /** @var \DateTime $now */
            $now = new \DateTime();

            if ($now >= $taxExemptionDateRange['taxExemptionRequestStartDate'] && $now <= $taxExemptionDateRange['taxExemptionRequestLimitDate']
                && null === $lenderTaxExemptionRepository->findOneBy(['idLender' => $wallet, 'year' => $year])
            ) {
                if (true === isset($post['agree']) && true === isset($post['attest'])
                    && 'agree-to-be-informed' === $post['agree'] && 'honor-attest' === $post['attest']
                ) {
                    $lenderTaxExemption = new LenderTaxExemption();
                    $lenderTaxExemption
                        ->setIdLender($wallet)
                        ->setIsoCountry('FR')
                        ->setYear($year);
                    $entityManager->persist($lenderTaxExemption);
                    $entityManager->flush($lenderTaxExemption);

                    $this->addFlash('exonerationSuccess', $translator->trans('lender-profile_fiscal-information-exoneration-validation-success'));
                }
            } else {
                $this->addFlash('exonerationError', $translator->trans('lender-profile_fiscal-information-exoneration-validation-error'));
                $logger->warning('The tax exemption request was not processed for the lender: (id_lender=' . $wallet->getId() . ') for the year: ' . $year .
                    '. Lender already exempted. Either declaration time has elapsed or declaration already done.',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $wallet->getId(), 'time' => $now->format('Y-m-d H:i:s')]);
            }
        } catch (\Exception $exception) {
            $this->addFlash('exonerationError', $translator->trans('lender-profile_fiscal-information-exoneration-validation-error'));
            $logger->error('Could not register lender tax exemption request for the lender: (id_lender=' . $wallet->getId() . ') for the year: ' . $year .
                ' Exception message : ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'id_lender' => $wallet->getId()]
            );
        }

        return $this->redirectToRoute('lender_profile_fiscal_information');
    }

    /**
     * Returns true if the declaration is possible, false otherwise
     *
     * @param array $taxExemptionHistory
     * @param array $taxExemptionDateRange
     * @param bool  $isEligible
     *
     * @return bool
     */
    private function checkIfTaxExemptionIsPossible(array $taxExemptionHistory, array $taxExemptionDateRange, bool $isEligible): bool
    {
        /** @var \DateTime $now */
        $now       = new \DateTime();
        $outOfDate = $now < $taxExemptionDateRange['taxExemptionRequestStartDate'] || $now > $taxExemptionDateRange['taxExemptionRequestLimitDate'];

        if (false === empty($taxExemptionHistory)) {
            $taxExemptionRequestDone = in_array(date('Y') + 1, $taxExemptionHistory);
        } else {
            $taxExemptionRequestDone = false;
        }

        return (true === $isEligible && false === $outOfDate && false === $taxExemptionRequestDone);
    }

    /**
     * @param Wallet $wallet
     *
     * @return bool
     */
    private function getTaxExemptionEligibility(Wallet $wallet): bool
    {
        $lenderImpositionHistoryRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:LendersImpositionHistory');
        try {
            $lenderInfo = $lenderImpositionHistoryRepository->getLenderTypeAndFiscalResidence($wallet->getId());
            if (false === empty($lenderInfo)) {
                $isEligible = 'fr' === $lenderInfo['fiscal_address'] && 'person' === $lenderInfo['client_type'];
            } else {
                $isEligible = false;
            }
        } catch (\Exception $exception) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->info('Could not get lender info to check tax exemption eligibility. (id_lender=' . $wallet->getId() . ') Error message: ' .
                $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $wallet->getId()]);
            $isEligible = false;
        }

        return $isEligible;
    }

    /**
     * @param Wallet $wallet
     *
     * @return array
     */
    private function getExemptionHistory(Wallet $wallet): array
    {
        $result = [];
        $lenderTaxExemptionRepository = $this->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:LenderTaxExemption');
        /** @var LenderTaxExemption $exemptionYear */
        foreach ($lenderTaxExemptionRepository->findBy(['idLender' => $wallet]) as $exemptionYear) {
            $result[] = $exemptionYear->getYear();
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getTaxExemptionDateRange(): array
    {
        /** @var \settings $settings */
        $settings  = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $dateRange = [];

        $settings->get('taxExemptionRequestLimitDate', 'type');
        $dateRange['taxExemptionRequestLimitDate'] = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y') . '-' . $settings->value . ' 23:59:59');

        $settings->get('taxExemptionRequestStartDate', 'type');
        $dateRange['taxExemptionRequestStartDate'] = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y') . '-' . $settings->value . ' 00:00:00');

        return $dateRange;
    }

    /**
     * @param array|null $modifications
     *
     * @return string|null
     */
    private function formatArrayToUnorderedList(?array $modifications): ?string
    {
        if (empty($modifications)) {
            return null;
        }

        $list = '<ul>';

        foreach ($modifications as $modification) {
            $list .= '<li>' . $modification . '</li>';
        }

        $list .= '</ul>';

        return $list;
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
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $frontUser     = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_FRONT);
        $clientAuditer = $this->get(ClientAuditer::class);
        $clientChanges = $clientAuditer->logChanges($client, $frontUser);

        if (false === empty($clientChanges['email'][0])) {
            $this->notifyEmailChangeToOldAddress($client, $clientChanges['email'][0]);
        }

        $entityManager->flush($client);

        return $clientChanges;
    }

    /**
     * @param Clients $client
     * @param string  $oldEmail
     */
    private function notifyEmailChangeToOldAddress(Clients $client, string $oldEmail): void
    {
        $walletRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet');
        /** @var Wallet $wallet */
        $wallet = $walletRepository->getWalletByType($client, WalletType::LENDER);

        if (null === $wallet) {
            $this->get('logger')->error('Could not notify email modification to old email address. Unable to find lender wallet.', [
                'id_client' => $client,
                'class'     => __CLASS__,
                'function'  => __FUNCTION__
            ]);

            return;
        }

        $message = $this->get('unilend.swiftmailer.message_provider')
            ->newMessage('alerte-changement-email-preteur', [
                'firstName'     => $client->getPrenom(),
                'lastName'      => $client->getNom(),
                'lenderPattern' => $wallet->getWireTransferPattern()
            ]);

        try {
            $message->setTo($oldEmail);
            $this->get('mailer')->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->error('Could not send email modification alert to the previous lender email. Error: ' . $exception->getMessage(), [
                'id_client'   => $client->getIdClient(),
                'template_id' => $message->getTemplateId(),
                'class'       => __CLASS__,
                'function'    => __FUNCTION__,
                'file'        => $exception->getFile(),
                'line'        => $exception->getLine()
            ]);
        }
    }
}
