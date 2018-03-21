<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\{
    Method, Route
};
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\{
    FormError, FormInterface
};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\{
    FileBag,
    JsonResponse,
    RedirectResponse,
    Request,
    Response
};
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Attachment,
    AttachmentType,
    Backpayline,
    Clients,
    ClientsAdresses,
    ClientsHistory,
    ClientsHistoryActions,
    ClientsStatus,
    Companies,
    OffresBienvenues,
    PaysV2,
    Users,
    WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Service\{
    GoogleRecaptchaManager, SponsorshipManager
};
use Unilend\Bundle\FrontBundle\Security\BCryptPasswordEncoder;
use Unilend\Bundle\FrontBundle\Service\{
    DataLayerCollector,
    SourceManager
};
use Unilend\core\Loader;

class LenderSubscriptionController extends Controller
{
    const SESSION_NAME_CAPTCHA = 'displayLenderSubscriptionCaptcha';

    /**
     * @Route("/inscription_preteur/etape1", name="lender_subscription_personal_information")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function personalInformationAction(Request $request): Response
    {
        $response = $this->checkProgressAndRedirect($request);
        if ($response instanceof RedirectResponse) {
            return $response;
        }

        $client        = new Clients();
        $clientAddress = new ClientsAdresses();
        $company       = new Companies();
        $sponsorCode   = $this->get('session')->get('sponsorCode');

        $this->addClientSources($client);

        if (false === empty($this->get('session')->get('landingPageData'))) {
            $landingPageData = $this->get('session')->get('landingPageData');
            $this->get('session')->remove('landingPageData');
            $client->setNom($landingPageData['prospect_name']);
            $client->setPrenom($landingPageData['prospect_first_name']);
            $client->setEmail($landingPageData['prospect_email']);

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

        $formManager         = $this->get('unilend.frontbundle.service.form_manager');
        $identityForm        = $formManager->getLenderSubscriptionPersonIdentityForm($client, $clientAddress);
        $companyIdentityForm = $formManager->getLenderSubscriptionLegalEntityIdentityForm($client, $company, $clientAddress);

        $identityForm->handleRequest($request);
        $companyIdentityForm->handleRequest($request);

        if ($request->isMethod(Request::METHOD_POST)) {
            if ($identityForm->isSubmitted() && $identityForm->isValid()) {
                $isValid = $this->handleIdentityPersonForm($client, $clientAddress, $identityForm, $request);
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
                $isValid = $this->handleLegalEntityForm($client, $clientAddress, $company, $companyIdentityForm, $request);
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
            'termsOfUseLegalEntity' => $this->generateUrl('lenders_terms_of_sales', ['type' => 'morale']),
            'termsOfUsePerson'      => $this->generateUrl('lenders_terms_of_sales'),
            'identityForm'          => $identityForm->createView(),
            'companyIdentityForm'   => $companyIdentityForm->createView()
        ];

        if ($this->get('session')->get(self::SESSION_NAME_CAPTCHA, false)) {
            $template['recaptchaKey'] = $this->getParameter('google.recaptcha_key');
        }

        return $this->render('lender_subscription/personal_information.html.twig', $template);
    }

    /**
     * @param Clients         $client
     * @param ClientsAdresses $clientAddress
     * @param FormInterface   $form
     * @param Request         $request
     *
     * @return bool
     */
    private function handleIdentityPersonForm(Clients $client, ClientsAdresses $clientAddress, FormInterface $form, Request $request): bool
    {
        /** @var \ficelle $ficelle */
        $ficelle       = Loader::loadLib('ficelle');
        $translator    = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (false === $this->isAtLeastEighteenYearsOld($client->getNaissance())) {
            $form->get('client')->get('naissance')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-age')));
        }

        if (PaysV2::COUNTRY_FRANCE == $clientAddress->getIdPaysFiscal() && null === $entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $clientAddress->getCpFiscal()])) {
            $form->get('fiscalAddress')->get('cpFiscal')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-fiscal-address-wrong-zip')));
        }

        $countryCheck = true;
        if (null === $entityManager->getRepository('UnilendCoreBusinessBundle:Nationalites')->find($client->getIdNationalite())) {
            $countryCheck = false;
            $form->get('client')->get('idNationalite')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-wrong-nationality')));
        }
        if (null === $entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2')->find($client->getIdPaysNaissance())) {
            $countryCheck = false;
            $form->get('client')->get('idNationalite')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-wrong-birth-country')));
        }

        if (PaysV2::COUNTRY_FRANCE == $client->getIdPaysNaissance() && empty($client->getInseeBirth())) {
            $countryCheck = false;
            $form->get('client')->get('villeNaissance')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-wrong-birth-place')));
        }

        if ($countryCheck) {
            if (PaysV2::COUNTRY_FRANCE == $client->getIdPaysNaissance() && false === empty($client->getInseeBirth())) {
                $cityByInsee = $entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneByInsee($client->getInseeBirth());

                if (null !== $cityByInsee) {
                    $client->setVilleNaissance($cityByInsee->getVille());
                }

            } else {
                $country        = $entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2')->find($client->getIdPaysNaissance());
                $inseeCountries = $this->get('unilend.service.entity_manager')->getRepository('insee_pays');
                if (null !== $country && $inseeCountries->getByCountryIso(trim($country->getIso()))) {
                    $client->setInseeBirth($inseeCountries->COG);
                }
                unset($country, $inseeCountries);
            }
        }

        if (false == $clientAddress->getMemeAdresseFiscal()) {
            $this->checkPostalAddressSection($clientAddress, $form);
        }

        $isValidCaptcha = $this->isValidCaptcha($request);

        if ($isValidCaptcha) {
            $this->checkSecuritySection($client, $form);
        }

        if (false === $form->get('tos')->getData()) {
            $form->get('tos')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-terms-of-use')));
        }

        if ($isValidCaptcha && $form->isValid()) {
            $clientType   = ($client->getIdPaysNaissance() == \nationalites_v2::NATIONALITY_FRENCH) ? Clients::TYPE_PERSON : Clients::TYPE_PERSON_FOREIGNER;
            $password     = password_hash($client->getPassword(), PASSWORD_DEFAULT); // TODO: use the Symfony\Component\Security\Core\Encoder\UserPasswordEncoder (need TECH-108)
            $slug         = $ficelle->generateSlug($client->getPrenom() . '-' . $client->getNom());

            $client
                ->setPassword($password)
                ->setType($clientType)
                ->setIdLangue('fr')
                ->setSlug($slug)
                ->setStatus(Clients::STATUS_ONLINE)
                ->setStatusInscriptionPreteur(1)
                ->setEtapeInscriptionPreteur(Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION)
                ->setType($clientType);

            if ($clientAddress->getMemeAdresseFiscal()) {
                $clientAddress
                    ->setAdresse1($clientAddress->getAdresseFiscal())
                    ->setCp($clientAddress->getCpFiscal())
                    ->setVille($clientAddress->getVilleFiscal())
                    ->setIdPays($clientAddress->getIdPaysFiscal());
            }

            $entityManager->beginTransaction();

            try {
                $entityManager->persist($client);
                $clientAddress->setIdClient($client);
                $entityManager->persist($clientAddress);
                $entityManager->flush($clientAddress);

                $this->get('unilend.service.client_creation_manager')->createAccount($client, WalletType::LENDER, Users::USER_ID_FRONT, ClientsStatus::CREATION);
                $this->get('unilend.service.terms_of_sale_manager')->acceptCurrentVersion($client);

                $entityManager->commit();
            } catch (ORMException $exception) {
                $entityManager->getConnection()->rollBack();
                $this->get('logger')->error('An error occurred while creating client ', [['class' => __CLASS__, 'function' => __FUNCTION__]]);
            }

            $this->addClientToDataLayer($client);
            $this->sendSubscriptionStartConfirmationEmail($client);

            return true;
        }
        return false;
    }

    /**
     * @param Clients         $client
     * @param ClientsAdresses $clientAddress
     * @param Companies       $company
     * @param FormInterface   $form
     * @param Request         $request
     *
     * @return bool
     */
    private function handleLegalEntityForm(Clients $client, ClientsAdresses $clientAddress, Companies $company, FormInterface $form, Request $request): bool
    {
        /** @var \ficelle $ficelle */
        $ficelle       = Loader::loadLib('ficelle');
        $translator    = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');

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

        if (
            false === empty($company->getIdPays())
            && PaysV2::COUNTRY_FRANCE == $company->getIdPays()
            && null === $entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneByCp($company->getZip())
        ) {
            $form->get('fiscalAddress')->get('zip')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-fiscal-address-wrong-zip')));
        }

        if (false == $clientAddress->getMemeAdresseFiscal()) {
            $this->checkPostalAddressSection($clientAddress, $form);
        }

        $isValidCaptcha = $this->isValidCaptcha($request);

        if ($isValidCaptcha) {
            $this->checkSecuritySection($client, $form);
        }

        if (false === $form->get('tos')->getData()) {
            $form->get('tos')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-terms-of-use')));
        }

        if ($isValidCaptcha && $form->isValid()) {
            $clientType   = ($client->getIdPaysNaissance() == \nationalites_v2::NATIONALITY_FRENCH) ? Clients::TYPE_LEGAL_ENTITY : Clients::TYPE_LEGAL_ENTITY_FOREIGNER;
            $password     = password_hash($client->getPassword(), PASSWORD_DEFAULT); // TODO: use the Symfony\Component\Security\Core\Encoder\UserPasswordEncoder (need TECH-108)
            $slug         = $ficelle->generateSlug($client->getPrenom() . '-' . $client->getNom());

            $client
                ->setIdLangue('fr')
                ->setSlug($slug)
                ->setPassword($password)
                ->setStatus(Clients::STATUS_ONLINE)
                ->setStatusInscriptionPreteur(1)
                ->setEtapeInscriptionPreteur(Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION)
                ->setType($clientType);

            $company->setStatusAdresseCorrespondance($clientAddress->getMemeAdresseFiscal());

            if ($clientAddress->getMemeAdresseFiscal()) {
                $clientAddress
                    ->setAdresse1($company->getAdresse1())
                    ->setCp($company->getZip())
                    ->setVille($company->getCity())
                    ->setIdPays($company->getIdPays());
            } else {
                $clientAddress
                    ->setAdresseFiscal($company->getAdresse1())
                    ->setCpFiscal($company->getZip())
                    ->setVilleFiscal($company->getCity())
                    ->setIdPaysFiscal($company->getIdPays());
            }

            $entityManager->beginTransaction();

            try {
                $entityManager->persist($client);

                $clientAddress->setIdClient($client);

                $entityManager->persist($clientAddress);
                $entityManager->flush($clientAddress);

                $company->setIdClientOwner($client);

                $entityManager->persist($company);
                $entityManager->flush($company);

                $this->get('unilend.service.client_creation_manager')->createAccount($client, WalletType::LENDER, Users::USER_ID_FRONT, ClientsStatus::CREATION);
                $this->get('unilend.service.terms_of_sale_manager')->acceptCurrentVersion($client);

                $entityManager->commit();
            } catch (ORMException $exception) {
                $entityManager->getConnection()->rollBack();
                $this->get('logger')->error('An error occurred while creating client ', [['class' => __CLASS__, 'function' => __FUNCTION__]]);
            }

            $this->addClientToDataLayer($client);
            $this->sendSubscriptionStartConfirmationEmail($client);

            return true;
        }
        return false;
    }

    /**
     * @link https://www.cloudways.com/blog/add-recaptcha-to-symfony-3-forms/
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
    private function sendSubscriptionStartConfirmationEmail(Clients $client)
    {
        $keywords = [
            'firstName' => $client->getPrenom()
        ];

        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-inscription-preteur', $keywords);

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
     * @param Clients       $clientEntity
     * @param FormInterface $form
     */
    private function checkSecuritySection(Clients $clientEntity, FormInterface $form): void
    {
        $translator    = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if ($entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->existEmail($clientEntity->getEmail(), Clients::STATUS_ONLINE)) {
            $form->get('client')->get('email')->addError(new FormError($translator->trans('lender-profile_security-identification-error-existing-email')));
            $this->get('session')->set(self::SESSION_NAME_CAPTCHA, true);
        }

        if (false === BCryptPasswordEncoder::isPasswordSafe($clientEntity->getPassword())) { // todo: "try" BCryptPasswordEncoder::encodePassword() to check if the password is safe (need TECH-108)
            $form->get('client')->get('password')->addError(new FormError($translator->trans('common-validator_password-invalid')));
        }
    }

    /**
     * @param ClientsAdresses $clientAddressEntity
     * @param FormInterface   $form
     */
    private function checkPostalAddressSection(ClientsAdresses $clientAddressEntity, FormInterface $form): void
    {
        $translator = $this->get('translator');

        if (empty($clientAddressEntity->getAdresse1())) {
            $form->get('postalAddress')->get('adresse1')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-postal-address')));
        }
        if (empty($clientAddressEntity->getVille())) {
            $form->get('postalAddress')->get('ville')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-postal-address-city')));
        }
        if (empty($clientAddressEntity->getCp())) {
            $form->get('postalAddress')->get('cp')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-postal-address-zip')));
        }
        if (empty($clientAddressEntity->getIdPays())) {
            $form->get('postalAddress')->get('idPays')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-missing-postal-address-country')));
        }
    }

    /**
     * @Route("/inscription_preteur/etape2/{clientHash}", name="lender_subscription_documents", requirements={"clientHash": "[0-9a-f-]{32,36}"})
     *
     * @param string  $clientHash
     * @param Request $request
     *
     * @return Response
     */
    public function documentsAction($clientHash, Request $request)
    {
        $response = $this->checkProgressAndRedirect($request, $clientHash);
        if ($response instanceof RedirectResponse) {
            return $response;
        }

        $client        = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->findOneByHash($clientHash);
        $clientAddress = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneByIdClient($client->getIdClient());
        $formManager   = $this->get('unilend.frontbundle.service.form_manager');
        $form          = $formManager->getBankInformationForm($client);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->saveClientHistoryAction($client, $request, Clients::SUBSCRIPTION_STEP_DOCUMENTS);
            if ($form->isValid()) {
                $isValid = $this->handleDocumentsForm($clientAddress, $form, $request->files);
                if ($isValid) {
                    return $this->redirectToRoute('lender_subscription_money_deposit', ['clientHash' => $client->getHash()]);
                }
            }
        }

        $template = [
            'client'         => $client,
            'isLivingAbroad' => $clientAddress->getIdPaysFiscal() > PaysV2::COUNTRY_FRANCE,
            'fundsOrigin'    => $this->getFundsOrigin($client->getType()),
            'form'           => $form->createView()
        ];

        if (in_array($client->getType(), [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
            $template['company'] = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
        }

        return $this->render('lender_subscription/documents.html.twig', $template);
    }


    /**
     * @param ClientsAdresses $clientAddress
     * @param FormInterface   $form
     * @param FileBag         $fileBag
     *
     * @return bool
     */
    private function handleDocumentsForm(ClientsAdresses $clientAddress, FormInterface $form, FileBag $fileBag)
    {
        $translator  = $this->get('translator');
        /** @var Clients $client */
        $client              = $form->get('client')->getData();
        $iban                = $form->get('bankAccount')->get('iban')->getData();
        $bic                 = $form->get('bankAccount')->get('bic')->getData();
        $bankAccountDocument = null;

        if ('FR' !== strtoupper(substr($iban, 0, 2))) {
            $form->get('bankAccount')->get('iban')->addError(new FormError($translator->trans('lender-subscription_documents-iban-not-french-error-message')));
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

        if (in_array($client->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
            $this->validateAttachmentsPerson($form, $client, $clientAddress, $fileBag);
        } else {
            $company = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
            $this->validateAttachmentsLegalEntity($form, $client, $company, $fileBag);
        }

        if ($form->isValid() && $bankAccountDocument) {
            $client->setEtapeInscriptionPreteur(Clients::SUBSCRIPTION_STEP_DOCUMENTS);
            $this->get('doctrine.orm.entity_manager')->flush();

            $bankAccountManager = $this->get('unilend.service.bank_account_manager');
            $bankAccountManager->saveBankInformation($client, $bic, $iban, $bankAccountDocument);

            $clientStatusManager = $this->get('unilend.service.client_status_manager');
            $clientStatusManager->addClientStatus($client, Users::USER_ID_FRONT, ClientsStatus::TO_BE_CHECKED);

            $this->get('unilend.service.notification_manager')->generateDefaultNotificationSettings($client->getIdClient());
            $this->sendFinalizedSubscriptionConfirmationEmail($client);

            return true;
        }
        return false;
    }

    /**
     * @param FormInterface     $form
     * @param Clients           $client
     * @param ClientsAdresses   $clientAddress
     * @param FileBag           $fileBag
     */
    private function validateAttachmentsPerson(FormInterface $form, Clients $client, ClientsAdresses $clientAddress, FileBag $fileBag)
    {
        $translator         = $this->get('translator');
        $uploadErrorMessage = $translator->trans('lender-subscription_documents-upload-files-error-message');

        $files = [
            AttachmentType::CNI_PASSPORTE       => $fileBag->get('id_recto'),
            AttachmentType::CNI_PASSPORTE_VERSO => $fileBag->get('id_verso'),
            AttachmentType::JUSTIFICATIF_DOMICILE => $fileBag->get('housing-certificate'),
        ];
        if ($clientAddress->getIdPaysFiscal() > PaysV2::COUNTRY_FRANCE) {
            $files[AttachmentType::JUSTIFICATIF_FISCAL] = $fileBag->get('tax-certificate');
        }
        if (false === empty($form->get('housedByThirdPerson')->getData())) {
            $files[AttachmentType::ATTESTATION_HEBERGEMENT_TIERS] = $fileBag->get('housed-by-third-person-declaration');
            $files[AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT] = $fileBag->get('id-third-person-housing');
        }
        foreach ($files as $attachmentTypeId => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $this->upload($client,  $attachmentTypeId, $file);
                } catch (\Exception $exception) {
                    $form->addError(new FormError($uploadErrorMessage . $attachmentTypeId . ' error : ' . $exception->getMessage()));
                }
            } else {
                switch ($attachmentTypeId) {
                    case AttachmentType::CNI_PASSPORTE :
                        $error = $translator->trans('lender-subscription_documents-person-missing-id');
                        break;
                    case AttachmentType::JUSTIFICATIF_FISCAL :
                        $error = $translator->trans('lender-subscription_documents-person-missing-tax-certificate');
                        break;
                    case AttachmentType::JUSTIFICATIF_DOMICILE :
                        $error = $translator->trans('lender-subscription_documents-person-missing-housing-certificate');
                        break;
                    case AttachmentType::ATTESTATION_HEBERGEMENT_TIERS :
                        $error = $translator->trans('lender-subscription_documents-person-missing-housed-by-third-person-declaration');
                        break;
                    case AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT :
                        $error = $translator->trans('lender-subscription_documents-person-missing-id-third-person-housing');
                        break;
                    default :
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
    private function validateAttachmentsLegalEntity(FormInterface $form, Clients $client, Companies $company, FileBag $fileBag)
    {
        $translator         = $this->get('translator');
        $uploadErrorMessage = $translator->trans('lender-subscription_documents-upload-files-error-message');

        $files = [
            AttachmentType::CNI_PASSPORTE_DIRIGEANT => $fileBag->get('id_recto'),
            AttachmentType::CNI_PASSPORTE_VERSO     =>  $fileBag->get('id_verso'),
            AttachmentType::KBIS                    =>  $fileBag->get('company-registration')
        ];
        if ($company->getStatusClient() > Companies::CLIENT_STATUS_MANAGER) {
            $files[AttachmentType::DELEGATION_POUVOIR] = $fileBag->get('delegation-of-authority');
        }

        foreach ($files as $attachmentTypeId => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $this->upload($client,  $attachmentTypeId, $file);
                } catch (\Exception $exception) {
                    $form->addError(new FormError($uploadErrorMessage));
                }
            } else {
                switch ($attachmentTypeId) {
                    case AttachmentType::CNI_PASSPORTE :
                        $error = $translator->trans('lender-subscription_documents-person-missing-id');
                        break;
                    case AttachmentType::JUSTIFICATIF_FISCAL :
                        $error = $translator->trans('lender-subscription_documents-person-missing-tax-certificate');
                        break;
                    case AttachmentType::JUSTIFICATIF_DOMICILE :
                        $error = $translator->trans('lender-subscription_documents-person-missing-housing-certificate');
                        break;
                    case AttachmentType::ATTESTATION_HEBERGEMENT_TIERS :
                        $error = $translator->trans('lender-subscription_documents-person-missing-housed-by-third-person-declaration');
                        break;
                    case AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT :
                        $error = $translator->trans('lender-subscription_documents-person-missing-id-third-person-housing');
                        break;
                    default :
                        continue 2;
                }
                $form->addError(new FormError($error));
            }
        }
    }

    /**
     * @Route("/inscription_preteur/etape3/{clientHash}", name="lender_subscription_money_deposit", requirements={"clientHash": "[0-9a-f-]{32,36}"})
     * @Method("GET")
     *
     * @param string  $clientHash
     * @param Request $request
     *
     * @return Response
     */
    public function moneyDepositAction($clientHash, Request $request)
    {
        $response = $this->checkProgressAndRedirect($request, $clientHash);
        if ($response instanceof RedirectResponse) {
            return $response;
        }

        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($clientHash, 'hash');
        $client->etape_inscription_preteur = Clients::SUBSCRIPTION_STEP_MONEY_DEPOSIT;
        $client->update();

        $template = [
            'client'           => $client->select('id_client = ' . $client->id_client)[0],
            'maxDepositAmount' => LenderWalletController::MAX_DEPOSIT_AMOUNT,
            'minDepositAmount' => LenderWalletController::MIN_DEPOSIT_AMOUNT,
            'lenderBankMotif'  => $client->getLenderPattern($client->id_client)
        ];

        return $this->render('lender_subscription/money_deposit.html.twig', $template);
    }

    /**
     * @Route("/inscription_preteur/etape3/{clientHash}", name="lender_subscription_money_deposit_form", requirements={"clientHash": "[0-9a-f-]{32,36}"})
     * @Method("POST")
     *
     * @param string  $clientHash
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function moneyDepositFormAction($clientHash, Request $request)
    {
        $response = $this->checkProgressAndRedirect($request, $clientHash);
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
                $client        = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $clientHash]);
                $wallet        = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->getIdClient(), WalletType::LENDER);
                $successUrl    = $this->generateUrl('lender_subscription_money_transfer', ['clientHash' => $wallet->getIdClient()->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);
                $cancelUrl     = $this->generateUrl('lender_subscription_money_deposit', ['clientHash' => $wallet->getIdClient()->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);

                $redirectUrl = $this->get('unilend.service.payline_manager')->pay($amount, $wallet, $successUrl, $cancelUrl);

                $formManager = $this->get('unilend.frontbundle.service.form_manager');
                $formManager->saveFormSubmission($client, ClientsHistoryActions::LENDER_PROVISION_BY_CREDIT_CARD, serialize(['id_client' => $client->getIdClient(), 'post' => $request->request->all()]), $request->getClientIp());

                if (false !== $redirectUrl) {
                    return $this->redirect($redirectUrl);
                }
            }
        }

        return $this->redirectToRoute('lender_subscription_money_deposit', ['clientHash' => $clientHash]);
    }

    /**
     * @Route("/inscription_preteur/payment/{clientHash}", name="lender_subscription_money_transfer")
     *
     * @return RedirectResponse
     */
    public function paymentAction(Request $request, $clientHash): RedirectResponse
    {
        $translator       = $this->get('translator');
        $entityManager    = $this->get('doctrine.orm.entity_manager');
        $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $client           = $clientRepository->findOneBy(['hash' => $clientHash]);

        if (null === $client) {
            return $this->redirectToRoute('home_lender');
        }

        $token   = $request->get('token');
        $version = $request->get('version', Backpayline::WS_DEFAULT_VERSION);

        if (true === empty($token)) {
            $this->get('logger')->error(
                'Payline token not found for client ' . $client->getIdClient(),
                ['id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );

            return $this->redirectToRoute('lender_wallet', ['depositResult' => true]);
        }

        $paylineManager   = $this->get('unilend.service.payline_manager');
        $paidAmountInCent = $paylineManager->handlePaylineReturn($token, $version);

        if (false !== $paidAmountInCent) {
            $clientHistory = new ClientsHistory();
            $clientHistory->setIdClient($client);
            $clientHistory->setType(ClientsHistory::TYPE_CLIENT_LENDER);
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
     * @Route("/devenir-preteur-lp", name="lender_landing_page")
     * @Method("GET")
     */
    public function landingPageAction()
    {
        return $this->render('lender_subscription/landing_page.html.twig', [
            'showWelcomeOffer'   => $this->get('unilend.service.welcome_offer_manager')->displayOfferOnLandingPage(),
            'welcomeOfferAmount' => $this->get('unilend.service.welcome_offer_manager')->getWelcomeOfferAmount(OffresBienvenues::TYPE_LANDING_PAGE)
        ]);
    }

    /**
     * @Route("/parrainage-preteur", name="lender_sponsorship_landing_page")
     * @Method("GET")
     */
    public function sponsorshipLandingPageAction(Request $request)
    {
        $sponsorshipManager = $this->get('unilend.service.sponsorship_manager');
        $template           = [
            'isSponsorship'   => false,
            'currentCampaign' => $sponsorshipManager->getCurrentSponsorshipCampaign()
        ];

        if (null === $template['currentCampaign']) {
            return $this->redirectToRoute('lender_landing_page');
        }

        if (
            SponsorshipManager::UTM_SOURCE === $request->query->get('utm_source')
            && SponsorshipManager::UTM_MEDIUM === $request->query->get('utm_medium')
            && SponsorshipManager::UTM_CAMPAIGN === $request->query->get('utm_campaign')
            && null !== $sponsorshipManager->getSponsorBySponsorCode($request->query->get('sponsor'))
            && null !== $template['currentCampaign']
        ) {
            $template['isSponsorship']    = true;
            $template['showWelcomeOffer'] = false;
            $template['sponsorCode']      = $request->query->get('sponsor');
        }

        return $this->render('lender_subscription/sponsorship_landing_page.html.twig', $template);
    }

    /**
     * @Route("/devenir-preteur-lp-form", name="lender_landing_page_form_only")
     * @Method("GET")
     * @return Response
     */
    public function landingPageFormOnlyAction()
    {
        return $this->render('lender_subscription/landing_page_form_only.html.twig', [
            'showWelcomeOffer'   => $this->get('unilend.service.welcome_offer_manager')->displayOfferOnLandingPage(),
            'welcomeOfferAmount' => $this->get('unilend.service.welcome_offer_manager')->getWelcomeOfferAmount(OffresBienvenues::TYPE_LANDING_PAGE)
        ]);
    }

    /**
     * Scheme and host are absolute to make partners LPs work
     * @Route("/devenir-preteur-lp", schemes="https", host="%url.host_default%", name="lender_landing_page_form")
     * @Method("POST")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function landingPageFormAction(Request $request)
    {
        /** @var \prospects $prospect */
        $prospect    = $this->get('unilend.service.entity_manager')->getRepository('prospects');
        $translator  = $this->get('translator');;
        $formManager = $this->get('unilend.frontbundle.service.form_manager');
        $post        = $formManager->cleanPostData($request->request->all());

        $this->get('session')->set('landingPageData', $post);

        if (isset($post['prospect_name'])) {
            $name = filter_var($post['prospect_name'], FILTER_SANITIZE_STRING);
        } else {
            $this->addFlash('landingPageErrors', $translator->trans('common-validator_last-name-empty'));
        }

        if (isset($post['prospect_first_name'])) {
            $firstName = filter_var($post['prospect_first_name'], FILTER_SANITIZE_STRING);
        } else {
            $this->addFlash('landingPageErrors', $translator->trans('common-validator_first-name-empty'));
        }

        if (isset($post['prospect_email'])) {
            $email = filter_var($post['prospect_email'], FILTER_VALIDATE_EMAIL);
            if (false === $email) {
                $this->addFlash('landingPageErrors', $translator->trans('lender-landing-page_error-email'));
            }
        } else {
            $this->addFlash('landingPageErrors', $translator->trans('lender-landing-page_error-email'));
        }

        if (false === $this->get('session')->getFlashBag()->has('landingPageErrors')) {
            if (false === $prospect->exist($post['prospect_email'], 'email') && isset($name, $firstName, $email)) {
                $sourceManager          = $this->get('unilend.frontbundle.service.source_manager');
                $prospect->source       = $sourceManager->getSource(SourceManager::SOURCE1);
                $prospect->source2      = $sourceManager->getSource(SourceManager::SOURCE2);
                $prospect->source3      = $sourceManager->getSource(SourceManager::SOURCE3);
                $prospect->slug_origine = $sourceManager->getSource(SourceManager::ENTRY_SLUG);
                $prospect->nom          = $name;
                $prospect->prenom       = $firstName;
                $prospect->email        = $email;
                $prospect->id_langue    = 'fr';
                $prospect->create();
            }
            return $this->redirectToRoute('lender_subscription_personal_information');
        } else {
            return $this->redirectToRoute('lender_landing_page');
        }
    }

    /**
     * @param Request     $request
     * @param string|null $clientHash
     *
     * @return RedirectResponse|null
     */
    private function checkProgressAndRedirect(Request $request, ?string $clientHash = null): ?RedirectResponse
    {
        $clientRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients');

        $redirectPath = null;
        $currentPath = $request->getPathInfo();

        $authorizationChecker = $this->get('security.authorization_checker');

        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') || false === is_null($clientHash)) {
            if ($authorizationChecker->isGranted('ROLE_BORROWER')) {
                return $this->redirectToRoute('projects_list');
            }

            if ($authorizationChecker->isGranted('ROLE_LENDER')) {
                $clientEntity = $clientRepository->find($this->getUser()->getClientId());

                if (
                    null !== $clientEntity->getIdClientStatusHistory()
                    && $clientEntity->getIdClientStatusHistory()->getIdStatus()->getId() >= ClientsStatus::MODIFICATION
                    && Clients::SUBSCRIPTION_STEP_MONEY_DEPOSIT === $clientEntity->getEtapeInscriptionPreteur()
                ) {
                    return $this->redirectToRoute('lender_dashboard');
                }
            } else {
                $clientEntity = $clientRepository->findOneBy(['hash' => $clientHash]);

                if (
                    null === $clientEntity
                    || Clients::STATUS_ONLINE !== $clientEntity->getStatus()
                    || $clientEntity->getEtapeInscriptionPreteur() > Clients::SUBSCRIPTION_STEP_MONEY_DEPOSIT
                ) {
                    return $this->redirectToRoute('login');
                }
            }
            $redirectPath = $this->get('unilend.service.client_manager')->getSubscriptionStepRedirectRoute($clientEntity);
        } else {
            $personalFormRoute =['lender_subscription_personal_information_person_form', 'lender_subscription_personal_information_legal_entity_form'];
            if (! in_array($request->get('_route'), $personalFormRoute)) {
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
     * @param Request $request
     * @param int     $step
     */
    private function saveClientHistoryAction(Clients $client, Request $request, $step)
    {
        $formManager = $this->get('unilend.frontbundle.service.form_manager');
        $post        = $formManager->cleanPostData($request->request->all());
        $files       = $request->files;

        if (1 == $step) {
            $post['form']['client']['password']['first']  = md5($post['form']['client']['password']['first']);
            $post['form']['client']['password']['second'] = md5($post['form']['client']['password']['second']);
            $post['form']['security']['secreteReponse']   = md5($post['form']['security']['secreteReponse']);
            $formType = in_array($client->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER]) ? ClientsHistoryActions::LENDER_PERSON_SUBSCRIPTION_PERSONAL_INFORMATION : ClientsHistoryActions::LENDER_LEGAL_ENTITY_SUBSCRIPTION_PERSONAL_INFORMATION;
        } else {
            $formType = in_array($client->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER]) ? ClientsHistoryActions::LENDER_PERSON_SUBSCRIPTION_BANK_DOCUMENTS : ClientsHistoryActions::LENDER_LEGAL_ENTITY_SUBSCRIPTION_BANK_DOCUMENTS;
        }

        if (false === empty($files)) {
            $post = array_merge($post, $formManager->getNamesOfFiles($files));
        }

        $formManager->saveFormSubmission($client, $formType, serialize(['id_client' => $client->getIdClient(), 'post' => $post]), $request->getClientIp());
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
     * @Route("/inscription_preteur/ajax/birth_place", name="lender_subscription_ajax_birth_place")
     * @Method("GET")
     */
    public function getBirthPlaceAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $locationManager = $this->get('unilend.service.location_manager');
            return new JsonResponse($locationManager->getCities($request->query->get('birthPlace'), true));
        }

        return new Response('not an ajax request');
    }

    /**
     * @Route("/inscription_preteur/ajax/city", name="lender_subscription_ajax_city")
     * @Method("GET")
     */
    public function getCityAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $locationManager = $this->get('unilend.service.location_manager');
            return new JsonResponse($locationManager->getCities($request->query->get('city')));
        }

        return new Response('not an ajax request');
    }

    /**
     * @Route("/inscription_preteur/ajax/zip", name="lender_subscription_ajax_zip")
     * @Method("GET")
     */
    public function getZipAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $locationManager = $this->get('unilend.service.location_manager');
            return new JsonResponse($locationManager->getCities($request->query->get('zip')));
        }

        return new Response('not an ajax request');
    }

    /**
     * @Route("/inscription_preteur/ajax/age", name="lender_subscription_ajax_age")
     * @Method("POST")
     */
    public function checkAgeAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            /** @var \dates $dates */
            $dates      = Loader::loadLib('dates');
            $translator = $this->get('translator');

            if ($dates->ageplus18($request->request->get('year_of_birth') . '-' . $request->request->get('month_of_birth') . '-' . $request->request->get('day_of_birth'))) {
                return new JsonResponse([
                    'status' => true
                ]);
            } else {
                return new JsonResponse([
                    'status' => false,
                    'error'  => $translator->trans('lender-subscription_personal-information-error-age')
                ]);
            }
        }
        return new Response('not an ajax request');
    }

    /**
     * @param Clients $client
     */
    private function sendFinalizedSubscriptionConfirmationEmail(Clients $client)
    {
        $keywords = [
            'firstName' => $client->getPrenom(),
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
     * @Route("/inscription_preteur/ajax/check-city", name="lender_subscription_ajax_check_city")
     * @Method("GET")
     */
    public function checkCityAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $get = $request->query->all();

            if (false === empty($get['country']) && PaysV2::COUNTRY_FRANCE != $get['country']) {
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
     * @Route("/inscription_preteur/ajax/check-city-insee", name="lender_subscription_ajax_check_city_insee")
     * @Method("GET")
     */
    public function checkCityInseeCodeAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $country = $request->query->get('country');
            $inseeCode = $request->query->get('insee');

            if (false === empty($country) && PaysV2::COUNTRY_FRANCE != $country) {
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
     * @param int $clientType
     *
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
     * @param Clients $client
     */
    private function addClientSources(Clients $client)
    {
        $sourceManager = $this->get('unilend.frontbundle.service.source_manager');

        $client->setSource($sourceManager->getSource(SourceManager::SOURCE1))
            ->setSource2($sourceManager->getSource(SourceManager::SOURCE2))
            ->setSource3($sourceManager->getSource(SourceManager::SOURCE3))
            ->setSlugOrigine($sourceManager->getSource(SourceManager::ENTRY_SLUG));
    }

    /**
     * @param Clients $clientEntity
     */
    private function addClientToDataLayer(Clients $clientEntity)
    {
        $this->get('session')->set(DataLayerCollector::SESSION_KEY_CLIENT_EMAIL, $clientEntity->getEmail());
        $this->get('session')->set(DataLayerCollector::SESSION_KEY_LENDER_CLIENT_ID, $clientEntity->getIdClient());
    }

    /**
     * @param \DateTime $birthDay
     * @return bool
     */
    private function isAtLeastEighteenYearsOld(\DateTime $birthDay)
    {
        $now = new \DateTime('NOW');
        $dateDiff = $birthDay->diff($now);

        return $dateDiff->y >= 18;
    }

}
