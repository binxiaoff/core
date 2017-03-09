<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\BankAccountType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\ClientEmailType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\ClientPasswordType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\OriginOfFundsType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PersonPhoneType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PersonProfileType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PostalAddressType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\CompanyAddressType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\CompanyIdentityType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PersonFiscalAddressType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\LegalEntityType;
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
        $client = $this->getClient();
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $client          = $em->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        $dbClient        = clone $client;
        $clientAddress   = $em->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $client->getIdClient()]);
        $dbClientAddress = clone $clientAddress;

        $postalAddressForm = $this->createForm(PostalAddressType::class, $clientAddress);
        $phoneForm         = $this->createForm(PersonPhoneType::class, $client);

        if (in_array($client->getType(), [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
            $company   = $em->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client->getIdClient()]);
            $dbCompany = clone $company;

            $identityFb      = $this->createFormBuilder()
                ->add('client', LegalEntityType::class, ['data' => $client])
                ->add('company', CompanyIdentityType::class, ['data' => $company]);
            $fiscalAddressForm = $this->createForm(CompanyAddressType::class, $company);
        } else {
            $identityFb      = $this->createFormBuilder()
                ->add('client', PersonProfileType::class, ['data' => $client]);
            $fiscalAddressForm = $this->createForm(PersonFiscalAddressType::class, $clientAddress);
        }

        $identityForm      = $identityFb->getForm();

        if ($request->isMethod('POST')) {
            if (isset($request->request->get('form')['client'])) {
                $identityForm->handleRequest($request);

                if ($identityForm->isSubmitted()) {
                    $this->saveClientHistoryAction($client, $request, 'info perso profile');
                    if ($identityForm->isValid()) {
                        if (isset($request->request->get('form')['company'])) {
                            $this->handleCompanyIdentity($dbClient, $client, $dbCompany, $company, $identityForm);
                        } else {
                            $this->handlePersonIdentity($dbClient, $client, $identityForm);
                        }
                    }
                }
            }

            if (false === empty($request->request->get('person_fiscal_address'))) {
                $fiscalAddressForm->handleRequest($request);

                if ($fiscalAddressForm->isSubmitted()) {
                    $this->saveClientHistoryAction($client, $request, 'info perso profile');
                    if ($fiscalAddressForm->isValid()) {
                        $this->handlePersonFiscalAddress($dbClientAddress, $clientAddress, $fiscalAddressForm);
                    }
                }
            }
            if (false === empty($request->request->get('company_address'))) {
                $fiscalAddressForm->handleRequest($request);

                if ($fiscalAddressForm->isSubmitted()) {
                    $this->saveClientHistoryAction($client, $request, 'info perso profile');
                    if ($fiscalAddressForm->isValid()) {
                        $this->handleCompanyFiscalAddress($dbCompany, $company, $fiscalAddressForm);
                    }
                }
            }

            if (false === empty($request->request->get('postal_address'))) {
                $postalAddressForm->handleRequest($request);

                if ($postalAddressForm->isSubmitted()) {
                    $this->saveClientHistoryAction($client, $request, 'info perso profile');
                    if ($postalAddressForm->isValid()) {
                        $this->handlePostalAddressForm($clientAddress);
                    }
                }
            }

            if (false === empty($request->request->get('person_phone'))) {
                $phoneForm->handleRequest($request);
                $this->saveClientHistoryAction($client, $request, 'info perso profile');
                if ($phoneForm->isValid()) {
                    $this->handlePhoneForm($client);
                }
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

            'isLivingAbroad' => ($clientAddress->getIdPaysFiscal() > \pays_v2::COUNTRY_FRANCE)
        ];

        $lenderAccount                       = $this->getLenderAccount();
        $setting                             = $em->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Liste deroulante conseil externe de l\'entreprise']);
        $templateData['externalCounselList'] = json_decode($setting->getValue(), true);

        if (false === empty($company)) {
            $templateData['companyIdAttachments']    = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [
                \attachment_type::CNI_PASSPORTE_DIRIGEANT,
                \attachment_type::CNI_PASSPORTE_VERSO
            ]);
            $templateData['companyOtherAttachments'] = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [
                \attachment_type::KBIS,
                \attachment_type::DELEGATION_POUVOIR
            ]);
        } else {
            $templateData['identityAttachments']  = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [
                \attachment_type::CNI_PASSPORTE,
                \attachment_type::CNI_PASSPORTE_VERSO
            ]);
            $templateData['residenceAttachments'] = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [
                \attachment_type::JUSTIFICATIF_DOMICILE,
                \attachment_type::ATTESTATION_HEBERGEMENT_TIERS,
                \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT,
                \attachment_type::JUSTIFICATIF_FISCAL
            ]);
        }

        return $this->render('pages/lender_profile/personal_information.html.twig', $templateData);
    }

    /**
     * @param Clients       $dbClientEntity
     * @param Clients       $clientEntity
     * @param FormInterface $form
     */
    private function handlePersonIdentity(Clients $dbClientEntity, Clients $clientEntity, FormInterface $form)
    {
        /** @var TranslatorInterface $translator */
        $translator    = $this->get('translator');
        $lenderAccount = $this->getLenderAccount();
        $modifications = [];

        if ($dbClientEntity->getIdNationalite() !== $clientEntity->getIdNationalite()) {
            $modifications[] = $translator->trans('common_nationality');
        }

        if ($dbClientEntity->getCivilite() !== $clientEntity->getCivilite()) {
            $modifications[] = $translator->trans('common_title');
        }

        if (isset($_FILES['id_recto'])  && false === empty($_FILES['id_recto']['name'])) {
            $attachmentIdRecto = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE, 'id_recto');
            if (false === is_numeric($attachmentIdRecto)) {
                $form->get('client')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));
            } else {
                 $modifications[] = $translator->trans('projet_document-type-' . \attachment_type::CNI_PASSPORTE);
            }
        }
        if (isset($_FILES['id_verso']) && false === empty($_FILES['id_verso']['name'])) {
            $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE_VERSO, 'id_verso');
            if (false === is_numeric($attachmentIdVerso)) {
                $form->get('client')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));
            } else {
                $modifications[] = $translator->trans('projet_document-type-' . \attachment_type::CNI_PASSPORTE_VERSO);
            }
        }

        if (false === isset($attachmentIdRecto)
            &&  ($dbClientEntity->getIdNationalite() !== $clientEntity->getIdNationalite()
                || $dbClientEntity->getCivilite() !== $clientEntity->getCivilite())
        ) {
            $form->get('client')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-change-ID-warning-message')));
        }

        if ($form->isValid()) {
            $this->addFlash('identitySuccess', $translator->trans('lender-profile_information-tab-identity-section-files-update-success-message'));

            /** @var EntityManager $em */
            $em = $this->get('doctrine.orm.entity_manager');

            $modifiedData = array_merge($modifications, $this->get('unilend.frontbundle.service.form_manager')->getModifiedContent($dbClientEntity, $clientEntity));
            if (false === empty($modifiedData)) {
                $this->updateClientStatusAndNotifyClient($this->getClient(), $modifiedData);
            }

            $em->persist($clientEntity);
            $em->flush();

            $this->redirectToRoute('lender_profile_personal_information');
        }
    }

    /**
     * @param Clients       $dbClientEntity
     * @param Clients       $clientEntity
     * @param Companies     $dbCompanyEntity
     * @param Companies     $companyEntity
     * @param FormInterface $form
     *
     * @return RedirectResponse
     */
    private function handleCompanyIdentity(Clients $dbClientEntity, Clients $clientEntity, Companies $dbCompanyEntity, Companies $companyEntity, FormInterface $form)
    {
        /** @var TranslatorInterface $translator */
        $translator    = $this->get('translator');
        $lenderAccount = $this->getLenderAccount();
        $modifications = [];

        if ($companyEntity->getStatusClient() > Companies::CLIENT_STATUS_MANAGER) {
            if (empty($companyEntity->getStatusConseilExterneEntreprise())) {
                $form->get('company')->get('statusConseilExterneEntreprise')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-external-counsel-error-message')));
            }
            if (
                Companies::CLIENT_STATUS_EXTERNAL_COUNSEL_OTHER == $companyEntity->getStatusConseilExterneEntreprise()
                && empty($companyEntity->getPreciserConseilExterneEntreprise())
            ) {
                $form->get('company')->get('preciserConseilExterneEntreprise')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-external-counsel-error-message')));
            }

            if (empty($companyEntity->getCiviliteDirigeant())) {
                $form->get('company')->get('civiliteDirigeant')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-director-form-of-address-missing')));
            }

            if (empty($companyEntity->getNomDirigeant())) {
                $form->get('company')->get('nomDirigeant')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-director-name-missing')));
            }

            if (empty($companyEntity->getPrenomDirigeant())) {
                $form->get('company')->get('prenomDirigeant')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-director-first-name-missing')));
            }

            if (empty($companyEntity->getFonctionDirigeant())) {
                $form->get('company')->get('fonctionDirigeant')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-director-position-missing')));
            }

            if (empty($companyEntity->getPhoneDirigeant())) {
                $form->get('company')->get('phoneDirigeant')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-company-director-phone-missing')));
            }

            if (empty($companyEntity->getEmailDirigeant())) {
                $form->get('company')->get('emailDirigeant')->addError(new FormError($translator->trans('common-validator_email-address-invalid')));
            }
        }

        if (isset($_FILES['id_recto']) && $_FILES['id_recto']['name'] != '') {
            $attachmentIdRecto = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE_DIRIGEANT, 'id_recto');
            if (false === is_numeric($attachmentIdRecto)) {
                $form->get('company')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));
            } else {
                $modifications[] = $translator->trans('projet_document-type-' . \attachment_type::CNI_PASSPORTE_DIRIGEANT);
            }
        }

        if (isset($_FILES['id_verso']) && $_FILES['id_verso']['name'] != '') {
            $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE_VERSO, 'id_verso');
            if (false === is_numeric($attachmentIdVerso)) {
                $form->get('company')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));
            } else {
                $modifications[] = $translator->trans('projet_document-type-' . \attachment_type::CNI_PASSPORTE_VERSO);
            }
        }

        if (isset($_FILES['company-registration']) && $_FILES['company-registration']['name'] != '') {
            $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::KBIS, 'company-registration');
            if (false === is_numeric($attachmentIdVerso)) {
                $form->get('company')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));
            } else {
                $modifications[] = $translator->trans('projet_document-type-' . \attachment_type::KBIS);
            }
        }

        if ($form['company_client_status'] > Companies::CLIENT_STATUS_MANAGER) {
            if (isset($_FILES['delegation-of-authority']) && $_FILES['delegation-of-authority']['name'] != '') {
                $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::DELEGATION_POUVOIR, 'delegation-of-authority');
                if (false === is_numeric($attachmentIdVerso)) {
                    $form->get('company')->addError(new FormError($translator->trans('lender-profile_information-tab-identity-section-upload-files-error-message')));
                } else {
                    $modifications[] = $translator->trans('projet_document-type-' . \attachment_type::DELEGATION_POUVOIR);
                }
            }
        }

        if ($form->isValid()) {
            $this->addFlash('identitySuccess', $translator->trans('lender-profile_information-tab-identity-section-files-update-success-message'));

            $formManager         = $this->get('unilend.frontbundle.service.form_manager');
            $modifiedDataClient  = $formManager->getModifiedContent($dbClientEntity, $clientEntity);
            $modifiedDataCompany = $formManager->getModifiedContent($dbCompanyEntity, $companyEntity);
            $modifiedData        = array_merge($modifiedDataClient, $modifiedDataCompany, $modifications);
            if (false === empty($modifiedData)) {
                $this->updateClientStatusAndNotifyClient($this->getClient(), $modifiedData);
            }

            /** @var EntityManager $em */
            $em = $this->get('doctrine.orm.entity_manager');
            $this->addFiscalAddressToCompany($companyEntity);
            $em->persist($clientEntity);
            $em->persist($companyEntity);
            $em->flush();

            return $this->redirectToRoute('lender_profile_personal_information');
        }
    }


    /**
     * @param ClientsAdresses $dbClientAddressEntity
     * @param ClientsAdresses $clientAddressEntity
     * @param FormInterface   $form
     *
     * @return RedirectResponse
     */
    private function handlePersonFiscalAddress(ClientsAdresses $dbClientAddressEntity, ClientsAdresses $clientAddressEntity, FormInterface $form)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var TranslatorInterface $translator */
        $translator    = $this->get('translator');
        $lenderAccount = $this->getLenderAccount();
        $modifications = [];

        if (
            $dbClientAddressEntity->getCpFiscal() !== $clientAddressEntity->getCpFiscal()
            && \pays_v2::COUNTRY_FRANCE == $clientAddressEntity->getIdPaysFiscal()
            && null === $em->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $clientAddressEntity->getCpFiscal()])
        ) {
            $form->get('cpFiscal')->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-section-unknown-zip-code-error-message')));
        }

        if ($form->get('noUsPerson')->getData()) {
            $modifications[]= ['noUsPerson'];
        }

        if (ClientsAdresses::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL == $clientAddressEntity->getMemeAdresseFiscal()) {
            $this->updateFiscalAndPostalAddress($clientAddressEntity);
        }

        if ($clientAddressEntity->getIdPaysFiscal() > \pays_v2::COUNTRY_FRANCE) {
            if (isset($_FILES['tax-certificate']) && $_FILES['tax-certificate']['name'] != '') {
                if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::JUSTIFICATIF_FISCAL, 'tax-certificate'))) {
                    $form->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-section-upload-files-error-message')));
                } else {
                    $modifications[] = $translator->trans('projet_document-type-' . \attachment_type::JUSTIFICATIF_FISCAL);
                }
            } else {
                $form->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-section-missing-tax-certificate')));
            }
        }

        if (isset($_FILES['housing-certificate']) && $_FILES['housing-certificate']['name'] != '') {
            if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::JUSTIFICATIF_DOMICILE, 'housing-certificate'))) {
                $form->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-section-upload-files-error-message')));
            } else {
                $modifications[] = $translator->trans('projet_document-type-' . \attachment_type::JUSTIFICATIF_DOMICILE);
            }
        }


        if ($form->get('housedByThirdPerson')->getData()) {
            if (isset($_FILES['housed-by-third-person-declaration']) && $_FILES['housed-by-third-person-declaration']['name'] != '') {
                if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::ATTESTATION_HEBERGEMENT_TIERS, 'housed-by-third-person-declaration'))) {
                    $form->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-section-upload-files-error-message')));
                } else {
                    $modifications[] = $translator->trans('projet_document-type-' . \attachment_type::ATTESTATION_HEBERGEMENT_TIERS);
                }
            } else {
                $form->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-missing-housed-by-third-person-declaration')));
            }

            if (isset($_FILES['id-third-person-housing']) && $_FILES['housed-by-third-person-declaration']['name'] != '') {
                if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT, 'id-third-person-housing'))) {
                    $form->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-section-upload-files-error-message')));
                } else {
                    $modifications[] = $translator->trans('projet_document-type-' . \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT);
                }
            } else {
                $form->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-missing-id-third-person-housing')));
            }
        }

        if ($form->isValid()) {
            $modifiedData = array_merge($modifications, $this->get('unilend.frontbundle.service.form_manager')->getModifiedContent($dbClientAddressEntity, $clientAddressEntity));
            if (false === empty($modifiedData)) {
                $this->updateClientStatusAndNotifyClient($this->getClient(), array_merge($modifiedData, $modifications));
            }

            if (ClientsAdresses::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL == $clientAddressEntity->getMemeAdresseFiscal()) {
                $this->updateFiscalAndPostalAddress($clientAddressEntity);
            }

            $em->persist($clientAddressEntity);
            $em->flush();

            $this->addFlash('fiscalAddressSuccess', $translator->trans('lender-profile_information-tab-fiscal-address-form-success-message'));
            return $this->redirectToRoute('lender_profile_personal_information');
        }
    }

    /**
     * @param Companies     $dbCompanyEntity
     * @param Companies     $companyEntity
     * @param FormInterface $form
     *
     * @return RedirectResponse
     */
    private function handleCompanyFiscalAddress(Companies $dbCompanyEntity, Companies $companyEntity, FormInterface $form)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        if (
            $dbCompanyEntity->getZip() !== $companyEntity->getZip()
            && \pays_v2::COUNTRY_FRANCE == $companyEntity->getIdPays()
            && null === $em->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $companyEntity->getZip()])
        ) {
            $form->get('company_fiscal_address')->get('zip')->addError(new FormError($translator->trans('lender-profile_information-tab-fiscal-address-section-unknown-zip-code-error-message')));
        }

        if ($form->isValid()) {
            $modifiedData = $this->get('unilend.frontbundle.service.form_manager')->getModifiedContent($dbCompanyEntity, $companyEntity);
            if (false === empty($modifiedData)) {
                $this->updateClientStatusAndNotifyClient($this->getClient(), $modifiedData);
            }

            $this->addFiscalAddressToCompany($companyEntity);
            if (Companies::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL == $companyEntity->getStatusAdresseCorrespondance()) {
                $this->updateFiscalAndPostalAddress($companyEntity);
            }

            $em->persist($companyEntity);
            $em->flush();

            $this->addFlash('fiscalAddressSuccess', $translator->trans('lender-profile_information-tab-fiscal-address-form-success-message'));
            return $this->redirect('lender_profile_personal_information');
        }
    }

    /**
     * @param ClientsAdresses $clientAddressEntity
     *
     * @return RedirectResponse
     */
    private function handlePostalAddressForm(ClientsAdresses $clientAddressEntity)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        if (in_array($em->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientAddressEntity->getIdClient())->getType(), [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
            $company = $em->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $clientAddressEntity->getIdClient()]);
            $company->setStatusAdresseCorrespondance($clientAddressEntity->getMemeAdresseFiscal());
            $em->persist($company);
        }

        if (ClientsAdresses::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL == $clientAddressEntity->getMemeAdresseFiscal()) {
            $this->updateFiscalAndPostalAddress($clientAddressEntity);
        }
        $em->persist($clientAddressEntity);
        $em->flush();

        $this->addFlash('postalAddressSuccess', $translator->trans('lender-profile_information-tab-postal-address-form-success-message'));
        return $this->redirectToRoute('lender_profile_personal_information');
    }

    /**
     * @param Clients $clientEntity
     *
     * @return RedirectResponse
     */
    private function handlePhoneForm(Clients $clientEntity)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        $this->addFlash('phoneSuccess', $translator->trans('lender-profile_information-tab-phone-form-success-message'));

        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        $em->persist($clientEntity);
        $em->flush();

        return $this->redirectToRoute('lender_profile_personal_information');
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
        $lenderAccount = $this->getLenderAccount();
        /** @var Clients $clientEntity */
        $clientEntity = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        /** @var BankAccount $currentBankAccount */
        $currentBankAccount = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($clientEntity);
        $dbBankAccount      = clone $currentBankAccount;
        $dbClientEntity     = clone $clientEntity;

        $form = $this->createFormBuilder()->add('client', OriginOfFundsType::class, ['data' => $clientEntity])
            ->add('bankAccount', BankAccountType::class, ['data' => $currentBankAccount])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->saveClientHistoryAction($client, $request, 'info perso profile');
            if ($form->isValid()) {
                $this->handleBankDetailsForm($dbBankAccount, $currentBankAccount, $dbClientEntity, $clientEntity, $form);
            }
        }

        $templateData = [
            'client'      => $clientEntity,
            'bankAccount' => $currentBankAccount,
            'isCIPActive' => $this->isCIPActive(),
            'bankForm'        => $form->createView()
        ];

        $this->addFiscalInformationTemplateData($templateData, $client, $lenderAccount);

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

        $clientEntity   = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        $dbClientEntity = clone $clientEntity;
        $emailForm      = $this->createForm(ClientEmailType::class, $clientEntity);
        $pwdForm        = $this->createForm(ClientPasswordType::class);
        $questionForm   = $this->createForm(SecurityQuestionType::class, $clientEntity);

        if ($request->isMethod('POST')) {
            if (false === empty($request->request->get('client_email'))) {
                $emailForm->handleRequest($request);

                if ($emailForm->isSubmitted()) {
                    $this->saveClientHistoryAction($clientEntity, $request, 'info perso profile');

                    if ($emailForm->isValid()) {
                        $this->handleEmailForm($dbClientEntity, $clientEntity, $emailForm);
                    }
                }
            }

            if (false === empty($request->request->get('client_password'))) {
                $pwdForm->handleRequest($request);

                if ($pwdForm->isSubmitted()) {
                    $this->saveClientHistoryAction($clientEntity, $request, 'change mdp');
                    if ($pwdForm->isValid()) {
                        $this->handlePasswordForm($clientEntity, $pwdForm);
                    }
                }
            }

            if (false === empty($request->request->get('security_question'))) {
                $questionForm->handleRequest($request);

                if ($questionForm->isSubmitted()) {
                    $this->saveClientHistoryAction($clientEntity, $request, 'change secret question');
                    if ($questionForm->isValid()) {
                        $this->handleQuestionForm($clientEntity, $questionForm);
                    }
                }
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
     * @param array             $templateData
     * @param \clients          $client
     * @param \lenders_accounts $lenderAccount
     */
    private function addFiscalInformationTemplateData(&$templateData, \clients $client, \lenders_accounts $lenderAccount)
    {
        /** @var \ifu $ifu */
        $ifu = $this->get('unilend.service.entity_manager')->getRepository('ifu');

        $attachment = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [\attachment_type::RIB]);

        $templateData['lenderAccount']['fiscal_info'] = [
            'documents'   => $ifu->select('id_client =' . $client->id_client . ' AND statut = 1', 'annee ASC'),
            'amounts'     => $this->getFiscalBalanceAndOwedCapital(),
            'rib'         => isset($attachment[\attachment_type::RIB]) ? $attachment[\attachment_type::RIB] : [],
            'fundsOrigin' => $this->getFundsOrigin($client->type)
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
    }


    /**
     * @Route("/profile/documents", name="lender_completeness")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function lenderCompletenessAction()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \clients $client */
        $client = $this->getClient();
        /** @var \clients_status_history $clientStatusHistory */
        $clientStatusHistory = $entityManager->getRepository('clients_status_history');
        /** @var \attachment_type $attachmentType */
        $attachmentType = $entityManager->getRepository('attachment_type');

        $completenessRequestContent  = $clientStatusHistory->getCompletnessRequestContent($client);
        $template['attachmentTypes'] = $attachmentType->getAllTypesForLender('fr');
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
        $client = $this->getClient();
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->getLenderAccount();
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        $files         = $request->request->get('files', []);
        $uploadSuccess = [];
        $uploadError   = [];

        foreach ($request->files->all() as $fileName => $file) {
            if ($file instanceof UploadedFile && false === empty($files[$fileName])) {
                if (false === $this->uploadAttachment($lenderAccount->id_lender_account, $request->request->get('files')[$fileName], $fileName)) {
                    $uploadError[] = $translator->trans('projet_document-type-' . $request->request->get('files')[$fileName]);
                } else {
                    $uploadSuccess[] = $translator->trans('projet_document-type-' . $request->request->get('files')[$fileName]);
                }
            }
        }

        if (empty($uploadError) && false === empty($uploadSuccess)) {
            $clientEmailContent = '<ul><li>' . implode('</li><li>', $uploadSuccess) . '</li></ul>';
            $this->updateClientStatusAndNotifyClient($client, $clientEmailContent);
            $this->addFlash('completenessSuccess', $translator->trans('lender-profile_completeness-form-success-message'));
        } elseif (false === empty($uploadError)) {
            $this->addFlash('completenessError', $translator->trans('lender-profile_completeness-form-error-message'));
        }

        $clientEntity = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        $this->saveClientHistoryAction($clientEntity, $request, 'upload doc profile');

        return $this->redirectToRoute('lender_completeness');
    }

    /**
     * @param int    $lenderAccountId
     * @param int    $attachmentType
     * @param string $fieldName
     * @return bool
     */
    private function uploadAttachment($lenderAccountId, $attachmentType, $fieldName)
    {
        /** @var \upload $uploadLib */
        $uploadLib = Loader::loadLib('upload');
        /** @var \attachment $attachments */
        $attachments = $this->get('unilend.service.entity_manager')->getRepository('attachment');
        /** @var \attachment_type $attachmentTypes */
        $attachmentTypes = $this->get('unilend.service.entity_manager')->getRepository('attachment_type');
        /** @var \attachment_helper $attachmentHelper */
        $attachmentHelper = Loader::loadLib('attachment_helper', [$attachments, $attachmentTypes, $this->get('kernel')->getRootDir() . '/../']);

        /** @var \greenpoint_attachment $greenPointAttachment */
        $greenPointAttachment = $this->get('unilend.service.entity_manager')->getRepository('greenpoint_attachment');
        /** @var mixed $result */
        $result = $attachmentHelper->attachmentExists($attachments, $lenderAccountId, \attachment::LENDER, $attachmentType);

        if (is_numeric($result)) {
            $greenPointAttachment->get($result, 'id_attachment');
            $greenPointAttachment->revalidate   = \greenpoint_attachment::REVALIDATE_YES;
            $greenPointAttachment->final_status = \greenpoint_attachment::FINAL_STATUS_NO;
            $greenPointAttachment->update();
        }

        return $attachmentHelper->upload($lenderAccountId, \attachment::LENDER, $attachmentType, $fieldName, $uploadLib);
    }

    /**
     * @param Clients $client
     * @param Request $request
     */
    private function saveClientHistoryAction(Clients $client, Request $request, $formName)
    {
        $formManager = $this->get('unilend.frontbundle.service.form_manager');
        $formData    = $formManager->cleanPostData($request->request->all());
        $formId      = '';

        if (isset($_FILES)) {
            $formData = array_merge($formData, $_FILES);
        }

        switch ($formName) {
            case 'info perso profile':
                $formId = 4;
                break;
            case 'change mdp':
                $formId = 7;
                break;
            case 'change secret question':
                $formId                 = 6;
                $formData['reponseSecrete'] = md5($formData['reponseSecrete']);
                break;
            case 'upload doc profile':
                $formId = 12;
                break;
            default:
                break;
        }

        /** @var \clients_history_actions $clientHistoryActions */
        $clientHistoryActions = $this->get('unilend.service.entity_manager')->getRepository('clients_history_actions');
        $clientHistoryActions->histo($formId, $formName, $client->getIdClient(), serialize(['id_client' => $client->getIdClient(), 'post' => $formData]));
    }

    /**
     * @param \clients $client
     * @param array    $historyContent
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
     * @param BankAccount   $dbBankAccount
     * @param BankAccount   $bankAccount
     * @param Clients       $dbClientEntity
     * @param Clients       $clientEntity
     * @param FormInterface $form
     *
     * @return RedirectResponse
     */
    private function handleBankDetailsForm(BankAccount $dbBankAccount, BankAccount $bankAccount, Clients $dbClientEntity, Clients $clientEntity, FormInterface $form )
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->getLenderAccount();
        $modifications = [];

        if ('FR' !== strtoupper(substr($bankAccount->getIban(), 0, 2))) {
            $form->get('bankAccount')->get('iban')->addError(new FormError($translator->trans('lender-subscription_documents-iban-not-french-error-message')));
        }

        if ($dbBankAccount->getIban() !== $bankAccount->getIban()){
           if (empty($_FILES['iban-certificate']['name'])) {
               $form->get('bankAccount')->addError(new FormError($translator->trans('lender-profile_rib-file-mandatory')));
           } else {
               if (false === $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::RIB, 'iban-certificate')) {
                   $form->addError(new FormError($translator->trans('lender-profile_fiscal-tab-rib-file-error')));
               } else {
                   $modifications[] = $translator->trans('lender-profile_fiscal-tab-bank-info-section-documents');
               }
           }
        }

        if ($form->isValid()) {
            $formManager              = $this->get('unilend.frontbundle.service.form_manager');
            $clientModifications      = $formManager->getModifiedContent($dbClientEntity, $clientEntity);
            $bankAccountModifications = $formManager->getModifiedContent($dbBankAccount, $bankAccount);
            $dataModifications        = array_merge($modifications, $clientModifications, $bankAccountModifications);
            if (false === empty($dataModifications)) {
                $this->updateClientStatusAndNotifyClient($this->getClient(), $modifications);
            }

            $bankAccountManager = $this->get('unilend.service.bank_account_manager');
            $bankAccountManager->saveBankInformation($clientEntity, $bankAccount->getBic(), $bankAccount->getIban());
            $this->addFlash('bankInfoUpdateSuccess', $translator->trans('lender-profile_fiscal-tab-bank-info-update-ok'));

            return $this->redirectToRoute('lender_profile_fiscal_information');
        }
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
     * @param Clients       $dbClientEntity
     * @param Clients       $clientEntity
     * @param FormInterface $form
     *
     * @return RedirectResponse
     */
    private function handleEmailForm(Clients $dbClientEntity, Clients $clientEntity, FormInterface $form)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        if ($clientEntity->getEmail() !== $form->get('emailConfirmation')->getData()) {
            $form->addError(new FormError($translator->trans('common-validator_email-address-invalid')));
        }

        if (
            $clientEntity->getEmail() !== $dbClientEntity->getEmail()
            && $em->getRepository('UnilendCoreBusinessBundle:Clients')->existEmail($clientEntity->getEmail())
        ) {
            $form->addError(new FormError($translator->trans('lender-profile_security-identification-error-existing-email')));
        }

        if ($form->isValid()) {
            $em->persist($clientEntity);
            $em->flush($clientEntity);

            $this->addFlash('securityIdentificationSuccess', $translator->trans('lender-profile_security-identification-form-success-message'));
            return $this->redirectToRoute('lender_profile_security');
        }
    }

    /**
     * @param Clients       $clientEntity
     * @param FormInterface $form
     *
     * @return RedirectResponse
     */
    public function handlePasswordForm(Clients $clientEntity, FormInterface $form)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var UserPasswordEncoder $securityPasswordEncoder */
        $securityPasswordEncoder = $this->get('security.password_encoder');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        if (false === $securityPasswordEncoder->isPasswordValid($this->getUser(), $form->get('formerPassword')->getData())) {
            $form->get('formerPassword')->addError(new FormError($translator->trans('lender-profile_security-password-section-error-wrong-former-password')));
        }
        if ($form->get('newPassword')->getData() !== $form->get('passwordConfirmation')->getData()) {
            $form->get('passwordConfirmation')->addError(new FormError($translator->trans('common-validator_password-not-equal')));
        }
        if (false === $ficelle->password_fo($form->get('newPassword')->getData(), 6)) {
            $form->get('passwordConfirmation')->addError(new FormError($translator->trans('common-validator_password-invalid')));
        }

        if ($form->isValid()) {
            $clientEntity->setPassword($securityPasswordEncoder->encodePassword($this->getUser(), $form->get('newPassword')->getData()));
            $em->persist($clientEntity);
            $em->flush($clientEntity);

            $this->sendPasswordModificationEmail($this->getClient());

            $this->addFlash('securityPasswordSuccess', $translator->trans('lender-profile_security-password-section-form-success-message'));
            return $this->redirectToRoute('lender_profile_security');
        }
    }

    /**
     * @param Clients       $clientEntity
     * @param FormInterface $form
     *
     * @return RedirectResponse
     */
    public function handleQuestionForm(Clients $clientEntity, FormInterface $form)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $clientEntity->setSecreteReponse(md5($form->get('secreteReponse')->getData()));
        $em->persist($clientEntity);
        $em->flush($clientEntity);

        $this->addFlash('securitySecretQuestionSuccess', $translator->trans('lender-profile_security-secret-question-section-form-success-message'));
        return $this->redirectToRoute('lender_profile_security');
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
        if ($address instanceof ClientsAdresses) {
            $address->setMemeAdresseFiscal(ClientsAdresses::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL);
            $address->setAdresse1($address->getAdresseFiscal());
            $address->setCp($address->getCpFiscal());
            $address->setVille($address->getVilleFiscal());
            $address->setIdPays($address->getIdPaysFiscal());
        }

        if ($address instanceof Companies) {
            $em            = $this->get('doctrine.orm.entity_manager');
            $clientAddress = $em->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $address->getIdClientOwner()]);

            $clientAddress->setMemeAdresseFiscal(ClientsAdresses::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL);
            $clientAddress->setAdresse1($address->getAdresse1());
            $clientAddress->setCp($address->getZip());
            $clientAddress->setVille($address->getCity());
            $clientAddress->setIdPays($address->getIdPays());

            $em->persist($clientAddress);
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
     * @param Companies $companyEntity
     */
    private function addFiscalAddressToCompany(Companies $companyEntity)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $clientAddress = $em->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $companyEntity->getIdClientOwner()]);
        $clientAddress->setAdresseFiscal($companyEntity->getAdresse1());
        $clientAddress->setCpFiscal($companyEntity->getZip());
        $clientAddress->setVilleFiscal($companyEntity->getCity());
        $clientAddress->setIdPaysFiscal($companyEntity->getIdPays());

        $em->persist($clientAddress);
    }
}
