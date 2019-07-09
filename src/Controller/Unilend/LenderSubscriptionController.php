<?php

namespace Unilend\Controller\Unilend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\{FormError, FormInterface};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\{FileBag, JsonResponse, RedirectResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\core\Loader;
use Unilend\Entity\{AddressType, Attachment, AttachmentType, Backpayline, ClientAddress, Clients, ClientsHistory, ClientsHistoryActions, ClientsStatus, Companies, CompanyAddress, Nationalites,
    NationalitesV2, OffresBienvenues, Pays, Users, Villes, Wallet, WalletType};
use Unilend\Form\{LenderSubscriptionIdentityLegalEntity, LenderSubscriptionIdentityPerson};
use Unilend\Service\Front\{DataLayerCollector, SourceManager};
use Unilend\Service\{GoogleRecaptchaManager, NewsletterManager, SponsorshipManager};

class LenderSubscriptionController extends Controller
{
    public const SESSION_NAME_CAPTCHA = 'displayLenderSubscriptionCaptcha';

    /**
     * @Route("/inscription_preteur/etape1", name="lender_subscription_personal_information")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function personalInformationAction(Request $request, ?UserInterface $client): Response
    {
//        $response = $this->checkProgressAndRedirect($request, null, $client);
//        if ($response instanceof RedirectResponse) {
//            return $response;
//        }

        $client      = new Clients();
        $company     = new Companies();
        $sponsorCode = $this->get('session')->get('sponsorCode');

        $this->addClientSources($client);

        if (false === empty($this->get('session')->get('landingPageData'))) {
            $landingPageData = $this->get('session')->get('landingPageData');
            $this->get('session')->remove('landingPageData');
            $client
                ->setLastName($landingPageData['prospect_name'])
                ->setFirstName($landingPageData['prospect_first_name'])
                ->setEmail($landingPageData['prospect_email'])
                ->setRoles([Clients::ROLE_LENDER])
            ;

            if (isset($landingPageData['sponsor_code']) && null !== $this->get('unilend.service.sponsorship_manager')->getCurrentSponsorshipCampaign()) {
                $this->get('session')->set('sponsorCode', $landingPageData['sponsor_code']);
            }

            if (false === isset($landingPageData['sponsor_code']) && $this->get('unilend.service.welcome_offer_manager')->displayOfferOnLandingPage()) {
                $this->get('session')->set('originLandingPage', true);
            }
        }

        if (
            in_array($client->getSource2(), [SourceManager::HP_SOURCE_NAME, SourceManager::HP_LENDER_SOURCE_NAME])
            && $this->get('unilend.service.welcome_offer_manager')->displayOfferOnHome()
        ) {
            $client->setOrigine(Clients::ORIGIN_WELCOME_OFFER_HOME);
        }

        if ($this->get('session')->get('originLandingPage')) {
            $client->setOrigine(Clients::ORIGIN_WELCOME_OFFER_LP);
        }

        $identityForm        = $this->createForm(LenderSubscriptionIdentityPerson::class, ['client' => $client]);
        $companyIdentityForm = $this->createForm(LenderSubscriptionIdentityLegalEntity::class, ['client' => $client, 'company' => $company]);

        $identityForm->handleRequest($request);
        $companyIdentityForm->handleRequest($request);

        if ($request->isMethod(Request::METHOD_POST)) {
            if ($identityForm->isSubmitted() && $identityForm->isValid()) {
                $isValid = $this->handlePersonForm($client, $identityForm, $request);

                if ($isValid) {
                    $this->saveClientHistoryAction($client, $request, Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION);

                    if (false === empty($sponsorCode)) {
                        $this->get('unilend.service.sponsorship_manager')->createSponsorship($client, $sponsorCode);
                        $this->get('session')->remove('sponsorCode');
                    }

                    $this->get('session')->remove('originLandingPage');
                    $this->get('session')->remove(self::SESSION_NAME_CAPTCHA);

                    return $this->redirectToRoute('lender_subscription_documents', ['clientHash' => $client->getHash()]);
                }
            }

            if ($companyIdentityForm->isSubmitted() && $companyIdentityForm->isValid()) {
                $isValid = $this->handleLegalEntityForm($client, $company, $companyIdentityForm, $request);

                if ($isValid) {
                    $this->saveClientHistoryAction($client, $request, Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION);

                    if (false === empty($sponsorCode)) {
                        $this->get('unilend.service.sponsorship_manager')->createSponsorship($client, $sponsorCode);
                        $this->get('session')->remove('sponsorCode');
                    }

                    $this->get('session')->remove('originLandingPage');
                    $this->get('session')->remove(self::SESSION_NAME_CAPTCHA);

                    return $this->redirectToRoute('lender_subscription_documents', ['clientHash' => $client->getHash()]);
                }
            }
        }

        $template = [
            'serviceTermsLegalEntity' => $this->generateUrl('service_terms', ['type' => 'morale']),
            'serviceTermsPerson'      => $this->generateUrl('service_terms'),
            'identityForm'            => $identityForm->createView(),
            'companyIdentityForm'     => $companyIdentityForm->createView(),
        ];

        if ($this->get('session')->get(self::SESSION_NAME_CAPTCHA, false)) {
            $template['recaptchaKey'] = getenv('GOOGLE_RECAPTCHA_KEY');
        }

        return $this->render('lender_subscription/personal_information.html.twig', $template);
    }

    /**
     * @Route("/inscription_preteur/etape2/{clientHash}", name="lender_subscription_documents", requirements={"clientHash": "[0-9a-f-]{32,36}"})
     *
     * @param string                     $clientHash
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     *
     * @return Response
     */
    public function documentsAction(string $clientHash, Request $request, ?UserInterface $client): Response
    {
        $response = $this->checkProgressAndRedirect($request, $clientHash, $client);
        if ($response instanceof RedirectResponse) {
            return $response;
        }

        $entityManager = $this->get('doctrine.orm.entity_manager');
        $client        = $entityManager->getRepository(Clients::class)->findOneBy(['hash' => $clientHash]);

        if ($client->isNaturalPerson()) {
            $clientAddress = $entityManager->getRepository(ClientAddress::class)->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
            $countryId     = $clientAddress->getIdCountry()->getIdPays();
        } else {
            $company        = $entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $client]);
            $companyAddress = $entityManager->getRepository(CompanyAddress::class)->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
            $countryId      = $companyAddress->getIdCountry()->getIdPays();
        }

