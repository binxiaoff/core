<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsHistoryActions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\BankAccountType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\ClientEmailType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\ClientPasswordType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\LegalEntityProfileType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\OriginOfFundsType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PersonPhoneType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PersonProfileType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PostalAddressType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\CompanyAddressType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\CompanyIdentityType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PersonFiscalAddressType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\SecurityQuestionType;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\core\Loader;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LenderProfileController extends Controller
{
    /**
     * @Route("/profile", name="lender_profile")
     * @Route("/profile/info-perso", name="lender_profile_personal_information")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function personalInformationAction(Request $request)
    {
        $client        = $this->getClient();
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $client                  = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        $unattachedClient        = clone $client;
        $clientAddress           = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $client->getIdClient()]);
        $unattachedClientAddress = clone $clientAddress;

        $postalAddressForm = $this->createForm(PostalAddressType::class, $clientAddress);
        $phoneForm         = $this->createForm(PersonPhoneType::class, $client);

        if (in_array($client->getType(), [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
            $company           = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client->getIdClient()]);
            $unattachedCompany = clone $company;

            $identityFb = $this->createFormBuilder()
                ->add('client', LegalEntityProfileType::class, ['data' => $client])
                ->add('company', CompanyIdentityType::class, ['data' => $company]);
            $identityFb->get('company')->remove('siren');
            $fiscalAddressForm = $this->createForm(CompanyAddressType::class, $company);
        } else {
            $identityFb = $this->createFormBuilder()
                ->add('client', PersonProfileType::class, ['data' => $client]);
            $fiscalAddressForm = $this->createForm(PersonFiscalAddressType::class, $clientAddress);
        }

        $identityForm = $identityFb->getForm();

        if ($request->isMethod('POST')) {
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

            if (false === empty($request->request->get('company_address'))) {
                $fiscalAddressForm->handleRequest($request);

                if ($fiscalAddressForm->isSubmitted()) {
                    $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::LENDER_PROFILE_PERSONAL_INFORMATION);
                    if ($fiscalAddressForm->isValid()) {
                        $isValid = $this->handleCompanyFiscalAddress($unattachedCompany, $company, $fiscalAddressForm);
                    }
                }
            }

            if (false === empty($request->request->get('postal_address'))) {
                $postalAddressForm->handleRequest($request);

                if ($postalAddressForm->isSubmitted()) {
                    $this->saveClientHistoryAction($client, $request, ClientsHistoryActions::LENDER_PROFILE_PERSONAL_INFORMATION);
                    if ($postalAddressForm->isValid()) {
                        $isValid = $this->handlePostalAddressForm($clientAddress);
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
            'client'          => $client,
            'clientsAdresses' => $clientAddress,
            'company'         => isset($company) ? $company : null,
            'isCIPActive'     => $this->isCIPActive(),
            'forms'           => [
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

        return $this->render('pages/lender_profile/personal_information.html.twig', $templateData);
    }

    /**
     * @param Clients       $unattachedClient
     * @param Clients       $client
     * @param FormInterface $form
     * @param FileBag       $fileBag
     */
    private function handlePersonIdentity(Clients $unattachedClient, Clients $client, FormInterface $form, FileBag $fileBag)
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
                $this->updateClientStatusAndNotifyClient($this->getClient(), $modifiedData);
            }

            $this->redirectToRoute('lender_profile_personal_information');
        }
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
     */
    private function handleCompanyIdentity(Clients $unattachedClient, Clients $client, Companies $unattachedCompany, Companies $company, FormInterface $form, FileBag $fileBag)
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
            $this->addFiscalAddressToCompany($company);
            $this->get('doctrine.orm.entity_manager')->flush();
            $this->addFlash('identitySuccess', $translator->trans('lender-profile_information-tab-identity-section-files-update-success-message'));

            $formManager         = $this->get('unilend.frontbundle.service.form_manager');
            $modifiedDataClient  = $formManager->getModifiedContent($unattachedClient, $client);
            $modifiedDataCompany = $formManager->getModifiedContent($unattachedCompany, $company);
            $modifiedData        = array_merge($modifiedDataClient, $modifiedDataCompany, $modifications);
            if (false === empty($modifiedData)) {
                $this->updateClientStatusAndNotifyClient($this->getClient(), $modifiedData);
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
     */
    private function handlePersonFiscalAddress(ClientsAdresses $unattachedClientAddress, ClientsAdresses $clientAddress, FormInterface $form, FileBag $fileBag)
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $translator    = $this->get('translator');
        $client        = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientAddress->getIdClient());
        $modifications = [];

        if (
            $unattachedClientAddress->getCpFiscal() !== $clientAddress->getCpFiscal()
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
                $this->updateClientStatusAndNotifyClient($this->getClient(), array_merge($modifiedData, $modifications));
            }

            return true;
        }
        return false;
    }

    /**
     * @param Companies     $unattachedCompany
     * @param Companies     $company
     * @param FormInterface $form
     *
     * @return bool
     */
    private function handleCompanyFiscalAddress(Companies $unattachedCompany, Companies $company, FormInterface $form)
    {
        $translator    = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (
            $unattachedCompany->getZip() !== $company->getZip()
            && PaysV2::COUNTRY_FRANCE == $company->getIdPays()
            && null === $entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $company->getZip()])
        ) {
            $form->get('company_fiscal_address')->get('zip')->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-section-unknown-zip-code-error-message')));
        }

        if ($form->isValid()) {
            $this->addFiscalAddressToCompany($company);
            if (Companies::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL == $company->getStatusAdresseCorrespondance()) {
                $this->updateFiscalAndPostalAddress($company);
            }
            $entityManager->flush($company);

            $modifiedData = $this->get('unilend.frontbundle.service.form_manager')->getModifiedContent($unattachedCompany, $company);
            if (false === empty($modifiedData)) {
                $this->updateClientStatusAndNotifyClient($this->getClient(), $modifiedData);
            }
            $this->addFlash('fiscalAddressSuccess', $translator->trans('lender-profile_information-tab-fiscal-address-form-success-message'));

            return true;
        }
        return false;
    }

    /**
     * @param ClientsAdresses $clientAddress
     *
     * @return bool
     */
    private function handlePostalAddressForm(ClientsAdresses $clientAddress)
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
     */
    public function fiscalInformationAction(Request $request)
    {
        /** @var \clients $client */
        $client = $this->getClient();
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount  = $this->getLenderAccount();
        $clientEntity   = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        $unattachedClientEntity = clone $clientEntity;
        $bankAccount            = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($clientEntity);

        $form = $this->createFormBuilder()
            ->add('client', OriginOfFundsType::class, ['data' => $clientEntity])
            ->add('bankAccount', BankAccountType::class)
            ->getForm();

        $form->get('bankAccount')->get('iban')->setData($bankAccount->getIban());
        $form->get('bankAccount')->get('bic')->setData($bankAccount->getBic());

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->saveClientHistoryAction($clientEntity, $request, ClientsHistoryActions::LENDER_PROFILE_BANK_INFORMATION);
            if ($form->isValid()) {
                $isValid = $this->handleBankDetailsForm($bankAccount, $unattachedClientEntity, $form, $request->files);
                if ($isValid) {
                    return $this->redirectToRoute('lender_profile_fiscal_information');
                }
            }
        }

        /** @var \ifu $ifu */
        $ifu          = $this->get('unilend.service.entity_manager')->getRepository('ifu');
        $templateData = [
            'client'      => $clientEntity,
            'bankAccount' => $bankAccount,
            'isCIPActive' => $this->isCIPActive(),
            'bankForm'    => $form->createView(),
            'lenderAccount' => [
                'fiscal_info' => [
                    'documents'   => $ifu->select('id_client =' . $client->id_client . ' AND statut = 1', 'annee ASC'),
                    'amounts'     => $this->getFiscalBalanceAndOwedCapital(),
                    'rib'         => $bankAccount->getAttachment(),
                    'fundsOrigin' => $this->getFundsOrigin($clientEntity->getType())
                ]
            ]
        ];

        if (in_array($client->type, [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
            /** @var \clients_adresses $clientAddress */
            $clientAddress = $this->getClientAddress();
            /** @var \lender_tax_exemption $lenderTaxExemption */
            $lenderTaxExemption = $this->get('unilend.service.entity_manager')->getRepository('lender_tax_exemption');
            /** @var \tax_type $taxType */
            $taxType = $this->get('unilend.service.entity_manager')->getRepository('tax_type');
            $taxType->get(\tax_type::TYPE_INCOME_TAX);
            $templateData['clientAddress']                = $clientAddress->select('id_client = ' . $client->id_client)[0];
            $templateData['currentYear']                  = date('Y');
            $templateData['lastYear']                     = $templateData['currentYear'] - 1;
            $templateData['nextYear']                     = $templateData['currentYear'] + 1;
            $taxExemptionDateRange                        = $this->getTaxExemptionDateRange();
            $templateData['taxExemptionRequestLimitDate'] = strftime('%d %B %Y', $taxExemptionDateRange['taxExemptionRequestLimitDate']->getTimestamp());
            $templateData['rateOfTaxDeductionAtSource']   = $taxType->rate;
            $taxExemptionHistory                          = $this->getExemptionHistory($lenderTaxExemption, $lenderAccount);
            $templateData['exemptions']                   = $taxExemptionHistory;
            $isEligible                                   = $this->getTaxExemptionEligibility($lenderAccount);
            $templateData['taxExemptionEligibility']      = $isEligible;
            $templateData['declarationIsPossible']        = $this->checkIfTaxExemptionIsPossible($taxExemptionHistory, $taxExemptionDateRange, $isEligible);
        }

        return $this->render('pages/lender_profile/fiscal_information.html.twig', $templateData);
    }

    /**
     * @Route("/profile/securite", name="lender_profile_security")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function securityAction(Request $request)
    {
        /** @var \clients $client */
        $client = $this->getClient();
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->getLenderAccount();

        $clientEntity           = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        $unattachedClientEntity = clone $clientEntity;
        $emailForm              = $this->createForm(ClientEmailType::class, $clientEntity);
        $pwdForm                = $this->createForm(ClientPasswordType::class);
        $questionForm           = $this->createForm(SecurityQuestionType::class, $clientEntity);

        if ($request->isMethod('POST')) {
            $isValid = null;
            if (false === empty($request->request->get('client_email'))) {
                $emailForm->handleRequest($request);

                if ($emailForm->isSubmitted()) {
                    $this->saveClientHistoryAction($clientEntity, $request, ClientsHistoryActions::LENDER_PROFILE_PERSONAL_INFORMATION);

                    if ($emailForm->isValid()) {
                        $isValid = $this->handleEmailForm($unattachedClientEntity, $clientEntity, $emailForm);
                    }
                }
            }

            if (false === empty($request->request->get('client_password'))) {
                $pwdForm->handleRequest($request);

                if ($pwdForm->isSubmitted()) {
                    $this->saveClientHistoryAction($clientEntity, $request, ClientsHistoryActions::CHANGE_PASSWORD);
                    if ($pwdForm->isValid()) {
                        $isValid = $this->handlePasswordForm($clientEntity, $pwdForm);
                    }
                }
            }

            if (false === empty($request->request->get('security_question'))) {
                $questionForm->handleRequest($request);

                if ($questionForm->isSubmitted()) {
                    $this->saveClientHistoryAction($clientEntity, $request, ClientsHistoryActions::LENDER_PROFILE_SECURITY_QUESTION);
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
            'client'        => $clientEntity,
            'lenderAccount' => $lenderAccount->select('id_lender_account = ' . $lenderAccount->id_lender_account)[0],
            'isCIPActive'   => $this->isCIPActive(),
            'forms'         => [
                'securityEmail'    => $emailForm->createView(),
                'securityPwd'      => $pwdForm->createView(),
                'securityQuestion' => $questionForm->createView()
            ]
        ];

        return $this->render('pages/lender_profile/security.html.twig', $templateData);
    }

    /**
     * @Route("/profile/alertes", name="lender_profile_notifications")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function notificationsAction()
    {
        /** @var \clients $client */
        $client = $this->getClient();
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->getLenderAccount();

        $templateData = [
            'client'        => $client->select('id_client = ' . $client->id_client)[0],
            'lenderAccount' => $lenderAccount->select('id_lender_account = ' . $lenderAccount->id_lender_account)[0],
            'isCIPActive'   => $this->isCIPActive()
        ];

        $this->addNotificationSettingsTemplate($templateData, $client);

        return $this->render('pages/lender_profile/notifications.html.twig', $templateData);
    }

    /**
     * @param array    $templateData
     * @param \clients $client
     */
    private function addNotificationSettingsTemplate(&$templateData, \clients $client)
    {
        /** @var \clients_gestion_notifications $notificationSettings */
        $notificationSettings = $this->get('unilend.service.entity_manager')->getRepository('clients_gestion_notifications');
        $notificationSetting  = $notificationSettings->getNotifs($client->id_client);

        if (empty($notificationSetting)) {
            $this->get('unilend.service.notification_manager')->generateDefaultNotificationSettings($client);
            $notificationSetting  = $notificationSettings->getNotifs($client->id_client);
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
     */
    public function updateNotificationAction(Request $request)
    {
        $sendingPeriod = $request->request->get('period');
        $typeId        = $request->request->get('type_id');
        $active        = $request->request->get('active');
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

        $error        = false;
        switch ($sendingPeriod) {
            case 'immediate' :
                $type = \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE;
                if (false === in_array($typeId, $immediateTypes)) {
                    $error = true;
                }
                break;
            case 'daily' :
                $type = \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY;
                if (false === in_array($typeId, $dailyTypes)) {
                    $error = true;
                }
                break;
            case 'weekly' :
                $type = \clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY;
                if (false === in_array($typeId, $weeklyTypes)) {
                    $error = true;
                }
                break;
            case 'monthly' :
                $type = \clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY;
                if (false === in_array($typeId, $monthlyTypes)) {
                    $error = true;
                }
                break;
            default:
                $error = true;
        }

        if (false === $error) {
            /** @var \clients_gestion_notifications $notificationSettings */
            $notificationSettings = $this->get('unilend.service.entity_manager')->getRepository('clients_gestion_notifications');
            $client               = $this->getClient();

            $notificationSettings->get(['id_client' => $client->id_client, 'id_notif' => $typeId]);
            $notificationSettings->$type = $active === 'true' ? 1 : 0;
            $notificationSettings->update(['id_client' => $client->id_client, 'id_notif' => $typeId]);
            return $this->json('ok');
        }
        return $this->json('ko');
    }

    /**
     * @Route("/profile/documents", name="lender_completeness")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function lenderCompletenessAction()
    {
        $entityManager     = $this->get('unilend.service.entity_manager');
        $attachmentManager = $this->get('unilend.service.attachment_manager');

        /** @var \clients $client */
        $client = $this->getClient();
        /** @var \clients_status_history $clientStatusHistory */
        $clientStatusHistory = $entityManager->getRepository('clients_status_history');

        $completenessRequestContent  = $clientStatusHistory->getCompletnessRequestContent($client);
        $template['attachmentTypes'] = $attachmentManager->getAllTypesForLender();
        $template['attachmentsList'] = '';

        if (false === empty($completenessRequestContent)) {
            $oDOMElement = new \DOMDocument();
            $oDOMElement->loadHTML($completenessRequestContent);
            $oList = $oDOMElement->getElementsByTagName('ul');
            if ($oList->length > 0 && $oList->item(0)->childNodes->length > 0) {
                $template['attachmentsList'] = $oList->item(0)->C14N();
            }
        }

        return $this->render('pages/lender_profile/lender_completeness.html.twig', $template);
    }

    /**
     * @Route("/profile/documents/submit", name="lender_completeness_submit")
     * @Method("POST")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function lenderCompletenessFormAction(Request $request)
    {
        /** @var \clients $client */
        $client        = $this->getClient();
        $translator    = $this->get('translator');
        $files         = $request->request->get('files', []);
        $uploadSuccess = [];
        $uploadError   = [];
        $clientEntity  = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        foreach ($request->files->all() as $fileName => $file) {
            if ($file instanceof UploadedFile && false === empty($files[$fileName])) {
                try {
                    $this->upload($clientEntity, $files[$fileName], $file);
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
        $this->saveClientHistoryAction($clientEntity, $request, ClientsHistoryActions::LENDER_UPLOAD_FILES);

        return $this->redirectToRoute('lender_completeness');
    }

    /**
     * @param Clients      $client
     * @param integer      $attachmentTypeId
     * @param UploadedFile $file
     *
     * @return Attachment
     * @throws \Exception
     */
    private function upload(Clients $client, $attachmentTypeId, UploadedFile $file)
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
     */
    private function saveClientHistoryAction(Clients $client, Request $request, $formName)
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
     * @param \clients $client
     * @param array    $modifiedData
     */
    private function updateClientStatusAndNotifyClient(\clients $client, $modifiedData)
    {
        $historyContent = $this->formatArrayToUnorderedList($modifiedData);

        /** @var ClientStatusManager $clientStatusManager */
        $clientStatusManager = $this->get('unilend.service.client_status_manager');
        $clientStatusManager->changeClientStatusTriggeredByClientAction($client, $historyContent);
        $this->sendAccountModificationEmail($client);
    }

    /**
     * @Route("/profile/ajax/zip", name="lender_profile_ajax_zip")
     * @Method("GET")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function getZipAction(Request $request)
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
     * @return Response
     */
    public function downloadIFUAction(Request $request)
    {
        /** @var \ifu $ifu */
        $ifu = $this->get('unilend.service.entity_manager')->getRepository('ifu');
        /** @var \clients $client */
        $client = $this->getClient();
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        if ($client->hash == $request->query->get('hash')) {

            if ($ifu->get($this->getUser()->getClientId(), 'annee = ' . $request->query->get('year') . ' AND statut = 1 AND id_client') &&
                file_exists($this->get('kernel')->getRootDir() . '/../' . $ifu->chemin)
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
     */
    private function handleBankDetailsForm(BankAccount $unattachedBankAccount, Clients $unattachedClient, FormInterface $form, FileBag $fileBag)
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
            $formManager              = $this->get('unilend.frontbundle.service.form_manager');
            $clientModifications      = $formManager->getModifiedContent($unattachedClient, $client);
            $dataModifications        = array_merge($clientModifications, $bankAccountModifications);
            if (false === empty($dataModifications)) {
                $this->updateClientStatusAndNotifyClient($this->getClient(), $dataModifications);
            }

            $bankAccountManager = $this->get('unilend.service.bank_account_manager');
            $bankAccountManager->saveBankInformation($client, $bic, $iban, $bankAccountDocument);
            $this->addFlash('bankInfoUpdateSuccess', $translator->trans('lender-profile_fiscal-tab-bank-info-update-ok'));
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    private function getFiscalBalanceAndOwedCapital()
    {
        /** @var \indexage_vos_operations $indexageVosOperations */
        $indexageVosOperations = $this->get('unilend.service.entity_manager')->getRepository('indexage_vos_operations');
        /** @var \projects_status_history $projectsStatusHistory */
        $projectsStatusHistory = $this->get('unilend.service.entity_manager')->getRepository('projects_status_history');
        /** @var \echeanciers $echeancier */
        $echeancier = $this->get('unilend.service.entity_manager')->getRepository('echeanciers');

        $projects_en_remboursement = $projectsStatusHistory->select('id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ') AND added < "' . date('Y') . '-01-01 00:00:00"');

        return [
            'balance'     => $indexageVosOperations->getFiscalBalanceToDeclare($this->getUser()->getClientId(), date('Y')),
            'owedCapital' => $echeancier->getLenderOwedCapital($this->getUser()->getClientId(), date('Y'), array_column($projects_en_remboursement, 'id_project'))
        ];
    }

    /**
     * @param int $clientType
     * @return array
     */
    private function getFundsOrigin($clientType)
    {
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');

        switch ($clientType) {
            case Clients::TYPE_PERSON:
            case Clients::TYPE_PERSON_FOREIGNER:
                $settings->get("Liste deroulante origine des fonds", 'type');
                break;
            default:
                $settings->get("Liste deroulante origine des fonds societe", 'type');
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
     */
    private function handleEmailForm(Clients $unattachedClient, Clients $client, FormInterface $form)
    {
        $translator = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (
            $client->getEmail() !== $unattachedClient->getEmail()
            && $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->existEmail($client->getEmail())
        ) {
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
     */
    public function handlePasswordForm(Clients $client, FormInterface $form)
    {
        $translator = $this->get('translator');
        $securityPasswordEncoder = $this->get('security.password_encoder');
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        if (false === $securityPasswordEncoder->isPasswordValid($this->getUser(), $form->get('formerPassword')->getData())) {
            $form->get('formerPassword')->addError(new FormError($translator->trans('lender-profile_security-password-section-error-wrong-former-password')));
        }
        if (false === $ficelle->password_fo($form->get('password')->getData(), 6)) {
            $form->get('password')->addError(new FormError($translator->trans('common-validator_password-invalid')));
        }

        if ($form->isValid()) {
            $client->setPassword($securityPasswordEncoder->encodePassword($this->getUser(), $form->get('password')->getData()));
            $entityManager->flush($client);

            $this->sendPasswordModificationEmail($this->getClient());
            $this->addFlash('securityPasswordSuccess', $translator->trans('lender-profile_security-password-section-form-success-message'));

            return true;
        }
        return false;
    }

    /**
     * @param \clients $client
     */
    private function sendPasswordModificationEmail(\clients $client)
    {
        $varMail = array_merge($this->getCommonEmailVariables(), [
            'login'    => $client->email,
            'prenom_p' => $client->prenom,
            'mdp'      => ''
        ]);

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('generation-mot-de-passe', $varMail);
        $message->setTo($client->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    /**
     * @param \clients $client
     */
    private function sendAccountModificationEmail(\clients $client)
    {
        $varMail = array_merge($this->getCommonEmailVariables(), [
            'prenom' => $client->prenom,
        ]);

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-modification-compte', $varMail);
        $message->setTo($client->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    private function getCommonEmailVariables()
    {
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Facebook', 'type');
        $fbLink = $settings->value;
        $settings->get('Twitter', 'type');
        $twLink = $settings->value;

        $varMail = [
            'surl'    => $this->get('assets.packages')->getUrl(''),
            'url'     => $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_default'),
            'lien_fb' => $fbLink,
            'lien_tw' => $twLink
        ];

        return $varMail;
    }

    private function getClient()
    {
        /** @var UserLender $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($clientId);

        return $client;
    }

    private function getLenderAccount()
    {
        /** @var UserLender $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lenderAccount->get($clientId, 'id_client_owner');

        return $lenderAccount;
    }

    private function getClientAddress()
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
    private function isCIPActive()
    {
        /** @var \lender_evaluation_log $evaluationLog */
        $evaluationLog = $this->get('unilend.service.entity_manager')->getRepository('lender_evaluation_log');
        $lender        = $this->getLenderAccount();

        return $evaluationLog->hasLenderLog($lender);
    }

    /**
     * @Route("/profile/request-tax-exemption", name="profile_fiscal_information_tax_exemption")
     * @Method("POST")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @return Response
     */
    public function requestTaxExemptionAction(Request $request)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var \lenders_accounts $lender */
        $lender = $this->getLenderAccount();
        /** @var \lender_tax_exemption $lenderTaxExemption */
        $lenderTaxExemption = $this->get('unilend.service.entity_manager')->getRepository('lender_tax_exemption');
        $year               = date('Y') + 1;

        $post = $request->request->all();

        try {
            $taxExemptionDateRange = $this->getTaxExemptionDateRange();
            /** @var \DateTime $now */
            $now = new \DateTime();

            if ($now >= $taxExemptionDateRange['taxExemptionRequestStartDate'] && $now <= $taxExemptionDateRange['taxExemptionRequestLimitDate']
                && true === empty($lenderTaxExemption->getLenderExemptionHistory($lender->id_lender_account, $year))
            ) {

                if (true === isset($post['agree']) && true === isset($post['attest'])
                    && 'agree-to-be-informed' === $post['agree'] && 'honor-attest' === $post['attest']
                ) {
                    $lenderTaxExemption->id_lender   = $lender->id_lender_account;
                    $lenderTaxExemption->iso_country = 'FR';
                    $lenderTaxExemption->year        = $year;
                    $lenderTaxExemption->create();

                    if (false === empty($lenderTaxExemption->id_lender_tax_exemption)) {
                        $this->addFlash('exonerationSuccess', $translator->trans('lender-profile_fiscal-information-exoneration-validation-success'));
                        $logger->info('The lender (id_lender=' . $lender->id_lender_account . ') requested to be exempted for the year: ' . $year,
                            ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $lender->id_lender_account]);
                    } else {
                        $this->addFlash('exonerationError', $translator->trans('lender-profile_fiscal-information-exoneration-validation-error'));
                        $logger->info('The tax exemption request was not processed for the lender: (id_lender=' . $lender->id_lender_account . ') for the year: ' . $year,
                            ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $lender->id_lender_account]);
                    }
                }
            } else {
                $this->addFlash('exonerationError', $translator->trans('lender-profile_fiscal-information-exoneration-validation-error'));
                $logger->info('The tax exemption request was not processed for the lender: (id_lender=' . $lender->id_lender_account . ') for the year: ' . $year .
                    '. Lender already exempted',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $lender->id_lender_account]);
            }
        } catch (\Exception $exception) {
            $this->addFlash('exonerationError', $translator->trans('lender-profile_fiscal-information-exoneration-validation-error'));
            $logger->error('Could not register lender tax exemption request for the lender: (id_lender=' . $lender->id_lender_account . ') for the year: ' . $year .
                ' Exception message : ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $lender->id_lender_account]);
        }

        return $this->redirectToRoute('lender_profile_fiscal_information');
    }

    /**
     * Returns true if the declaration is possible, false otherwise
     * @param array $taxExemptionHistory
     * @param array $taxExemptionDateRange
     * @param bool $isEligible
     * @return bool
     */
    private function checkIfTaxExemptionIsPossible(array $taxExemptionHistory, array $taxExemptionDateRange, $isEligible)
    {
        /** @var \DateTime $now */
        $now       = new \DateTime();
        $outOfDate = $now < $taxExemptionDateRange['taxExemptionRequestStartDate'] || $now > $taxExemptionDateRange['taxExemptionRequestLimitDate'];

        if (false === empty($taxExemptionHistory)) {
            $taxExemptionRequestDone = in_array(date('Y') + 1, array_column($taxExemptionHistory, 'year'));
        } else {
            $taxExemptionRequestDone = false;
        }

        return (true === $isEligible && false === $outOfDate && false === $taxExemptionRequestDone);
    }

    /**
     * @param \lenders_accounts $lenderAccount
     * @return bool
     */
    private function getTaxExemptionEligibility(\lenders_accounts $lenderAccount)
    {
        try {
            $lenderInfo = $lenderAccount->getLenderTypeAndFiscalResidence($lenderAccount->id_lender_account);
            if (false === empty($lenderInfo)) {
                $isEligible = 'fr' === $lenderInfo['fiscal_address'] && 'person' === $lenderInfo['client_type'];
            } else {
                $isEligible = false;
            }
        } catch (\Exception $exception) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->info('Could not get lender info to check tax exemption eligibility. (id_lender=' . $lenderAccount->id_lender_account . ') Error message: ' .
                $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $lenderAccount->id_lender_account]);
            $isEligible = false;
        }

        return $isEligible;
    }

    /**
     * @param \lender_tax_exemption $lenderTaxExemption
     * @param \lenders_accounts $lenderAccount
     * @param string|null $year
     * @return array
     */
    private function getExemptionHistory(\lender_tax_exemption $lenderTaxExemption, \lenders_accounts $lenderAccount, $year = null)
    {
        try {
            $result = $lenderTaxExemption->getLenderExemptionHistory($lenderAccount->id_lender_account, $year);
        } catch (\Exception $exception) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->error('Could not get lender exemption history (id_lender = ' . $lenderAccount->id_lender_account . ') Exception message : ' . $exception->getMessage(), array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $lenderAccount->id_lender_account));
            $result = [];
        }
        return $result;
    }

    /**
     * @param object $address
     */
    private function updateFiscalAndPostalAddress($address)
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
    private function getTaxExemptionDateRange()
    {
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
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
    private function formatArrayToUnorderedList(array $modifications)
    {
        $list = '<ul>';

        foreach($modifications as $modification) {
            $list .= '<li>' . $modification . '</li>';
        }

        $list .= '</ul>';

        return $list;
    }

    /**
     * @param Companies $company
     */
    private function addFiscalAddressToCompany(Companies $company)
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $clientAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $company->getIdClientOwner()]);
        $clientAddress->setAdresseFiscal($company->getAdresse1());
        $clientAddress->setCpFiscal($company->getZip());
        $clientAddress->setVilleFiscal($company->getCity());
        $clientAddress->setIdPaysFiscal($company->getIdPays());

        $entityManager->flush($clientAddress);
    }
}
