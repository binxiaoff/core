<?php

namespace Unilend\Controller\Unilend;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\{Extension\Core\Type\CheckboxType, FormError, FormInterface};
use Symfony\Component\HttpFoundation\{File\UploadedFile, JsonResponse, RedirectResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{
    AcceptationsLegalDocs,
    AddressType,
    Attachment,
    AttachmentType,
    BankAccount,
    ClientAddress,
    Clients,
    ClientsGestionNotifications,
    ClientsGestionTypeNotif,
    ClientsHistoryActions,
    ClientsStatus,
    ClientsStatusHistory,
    Companies,
    CompanyAddress,
    Ifu,
    LenderTaxExemption,
    LendersImpositionHistory,
    Operation,
    Pays,
    Settings,
    TaxType,
    Wallet,
    WalletBalanceHistory,
    WalletType
};
use Unilend\Form\ClientPasswordType;
use Unilend\Form\LenderPersonContactType;
use Unilend\Form\LenderSubscriptionProfile\{BankAccountType, ClientEmailType, CompanyIdentityType, LegalEntityProfileType, OriginOfFundsType, PersonProfileType, SecurityQuestionType};
use Unilend\Service\Front\LenderProfileFormsHandler;
use Unilend\Service\{ClientDataHistoryManager, LocationManager, NewsletterManager, UserActivity\UserActivityDisplayManager};

class LenderProfileController extends Controller
{
    /**
     * @Route("/profile", name="lender_profile")
     * @Route("/profile/info-perso", name="lender_profile_personal_information")
     *
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     *
     * @return Response
     */
    public function personalInformationAction(Request $request, ?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $entityManager            = $this->get('doctrine.orm.entity_manager');
        $formManager              = $this->get('unilend.frontbundle.service.form_manager');
        $companyAddressRepository = $entityManager->getRepository(CompanyAddress::class);
        $clientAddressRepository  = $entityManager->getRepository(ClientAddress::class);
        $unattachedClient         = clone $client;

        if ($client->isNaturalPerson()) {
            $contactForm = $this->createForm(LenderPersonContactType::class, $client);

            $lastModifiedMainAddress = $clientAddressRepository->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
            $postalAddress           = $client->getIdPostalAddress();

            $identityFormBuilder = $this->createFormBuilder()
                ->add('client', PersonProfileType::class, ['data' => $client])
            ;
            $mainAddressForm = $formManager->getClientAddressFormBuilder($client, $lastModifiedMainAddress, AddressType::TYPE_MAIN_ADDRESS)->getForm();

            $postalAddressForm = $formManager->getClientAddressFormBuilder($client, $postalAddress, AddressType::TYPE_POSTAL_ADDRESS)->getForm();
            $hasPostalAddress  = null === $postalAddress;
            $postalAddressForm->add('samePostalAddress', CheckboxType::class, ['data' => $hasPostalAddress, 'required' => false]);
        } else {
            $company                 = $entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $client]);
            $unattachedCompany       = clone $company;
            $lastModifiedMainAddress = $companyAddressRepository->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
            $postalAddress           = $company->getIdPostalAddress();

            $identityFormBuilder = $this->createFormBuilder()
                ->add('client', LegalEntityProfileType::class, ['data' => $client])
                ->add('company', CompanyIdentityType::class, ['data' => $company])
            ;
            $identityFormBuilder->get('company')->remove('siren');
            $identityFormBuilder->get('client')->remove('email');
            $identityFormBuilder->add('clientEmail', ClientEmailType::class, ['data' => $client]);

            $mainAddressForm   = $formManager->getCompanyAddressFormBuilder($lastModifiedMainAddress, AddressType::TYPE_MAIN_ADDRESS)->getForm();
            $postalAddressForm = $formManager->getCompanyAddressFormBuilder($postalAddress, AddressType::TYPE_POSTAL_ADDRESS)->getForm();
            $hasPostalAddress  = null === $postalAddress;
            $postalAddressForm->add('samePostalAddress', CheckboxType::class, ['data' => $hasPostalAddress, 'required' => false]);
        }

        $identityForm = $identityFormBuilder->getForm();

        if ($request->isMethod(Request::METHOD_POST)) {
            $formHandler = $this->get(LenderProfileFormsHandler::class);
            $translator  = $this->get('translator');
            $isValid     = false;

            $identityForm->handleRequest($request);
            if ($identityForm->isSubmitted() && $identityForm->isValid()) {
                if ($client->isNaturalPerson()) {
                    $isValid = $formHandler->handlePersonIdentity($client, $unattachedClient, $identityForm->get('client'), $request->files);
                } else {
                    $isValid = $formHandler->handleCompanyIdentity($client, $unattachedClient, $company, $unattachedCompany, $identityForm->get('company'), $request->files);
                }

                if ($isValid) {
                    $this->addFlash('identitySuccess', $translator->trans('lender-profile_information-tab-identity-section-files-update-success-message'));
                }
            }

            $mainAddressForm->handleRequest($request);
            if ($mainAddressForm->isSubmitted() && $mainAddressForm->isValid()) {
                if ($client->isNaturalPerson()) {
                    $isValid = $formHandler->handlePersonAddress($client, $unattachedClient, $mainAddressForm, $request->files, AddressType::TYPE_MAIN_ADDRESS, $lastModifiedMainAddress);
                } else {
                    $isValid = $formHandler->handleCompanyAddress($company, $mainAddressForm, AddressType::TYPE_MAIN_ADDRESS, $lastModifiedMainAddress);
                }

                if ($isValid) {
                    $this->addFlash('mainAddressSuccess', $translator->trans('lender-profile_information-tab-fiscal-address-form-success-message'));
                }
            }

            $postalAddressForm->handleRequest($request);
            if ($postalAddressForm->isSubmitted() && $postalAddressForm->isValid()) {
                if ($client->isNaturalPerson()) {
                    $isValid = $formHandler->handlePersonAddress($client, $unattachedClient, $postalAddressForm, $request->files, AddressType::TYPE_POSTAL_ADDRESS, $postalAddress);
                } else {
                    $isValid = $formHandler->handleCompanyAddress($company, $postalAddressForm, AddressType::TYPE_POSTAL_ADDRESS, $postalAddress);
                }

                if ($isValid) {
                    $this->addFlash('postalAddressSuccess', $translator->trans('lender-profile_information-tab-postal-address-form-success-message'));
                }
            }

            if (isset($contactForm) && $contactForm instanceof FormInterface) {
                $contactForm->handleRequest($request);
                if ($contactForm->isSubmitted() && $contactForm->isValid()) {
                    $isValid = $formHandler->handleContactForm($client, $unattachedClient, $contactForm);

                    if ($isValid) {
                        $this->addFlash('contactSuccess', $translator->trans('lender-profile_information-tab-contact-form-success-message'));
                    }
                }
            }

            if ($isValid) {
                return $this->redirectToRoute('lender_profile_personal_information');
            }
        }

        $templateData = [
            'client'               => $client,
            'clientMainAddress'    => $lastModifiedMainAddress,
            'clientPostalAddress'  => $postalAddress,
            'company'              => $company ?? null,
            'companyMainAddress'   => $lastModifiedMainAddress,
            'companyPostalAddress' => $postalAddress,
            'isCIPActive'          => $this->isCIPActive($client),
            'forms'                => [
                'identity'      => $identityForm->createView(),
                'mainAddress'   => $mainAddressForm->createView(),
                'postalAddress' => $postalAddressForm->createView(),
            ],
            'isLivingAbroad'       => $lastModifiedMainAddress ? (Pays::COUNTRY_FRANCE !== $lastModifiedMainAddress->getIdCountry()->getIdPays()) : false,
            'acceptedServiceTerms' => $entityManager->getRepository(AcceptationsLegalDocs::class)->findBy(['idClient' => $client], ['added' => 'ASC']),
        ];
        if (isset($contactForm) && $contactForm instanceof FormInterface) {
            $templateData['forms']['contact'] = $contactForm->createView();
        }

        $setting                             = $entityManager->getRepository(Settings::class)->findOneBy(['type' => 'Liste deroulante conseil externe de l\'entreprise']);
        $templateData['externalCounselList'] = json_decode($setting->getValue(), true);
        $attachmentRepository                = $entityManager->getRepository(Attachment::class);
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
     * @Route("/profile/info-fiscal", name="lender_profile_fiscal_information")
     *
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function fiscalInformationAction(Request $request, ?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $clientAddressRepository = $entityManager->getRepository(ClientAddress::class);
        $unattachedClient        = clone $client;
        $bankAccount             = $entityManager->getRepository(BankAccount::class)->getLastModifiedBankAccount($client);
        $wallet                  = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);

        $form = $this->createFormBuilder()
            ->add('client', OriginOfFundsType::class, ['data' => $client])
            ->add('bankAccount', BankAccountType::class)
            ->getForm()
        ;

        if (null !== $bankAccount) {
            $form->get('bankAccount')->get('iban')->setData($bankAccount->getIban());
            $form->get('bankAccount')->get('bic')->setData($bankAccount->getBic());
        }

        $formHandler = $this->get(LenderProfileFormsHandler::class);
        $form->handleRequest($request);

        if (
            $form->isSubmitted() && $form->isValid() && $formHandler->handleBankDetailsForm($client, $unattachedClient, $form->get('bankAccount'), $request->files)
        ) {
            $this->addFlash('bankInfoUpdateSuccess', $this->get('translator')->trans('lender-profile_fiscal-tab-bank-info-update-ok'));

            return $this->redirectToRoute('lender_profile_fiscal_information');
        }

        $ifuRepository         = $entityManager->getRepository(Ifu::class);
        $taxType               = $entityManager->getRepository(TaxType::class)->find(TaxType::TYPE_STATUTORY_CONTRIBUTIONS);
        $taxExemptionDateRange = $this->getTaxExemptionDateRange();
        $taxExemptionHistory   = $this->getExemptionHistory($wallet);
        $isEligible            = $this->getTaxExemptionEligibility($wallet);

        $templateData = [
            'client'                       => $client,
            'bankAccount'                  => $bankAccount,
            'isCIPActive'                  => $this->isCIPActive($client),
            'bankForm'                     => $form->createView(),
            'clientAddress'                => $clientAddressRepository->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS),
            'currentYear'                  => date('Y'),
            'lastYear'                     => date('Y') - 1,
            'nextYear'                     => date('Y') + 1,
            'taxExemptionRequestLimitDate' => strftime('%d %B %Y', $taxExemptionDateRange['taxExemptionRequestLimitDate']->getTimestamp()),
            'rateOfTaxDeductionAtSource'   => $taxType->getRate(),
            'exemptions'                   => $taxExemptionHistory,
            'taxExemptionEligibility'      => $isEligible,
            'declarationIsPossible'        => $this->checkIfTaxExemptionIsPossible($taxExemptionHistory, $taxExemptionDateRange, $isEligible),
            'lender'                       => [
                'fiscal_info' => [
                    'documents'   => $ifuRepository->findBy(['idClient' => $client->getIdClient(), 'statut' => Ifu::STATUS_ACTIVE], ['annee' => 'DESC']),
                    'amounts'     => $this->getFiscalBalanceAndOwedCapital($client),
                    'rib'         => $bankAccount ? $bankAccount->getAttachment() : '',
                    'fundsOrigin' => $this->get('unilend.service.lender_manager')->getFundsOrigins($client->getType()),
                ],
            ],
        ];

        return $this->render('lender_profile/fiscal_information.html.twig', $templateData);
    }

    /**
     * @Route("/profile/securite", name="lender_profile_security")
     *
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return Response
     */
    public function securityAction(Request $request, ?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $entityManager = $this->get('doctrine.orm.entity_manager');
        $passwordForm  = $this->createForm(ClientPasswordType::class);
        $questionForm  = $this->createForm(SecurityQuestionType::class, $client);

        if ($request->isMethod(Request::METHOD_POST)) {
            $isValid = false;

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
            'client'       => $client,
            'isCIPActive'  => $this->isCIPActive($client),
            'loginHistory' => $this->get(UserActivityDisplayManager::class)->getLoginHistory($client, $request->headers->get('User-Agent')),
            'forms'        => [
                'securityPwd'      => $passwordForm->createView(),
                'securityQuestion' => $questionForm->createView(),
            ],
        ];

        return $this->render('lender_profile/security.html.twig', $templateData);
    }

    /**
     * @Route("/profile/alertes", name="lender_profile_notifications")
     *
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function notificationsAction(Request $request, ?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $templateData = [
            'isCIPActive'       => $this->isCIPActive($client),
            'newsletterConsent' => $client->getOptin1(),
            'siteUrl'           => $request->getSchemeAndHttpHost(),
        ];

        $this->addNotificationSettingsTemplate($templateData, $client);

        return $this->render('lender_profile/notifications.html.twig', $templateData);
    }

    /**
     * @Route("/profile/notification", name="lender_profile_notification", condition="request.isXmlHttpRequest()", methods={"POST"})
     *
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return JsonResponse
     */
    public function updateNotificationAction(Request $request, ?UserInterface $client): JsonResponse
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->json('ko');
        }

        $sendingPeriod = $request->request->filter('period', FILTER_SANITIZE_STRING);
        $active        = $request->request->getBoolean('active');

        switch ($sendingPeriod) {
            case 'newsletter':
                return $this->updateNewsletterConsent($client, $active, $request->getClientIp());
            case 'immediate':
            case 'daily':
            case 'weekly':
            case 'monthly':
                return $this->updateNotificationSettings($client, $sendingPeriod, $active, $request->request->getInt('type_id'));
            default:
                return $this->json('ko');
        }
    }

    /**
     * @Route("/profile/documents", name="lender_completeness")
     *
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function lenderCompletenessAction(?UserInterface $client): Response
    {
        if (false === $client->isInCompleteness()) {
            return $this->redirectToRoute('lender_dashboard');
        }

        $attachmentManager = $this->get('unilend.service.attachment_manager');
        $bankAccountForm   = $this->getBankAccountForm($client);
        $mainAddressForm   = $client->isNaturalPerson() ? $this->getMainAddressForm($client) : null;

        $template = [
            'attachmentTypes' => $attachmentManager->getAllTypesForLender(),
            'attachmentsList' => $this->getAttachmentList($client),
            'bankForm'        => null === $bankAccountForm ? null : $bankAccountForm->createView(),
            'mainAddressForm' => null === $mainAddressForm ? null : $mainAddressForm->createView(),
        ];

        return $this->render('lender_profile/lender_completeness.html.twig', $template);
    }

    /**
     * @Route("/profile/documents/submit", name="lender_completeness_submit", methods={"POST"})
     *
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return RedirectResponse
     */
    public function lenderCompletenessFormAction(Request $request, ?UserInterface $client): RedirectResponse
    {
        if (false === $client->isInCompleteness()) {
            return $this->redirectToRoute('lender_dashboard');
        }

        $translator            = $this->get('translator');
        $entityManager         = $this->get('doctrine.orm.entity_manager');
        $unattachedClient      = clone $client;
        $company               = null;
        $unattachedCompany     = null;
        $isFileUploaded        = false;
        $isBankAccountModified = false;
        $isMainAddressModified = false;
        $newAttachments        = [];
        $files                 = $request->request->get('files', []);
        $modifiedAddressType   = '';

        foreach ($request->files->all() as $fileName => $file) {
            if ($file instanceof UploadedFile && false === empty($files[$fileName])) {
                try {
                    $newAttachments[] = $document = $this->upload($client, $files[$fileName], $file);
                    $isFileUploaded   = true;

                    if (AttachmentType::RIB === $document->getType()->getId()) {
                        $this->handleCompletenessRib($request, $client, $document);
                        $isBankAccountModified = true;
                    }

                    if (AttachmentType::JUSTIFICATIF_DOMICILE === $document->getType()->getId()) {
                        $this->handleCompletenessHousingCertificate($request, $client, $document);
                        $isMainAddressModified = true;
                        $modifiedAddressType   = AddressType::TYPE_MAIN_ADDRESS;
                    }
                } catch (\Exception $exception) {
                    $this->get('logger')->error('An exception occurred during handling of completeness form. Message: ' . $exception->getMessage(), [
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine(),
                        'id_client' => $client->getIdClient(),
                    ]);
                    $this->addFlash('completenessError', $translator->trans('lender-profile_completeness-form-error-message'));
                }
            }
        }

        if ($isFileUploaded && empty($this->get('session')->getFlashBag()->peek('completenessError'))) {
            if (false === $client->isNaturalPerson()) {
                $company           = $entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $client]);
                $unattachedCompany = clone $company;
            }

            $this->get('unilend.service.client_status_manager')
                ->changeClientStatusTriggeredByClientAction($client, $unattachedClient, $company, $unattachedCompany, $isMainAddressModified, $isBankAccountModified, $newAttachments)
            ;

            $this->get(ClientDataHistoryManager::class)->sendLenderProfileModificationEmail($client, [], [], $newAttachments, $modifiedAddressType, $isBankAccountModified);

            // currently this message is not displayed because of the redirection. RUN-3054
            $this->addFlash('completenessSuccess', $translator->trans('lender-profile_completeness-form-success-message'));
        }

        return $this->redirectToRoute('lender_completeness');
    }

    /**
     * @Route("/profile/ajax/zip", name="lender_profile_ajax_zip", methods={"GET"})
     *
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
     * @Route("/profile/ifu", name="get_ifu", methods={"GET"})
     *
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function downloadIFUAction(Request $request, ?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        /** @var \ifu $ifu */
        $ifu        = $this->get('unilend.service.entity_manager')->getRepository('ifu');
        $translator = $this->get('translator');

        if ($client->getHash() === $request->query->filter('hash', FILTER_SANITIZE_STRING)) {
            if (
                $ifu->get($client->getIdClient(), 'annee = ' . $request->query->getInt('year', 0) . ' AND statut = ' . Ifu::STATUS_ACTIVE . ' AND id_client')
                && file_exists($this->get('kernel')->getRootDir() . '/../' . $ifu->chemin)
            ) {
                return new Response(
                    @file_get_contents($this->get('kernel')->getRootDir() . '/../' . $ifu->chemin),
                    Response::HTTP_OK,
                    [
                        'Content-Description' => 'File Transfer',
                        'Content-type'        => 'application/force-download;',
                        'content-disposition' => 'attachment; filename="' . basename($ifu->chemin) . '";',
                    ]
                );
            }
            $errorTitle = $translator->trans('lender-error-page_file-not-found');
            $status     = Response::HTTP_NOT_FOUND;
        } else {
            $errorTitle = $translator->trans('lender-error-page_access-denied');
            $status     = Response::HTTP_FORBIDDEN;
        }

        return $this->render('exception/error.html.twig', ['errorTitle' => $errorTitle])->setStatusCode($status);
    }

    /**
     * @Route("/profile/request-tax-exemption", name="profile_fiscal_information_tax_exemption", methods={"POST"})
     *
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function requestTaxExemptionAction(Request $request, ?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $entityManager                = $this->get('doctrine.orm.entity_manager');
        $translator                   = $this->get('translator');
        $logger                       = $this->get('logger');
        $lenderTaxExemptionRepository = $entityManager->getRepository(LenderTaxExemption::class);
        $wallet                       = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);
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
                        ->setYear($year)
                    ;
                    $entityManager->persist($lenderTaxExemption);
                    $entityManager->flush($lenderTaxExemption);

                    $this->addFlash('exonerationSuccess', $translator->trans('lender-profile_fiscal-information-exoneration-validation-success'));
                }
            } else {
                $this->addFlash('exonerationError', $translator->trans('lender-profile_fiscal-information-exoneration-validation-error'));
                $logger->warning(
                    'The tax exemption request was not processed for the lender: (id_lender=' . $wallet->getId() . ') for the year: ' . $year .
                    '. Lender already exempted. Either declaration time has elapsed or declaration already done.',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $wallet->getId(), 'time' => $now->format('Y-m-d H:i:s')]
                );
            }
        } catch (\Exception $exception) {
            $this->addFlash('exonerationError', $translator->trans('lender-profile_fiscal-information-exoneration-validation-error'));
            $logger->error(
                'Could not register lender tax exemption request for the lender: (id_lender=' . $wallet->getId() . ') for the year: ' . $year .
                ' Exception message : ' . $exception->getMessage(),
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'id_lender' => $wallet->getId()]
            );
        }

        return $this->redirectToRoute('lender_profile_fiscal_information');
    }

    /**
     * @param array   $templateData
     * @param Clients $client
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function addNotificationSettingsTemplate(array &$templateData, Clients $client): void
    {
        $this->get('unilend.service.notification_manager')->checkNotificationSettingsAndCreateDefaultIfMissing($client);

        $settings = $this->get('unilend.service.entity_manager')->getRepository('clients_gestion_notifications')->getNotifs($client->getIdClient());

        $templateData['notification_settings']['immediate'] = [
            ClientsGestionTypeNotif::TYPE_NEW_PROJECT                   => $settings[ClientsGestionTypeNotif::TYPE_NEW_PROJECT][ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_BID_PLACED                    => $settings[ClientsGestionTypeNotif::TYPE_BID_PLACED][ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_BID_REJECTED                  => $settings[ClientsGestionTypeNotif::TYPE_BID_REJECTED][ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED                 => $settings[ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED][ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_PROJECT_PROBLEM               => $settings[ClientsGestionTypeNotif::TYPE_PROJECT_PROBLEM][ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID => $settings[ClientsGestionTypeNotif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID][ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_REPAYMENT                     => $settings[ClientsGestionTypeNotif::TYPE_REPAYMENT][ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_BANK_TRANSFER_CREDIT          => $settings[ClientsGestionTypeNotif::TYPE_BANK_TRANSFER_CREDIT][ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_CREDIT_CARD_CREDIT            => $settings[ClientsGestionTypeNotif::TYPE_CREDIT_CARD_CREDIT][ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE],
            ClientsGestionTypeNotif::TYPE_DEBIT                         => $settings[ClientsGestionTypeNotif::TYPE_DEBIT][ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE],
        ];

        $templateData['notification_settings']['daily'] = [
            ClientsGestionTypeNotif::TYPE_NEW_PROJECT   => $settings[ClientsGestionTypeNotif::TYPE_NEW_PROJECT][ClientsGestionNotifications::TYPE_NOTIFICATION_DAILY],
            ClientsGestionTypeNotif::TYPE_BID_PLACED    => $settings[ClientsGestionTypeNotif::TYPE_BID_PLACED][ClientsGestionNotifications::TYPE_NOTIFICATION_DAILY],
            ClientsGestionTypeNotif::TYPE_BID_REJECTED  => $settings[ClientsGestionTypeNotif::TYPE_BID_REJECTED][ClientsGestionNotifications::TYPE_NOTIFICATION_DAILY],
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED => $settings[ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED][ClientsGestionNotifications::TYPE_NOTIFICATION_DAILY],
            ClientsGestionTypeNotif::TYPE_REPAYMENT     => $settings[ClientsGestionTypeNotif::TYPE_REPAYMENT][ClientsGestionNotifications::TYPE_NOTIFICATION_DAILY],
        ];

        $templateData['notification_settings']['weekly'] = [
            ClientsGestionTypeNotif::TYPE_NEW_PROJECT   => $settings[ClientsGestionTypeNotif::TYPE_NEW_PROJECT][ClientsGestionNotifications::TYPE_NOTIFICATION_WEEKLY],
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED => $settings[ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED][ClientsGestionNotifications::TYPE_NOTIFICATION_WEEKLY],
            ClientsGestionTypeNotif::TYPE_REPAYMENT     => $settings[ClientsGestionTypeNotif::TYPE_REPAYMENT][ClientsGestionNotifications::TYPE_NOTIFICATION_WEEKLY],
        ];

        $templateData['notification_settings']['monthly'] = [
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED => $settings[ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED][ClientsGestionNotifications::TYPE_NOTIFICATION_MONTHLY],
            ClientsGestionTypeNotif::TYPE_REPAYMENT     => $settings[ClientsGestionTypeNotif::TYPE_REPAYMENT][ClientsGestionNotifications::TYPE_NOTIFICATION_MONTHLY],
        ];
    }

    /**
     * @param Clients     $client
     * @param bool        $active
     * @param string|null $ipAddress
     *
     * @return JsonResponse
     */
    private function updateNewsletterConsent(Clients $client, bool $active, ?string $ipAddress): JsonResponse
    {
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
     * @param Clients  $client
     * @param string   $sendingPeriod
     * @param bool     $active
     * @param int|null $typeId
     *
     * @return JsonResponse
     */
    private function updateNotificationSettings(Clients $client, string $sendingPeriod, bool $active, ?int $typeId): JsonResponse
    {
        $type = null;

        // Put it temporary here, because we don't need it after the project refectory notification
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
            ClientsGestionTypeNotif::TYPE_DEBIT,
        ];
        $dailyTypes = [
            ClientsGestionTypeNotif::TYPE_NEW_PROJECT,
            ClientsGestionTypeNotif::TYPE_BID_PLACED,
            ClientsGestionTypeNotif::TYPE_BID_REJECTED,
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED,
            ClientsGestionTypeNotif::TYPE_REPAYMENT,
        ];
        $weeklyTypes = [
            ClientsGestionTypeNotif::TYPE_NEW_PROJECT,
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED,
            ClientsGestionTypeNotif::TYPE_REPAYMENT,
        ];

        $monthlyTypes = [
            ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED,
            ClientsGestionTypeNotif::TYPE_REPAYMENT,
        ];

        switch ($sendingPeriod) {
            case 'immediate':
                $type = ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE;
                if (false === in_array($typeId, $immediateTypes, true)) {
                    return $this->json('ko');
                }

                break;
            case 'daily':
                $type = ClientsGestionNotifications::TYPE_NOTIFICATION_DAILY;
                if (false === in_array($typeId, $dailyTypes, true)) {
                    return $this->json('ko');
                }

                break;
            case 'weekly':
                $type = ClientsGestionNotifications::TYPE_NOTIFICATION_WEEKLY;
                if (false === in_array($typeId, $weeklyTypes, true)) {
                    return $this->json('ko');
                }

                break;
            case 'monthly':
                $type = ClientsGestionNotifications::TYPE_NOTIFICATION_MONTHLY;
                if (false === in_array($typeId, $monthlyTypes, true)) {
                    return $this->json('ko');
                }

                break;
            default:
                return $this->json('ko');
        }

        try {
            $entityManager                  = $this->get('doctrine.orm.entity_manager');
            $notificationSettingsRepository = $entityManager->getRepository(ClientsGestionNotifications::class);
            $settingType                    = $entityManager->getRepository(ClientsGestionTypeNotif::class)->find($typeId);
            $notificationSetting            = $notificationSettingsRepository->findOneBy([
                'idClient' => $client->getIdClient(),
                'idNotif'  => $settingType->getIdClientGestionTypeNotif(),
            ]);

            if (null === $notificationSetting) {
                $notificationSetting = $this->get('unilend.service.notification_manager')->createMissingNotificationSettingWithDefaultValue($settingType, $client);
            }

            $notificationSetting->{'set' . ucfirst($type)}($active);

            $entityManager->flush($notificationSetting);
        } catch (\Exception $exception) {
            $this->get('logger')->error('Could not update lender notifications. Error: ' . $exception->getMessage(), [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
            ]);

            return $this->json('ko');
        }

        return $this->json('ok');
    }

    /**
     * @param Clients $client
     *
     * @return FormInterface|null
     */
    private function getBankAccountForm(Clients $client): ?FormInterface
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');

        try {
            $bankAccount = $entityManager->getRepository(BankAccount::class)->getLastModifiedBankAccount($client);
        } catch (NonUniqueResultException $exception) {
            $this->get('logger')->error('Client has more than one last modified bank account', [
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'id_client' => $client->getIdClient(),
            ]);

            return null;
        }

        $iban = $bankAccount ? $bankAccount->getIban() : '';
        $bic  = $bankAccount ? $bankAccount->getBic() : '';

        $bankAccountForm = $this->createFormBuilder()
            ->add('bankAccount', BankAccountType::class)
            ->getForm()
        ;

        $bankAccountForm->get('bankAccount')->get('iban')->setData($iban);
        $bankAccountForm->get('bankAccount')->get('bic')->setData($bic);

        return $bankAccountForm;
    }

    /**
     * @param Clients $client
     *
     * @return FormInterface|null
     */
    private function getMainAddressForm(Clients $client): ?FormInterface
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $formManager   = $this->get('unilend.frontbundle.service.form_manager');

        try {
            $lastModifiedMainAddress = $entityManager->getRepository(ClientAddress::class)
                ->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS)
            ;
        } catch (NonUniqueResultException $exception) {
            $this->get('logger')->error('Client has more than one last modified main address', [
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'id_client' => $client->getIdClient(),
            ]);

            return null;
        }

        if (null === $lastModifiedMainAddress) {
            $this->get('logger')->error('Client has no main address', [
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'id_client' => $client->getIdClient(),
            ]);

            return null;
        }

        $formBuilder = $formManager->getClientAddressFormBuilder($client, $lastModifiedMainAddress, AddressType::TYPE_MAIN_ADDRESS);

        return $formBuilder->getForm();
    }

    /**
     * @param Clients $client
     *
     * @return string
     */
    private function getAttachmentList(Clients $client): string
    {
        $attachmentsList     = '';
        $entityManager       = $this->get('doctrine.orm.entity_manager');
        $completenessRequest = $entityManager->getRepository(ClientsStatusHistory::class)->findOneBy(
            ['idClient' => $client->getIdClient(), 'idStatus' => ClientsStatus::STATUS_COMPLETENESS],
            ['added' => 'DESC', 'id' => 'DESC']
        );

        if (null !== $completenessRequest) {
            $domElement = new \DOMDocument();
            $domElement->loadHTML($completenessRequest->getContent());
            $list = $domElement->getElementsByTagName('ul');
            if ($list->length > 0 && $list->item(0)->childNodes->length > 0) {
                $attachmentsList = $list->item(0)->C14N();
            }
        }

        return $attachmentsList;
    }

    /**
     * @param Request    $request
     * @param Clients    $client
     * @param Attachment $document
     *
     * @throws \Exception
     */
    private function handleCompletenessRib(Request $request, Clients $client, Attachment $document): void
    {
        $translator = $this->get('translator');
        $form       = $request->request->get('form', ['bankAccount' => ['bic' => '', 'iban' => '']]);
        $iban       = $form['bankAccount']['iban'];
        $bic        = $form['bankAccount']['bic'];

        if (empty($iban) || empty($bic)) {
            $this->addFlash('completenessError', $translator->trans('lender-profile_completeness-bank-account-data-is-empty'));

            return;
        }

        if (false === in_array(mb_strtoupper(mb_substr($iban, 0, 2)), Pays::EEA_COUNTRIES_ISO)) {
            $this->addFlash('completenessError', $translator->trans('lender-subscription_documents-iban-not-european-error-message'));

            return;
        }

        $bankAccountManager = $this->get('unilend.service.bank_account_manager');
        $bankAccountManager->saveBankInformation($client, $bic, $iban, $document);
    }

    /**
     * @param Request    $request
     * @param Clients    $client
     * @param Attachment $document
     *
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws \Exception
     */
    private function handleCompletenessHousingCertificate(Request $request, Clients $client, Attachment $document): void
    {
        if (false === $client->isNaturalPerson()) {
            $this->get('logger')->error('Lender legal entity uploaded a housing certificate which is only used for natural person. ', [
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'id_client' => $client->getIdClient(),
            ]);

            return;
        }

        $translator    = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $addressForm   = $request->request->get('main_address');
        $address       = $addressForm['address']   ?? '';
        $zip           = $addressForm['zip']       ?? '';
        $city          = $addressForm['city']      ?? '';
        $countryId     = $addressForm['idCountry'] ?? '';

        if (empty($address) || empty($zip) || empty($city) || empty($countryId)) {
            $this->addFlash('completenessError', $translator->trans('lender-profile_completeness-address-data-is-empty'));

            return;
        }

        $addressManager = $this->get('unilend.service.address_manager');
        $addressManager->saveClientAddress($address, $zip, $city, $countryId, $client, AddressType::TYPE_MAIN_ADDRESS);

        $lastModifiedAddress = $entityManager
            ->getRepository(ClientAddress::class)
            ->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS)
        ;

        if (null === $lastModifiedAddress) {
            $this->get('logger')->error('Cannot link attachment to address as client has no unarchived main address', [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
            ]);

            return;
        }

        $addressManager->linkAttachmentToAddress($lastModifiedAddress, $document);
    }

    /**
     * @param Clients      $client
     * @param int          $attachmentTypeId
     * @param UploadedFile $file
     *
     * @throws \Exception
     *
     * @return Attachment
     */
    private function upload(Clients $client, int $attachmentTypeId, UploadedFile $file): Attachment
    {
        $attachmentManager = $this->get('unilend.service.attachment_manager');
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $attachmentType    = $entityManager->getRepository(AttachmentType::class)->find($attachmentTypeId);
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
     *
     * @throws \Doctrine\ORM\ORMException
     *
     * @return array
     */
    private function getFiscalBalanceAndOwedCapital(Clients $client): array
    {
        $entityManager                  = $this->get('doctrine.orm.entity_manager');
        $walletRepository               = $entityManager->getRepository(Wallet::class);
        $walletBalanceHistoryRepository = $entityManager->getRepository(WalletBalanceHistory::class);
        $operationRepository            = $entityManager->getRepository(Operation::class);

        $lastYear = new \DateTime('Last day of december last year');
        $wallet   = $walletRepository->getWalletByType($client->getIdClient(), WalletType::LENDER);
        /** @var WalletBalanceHistory $history */
        $history = $walletBalanceHistoryRepository->getBalanceOfTheDay($wallet, $lastYear);

        return [
            'balance'     => null !== $history ? bcadd($history->getAvailableBalance(), $history->getCommittedBalance(), 2) : 0,
            'owedCapital' => $operationRepository->getRemainingDueCapitalAtDate($client->getIdClient(), $lastYear),
        ];
    }

    /**
     * @param Clients       $client
     * @param FormInterface $form
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return bool
     */
    private function handlePasswordForm(Clients $client, FormInterface $form): bool
    {
        $translator              = $this->get('translator');
        $securityPasswordEncoder = $this->get('security.password_encoder');
        $entityManager           = $this->get('doctrine.orm.entity_manager');

        if (false === $securityPasswordEncoder->isPasswordValid($client, $form->get('formerPassword')->getData())) {
            $form->get('formerPassword')->addError(new FormError($translator->trans('lender-profile_security-password-section-error-wrong-former-password')));
        }

        $encodedPassword = '';

        try {
            $encodedPassword = $securityPasswordEncoder->encodePassword($client, $form->get('password')->getData());
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
            'firstName'     => $client->getFirstName(),
            'password'      => '',
            'lenderPattern' => $this->get('doctrine.orm.entity_manager')
                ->getRepository(Wallet::class)
                ->getWalletByType($client->getIdClient(), WalletType::LENDER)
                ->getWireTransferPattern(),
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
                'line'             => $exception->getLine(),
            ]);
        }
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    private function isCIPActive(Clients $client): bool
    {
        /** @var \lender_evaluation_log $evaluationLog */
        $evaluationLog = $this->get('unilend.service.entity_manager')->getRepository('lender_evaluation_log');
        $wallet        = $this->get('doctrine.orm.entity_manager')->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);

        return $evaluationLog->hasLenderLog($wallet->getId());
    }

    /**
     * Returns true if the declaration is possible, false otherwise.
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

        return true === $isEligible && false === $outOfDate && false === $taxExemptionRequestDone;
    }

    /**
     * @param Wallet $wallet
     *
     * @return bool
     */
    private function getTaxExemptionEligibility(Wallet $wallet): bool
    {
        $lenderImpositionHistoryRepository = $this->get('doctrine.orm.entity_manager')->getRepository(LendersImpositionHistory::class);

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
        $result                       = [];
        $lenderTaxExemptionRepository = $this->get('doctrine.orm.entity_manager')
            ->getRepository(LenderTaxExemption::class)
        ;
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
}