        $formManager = $this->get('unilend.frontbundle.service.form_manager');
        $form        = $formManager->getBankInformationForm($client);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->saveClientHistoryAction($client, $request, Clients::SUBSCRIPTION_STEP_DOCUMENTS);

            if ($form->isValid()) {
                $isValid = $this->handleDocumentsForm($form, $request->files, $countryId);

                if ($isValid) {
                    return $this->redirectToRoute('lender_subscription_money_deposit', ['clientHash' => $client->getHash()]);
                }
            }
        }

        $template = [
            'client'         => $client,
            'isLivingAbroad' => Pays::COUNTRY_FRANCE !== $countryId,
            'fundsOrigin'    => $this->getFundsOrigin($client->getType()),
            'form'           => $form->createView(),
        ];

        if (in_array($client->getType(), [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
            $template['company'] = $company;
        }

        return $this->render('lender_subscription/documents.html.twig', $template);
    }

    /**
     * @Route("/inscription_preteur/etape3/{clientHash}", name="lender_subscription_money_deposit",
     * requirements={"clientHash": "[0-9a-f-]{32,36}"}, methods={"GET"})
     *
     * @param string                     $clientHash
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function moneyDepositAction(string $clientHash, Request $request, ?UserInterface $client): Response
    {
        $response = $this->checkProgressAndRedirect($request, $clientHash, $client);
        if ($response instanceof RedirectResponse) {
            return $response;
        }

        $entityManager = $this->get('doctrine.orm.entity_manager');
        $client        = $entityManager->getRepository(Clients::class)->findOneBy(['hash' => $clientHash]);

        $client->setEtapeInscriptionPreteur(Clients::SUBSCRIPTION_STEP_MONEY_DEPOSIT);
        $entityManager->flush($client);

        $template = [
            'client'           => $client,
            'maxDepositAmount' => LenderWalletController::MAX_DEPOSIT_AMOUNT,
            'minDepositAmount' => LenderWalletController::MIN_DEPOSIT_AMOUNT,
            'lenderBankMotif'  => $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER)->getWireTransferPattern(),
        ];

        return $this->render('lender_subscription/money_deposit.html.twig', $template);
    }

    /**
     * @Route("/inscription_preteur/etape3/{clientHash}", name="lender_subscription_money_deposit_form",
     * requirements={"clientHash": "[0-9a-f-]{32,36}"}, methods={"POST"})
     *
     * @param string                     $clientHash
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return RedirectResponse
     */
    public function moneyDepositFormAction(string $clientHash, Request $request, ?UserInterface $client): RedirectResponse
    {
        $response = $this->checkProgressAndRedirect($request, $clientHash, $client);
        if ($response instanceof RedirectResponse) {
            return $response;
        }
        $post = $request->request->all();
        $this->get('session')->set('subscriptionStep3WalletData', $post);

        if (isset($post['amount'])) {
            /** @var \ficelle $ficelle */
            $ficelle = Loader::loadLib('ficelle');
            $amount  = $ficelle->cleanFormatedNumber($post['amount']);

            if (is_numeric($amount) && $amount >= LenderWalletController::MIN_DEPOSIT_AMOUNT && $amount <= LenderWalletController::MAX_DEPOSIT_AMOUNT) {
                $entityManager = $this->get('doctrine.orm.entity_manager');
                $client        = $entityManager->getRepository(Clients::class)->findOneBy(['hash' => $clientHash]);
                $wallet        = $entityManager->getRepository(Wallet::class)->getWalletByType($client->getIdClient(), WalletType::LENDER);
                $successUrl    = $this->generateUrl('lender_subscription_money_transfer', ['clientHash' => $wallet->getIdClient()->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);
                $cancelUrl     = $this->generateUrl('lender_subscription_money_deposit', ['clientHash' => $wallet->getIdClient()->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);

                $redirectUrl = $this->get('unilend.service.payline_manager')->pay($amount, $wallet, $successUrl, $cancelUrl);

                $formManager = $this->get('unilend.frontbundle.service.form_manager');
                $formManager->saveFormSubmission($client, ClientsHistoryActions::LENDER_PROVISION_BY_CREDIT_CARD, serialize(['id_client' => $client->getIdClient(), 'post' => $request->request->all()]), $request->getClientIp());

                if (null !== $redirectUrl) {
                    return $this->redirect($redirectUrl);
                }
            }
        }

        return $this->redirectToRoute('lender_subscription_money_deposit', ['clientHash' => $clientHash]);
    }

    /**
     * @Route("/inscription_preteur/payment/{clientHash}", name="lender_subscription_money_transfer")
     *
     * @param Request $request
     * @param string  $clientHash
     *
     * @return RedirectResponse
     */
    public function paymentAction(Request $request, string $clientHash): RedirectResponse
    {
        $translator       = $this->get('translator');
        $entityManager    = $this->get('doctrine.orm.entity_manager');
        $clientRepository = $entityManager->getRepository(Clients::class);
        $client           = $clientRepository->findOneBy(['hash' => $clientHash]);

        if (null === $client) {
            return $this->redirectToRoute('home_lender');
        }

        $token   = $request->query->filter('token', FILTER_SANITIZE_STRING);
        $version = $request->query->getInt('version', Backpayline::WS_DEFAULT_VERSION);

        if (true === empty($token)) {
            $this->get('logger')->error(
                'Payline token not found for client ' . $client->getIdClient(),
                ['id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );

            return $this->redirectToRoute('lender_wallet', ['depositResult' => true]);
        }

        $paylineManager   = $this->get('unilend.service.payline_manager');
        $paidAmountInCent = $paylineManager->handleResponse($token, $version);

        if (false !== $paidAmountInCent) {
            $clientHistory = new ClientsHistory();
            $clientHistory->setIdClient($client);
            $clientHistory->setStatus(ClientsHistory::STATUS_ACTION_ACCOUNT_CREATION);

            $entityManager->persist($clientHistory);
            $entityManager->flush($clientHistory);

            /** @var \ficelle $ficelle */
            $ficelle    = Loader::loadLib('ficelle');
            $paidAmount = bcdiv($paidAmountInCent, 100, 2);

            $this->addFlash(
                'moneyTransferSuccess',
                $translator->trans('lender-subscription_money-transfer-success-message', ['%depositAmount%' => $ficelle->formatNumber($paidAmount, 2)])
            );
        } else {
            $this->addFlash('moneyTransferError', $translator->trans('lender-subscription_money-transfer-error-message'));
        }

        return $this->redirectToRoute('lender_subscription_money_deposit', ['clientHash' => $client->getHash()]);
    }

    /**
     * @Route("/inscription_preteur/ajax/birth_place", name="lender_subscription_ajax_birth_place", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function getBirthPlaceAction(Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            $locationManager = $this->get('unilend.service.location_manager');

            return new JsonResponse($locationManager->getCities($request->query->get('birthPlace'), true));
        }

        return new Response('not an ajax request');
    }

    /**
     * @Route("/inscription_preteur/ajax/city", name="lender_subscription_ajax_city", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function getCityAction(Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            $locationManager = $this->get('unilend.service.location_manager');

            return new JsonResponse($locationManager->getCities($request->query->get('city')));
        }

        return new Response('not an ajax request');
    }

    /**
     * @Route("/inscription_preteur/ajax/zip", name="lender_subscription_ajax_zip", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function getZipAction(Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            $locationManager = $this->get('unilend.service.location_manager');

            return new JsonResponse($locationManager->getCities($request->query->get('zip')));
        }

        return new Response('not an ajax request');
    }

    /**
     * @Route("/inscription_preteur/ajax/age", name="lender_subscription_ajax_age", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function checkAgeAction(Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            $translator              = $this->get('translator');
            $lenderValidationManager = $this->get('unilend.service.lender_validation_manager');
            $birthday                = \DateTime::createFromFormat('Y-m-d', $request->request->get('year_of_birth') . '-' . $request->request->get('month_of_birth') . '-' . $request->request->get('day_of_birth'));

            if ($lenderValidationManager->validateAge($birthday)) {
                return new JsonResponse([
                    'status' => true,
                ]);
            }

            return new JsonResponse([
                'status' => false,
                'error'  => $translator->trans('lender-subscription_personal-information-error-age'),
            ]);
        }

        return new Response('not an ajax request');
    }

    /**
     * @Route("/inscription_preteur/ajax/check-city", name="lender_subscription_ajax_check_city", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function checkCityAction(Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            $get = $request->query->all();

            if (false === empty($get['country']) && Pays::COUNTRY_FRANCE != $get['country']) {
                return $this->json(['status' => true]);
            }

            if (empty($get['zip'])) {
                $get['zip'] = null;
            }

            if (false === empty($get['city'])) {
                $locationManager = $this->get('unilend.service.location_manager');

                return $this->json(['status' => $locationManager->checkFrenchCity($get['city'], $get['zip'])]);
            }

            return $this->json(['status' => false]);
        }

        return new Response('not an ajax request');
    }

    /**
     * @Route("/inscription_preteur/ajax/check-city-insee", name="lender_subscription_ajax_check_city_insee", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function checkCityInseeCodeAction(Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            $country   = $request->query->get('country');
            $inseeCode = $request->query->get('insee');

            if (false === empty($country) && Pays::COUNTRY_FRANCE != $country) {
                return $this->json(['status' => true]);
            }

            if (false === empty($inseeCode)) {
                $locationManager = $this->get('unilend.service.location_manager');

                return $this->json(['status' => $locationManager->checkFrenchCityInsee($inseeCode)]);
            }

            return $this->json(['status' => false]);
        }

        return new Response('not an ajax request');
    }

    /**
     * @param Clients       $client
     * @param FormInterface $form
     * @param Request       $request
     *
     * @throws \Doctrine\DBAL\ConnectionException
     *
     * @return bool
     */
    private function handlePersonForm(Clients $client, FormInterface $form, Request $request): bool
    {
        /** @var \ficelle $ficelle */
        $ficelle                = Loader::loadLib('ficelle');
        $translator             = $this->get('translator');
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $addressManager         = $this->get('unilend.service.address_manager');
        $lenderValidatorManager = $this->get('unilend.service.lender_validation_manager');

        if (false === $lenderValidatorManager->validateAge($client->getDateOfBirth())) {
            $form->get('client')->get('naissance')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-age')));
        }

        $countryId  = $form->get('mainAddress')->get('idCountry')->getData();
        $zip        = $form->get('mainAddress')->get('zip')->getData();
        $noUsPerson = $form->get('noUsPerson')->getData();

        $noUsPerson ? $client->setUsPerson(false) : $client->setUsPerson(true);

        if (Pays::COUNTRY_FRANCE == $countryId && null === $entityManager->getRepository(Villes::class)->findOneBy(['cp' => $zip])) {
            $form->get('fiscalAddress')->get('cpFiscal')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-fiscal-address-wrong-zip')));
        }

        $countryCheck = true;
        if (null === $entityManager->getRepository(Nationalites::class)->find($client->getIdNationality())) {
            $countryCheck = false;
            $form->get('client')->get('idNationalite')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-wrong-nationality')));
        }
        if (null === $entityManager->getRepository(Pays::class)->find($client->getIdBirthCountry())) {
            $countryCheck = false;
            $form->get('client')->get('idNationalite')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-wrong-birth-country')));
        }

        if (Pays::COUNTRY_FRANCE == $client->getIdBirthCountry() && empty($client->getInseeBirth())) {
            $countryCheck = false;
            $form->get('client')->get('villeNaissance')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-wrong-birth-place')));
        }

        if ($countryCheck) {
            if (Pays::COUNTRY_FRANCE == $client->getIdBirthCountry() && false === empty($client->getInseeBirth())) {
                $cityByInsee = $entityManager->getRepository(Villes::class)->findOneBy(['insee' => $client->getInseeBirth()]);

                if (null !== $cityByInsee) {
                    $client->setBirthCity($cityByInsee->getVille());
                }
            } else {
                $country        = $entityManager->getRepository(Pays::class)->find($client->getIdBirthCountry());
                $inseeCountries = $this->get('unilend.service.entity_manager')->getRepository('insee_pays');
                if (null !== $country && $inseeCountries->getByCountryIso(trim($country->getIso()))) {
                    $client->setInseeBirth($inseeCountries->COG);
                }
                unset($country, $inseeCountries);
            }
        }

        if (false === $form->get('samePostalAddress')->getData()) {
            $this->checkPostalAddressSection($form);
        }

        $isValidCaptcha = $this->isValidCaptcha($request);

        if ($isValidCaptcha) {
            $this->checkSecuritySection($client, $form);
        }

        if (false === $form->get('tos')->getData()) {
            $form->get('tos')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-terms-of-use')));
        }

        if ($isValidCaptcha && $form->isValid()) {
            $clientType        = (NationalitesV2::NATIONALITY_FRENCH == $client->getIdBirthCountry()) ? Clients::TYPE_PERSON : Clients::TYPE_PERSON_FOREIGNER;
            $password          = $this->get('security.password_encoder')->encodePassword($client, $client->getPassword());
            $slug              = $ficelle->generateSlug($client->getFirstName() . '-' . $client->getLastName());
            $newsletterConsent = $client->getOptin1() ? Clients::NEWSLETTER_OPT_IN_ENROLLED : Clients::NEWSLETTER_OPT_IN_NOT_ENROLLED;

            $client
                ->setPassword($password)
                ->setType($clientType)
                ->setIdLanguage('fr')
                ->setSlug($slug)
                ->setStatusInscriptionPreteur(1)
                ->setEtapeInscriptionPreteur(Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION)
                ->setType($clientType)
                ->setOptin1($newsletterConsent)
            ;

            $entityManager->beginTransaction();

            try {
                $entityManager->persist($client);
                $entityManager->flush($client);

                $this->get('unilend.service.client_creation_manager')->createAccount($client, WalletType::LENDER, Users::USER_ID_FRONT, ClientsStatus::STATUS_CREATION);
                $this->get('unilend.service.service_terms_manager')->acceptCurrentVersion($client);

                $addressManager->saveClientAddress(
                    $form->get('mainAddress')->get('address')->getData(),
                    $form->get('mainAddress')->get('zip')->getData(),
                    $form->get('mainAddress')->get('city')->getData(),
                    $form->get('mainAddress')->get('idCountry')->getData(),
                    $client,
                    AddressType::TYPE_MAIN_ADDRESS
                );

                if (false === $form->get('samePostalAddress')->getData()) {
                    $addressManager->saveClientAddress(
                        $form->get('postalAddress')->get('address')->getData(),
                        $form->get('postalAddress')->get('zip')->getData(),
                        $form->get('postalAddress')->get('city')->getData(),
                        $form->get('postalAddress')->get('idCountry')->getData(),
                        $client,
                        AddressType::TYPE_POSTAL_ADDRESS
                    );
                }

                $entityManager->commit();
            } catch (\Exception $exception) {
                $entityManager->getConnection()->rollBack();

                $this->get('logger')->error('An error occurred while creating client. Message: ' . $exception->getMessage(), [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__,
                    'file'     => $exception->getFile(),
                    'line'     => $exception->getLine(),
                ]);

                return false;
            }

            if (Clients::NEWSLETTER_OPT_IN_ENROLLED === $newsletterConsent) {
                $newsletterManager = $this->get(NewsletterManager::class);
                $newsletterManager->subscribeNewsletter($client, $request->getClientIp());
            }

            $this->addClientToDataLayer($client);
            $this->sendSubscriptionStartConfirmationEmail($client);

            return true;
        }

        return false;
    }

    /**
     * @param Clients       $client
     * @param Companies     $company
     * @param FormInterface $form
     * @param Request       $request
     *
     * @throws \Doctrine\DBAL\ConnectionException
     *
     * @return bool
     */
    private function handleLegalEntityForm(Clients $client, Companies $company, FormInterface $form, Request $request): bool
    {
        /** @var \ficelle $ficelle */
        $ficelle       = Loader::loadLib('ficelle');
        $translator    = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $this->checkCompany($company, $form);
        $this->checkCompanyAddress($form);

        $isValidCaptcha = $this->isValidCaptcha($request);
        if ($isValidCaptcha) {
            $this->checkSecuritySection($client, $form);
        }

        if (false === $form->get('tos')->getData()) {
            $form->get('tos')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-terms-of-use')));
        }

        if ($isValidCaptcha && $form->isValid()) {
            $clientType = Pays::COUNTRY_FRANCE === $form->get('mainAddress')->get('idCountry')->getData() ? Clients::TYPE_LEGAL_ENTITY : Clients::TYPE_LEGAL_ENTITY_FOREIGNER;
            $password   = $this->get('security.password_encoder')->encodePassword($client, $client->getPassword());
            $slug       = $ficelle->generateSlug($client->getFirstName() . '-' . $client->getLastName());

            $client
                ->setIdLanguage('fr')
                ->setSlug($slug)
                ->setPassword($password)
                ->setStatusInscriptionPreteur(1)
                ->setEtapeInscriptionPreteur(Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION)
                ->setType($clientType)
            ;

            $entityManager->beginTransaction();

            try {
                $entityManager->persist($client);
                $entityManager->flush($client);

                $company->setIdClientOwner($client);
                $entityManager->persist($company);
                $entityManager->flush($company);

                $this->get('unilend.service.client_creation_manager')->createAccount($client, WalletType::LENDER, Users::USER_ID_FRONT, ClientsStatus::STATUS_CREATION);

                $addressManager = $this->get('unilend.service.address_manager');
                $addressManager->saveCompanyAddress(
                    $form->get('mainAddress')->get('address')->getData(),
                    $form->get('mainAddress')->get('zip')->getData(),
                    $form->get('mainAddress')->get('city')->getData(),
                    $form->get('mainAddress')->get('idCountry')->getData(),
                    $company,
                    AddressType::TYPE_MAIN_ADDRESS
                );

                if (false === $form->get('samePostalAddress')->getData()) {
                    $addressManager->saveCompanyAddress(
                        $form->get('postalAddress')->get('address')->getData(),
                        $form->get('postalAddress')->get('zip')->getData(),
                        $form->get('postalAddress')->get('city')->getData(),
                        $form->get('postalAddress')->get('idCountry')->getData(),
                        $company,
                        AddressType::TYPE_POSTAL_ADDRESS
                    );
                }
                $this->get('unilend.service.service_terms_manager')->acceptCurrentVersion($client);

                $entityManager->commit();
            } catch (\Exception $exception) {
                $entityManager->getConnection()->rollBack();

                $this->get('logger')->error('An error occurred while creating client. Message: ' . $exception->getMessage(), [
                    'class'  => __CLASS__,
                    'method' => __METHOD__,
                    'file'   => $exception->getFile(),
                    'line'   => $exception->getLine(),
                ]);
            }

            $this->addClientToDataLayer($client);
            $this->sendSubscriptionStartConfirmationEmail($client);

            return true;
        }

        return false;
    }

    /**
     * @param Companies     $company
     * @param FormInterface $form
     */
    private function checkCompany(Companies $company, FormInterface $form): void
    {
        $translator = $this->get('translator');

        if (
            Companies::CLIENT_STATUS_DELEGATION_OF_POWER == $company->getStatusClient()
            || Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT == $company->getStatusClient()
        ) {
            if (empty($company->getCiviliteDirigeant())) {
                $form->get('company')->get('civiliteDirigeant')->addError(new FormError($translator->trans('lender-subscription_personal-information-company-director-form-of-address-missing')));
            }
            if (empty($company->getNomDirigeant())) {
                $form->get('company')->get('civiliteDirigeant')->addError(new FormError($translator->trans('lender-subscription_personal-information-company-director-name-missing')));
            }
            if (empty($company->getPrenomDirigeant())) {
                $form->get('company')->get('civiliteDirigeant')->addError(new FormError($translator->trans('lender-subscription_personal-information-company-director-first-name-missing')));
            }
            if (empty($company->getFonctionDirigeant())) {
                $form->get('company')->get('civiliteDirigeant')->addError(new FormError($translator->trans('lender-subscription_personal-information-company-director-position-missing')));
            }
            if (empty($company->getEmailDirigeant())) {
                $form->get('company')->get('emailDirigeant')->addError(new FormError($translator->trans('common_email-missing')));
            }
            if (empty($company->getPhoneDirigeant())) {
                $form->get('company')->get('civiliteDirigeant')->addError(new FormError($translator->trans('lender-subscription_personal-information-company-director-phone-missing')));
            }
            if (Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT == $company->getStatusClient()) {
                if (empty($company->getStatusConseilExterneEntreprise())) {
                    $form->get('company')->get('statusConseilExterneEntreprise')->addError(new FormError($translator->trans('lender-subscription_personal-information-company-external-counsel-error-message')));
                    if (
                        Companies::CLIENT_STATUS_EXTERNAL_COUNSEL_OTHER == $company->getStatusConseilExterneEntreprise()
                        && empty($company->getPreciserConseilExterneEntreprise())
                    ) {
                        $form->get('company')->get('statusConseilExterneEntreprise')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-identity-company-missing-external-counsel-other')));
                    }
                }
            }
        }
    }

    /**
     * @param FormInterface $form
     */
    private function checkCompanyAddress(FormInterface $form): void
    {
        $translator    = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (empty($form->get('mainAddress')->get('address')->getData())) {
            $form->get('mainAddress')->get('address')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-main-address')));
        }

        if (empty($form->get('mainAddress')->get('city')->getData())) {
            $form->get('mainAddress')->get('city')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-main-address-city')));
        }
        $zip = $form->get('mainAddress')->get('zip')->getData();
        if (empty($zip)) {
            $form->get('mainAddress')->get('zip')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-main-address-zip')));
        }

        $countryId = $form->get('mainAddress')->get('idCountry')->getData();
        if (false === empty($countryId) && Pays::COUNTRY_FRANCE === $countryId && null === $entityManager->getRepository(Villes::class)->findOneBy(['cp' => $zip])) {
            $form->get('fiscalAddress')->get('zip')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-fiscal-address-wrong-zip')));
        }

        if (false === $form->get('samePostalAddress')->getData()) {
            if (empty($form->get('postalAddress')->get('address')->getData())) {
                $form->get('postalAddress')->get('address')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-postal-address')));
            }

            if (empty($form->get('postalAddress')->get('city')->getData())) {
                $form->get('postalAddress')->get('city')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-postal-address-city')));
            }

            if (empty($form->get('postalAddress')->get('zip')->getData())) {
                $form->get('postalAddress')->get('zip')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-postal-address-zip')));
            }

            if (empty($form->get('postalAddress')->get('idCountry')->getData())) {
                $form->get('postalAddress')->get('idCountry')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-postal-address-country')));
            }
        }
    }

    /**
     * @see https://www.cloudways.com/blog/add-recaptcha-to-symfony-3-forms/
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isValidCaptcha(Request $request): bool
    {
        if (false === $this->get('session')->get(self::SESSION_NAME_CAPTCHA, false)) {
            return true;
        }

        $response = $request->request->get(GoogleRecaptchaManager::FORM_FIELD_NAME);

        if (empty($response)) {
            $this->addFlash('lenderSubscriptionCaptchaError', $this->get('translator')->trans('lender-subscription_invalid-captcha-error'));

            return false;
        }

        $googleRecaptchaManager = $this->get('unilend.service.google_recaptcha_manager');

        if ($googleRecaptchaManager->isValid($response)) {
            return true;
        }

        $this->addFlash('lenderSubscriptionCaptchaError', $this->get('translator')->trans('lender-subscription_invalid-captcha-error'));

        return false;
    }

    /**
     * @param Clients $client
     */
    private function sendSubscriptionStartConfirmationEmail(Clients $client): void
    {
        $keywords = ['firstName' => $client->getFirstName()];
        $message  = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-inscription-preteur', $keywords);

        try {
            $message->setTo($client->getEmail());
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->warning(
                'Could not send email: confirmation-inscription-preteur - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param Clients       $client
     * @param FormInterface $form
     */
    private function checkSecuritySection(Clients $client, FormInterface $form): void
    {
        $translator    = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $duplicates    = $entityManager->getRepository(Clients::class)->findGrantedLoginAccountsByEmail($client->getEmail());

        if (false === empty($duplicates)) {
            $form->get('client')->get('email')->addError(new FormError($translator->trans('lender-profile_security-identification-error-existing-email')));
            $this->get('session')->set(self::SESSION_NAME_CAPTCHA, true);
        }

        try {
            $this->get('security.password_encoder')->encodePassword($client, $client->getPassword());
        } catch (\Exception $exception) {
            $form->get('client')->get('password')->addError(new FormError($translator->trans('common-validator_password-invalid')));
        }
    }

    /**
     * @param FormInterface $form
     */
    private function checkPostalAddressSection(FormInterface $form): void
    {
        $translator = $this->get('translator');

        if (empty($form->get('postalAddress')->get('address'))) {
            $form->get('postalAddress')->get('address')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-postal-address')));
        }
        if (empty($form->get('postalAddress')->get('city'))) {
            $form->get('postalAddress')->get('city')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-postal-address-city')));
        }
        if (empty($form->get('postalAddress')->get('zip'))) {
            $form->get('postalAddress')->get('zip')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-postal-address-zip')));
        }
        if (empty($form->get('postalAddress')->get('idCountry'))) {
            $form->get('postalAddress')->get('idCountry')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-postal-address-country')));
        }
    }

    /**
     * @param FormInterface $form
     * @param FileBag       $fileBag
     * @param int           $countryId
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     *
     * @return bool
     */
    private function handleDocumentsForm(FormInterface $form, FileBag $fileBag, int $countryId): bool
    {
        $translator = $this->get('translator');
        /** @var Clients $client */
        $client              = $form->get('client')->getData();
        $iban                = $form->get('bankAccount')->get('iban')->getData();
        $bic                 = $form->get('bankAccount')->get('bic')->getData();
        $bankAccountDocument = null;

        if (false === in_array(mb_strtoupper(mb_substr($iban, 0, 2)), Pays::EEA_COUNTRIES_ISO)) {
            $form->get('bankAccount')->get('iban')->addError(new FormError($translator->trans('lender-subscription_documents-iban-not-european-error-message')));
        }

        $fundsOrigin = $this->getFundsOrigin($client->getType());
        if (empty($client->getFundsOrigin()) || empty($fundsOrigin[$client->getFundsOrigin()])) {
            $form->get('client')->get('fundsOrigin')->addError(new FormError($translator->trans('lender-subscription-documents_wrong-funds-origin')));
        }

        $file = $fileBag->get('rib');
        if ($file instanceof UploadedFile) {
            try {
                $bankAccountDocument = $this->upload($client, AttachmentType::RIB, $file);
            } catch (\Exception $exception) {
                $form->get('bankAccount')->addError(new FormError($translator->trans('lender-subscription_documents-upload-files-error-message')));
            }
        } else {
            $form->addError(new FormError($translator->trans('lender-subscription_documents-missing-rib')));
        }

        if ($client->isNaturalPerson()) {
            $this->validateAttachmentsPerson($form, $client, $fileBag, $countryId);
        } else {
            $company = $this->get('doctrine.orm.entity_manager')->getRepository(Companies::class)->findOneBy(['idClientOwner' => $client]);
            $this->validateAttachmentsLegalEntity($form, $client, $company, $fileBag);
        }

        if ($form->isValid() && $bankAccountDocument) {
            $client->setEtapeInscriptionPreteur(Clients::SUBSCRIPTION_STEP_DOCUMENTS);
            $this->get('doctrine.orm.entity_manager')->flush();

            $bankAccountManager = $this->get('unilend.service.bank_account_manager');
            $bankAccountManager->saveBankInformation($client, $bic, $iban, $bankAccountDocument);

            $clientStatusManager = $this->get('unilend.service.client_status_manager');
            $clientStatusManager->addClientStatus($client, Users::USER_ID_FRONT, ClientsStatus::STATUS_TO_BE_CHECKED);

            $this->get('unilend.service.notification_manager')->generateDefaultNotificationSettings($client);
            $this->sendFinalizedSubscriptionConfirmationEmail($client);

            return true;
        }

        return false;
    }

    /**
     * @param FormInterface $form
     * @param Clients       $client
     * @param FileBag       $fileBag
     * @param int           $countryId
     */
    private function validateAttachmentsPerson(FormInterface $form, Clients $client, FileBag $fileBag, int $countryId): void
    {
        $translator         = $this->get('translator');
        $addressManager     = $this->get('unilend.service.address_manager');
        $entityManager      = $this->get('doctrine.orm.entity_manager');
        $uploadErrorMessage = $translator->trans('lender-subscription_documents-upload-files-error-message');

        $files = [
            AttachmentType::CNI_PASSPORTE         => $fileBag->get('id_recto'),
            AttachmentType::CNI_PASSPORTE_VERSO   => $fileBag->get('id_verso'),
            AttachmentType::JUSTIFICATIF_DOMICILE => $fileBag->get('housing-certificate'),
        ];
        if (Pays::COUNTRY_FRANCE !== $countryId) {
            $files[AttachmentType::JUSTIFICATIF_FISCAL] = $fileBag->get('tax-certificate');
        }
        if (false === empty($form->get('housedByThirdPerson')->getData())) {
            $files[AttachmentType::ATTESTATION_HEBERGEMENT_TIERS] = $fileBag->get('housed-by-third-person-declaration');
            $files[AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT] = $fileBag->get('id-third-person-housing');
        }
        foreach ($files as $attachmentTypeId => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $attachment = $this->upload($client, $attachmentTypeId, $file);

                    if (AttachmentType::JUSTIFICATIF_DOMICILE == $attachmentTypeId && $attachment instanceof Attachment) {
                        $address = $entityManager
                            ->getRepository(ClientAddress::class)
                            ->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS)
                        ;

                        if ($address instanceof ClientAddress) {
                            $addressManager->linkAttachmentToAddress($address, $attachment);
                        } else {
                            $this->get('logger')->error('Client has no address to link housing certificate to during subscription process.', [
                                'id_client' => $client->getIdClient(),
                                'class'     => __CLASS__,
                                'function'  => __FUNCTION__,
                            ]);
                        }
                    }
                } catch (\Exception $exception) {
                    $form->addError(new FormError($uploadErrorMessage));
                    $this->get('logger')->error('Lender attachment could not be uploaded. Message : ' . $exception->getMessage(), [
                        'file'               => $exception->getFile(),
                        'line'               => $exception->getLine(),
                        'class'              => __CLASS__,
                        'function'           => __FUNCTION__,
                        'id_type_attachment' => $attachmentTypeId,
                        'id_client'          => $client->getIdClient(),
                    ]);
                }
            } else {
                switch ($attachmentTypeId) {
                    case AttachmentType::CNI_PASSPORTE:
                        $error = $translator->trans('lender-subscription_documents-person-missing-id');

                        break;
                    case AttachmentType::JUSTIFICATIF_FISCAL:
                        $error = $translator->trans('lender-subscription_documents-person-missing-tax-certificate');

                        break;
                    case AttachmentType::JUSTIFICATIF_DOMICILE:
                        $error = $translator->trans('lender-subscription_documents-person-missing-housing-certificate');

                        break;
                    case AttachmentType::ATTESTATION_HEBERGEMENT_TIERS:
                        $error = $translator->trans('lender-subscription_documents-person-missing-housed-by-third-person-declaration');

                        break;
                    case AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT:
                        $error = $translator->trans('lender-subscription_documents-person-missing-id-third-person-housing');

                        break;
                    default:
                        continue 2;
                }
                $form->addError(new FormError($error));
            }
        }
    }

    /**
     * @param FormInterface $form
     * @param Clients       $client
     * @param Companies     $company
     * @param FileBag       $fileBag
     */
    private function validateAttachmentsLegalEntity(FormInterface $form, Clients $client, Companies $company, FileBag $fileBag): void
    {
        $translator         = $this->get('translator');
        $uploadErrorMessage = $translator->trans('lender-subscription_documents-upload-files-error-message');

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
                } catch (\Exception $exception) {
                    $form->addError(new FormError($uploadErrorMessage));
                    $this->get('logger')->error('Lender attachment could not be uploaded. Message : ' . $exception->getMessage(), [
                        'file'               => $exception->getFile(),
                        'line'               => $exception->getLine(),
                        'class'              => __CLASS__,
                        'function'           => __FUNCTION__,
                        'id_type_attachment' => $attachmentTypeId,
                        'id_client'          => $client->getIdClient(),
                    ]);
                }
            } else {
                switch ($attachmentTypeId) {
                    case AttachmentType::CNI_PASSPORTE:
                        $error = $translator->trans('lender-subscription_documents-person-missing-id');

                        break;
                    case AttachmentType::JUSTIFICATIF_FISCAL:
                        $error = $translator->trans('lender-subscription_documents-person-missing-tax-certificate');

                        break;
                    case AttachmentType::JUSTIFICATIF_DOMICILE:
                        $error = $translator->trans('lender-subscription_documents-person-missing-housing-certificate');

                        break;
                    case AttachmentType::ATTESTATION_HEBERGEMENT_TIERS:
                        $error = $translator->trans('lender-subscription_documents-person-missing-housed-by-third-person-declaration');

                        break;
                    case AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT:
                        $error = $translator->trans('lender-subscription_documents-person-missing-id-third-person-housing');

                        break;
                    default:
                        continue 2;
                }
                $form->addError(new FormError($error));
            }
        }
    }

    /**
     * @param Request      $request
     * @param string|null  $clientHash
     * @param Clients|null $client
     *
     * @return RedirectResponse|null
     */
    private function checkProgressAndRedirect(Request $request, ?string $clientHash, ?Clients $client): ?RedirectResponse
    {
        $redirectPath         = null;
        $currentPath          = $request->getPathInfo();
        $authorizationChecker = $this->get('security.authorization_checker');
        $clientRepository     = $this->get('doctrine.orm.entity_manager')->getRepository(Clients::class);

        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            if ($authorizationChecker->isGranted('ROLE_BORROWER')) {
                return $this->redirectToRoute('projects_list');
            }

            if ($authorizationChecker->isGranted(Clients::ROLE_ARRANGER)) {
                return $this->redirectToRoute('partner_home');
            }
        } elseif (null !== $clientHash) {
            $client = $clientRepository->findOneBy(['hash' => $clientHash]);
        }

        if ($client instanceof Clients && $client->isLender()) {
            switch ($client->getIdClientStatusHistory()->getIdStatus()->getId()) {
                case ClientsStatus::STATUS_CREATION:
                case ClientsStatus::STATUS_TO_BE_CHECKED:
                    $redirectPath = $this->getLenderSubscriptionStepRoute($client);

                    break;
                case ClientsStatus::STATUS_COMPLETENESS:
                case ClientsStatus::STATUS_COMPLETENESS_REMINDER:
                    return $this->redirectToRoute('lender_completeness');
                case ClientsStatus::STATUS_COMPLETENESS_REPLY:
                case ClientsStatus::STATUS_MODIFICATION:
                case ClientsStatus::STATUS_VALIDATED:
                case ClientsStatus::STATUS_SUSPENDED:
                default:
                    return $this->redirectToRoute('lender_dashboard');
            }
        } else {
            $personalFormRoute = ['lender_subscription_personal_information_person_form', 'lender_subscription_personal_information_legal_entity_form'];
            if (false === in_array($request->get('_route'), $personalFormRoute)) {
                $redirectPath = $this->generateUrl('lender_subscription_personal_information');
            }
        }

        if (null !== $redirectPath && $currentPath !== $redirectPath) {
            return $this->redirect($redirectPath);
        }

        return null;
    }

    /**
     * @param Clients $client
     *
     * @return string|null
     */
    private function getLenderSubscriptionStepRoute(Clients $client): ?string
    {
        switch ($client->getEtapeInscriptionPreteur()) {
            case Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION:
                return $this->generateUrl('lender_subscription_documents', ['clientHash' => $client->getHash()]);
            case Clients::SUBSCRIPTION_STEP_DOCUMENTS:
            case Clients::SUBSCRIPTION_STEP_MONEY_DEPOSIT:
                return $this->generateUrl('lender_subscription_money_deposit', ['clientHash' => $client->getHash()]);
            default:
                return null;
        }
    }

    /**
     * @param Clients $client
     * @param Request $request
     * @param int     $step
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveClientHistoryAction(Clients $client, Request $request, int $step): void
    {
        $formManager = $this->get('unilend.frontbundle.service.form_manager');
        $post        = $formManager->cleanPostData($request->request->all());
        $files       = $request->files->all();

        if (Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION === $step) {
            if (isset($post['form_person'])) {
                $form = &$post['form_person'];
            } elseif (isset($post['form_legal_entity'])) {
                $form = &$post['form_legal_entity'];
            } else {
                $this->get('logger')->error('Unable to save client action history: submitted form cannot be found.', [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__,
                ]);
            }

            if (isset($form)) {
                $form['client']['password']['first']  = md5($form['client']['password']['first']);
                $form['client']['password']['second'] = md5($form['client']['password']['second']);
                $form['security']['secreteReponse']   = md5($form['security']['secreteReponse']);
            }

            $formType = $client->isNaturalPerson() ? ClientsHistoryActions::LENDER_PERSON_SUBSCRIPTION_PERSONAL_INFORMATION : ClientsHistoryActions::LENDER_LEGAL_ENTITY_SUBSCRIPTION_PERSONAL_INFORMATION;
        } else {
            $formType = $client->isNaturalPerson() ? ClientsHistoryActions::LENDER_PERSON_SUBSCRIPTION_BANK_DOCUMENTS : ClientsHistoryActions::LENDER_LEGAL_ENTITY_SUBSCRIPTION_BANK_DOCUMENTS;
        }

        if (false === empty($files)) {
            $post = array_merge($post, $formManager->getNamesOfFiles($files));
        }

        $formManager->saveFormSubmission($client, $formType, serialize(['id_client' => $client->getIdClient(), 'post' => $post]), $request->getClientIp());
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
     */
    private function sendFinalizedSubscriptionConfirmationEmail(Clients $client): void
    {
        $keywords = [
            'firstName' => $client->getFirstName(),
        ];

        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-inscription-preteur-etape-3', $keywords);

        try {
            $message->setTo($client->getEmail());
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->warning(
                'Could not send email: confirmation-inscription-preteur-etape-3 - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
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
     * @param Clients $client
     */
    private function addClientSources(Clients $client): void
    {
        $sourceManager = $this->get('unilend.frontbundle.service.source_manager');

        $client->setSource($sourceManager->getSource(SourceManager::SOURCE1))
            ->setSource2($sourceManager->getSource(SourceManager::SOURCE2))
            ->setSource3($sourceManager->getSource(SourceManager::SOURCE3))
            ->setSlugOrigine($sourceManager->getSource(SourceManager::ENTRY_SLUG))
        ;
    }

    /**
     * @param Clients $client
     */
    private function addClientToDataLayer(Clients $client): void
    {
        $this->get('session')->set(DataLayerCollector::SESSION_KEY_CLIENT_EMAIL, $client->getEmail());
        $this->get('session')->set(DataLayerCollector::SESSION_KEY_LENDER_CLIENT_ID, $client->getIdClient());
    }
}
