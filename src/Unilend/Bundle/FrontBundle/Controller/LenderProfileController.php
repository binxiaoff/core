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
    FileBag, JsonResponse, RedirectResponse, Request, Response
};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Attachment, AttachmentType, BankAccount, Clients, ClientsAdresses, ClientsHistoryActions, ClientsStatus, Companies, CompanyAddress, GreenpointAttachment, Ifu, LenderTaxExemption, PaysV2, TaxType, Wallet, WalletBalanceHistory, WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;
use Unilend\Bundle\FrontBundle\Form\ClientPasswordType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\{
    BankAccountType, ClientEmailType, CompanyAddressType, CompanyIdentityType, LegalEntityProfileType, OriginOfFundsType, PersonFiscalAddressType, PersonPhoneType, PersonProfileType, PostalAddressType, SecurityQuestionType
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
        $companyAddressRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress');
        $client                   = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());
        $unattachedClient         = clone $client;
        $clientAddress            = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $client->getIdClient()]);
        $unattachedClientAddress  = clone $clientAddress;

        $phoneForm             = $this->createForm(PersonPhoneType::class, $client);

        if (in_array($client->getType(), [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
            $company                   = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
            $unattachedCompany         = clone $company;
            $lastModifiedMainAddress   = $companyAddressRepository->findLastModifiedCompanyAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
            $lastModifiedPostalAddress = $companyAddressRepository->findLastModifiedCompanyAddressByType($company, AddressType::TYPE_POSTAL_ADDRESS);

            $identityFormBuilder = $this->createFormBuilder()
                ->add('client', LegalEntityProfileType::class, ['data' => $client])
                ->add('company', CompanyIdentityType::class, ['data' => $company]);
            $identityFormBuilder->get('company')->remove('siren');

            $fiscalAddressForm = $this->getCompanyAddressForm($lastModifiedMainAddress, AddressType::TYPE_MAIN_ADDRESS);
            $postalAddressForm = $this->getCompanyAddressForm($lastModifiedPostalAddress, AddressType::TYPE_POSTAL_ADDRESS);
            $hasPostalAddress  = null === $lastModifiedPostalAddress;
            $postalAddressForm->add('samePostalAddress', CheckboxType::class, ['data' => $hasPostalAddress, 'required' => false]);
        } else {
            $identityFormBuilder = $this->createFormBuilder()
                ->add('client', PersonProfileType::class, ['data' => $client]);
            $fiscalAddressForm = $this->createForm(PersonFiscalAddressType::class, $clientAddress);
            $postalAddressForm = $this->createForm(PostalAddressType::class, $clientAddress);
        }

        $identityForm = $identityFormBuilder->getForm();

        if ($request->isMethod(Request::METHOD_POST)) {
            $isValid = false;
            if (isset($request->request->get('form')['client'])) {
                $identityForm->handleRequest($request);

                if ($identityForm->isSubmitted()) {
                    $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::LENDER_PROFILE_PERSONAL_INFORMATION);

                    if ($identityForm->isValid()) {
                        if (isset($request->request->get('form')['company'])) {
                            $isValid = $this->handleCompanyIdentity($unattachedClient, $client, $unattachedCompany, $company, $identityForm, $request->files);
                        } else {
                            $isValid = $this->handlePersonIdentity($unattachedClient, $client, $identityForm, $request->files);
                        }
                    }
                }
            }

            if (false === empty($request->request->get('person_fiscal_address'))) {
                $fiscalAddressForm->handleRequest($request);

                if ($fiscalAddressForm->isSubmitted()) {
                    $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::LENDER_PROFILE_PERSONAL_INFORMATION);

                    if ($fiscalAddressForm->isValid()) {
                        $isValid = $this->handlePersonFiscalAddress($unattachedClientAddress, $clientAddress, $fiscalAddressForm, $request->files);
                    }
                }
            }

            if (false === empty($request->request->get('main_address'))) {
                $fiscalAddressForm->handleRequest($request);

                if ($fiscalAddressForm->isSubmitted()) {
                    $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::LENDER_PROFILE_PERSONAL_INFORMATION);

                    if ($fiscalAddressForm->isValid()) {
                        $isValid = $this->handleCompanyAddress($company, $fiscalAddressForm, AddressType::TYPE_MAIN_ADDRESS);
                    }
                }
            }

            if (false === empty($request->request->get('postal_address'))) {
                $postalAddressForm->handleRequest($request);

                if ($postalAddressForm->isSubmitted()) {
                    $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::LENDER_PROFILE_PERSONAL_INFORMATION);

                    if ($postalAddressForm->isValid()) {

                        if ($client->isNaturalPerson()) {
                            $isValid = $this->handlePostalAddressForm($clientAddress);
                        } else {
                            $isValid = $this->handleCompanyAddress($company, $postalAddressForm, AddressType::TYPE_POSTAL_ADDRESS);
                        }
                    }
                }
            }

            if (false === empty($request->request->get('person_phone'))) {
                $phoneForm->handleRequest($request);
                $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::LENDER_PROFILE_PERSONAL_INFORMATION);

                if ($phoneForm->isValid()) {
                    $translator = $this->get('translator');
                    $entityManager->flush($client);
                    $this->addFlash('phoneSuccess', $translator->trans('lender-profile_information-tab-phone-form-success-message'));
                    $isValid = true;
                }
            }

            if ($isValid) {
                return $this->redirectToRoute('lender_profile_personal_information');
            }
        }

        $templateData = [
            'client'               => $client,
            'clientsAdresses'      => $clientAddress,
            'company'              => isset($company) ? $company : null,
            'companyMainAddress'   => $lastModifiedMainAddress,
            'companyPostalAddress' => $lastModifiedPostalAddress,
            'isCIPActive'          => $this->isCIPActive(),
            'forms'                => [
                'identity'      => $identityForm->createView(),
                'fiscalAddress' => $fiscalAddressForm->createView(),
                'postalAddress' => $postalAddressForm->createView(),
                'phone'         => $phoneForm->createView()
            ],

            'isLivingAbroad' => ($clientAddress->getIdPaysFiscal() > PaysV2::COUNTRY_FRANCE)
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
        $translator    = $this->get('translator');
        $modifications = [];

        $idRectoUploaded = false;
        $files           = [
            AttachmentType::CNI_PASSPORTE       => $fileBag->get('id_recto'),
            AttachmentType::CNI_PASSPORTE_VERSO => $fileBag->get('id_verso')
        ];
        foreach ($files as $attachmentTypeId => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $this->upload($client, $attachmentTypeId, $file);
                    $modifications[] = $translator->trans('projet_document-type-' . $attachmentTypeId);
                    if (AttachmentType::CNI_PASSPORTE === $attachmentTypeId) {
                        $idRectoUploaded = true;
                    }
                } catch (\Exception $exception) {
                    $form->get('client')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));
                }
            }
        }

        if (
            false === $idRectoUploaded
            &&  ($unattachedClient->getIdNationalite() !== $client->getIdNationalite()
                || $unattachedClient->getCivilite() !== $client->getCivilite())
        ) {
            $form->get('client')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-change-ID-warning-message')));
        }

        if ($form->isValid()) {
            $this->get('doctrine.orm.entity_manager')->flush($client);
            $this->addFlash('identitySuccess', $translator->trans('lender-profile_information-tab-identity-section-files-update-success-message'));

            $modifiedData = array_merge($modifications, $this->get('unilend.frontbundle.service.form_manager')->getModifiedContent($unattachedClient, $client));
            if (false === empty($modifiedData)) {
                $this->updateClientStatusAndNotifyClient($client, $modifiedData);
            }

            return true;
        }

        return false;
    }

    /**
     * @param Clients       $unattachedClient
     * @param Clients       $client
     * @param Companies     $unattachedCompany
     * @param Companies     $company
     * @param FormInterface $form
     * @param FileBag       $fileBag
     *
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function handleCompanyIdentity(Clients $unattachedClient, Clients $client, Companies $unattachedCompany, Companies $company, FormInterface $form, FileBag $fileBag): bool
    {
        $translator    = $this->get('translator');
        $modifications = [];

        if ($company->getStatusClient() > Companies::CLIENT_STATUS_MANAGER) {
            if (empty($company->getStatusConseilExterneEntreprise())) {
                $form->get('company')->get('statusConseilExterneEntreprise')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-external-counsel-error-message')));
            }
            if (
                Companies::CLIENT_STATUS_EXTERNAL_COUNSEL_OTHER == $company->getStatusConseilExterneEntreprise()
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
                    $modifications[] = $translator->trans('projet_document-type-' . $attachmentTypeId);
                } catch (\Exception $exception) {
                    $form->get('company')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));
                }
            }
        }

        if ($form->isValid()) {
            $this->addFlash('identitySuccess', $translator->trans('lender-profile_information-tab-identity-section-files-update-success-message'));

            $formManager         = $this->get('unilend.frontbundle.service.form_manager');
            $modifiedDataClient  = $formManager->getModifiedContent($unattachedClient, $client);
            $modifiedDataCompany = $formManager->getModifiedContent($unattachedCompany, $company);
            $modifiedData        = array_merge($modifiedDataClient, $modifiedDataCompany, $modifications);

            if (false === empty($modifiedData)) {
                $this->updateClientStatusAndNotifyClient($client, $modifiedData);
            }

            return true;
        }

        return false;
    }

    /**
     * @param ClientsAdresses $unattachedClientAddress
     * @param ClientsAdresses $clientAddress
     * @param FormInterface   $form
     * @param FileBag         $fileBag
     *
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function handlePersonFiscalAddress(ClientsAdresses $unattachedClientAddress, ClientsAdresses $clientAddress, FormInterface $form, FileBag $fileBag): bool
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $translator    = $this->get('translator');
        $client        = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientAddress->getIdClient());
        $modifications = [];

        if (
            ($unattachedClientAddress->getCpFiscal() !== $clientAddress->getCpFiscal() || $unattachedClientAddress->getIdPaysFiscal() !== $clientAddress->getIdPaysFiscal())
            && PaysV2::COUNTRY_FRANCE == $clientAddress->getIdPaysFiscal()
            && null === $entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $clientAddress->getCpFiscal()])
        ) {
            $form->get('cpFiscal')->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-section-unknown-zip-code-error-message')));
        }

        if ($form->get('noUsPerson')->getData()) {
            $modifications[] = 'noUsPerson';
        }
        $files[AttachmentType::JUSTIFICATIF_DOMICILE] = $fileBag->get('housing-certificate');
        if ($clientAddress->getIdPaysFiscal() > PaysV2::COUNTRY_FRANCE) {
            $files[AttachmentType::JUSTIFICATIF_FISCAL] = $fileBag->get('tax-certificate');
        }
        if ($form->get('housedByThirdPerson')->getData()) {
            $files[AttachmentType::ATTESTATION_HEBERGEMENT_TIERS] = $fileBag->get('housed-by-third-person-declaration');
            $files[AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT] = $fileBag->get('id-third-person-housing');
        }
        foreach ($files as $attachmentTypeId => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $this->upload($client, $attachmentTypeId, $file);
                    $modifications[] = $translator->trans('projet_document-type-' . $attachmentTypeId);
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

        if ($form->isValid()) {
            if ($clientAddress->getMemeAdresseFiscal()) {
                $this->updateFiscalAndPostalAddress($clientAddress);
            }

            $entityManager->flush($clientAddress);
            $this->addFlash('fiscalAddressSuccess', $translator->trans('lender-profile_information-tab-fiscal-address-form-success-message'));

            $modifiedData = array_merge($modifications, $this->get('unilend.frontbundle.service.form_manager')->getModifiedContent($unattachedClientAddress, $clientAddress));

            if (false === empty($modifiedData)) {
                $this->updateClientStatusAndNotifyClient($client, array_merge($modifiedData, $modifications));
            }

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
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function handleCompanyAddress(Companies $company, FormInterface $form, string $type): bool
    {
        $translator     = $this->get('translator');
        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $addressManager = $this->get('unilend.service.address_manager');

        $zip       = $form->get('zip')->getData();
        $countryId = $form->get('idCountry')->getData();

        if (AddressType::TYPE_MAIN_ADDRESS === $type) {
            if (
                false === empty($zip) && false === empty($countryId)
                && PaysV2::COUNTRY_FRANCE == $countryId
                && null === $entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $zip])
            ) {
                $form->get('zip')->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-section-unknown-zip-code-error-message')));
            }

            $modifiedContent = ['adresse principale'];
            $success         = 'fiscalAddressSuccess';
            $translation     = $translator->trans('lender-profile_information-tab-fiscal-address-form-success-message');
        } else {
            $modifiedContent = ['adresse de correspondance'];
            $success         = 'postalAddressSuccess';
            $translation     = $translator->trans('lender-profile_information-tab-postal-address-form-success-message');
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
                    $type);
            }

            //TODO will be changed with BLD-147
            $this->updateClientStatusAndNotifyClient($company->getIdClientOwner(), $modifiedContent);

            $this->addFlash($success, $translation);

            return true;
        }

        return false;
    }

    /**
     * @param ClientsAdresses $clientAddress
     *
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function handlePostalAddressForm(ClientsAdresses $clientAddress): bool
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $translator    = $this->get('translator');

        if (in_array($clientAddress->getIdClient()->getType(), [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
            $company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $clientAddress->getIdClient()]);
            $company->setStatusAdresseCorrespondance($clientAddress->getMemeAdresseFiscal());
            $entityManager->flush($company);
        }

        if ($clientAddress->getMemeAdresseFiscal()) {
            $this->updateFiscalAndPostalAddress($clientAddress);
        }
        $entityManager->flush($clientAddress);
        $this->addFlash('postalAddressSuccess', $translator->trans('lender-profile_information-tab-postal-address-form-success-message'));

        return true;
    }

    /**
     * @Route("/profile/info-fiscal", name="lender_profile_fiscal_information")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function fiscalInformationAction(Request $request): Response
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->redirectToRoute('home');
        }

        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $client                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());
        $unattachedClientEntity = clone $client;
        $bankAccount            = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($client);
        $wallet                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        $form = $this->createFormBuilder()
            ->add('client', OriginOfFundsType::class, ['data' => $client])
            ->add('bankAccount', BankAccountType::class)
            ->getForm();

        $form->get('bankAccount')->get('iban')->setData($bankAccount->getIban());
        $form->get('bankAccount')->get('bic')->setData($bankAccount->getBic());

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::LENDER_PROFILE_BANK_INFORMATION);
            if ($form->isValid()) {
                $isValid = $this->handleBankDetailsForm($bankAccount, $unattachedClientEntity, $form, $request->files);
                if ($isValid) {
                    return $this->redirectToRoute('lender_profile_fiscal_information');
                }
            }
        }

        $ifuRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Ifu');
        $templateData  = [
            'client'      => $client,
            'bankAccount' => $bankAccount,
            'isCIPActive' => $this->isCIPActive(),
            'bankForm'    => $form->createView(),
            'lender'      => [
                'fiscal_info' => [
                    'documents'   => $ifuRepository->findBy(['idClient' => $client->getIdClient(), 'statut' => Ifu::STATUS_ACTIVE], ['annee' => 'DESC']),
                    'amounts'     => $this->getFiscalBalanceAndOwedCapital($client),
                    'rib'         => $bankAccount->getAttachment(),
                    'fundsOrigin' => $this->getFundsOrigin($client->getType())
                ]
            ]
        ];

        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->getClientAddress();
        /** @var \tax_type $taxType */
        $taxType = $this->get('unilend.service.entity_manager')->getRepository('tax_type');
        $taxType->get(TaxType::TYPE_STATUTORY_CONTRIBUTIONS);
        $templateData['clientAddress']                = $clientAddress->select('id_client = ' . $client->getIdClient())[0];
        $templateData['currentYear']                  = date('Y');
        $templateData['lastYear']                     = $templateData['currentYear'] - 1;
        $templateData['nextYear']                     = $templateData['currentYear'] + 1;
        $taxExemptionDateRange                        = $this->getTaxExemptionDateRange();
        $templateData['taxExemptionRequestLimitDate'] = strftime('%d %B %Y', $taxExemptionDateRange['taxExemptionRequestLimitDate']->getTimestamp());
        $templateData['rateOfTaxDeductionAtSource']   = $taxType->rate;
        $taxExemptionHistory                          = $this->getExemptionHistory($wallet);
        $templateData['exemptions']                   = $taxExemptionHistory;
        $isEligible                                   = $this->getTaxExemptionEligibility($wallet);
        $templateData['taxExemptionEligibility']      = $isEligible;
        $templateData['declarationIsPossible']        = $this->checkIfTaxExemptionIsPossible($taxExemptionHistory, $taxExemptionDateRange, $isEligible);

        return $this->render('lender_profile/fiscal_information.html.twig', $templateData);
    }

    /**
     * @Route("/profile/securite", name="lender_profile_security")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function securityAction(Request $request): Response
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->redirectToRoute('home');
        }

        $client                 = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());
        $unattachedClientEntity = clone $client;
        $emailForm              = $this->createForm(ClientEmailType::class, $client);
        $pwdForm                = $this->createForm(ClientPasswordType::class);
        $questionForm           = $this->createForm(SecurityQuestionType::class, $client);

        if ($request->isMethod(Request::METHOD_POST)) {
            $isValid = null;
            if (false === empty($request->request->get('client_email'))) {
                $emailForm->handleRequest($request);

                if ($emailForm->isSubmitted()) {
                    $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::LENDER_PROFILE_PERSONAL_INFORMATION);

                    if ($emailForm->isValid()) {
                        $isValid = $this->handleEmailForm($unattachedClientEntity, $client, $emailForm);
                    }
                }
            }

            if (false === empty($request->request->get('client_password'))) {
                $pwdForm->handleRequest($request);

                if ($pwdForm->isSubmitted()) {
                    $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::CHANGE_PASSWORD);

                    if ($pwdForm->isValid()) {
                        $isValid = $this->handlePasswordForm($client, $pwdForm);
                    }
                }
            }

            if (false === empty($request->request->get('security_question'))) {
                $questionForm->handleRequest($request);

                if ($questionForm->isSubmitted()) {
                    $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::LENDER_PROFILE_SECURITY_QUESTION);

                    if ($questionForm->isValid()) {
                        $translator = $this->get('translator');
                        $this->get('doctrine.orm.entity_manager')->flush($client);
                        $this->addFlash('securitySecretQuestionSuccess', $translator->trans('lender-profile_security-secret-question-section-form-success-message'));
                        $isValid = true;
                    }
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
                'securityPwd'      => $pwdForm->createView(),
                'securityQuestion' => $questionForm->createView()
            ]
        ];

        return $this->render('lender_profile/security.html.twig', $templateData);
    }

    /**
     * @Route("/profile/alertes", name="lender_profile_notifications")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @return Response
     */
    public function notificationsAction(): Response
    {
        if (false === in_array($this->getUser()->getClientStatus(), ClientsStatus::GRANTED_LENDER_ACCOUNT_READ)) {
            return $this->redirectToRoute('home');
        }

        $templateData = [
            'isCIPActive' => $this->isCIPActive()
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

        if (empty($notificationSetting)) {
            $this->get('unilend.service.notification_manager')->generateDefaultNotificationSettings($this->getUser()->getClientId());
            $notificationSetting  = $notificationSettings->getNotifs($this->getUser()->getClientId());
        }

        $templateData['notification_settings']['immediate'] = [
            \clients_gestion_type_notif::TYPE_NEW_PROJECT                   => $notificationSetting[\clients_gestion_type_notif::TYPE_NEW_PROJECT][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            \clients_gestion_type_notif::TYPE_BID_PLACED                    => $notificationSetting[\clients_gestion_type_notif::TYPE_BID_PLACED][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            \clients_gestion_type_notif::TYPE_BID_REJECTED                  => $notificationSetting[\clients_gestion_type_notif::TYPE_BID_REJECTED][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED                 => $notificationSetting[\clients_gestion_type_notif::TYPE_LOAN_ACCEPTED][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM               => $notificationSetting[\clients_gestion_type_notif::TYPE_PROJECT_PROBLEM][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            \clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID => $notificationSetting[\clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            \clients_gestion_type_notif::TYPE_REPAYMENT                     => $notificationSetting[\clients_gestion_type_notif::TYPE_REPAYMENT][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT          => $notificationSetting[\clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            \clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT            => $notificationSetting[\clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
            \clients_gestion_type_notif::TYPE_DEBIT                         => $notificationSetting[\clients_gestion_type_notif::TYPE_DEBIT][\clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE],
        ];

        $templateData['notification_settings']['daily'] = [
            \clients_gestion_type_notif::TYPE_NEW_PROJECT   => $notificationSetting[\clients_gestion_type_notif::TYPE_NEW_PROJECT][\clients_gestion_notifications::TYPE_NOTIFICATION_DAILY],
            \clients_gestion_type_notif::TYPE_BID_PLACED    => $notificationSetting[\clients_gestion_type_notif::TYPE_BID_PLACED][\clients_gestion_notifications::TYPE_NOTIFICATION_DAILY],
            \clients_gestion_type_notif::TYPE_BID_REJECTED  => $notificationSetting[\clients_gestion_type_notif::TYPE_BID_REJECTED][\clients_gestion_notifications::TYPE_NOTIFICATION_DAILY],
            \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED => $notificationSetting[\clients_gestion_type_notif::TYPE_LOAN_ACCEPTED][\clients_gestion_notifications::TYPE_NOTIFICATION_DAILY],
            \clients_gestion_type_notif::TYPE_REPAYMENT     => $notificationSetting[\clients_gestion_type_notif::TYPE_REPAYMENT][\clients_gestion_notifications::TYPE_NOTIFICATION_DAILY]
        ];

        $templateData['notification_settings']['weekly'] = [
            \clients_gestion_type_notif::TYPE_NEW_PROJECT   => $notificationSetting[\clients_gestion_type_notif::TYPE_NEW_PROJECT][\clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY],
            \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED => $notificationSetting[\clients_gestion_type_notif::TYPE_LOAN_ACCEPTED][\clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY],
            \clients_gestion_type_notif::TYPE_REPAYMENT     => $notificationSetting[\clients_gestion_type_notif::TYPE_REPAYMENT][\clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY]
        ];

        $templateData['notification_settings']['monthly'] = [
            \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED => $notificationSetting[\clients_gestion_type_notif::TYPE_LOAN_ACCEPTED][\clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY],
            \clients_gestion_type_notif::TYPE_REPAYMENT     => $notificationSetting[\clients_gestion_type_notif::TYPE_REPAYMENT][\clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY]
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
        $typeId        = $request->request->getInt('type_id');
        $active        = $request->request->getBoolean('active');
        $type          = null;

        /* Put it temporary here, because we don't need it after the project refectory notification  */
        $immediateTypes = [
            \clients_gestion_type_notif::TYPE_NEW_PROJECT,
            \clients_gestion_type_notif::TYPE_BID_PLACED,
            \clients_gestion_type_notif::TYPE_BID_REJECTED,
            \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED,
            \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM,
            \clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID,
            \clients_gestion_type_notif::TYPE_REPAYMENT,
            \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT,
            \clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT,
            \clients_gestion_type_notif::TYPE_DEBIT
        ];
        $dailyTypes     = [
            \clients_gestion_type_notif::TYPE_NEW_PROJECT,
            \clients_gestion_type_notif::TYPE_BID_PLACED,
            \clients_gestion_type_notif::TYPE_BID_REJECTED,
            \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED,
            \clients_gestion_type_notif::TYPE_REPAYMENT
        ];
        $weeklyTypes    = [
            \clients_gestion_type_notif::TYPE_NEW_PROJECT,
            \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED,
            \clients_gestion_type_notif::TYPE_REPAYMENT
        ];

        $monthlyTypes = [
            \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED,
            \clients_gestion_type_notif::TYPE_REPAYMENT
        ];

        $error = false;

        switch ($sendingPeriod) {
            case 'immediate':
                $type = \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE;
                if (false === in_array($typeId, $immediateTypes, true)) {
                    $error = true;
                }
                break;
            case 'daily':
                $type = \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY;
                if (false === in_array($typeId, $dailyTypes, true)) {
                    $error = true;
                }
                break;
            case 'weekly':
                $type = \clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY;
                if (false === in_array($typeId, $weeklyTypes, true)) {
                    $error = true;
                }
                break;
            case 'monthly':
                $type = \clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY;
                if (false === in_array($typeId, $monthlyTypes, true)) {
                    $error = true;
                }
                break;
            default:
                $error = true;
        }

        if (false === $error) {
            /** @var \clients_gestion_notifications $notificationSettings */
            $notificationSettings = $this->get('unilend.service.entity_manager')->getRepository('clients_gestion_notifications');
            $notificationSettings->get(['id_client' => $this->getUser()->getClientId(), 'id_notif' => $typeId]);
            $notificationSettings->$type = $active ? 1 : 0;
            $notificationSettings->update(['id_client' => $this->getUser()->getClientId(), 'id_notif' => $typeId]);

            return $this->json('ok');
        }

        return $this->json('ko');
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

        $translator    = $this->get('translator');
        $files         = $request->request->get('files', []);
        $uploadSuccess = [];
        $uploadError   = [];
        $client        = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());

        foreach ($request->files->all() as $fileName => $file) {
            if ($file instanceof UploadedFile && false === empty($files[$fileName])) {
                try {
                    $document = $this->upload($client, $files[$fileName], $file);

                    if (AttachmentType::RIB === $document->getType()->getId()) {
                        $form               = $request->request->get('form', ['bankAccount' => ['bic' => '', 'iban' => '']]);
                        $iban               = $form['bankAccount']['iban'];
                        $bic                = $form['bankAccount']['bic'];
                        $bankAccountManager = $this->get('unilend.service.bank_account_manager');
                        $bankAccountManager->saveBankInformation($client, $bic, $iban, $document);
                    }
                    $uploadSuccess[] = $translator->trans('projet_document-type-' . $request->request->get('files')[$fileName]);
                } catch (\Exception $exception) {
                    $uploadError[] = $translator->trans('projet_document-type-' . $request->request->get('files')[$fileName]);
                }
            }
        }
        if (empty($uploadError) && false === empty($uploadSuccess)) {
            $this->updateClientStatusAndNotifyClient($client, $uploadSuccess);
            $this->addFlash('completenessSuccess', $translator->trans('lender-profile_completeness-form-success-message'));
        } elseif (false === empty($uploadError)) {
            $this->addFlash('completenessError', $translator->trans('lender-profile_completeness-form-error-message'));
        }
        $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::LENDER_UPLOAD_FILES);

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
     * @param Clients $client
     * @param array   $modifiedData
     */
    private function updateClientStatusAndNotifyClient(Clients $client, array $modifiedData): void
    {
        $historyContent      = $this->formatArrayToUnorderedList($modifiedData);
        $clientStatusManager = $this->get('unilend.service.client_status_manager');
        $clientStatusManager->changeClientStatusTriggeredByClientAction($client, $historyContent);

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
     * @param BankAccount   $unattachedBankAccount
     * @param Clients       $unattachedClient
     * @param FormInterface $form
     * @param FileBag       $fileBag
     *
     * @return bool
     * @throws \Exception
     */
    private function handleBankDetailsForm(BankAccount $unattachedBankAccount, Clients $unattachedClient, FormInterface $form, FileBag $fileBag): bool
    {
        $translator               = $this->get('translator');
        $bankAccountModifications = [];
        $iban                     = $form->get('bankAccount')->get('iban')->getData();
        $bic                      = $form->get('bankAccount')->get('bic')->getData();
        $client                   = $form->get('client')->getData();
        $bankAccountDocument      = null;

        if ('FR' !== strtoupper(substr($iban, 0, 2))) {
            $form->get('bankAccount')->get('iban')->addError(new FormError($translator->trans('lender-subscription_documents-iban-not-french-error-message')));
        }

        if ($unattachedBankAccount->getIban() !== $iban || $unattachedBankAccount->getBic() !== $bic) {
            if ($unattachedBankAccount->getIban() !== $iban) {
                $bankAccountModifications[] = $translator->trans('lender-profile_fiscal-tab-bank-info-section-iban');
            }
            if ($unattachedBankAccount->getBic() !== $bic) {
                $bankAccountModifications[] = $translator->trans('lender-profile_fiscal-tab-bank-info-section-bic');
            }
            $file = $fileBag->get('iban-certificate');
            if (false === $file instanceof UploadedFile) {
               $form->get('bankAccount')->addError(new FormError($translator->trans('lender-profile_rib-file-mandatory')));
            } else {
               try {
                   $bankAccountDocument        = $this->upload($client, AttachmentType::RIB, $file);
                   $bankAccountModifications[] = $translator->trans('lender-profile_fiscal-tab-bank-info-section-documents');
               } catch (\Exception $exception) {
                   $form->addError(new FormError($translator->trans('lender-profile_fiscal-tab-rib-file-error')));
               }
           }
        }

        if ($form->isValid() && $bankAccountDocument) {
            $formManager         = $this->get('unilend.frontbundle.service.form_manager');
            $clientModifications = $formManager->getModifiedContent($unattachedClient, $client);
            $dataModifications   = array_merge($clientModifications, $bankAccountModifications);

            if (false === empty($dataModifications)) {
                $this->updateClientStatusAndNotifyClient($client, $dataModifications);
            }

            $bankAccountManager = $this->get('unilend.service.bank_account_manager');
            $bankAccountManager->saveBankInformation($client, $bic, $iban, $bankAccountDocument);
            $this->addFlash('bankInfoUpdateSuccess', $translator->trans('lender-profile_fiscal-tab-bank-info-update-ok'));

            return true;
        }

        return false;
    }

    /**
     * @param Clients $client
     *
     * @return array
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
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');

        switch ($clientType) {
            case Clients::TYPE_PERSON:
            case Clients::TYPE_PERSON_FOREIGNER:
                $settings->get('Liste deroulante origine des fonds', 'type');
                break;
            default:
                $settings->get('Liste deroulante origine des fonds societe', 'type');
                break;
        }
        $fundsOriginList = explode(';', $settings->value);

        return array_combine(range(1, count($fundsOriginList)), array_values($fundsOriginList));
    }

    /**
     * @param Clients       $unattachedClient
     * @param Clients       $client
     * @param FormInterface $form
     *
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function handleEmailForm(Clients $unattachedClient, Clients $client, FormInterface $form): bool
    {
        $translator    = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if ($client->getEmail() !== $unattachedClient->getEmail() && $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->existEmail($client->getEmail())) {
            $form->addError(new FormError($translator->trans('lender-profile_security-identification-error-existing-email')));
        }

        if ($form->isValid()) {
            $entityManager->flush($client);
            $this->addFlash('securityIdentificationSuccess', $translator->trans('lender-profile_security-identification-form-success-message'));

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
                    'method'           => __METHOD__,
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
     * @return \clients_adresses
     */
    private function getClientAddress(): \clients_adresses
    {
        /** @var UserLender $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();

        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->get('unilend.service.entity_manager')->getRepository('clients_adresses');
        $clientAddress->get($clientId, 'id_client');

        return $clientAddress;
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
     * @param ClientsAdresses|Companies $address
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function updateFiscalAndPostalAddress($address): void
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if ($address instanceof ClientsAdresses) {
            $address->setMemeAdresseFiscal(true);
            $address->setAdresse1($address->getAdresseFiscal());
            $address->setCp($address->getCpFiscal());
            $address->setVille($address->getVilleFiscal());
            $address->setIdPays($address->getIdPaysFiscal());
            $entityManager->flush($address);
        }

        if ($address instanceof Companies) {
            $clientAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $address->getIdClientOwner()]);

            $clientAddress->setMemeAdresseFiscal(true);
            $clientAddress->setAdresse1($address->getAdresse1());
            $clientAddress->setCp($address->getZip());
            $clientAddress->setVille($address->getCity());
            $clientAddress->setIdPays($address->getIdPays());
            $entityManager->flush($clientAddress);
        }
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
     * @param array $modifications
     *
     * @return string
     */
    private function formatArrayToUnorderedList(array $modifications): string
    {
        $list = '<ul>';

        foreach ($modifications as $modification) {
            $list .= '<li>' . $modification . '</li>';
        }

        $list .= '</ul>';

        return $list;
    }

    /**
     * @param null|CompanyAddress $address
     * @param string              $type
     *
     * @return FormInterface
     */
    private function getCompanyAddressForm(?CompanyAddress $address, string $type): FormInterface
    {
        $form = $this->get('form.factory')->createNamed($type, CompanyAddressType::class);

        if (null !== $address) {
            $form->get('address')->setData($address->getAddress());
            $form->get('zip')->setData($address->getZip());
            $form->get('city')->setData($address->getCity());
            $form->get('idCountry')->setData($address->getIdCountry()->getIdPays());
        }
        return $form;
    }
}
