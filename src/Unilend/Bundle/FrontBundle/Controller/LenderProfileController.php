<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\{
    Method, Security
};
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\{
    Extension\Core\Type\CheckboxType, FormError, FormInterface
};
use Symfony\Component\HttpFoundation\{
    File\UploadedFile, JsonResponse, RedirectResponse, Request, Response
};
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Attachment, AttachmentType, Clients, ClientsGestionTypeNotif, ClientsHistoryActions, ClientsStatus, GreenpointAttachment, Ifu, LenderTaxExemption, PaysV2, TaxType, Wallet, WalletBalanceHistory, WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Service\{
    ClientDataHistoryManager, LocationManager, NewsletterManager
};
use Unilend\Bundle\FrontBundle\Form\ClientPasswordType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\{
    BankAccountType, ClientEmailType, CompanyIdentityType, LegalEntityProfileType, OriginOfFundsType, PersonPhoneType, PersonProfileType, SecurityQuestionType
};
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\Bundle\FrontBundle\Service\LenderProfileFormsHandler;

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
            $mainAddressForm     = $formManager->getClientAddressFormBuilder($lastModifiedMainAddress, AddressType::TYPE_MAIN_ADDRESS)->getForm();
            $mainAddressForm
                ->add('noUsPerson', CheckboxType::class, ['data' => true !== $client->getUsPerson(), 'required' => false])
                ->add('housedByThirdPerson', CheckboxType::class, ['required' => false]);

            $postalAddressForm = $formManager->getClientAddressFormBuilder($postalAddress, AddressType::TYPE_POSTAL_ADDRESS)->getForm();
            $hasPostalAddress  = null === $postalAddress;
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

            $phoneForm->handleRequest($request);
            if ($phoneForm->isSubmitted() && $phoneForm->isValid()) {
                $formHandler->handlePhoneForm($client, $unattachedClient);
                $this->addFlash('phoneSuccess', $translator->trans('lender-profile_information-tab-phone-form-success-message'));

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
     * @Route("/profile/info-fiscal", name="lender_profile_fiscal_information")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
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
        $unattachedClient        = clone $client;
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

        $formHandler = $this->get(LenderProfileFormsHandler::class);
        $form->handleRequest($request);

        if (
            $form->isSubmitted() &&
            $form->isValid() &&
            $formHandler->handleBankDetailsForm($client, $unattachedClient, $form->get('bankAccount'), $request->files)
        ) {
            $translator = $this->get('translator');
            $this->addFlash('bankInfoUpdateSuccess', $translator->trans('lender-profile_fiscal-tab-bank-info-update-ok'));
            return $this->redirectToRoute('lender_profile_fiscal_information');
        }

        $ifuRepository         = $entityManager->getRepository('UnilendCoreBusinessBundle:Ifu');
        $taxType               = $entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_STATUTORY_CONTRIBUTIONS);
        $taxExemptionDateRange = $this->getTaxExemptionDateRange();
        $taxExemptionHistory   = $this->getExemptionHistory($wallet);
        $isEligible            = $this->getTaxExemptionEligibility($wallet);

        $templateData = [
            'client'                       => $client,
            'bankAccount'                  => $bankAccount,
            'isCIPActive'                  => $this->isCIPActive(),
            'bankForm'                     => $form->createView(),
            'lender'                       => [
                'fiscal_info' => [
                    'documents'   => $ifuRepository->findBy(['idClient' => $client->getIdClient(), 'statut' => Ifu::STATUS_ACTIVE], ['annee' => 'DESC']),
                    'amounts'     => $this->getFiscalBalanceAndOwedCapital($client),
                    'rib'         => $bankAccount ? $bankAccount->getAttachment() : '',
                    'fundsOrigin' => $this->get('unilend.service.lender_manager')->getFundsOrigins($client->getType())
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

        $entityManager    = $this->get('doctrine.orm.entity_manager');
        $client           = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());
        $unattachedClient = clone $client;
        $emailForm        = $this->createForm(ClientEmailType::class, $client);
        $passwordForm     = $this->createForm(ClientPasswordType::class);
        $questionForm     = $this->createForm(SecurityQuestionType::class, $client);

        if ($request->isMethod(Request::METHOD_POST)) {
            $isValid     = false;
            $formHandler = $this->get(LenderProfileFormsHandler::class);

            $emailForm->handleRequest($request);
            if (
                $emailForm->isSubmitted() &&
                $emailForm->isValid() &&
                $formHandler->handleEmailForm($client, $unattachedClient, $emailForm)
            ) {
                $this->addFlash('securityIdentificationSuccess', $this->get('translator')->trans('lender-profile_security-identification-form-success-message'));
                $isValid = true;
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
            'client'      => $client,
            'isCIPActive' => $this->isCIPActive(),
            'forms'       => [
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

        if (empty($notificationSetting)) {
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

            $notificationSettings->{'set' . ucfirst($type)}($active);

            $entityManager->flush($notificationSettings);
        } catch (OptimisticLockException $exception) {
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

        $translator            = $this->get('translator');
        $isFileUploaded        = false;
        $error                 = '';
        $files                 = $request->request->get('files', []);
        $client                = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());
        $unattachedClient      = clone $client;
        $newAttachments        = [];
        $isBankAccountModified = false;

        foreach ($request->files->all() as $fileName => $file) {
            if ($file instanceof UploadedFile && false === empty($files[$fileName])) {
                try {
                    $newAttachments[] = $document = $this->upload($client, $files[$fileName], $file);
                    $isFileUploaded   = true;

                    if (AttachmentType::RIB === $document->getType()->getId()) {
                        $form = $request->request->get('form', ['bankAccount' => ['bic' => '', 'iban' => '']]);
                        $iban = $form['bankAccount']['iban'];
                        $bic  = $form['bankAccount']['bic'];

                        if (in_array(strtoupper(substr($iban, 0, 2)), PaysV2::EEA_COUNTRIES_ISO)) {
                            $bankAccountManager = $this->get('unilend.service.bank_account_manager');
                            $bankAccountManager->saveBankInformation($client, $bic, $iban, $document);
                            $isBankAccountModified = true;
                        } else {
                            $error = $translator->trans('lender-subscription_documents-iban-not-european-error-message');
                        }
                    }
                } catch (\Exception $exception) {
                    $error = $translator->trans('lender-profile_completeness-form-error-message');
                }
            }
        }
        $this->get('unilend.service.client_status_manager')
            ->changeClientStatusTriggeredByClientAction($client, $unattachedClient, null, null, false, $isBankAccountModified, $newAttachments);

        if (empty($error) && $isFileUploaded) {
            $this->get(ClientDataHistoryManager::class)->sendLenderProfileModificationEmail($client, [], [], $newAttachments, '', $isBankAccountModified);

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
        $result                       = [];
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
}
