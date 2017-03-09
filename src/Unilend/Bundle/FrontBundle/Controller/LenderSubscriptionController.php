<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Entity\Settings;
use Unilend\Bundle\CoreBusinessBundle\Entity\Villes;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;
use Unilend\Bundle\FrontBundle\Service\ContentManager;
use Unilend\Bundle\FrontBundle\Service\DataLayerCollector;
use Unilend\Bundle\FrontBundle\Service\SourceManager;
use Unilend\core\Loader;

class LenderSubscriptionController extends Controller
{
    /**
     * @Route("inscription_preteur/etape1", name="lender_subscription_personal_information")
     */
    public function personalInformationAction(Request $request)
    {
        $response = $this->checkProgressAndRedirect($request);
        if ($response instanceof RedirectResponse){
            return $response;
        }

        $client        = new Clients();
        $clientAddress = new ClientsAdresses();
        $company       = new Companies();

        if (false === empty($this->get('session')->get('landingPageData'))) {
            $landingPageData = $this->get('session')->get('landingPageData');
            $this->get('session')->remove('landingPageData');
            $client->setNom($landingPageData['prospect_name']);
            $client->setPrenom($landingPageData['prospect_first_name']);
            $client->setEmail($landingPageData['prospect_email']);
        }

        $formManager         = $this->get('unilend.frontbundle.service.form_manager');
        $identityForm        = $formManager->getLenderSubscriptionPersonIdentityForm($client, $clientAddress);
        $companyIdentityForm = $formManager->getLenderSubscriptionLegalEntityIdentityForm($client, $company, $clientAddress);

        $identityForm->handleRequest($request);
        $companyIdentityForm->handleRequest($request);

        if ($request->isMethod('POST')) {
            if ($identityForm->isSubmitted() && $identityForm->isValid()) {
                $isValid = $this->handleIdentityPersonForm($client, $clientAddress, $identityForm);
                if ($isValid) {
                    $this->saveClientHistoryAction($client, $request, Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION);
                    return $this->redirectToRoute('lender_subscription_documents', ['clientHash' => $client->getHash()]);
                }
            }

            if ($companyIdentityForm->isSubmitted() && $companyIdentityForm->isValid()) {
                $isValid = $this->handleLegalEntityForm($client, $clientAddress, $company, $companyIdentityForm);
                if ($isValid) {
                    $this->saveClientHistoryAction($client, $request, Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION);
                    return $this->redirectToRoute('lender_subscription_documents', ['clientHash' => $client->getHash()]);
                }
            }
        }

        /** @var array $template */
        $template = [
            'termsOfUseLegalEntity' => $this->generateUrl('lenders_terms_of_sales', ['type' => 'morale']),
            'termsOfUsePerson'      => $this->generateUrl('lenders_terms_of_sales'),
            'identityForm'          => $identityForm->createView(),
            'companyIdentityForm'   => $companyIdentityForm->createView()
        ];

        return $this->render('pages/lender_subscription/personal_information.html.twig', $template);
    }

    /**
     * @param Clients         $clientEntity
     * @param ClientsAdresses $clientAddressEntity
     * @param FormInterface   $form
     *
     * @return bool
     */
    private function handleIdentityPersonForm(Clients $clientEntity, ClientsAdresses $clientAddressEntity, FormInterface $form)
    {
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        if (false === $this->isAtLeastEighteenYearsOld($clientEntity->getNaissance())) {
            $form->get('client')->get('naissance')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-age')));
        }

        if (\pays_v2::COUNTRY_FRANCE == $clientAddressEntity->getIdPaysFiscal() && null === $em->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $clientAddressEntity->getCpFiscal()])) {
            $form->get('fiscalAddress')->get('cpFiscal')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-fiscal-address-wrong-zip')));
        }

        $countryCheck = true;
        if (null === $em->getRepository('UnilendCoreBusinessBundle:Nationalites')->find($clientEntity->getIdNationalite())) {
            $countryCheck = false;
            $form->get('client')->get('idNationalite')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-wrong-nationality')));
        }
        if (null === $em->getRepository('UnilendCoreBusinessBundle:PaysV2')->find($clientEntity->getIdPaysNaissance())) {
            $countryCheck = false;
            $form->get('client')->get('idNationalite')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-wrong-birth-country')));
        }

        if (\pays_v2::COUNTRY_FRANCE == $clientEntity->getIdPaysNaissance() && empty($clientEntity->getInseeBirth())) {
            $countryCheck = false;
            $form->get('client')->get('villeNaissance')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-wrong-birth-place')));
        }

        if ($countryCheck) {
            if (\pays_v2::COUNTRY_FRANCE == $clientEntity->getIdPaysNaissance() && false === empty($clientEntity->getInseeBirth())) {
                /** @var Villes $cityByInsee */
                $cityByInsee = $em->getRepository('UnilendCoreBusinessBundle:Villes')->findOneByInsee($clientEntity->getInseeBirth());
                /** @var Villes $cityByCity */
                $cityByCity = $em->getRepository('UnilendCoreBusinessBundle:Villes')->findOneByVille($clientEntity->getVilleNaissance());

                if (null !== $cityByInsee && null !== $cityByCity && $cityByInsee->getInsee() === $cityByCity->getInsee()) {
                    $clientEntity->setVilleNaissance($cityByCity->getVille());
                } else {
                    $clientEntity->setInseeBirth($cityByCity->getVille());
                }

            } else {
                /** @var PaysV2 $country */
                $country = $em->getRepository('UnilendCoreBusinessBundle:PaysV2')->find($clientEntity->getIdPaysNaissance());
                /** @var \insee_pays $inseeCountries */
                $inseeCountries = $this->get('unilend.service.entity_manager')->getRepository('insee_pays');
                if (null !== $country && $inseeCountries->getByCountryIso(trim($country->getIso()))) {
                    $clientEntity->setInseeBirth($inseeCountries->COG);
                }
                unset($country, $inseeCountries);
            }
        }

        if (false == $clientAddressEntity->getMemeAdresseFiscal()) {
            $this->checkPostalAddressSection($clientAddressEntity, $form);
        }

        $this->checkSecuritySection($clientEntity, $form);

        if (false === $form->get('tos')->getData()) {
            $form->get('tos')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-terms-of-use')));
        }

        if ($form->isValid()) {
            $this->addClientSources($clientEntity);

            $clientType   = ($clientEntity->getIdPaysNaissance() == \nationalites_v2::NATIONALITY_FRENCH) ? Clients::TYPE_PERSON : Clients::TYPE_PERSON_FOREIGNER;
            $secretAnswer = md5($clientEntity->getSecreteReponse());
            $password     = password_hash($clientEntity->getPassword(), PASSWORD_DEFAULT); // TODO: use the Symfony\Component\Security\Core\Encoder\UserPasswordEncoder (need TECH-108)
            $slug         = $ficelle->generateSlug($clientEntity->getPrenom() . '-' . $clientEntity->getNom());

            $clientEntity
                ->setPassword($password)
                ->setSecreteReponse($secretAnswer)
                ->setType($clientType)
                ->setIdLangue('fr')
                ->setSlug($slug)
                ->setStatus(Clients::STATUS_ONLINE)
                ->setStatusInscriptionPreteur(1)
                ->setEtapeInscriptionPreteur(Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION)
                ->setType($clientType);

            if ($clientAddressEntity->getMemeAdresseFiscal()) {
                $clientAddressEntity
                    ->setAdresse1($clientAddressEntity->getAdresseFiscal())
                    ->setCp($clientAddressEntity->getCpFiscal())
                    ->setVille($clientAddressEntity->getVilleFiscal())
                    ->setIdPays($clientAddressEntity->getIdPaysFiscal());
            }

            /** @var EntityManager $em */
            $em = $this->get('doctrine.orm.entity_manager');
            $em->beginTransaction();
            try {
                $em->persist($clientEntity);
                $em->flush();
                $clientAddressEntity->setIdClient($clientEntity->getIdClient());
                $em->persist($clientAddressEntity);
                $em->flush();
                $this->get('unilend.service.wallet_creation_manager')->createWallet($clientEntity, WalletType::LENDER);
                $this->get('unilend.service.client_manager')->acceptLastTos($clientEntity);
                $em->commit();
            } catch (Exception $exception) {
                $em->getConnection()->rollBack();
                $this->get('logger')->error('An error occurred while creating client ' [['class'    => __CLASS__, 'function' => __FUNCTION__]]);
            }

            $this->addClientToDataLayer($clientEntity);
            $this->sendSubscriptionStartConfirmationEmail($clientEntity);

            return true;
        }
        return false;
    }

    /**
     * @param Clients         $clientEntity
     * @param ClientsAdresses $clientAddressEntity
     * @param Companies       $companyEntity
     * @param FormInterface   $form
     *
     * @return bool
     */
    private function handleLegalEntityForm(Clients $clientEntity, ClientsAdresses $clientAddressEntity, Companies $companyEntity, FormInterface $form)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        if (
            Companies::CLIENT_STATUS_DELEGATION_OF_POWER == $companyEntity->getStatusClient()
            || Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT == $companyEntity->getStatusClient()
        ) {
            if (empty($companyEntity->getCiviliteDirigeant())) {
                $form->get('company')->get('civiliteDirigeant')->addError(new FormError($translator->trans('lender-subscription_personal-information-company-director-form-of-address-missing')));
            }
            if (empty($companyEntity->getNomDirigeant())) {
                $form->get('company')->get('civiliteDirigeant')->addError(new FormError($translator->trans('lender-subscription_personal-information-company-director-name-missing')));
            }
            if (empty($companyEntity->getPrenomDirigeant())) {
                $form->get('company')->get('civiliteDirigeant')->addError(new FormError($translator->trans('lender-subscription_personal-information-company-director-first-name-missing')));
            }
            if (empty($companyEntity->getFonctionDirigeant())) {
                $form->get('company')->get('civiliteDirigeant')->addError(new FormError($translator->trans('lender-subscription_personal-information-company-director-position-missing')));
            }
            if (empty($companyEntity->getEmailDirigeant())) {
                $form->get('company')->get('emailDirigeant')->addError(new FormError($translator->trans('common_email-missing')));
            }
            if (empty($companyEntity->getPhoneDirigeant())) {
                $form->get('company')->get('civiliteDirigeant')->addError(new FormError($translator->trans('lender-subscription_personal-information-company-director-phone-missing')));
            }
            if (Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT == $companyEntity->getStatusClient()) {
                if (empty($companyEntity->getStatusConseilExterneEntreprise())) {
                    $form->get('company')->get('statusConseilExterneEntreprise')->addError(new FormError($translator->trans('lender-subscription_personal-information-company-external-counsel-error-message')));
                    if (
                        Companies::CLIENT_STATUS_EXTERNAL_COUNSEL_OTHER == $companyEntity->getStatusConseilExterneEntreprise()
                        && empty($companyEntity->getPreciserConseilExterneEntreprise())
                    ) {
                        $form->get('company')->get('statusConseilExterneEntreprise')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-identity-company-missing-external-counsel-other')));
                    }
                }
            }
        }

        if (
            false === empty($companyEntity->getIdPays())
            && \pays_v2::COUNTRY_FRANCE == $companyEntity->getIdPays()
            && null === $em->getRepository('UnilendCoreBusinessBundle:Villes')->findOneByCp($companyEntity->getZip())
        ) {
            $form->get('fiscalAddress')->get('zip')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-fiscal-address-wrong-zip')));
        }

        if (false == $clientAddressEntity->getMemeAdresseFiscal()) {
            $this->checkPostalAddressSection($clientAddressEntity, $form);
        }

        $this->checkSecuritySection($clientEntity, $form);

        if (false === $form->get('tos')->getData()) {
            $form->get('tos')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-terms-of-use')));
        }

        if ($form->isValid()){
            $this->addClientSources($clientEntity);

            $clientType   = ($clientEntity->getIdPaysNaissance() == \nationalites_v2::NATIONALITY_FRENCH) ? Clients::TYPE_LEGAL_ENTITY : Clients::TYPE_LEGAL_ENTITY_FOREIGNER;
            $secretAnswer = md5($clientEntity->getSecreteReponse());
            $password     = password_hash($clientEntity->getPassword(), PASSWORD_DEFAULT); // TODO: use the Symfony\Component\Security\Core\Encoder\UserPasswordEncoder (need TECH-108)
            $slug         = $ficelle->generateSlug($clientEntity->getPrenom() . '-' . $clientEntity->getNom());

            $clientEntity
                ->setIdLangue('fr')
                ->setSlug($slug)
                ->setSecreteReponse($secretAnswer)
                ->setPassword($password)
                ->setStatus(Clients::STATUS_ONLINE)
                ->setStatusInscriptionPreteur(1)
                ->setEtapeInscriptionPreteur(1)
                ->setType($clientType);

            $companyEntity->setStatusAdresseCorrespondance($clientAddressEntity->getMemeAdresseFiscal());

            if ($clientAddressEntity->getMemeAdresseFiscal()) {
                $clientAddressEntity
                    ->setAdresse1($companyEntity->getAdresse1())
                    ->setCp($companyEntity->getZip())
                    ->setVille($companyEntity->getCity())
                    ->setIdPays($companyEntity->getIdPays());
            } else {
                $clientAddressEntity
                    ->setAdresseFiscal($companyEntity->getAdresse1())
                    ->setCpFiscal($companyEntity->getZip())
                    ->setVilleFiscal($companyEntity->getCity())
                    ->setIdPaysFiscal($companyEntity->getIdPays());
            }

            /** @var EntityManager $em */
            $em = $this->get('doctrine.orm.entity_manager');
            $em->beginTransaction();
            try {
                $em->persist($clientEntity);
                $em->flush();
                $clientAddressEntity->setIdClient($clientEntity->getIdClient());
                $em->persist($clientAddressEntity);
                $companyEntity->setIdClientOwner($clientEntity->getIdClient());
                $em->persist($companyEntity);
                $em->flush();
                $this->get('unilend.service.wallet_creation_manager')->createWallet($clientEntity, WalletType::LENDER);
                $em->commit();
            } catch (Exception $exception) {
                $em->getConnection()->rollBack();
                $this->get('logger')->error('An error occurred while creating client ' [['class' => __CLASS__, 'function' => __FUNCTION__]]);
            }

            $this->get('unilend.service.client_manager')->acceptLastTos($clientEntity);
            $this->addClientToDataLayer($clientEntity);
            $this->sendSubscriptionStartConfirmationEmail($clientEntity);

            return true;
        }
        return false;
    }

    /**
     * @param Clients $client
     */
    private function sendSubscriptionStartConfirmationEmail(Clients $client)
    {
        $settingRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Settings');
        /** @var Settings $facebookSetting */
        $faceBookSetting = $settingRepository->findOneBy(['type' => 'Facebook']);
        /** @var Settings $twitterSetting */
        $twitterSetting = $settingRepository->findOneBy(['type' => 'Twitter']);
        /** @var LenderManager $lenderManager */
        $lenderManager = $this->get('unilend.service.lender_manager');

        $varMail = [
            'surl'           => $this->get('assets.packages')->getUrl(''),
            'url'            => $this->get('assets.packages')->getUrl(''),
            'prenom'         => $client->getPrenom(),
            'email_p'        => $client->getEmail(),
            'motif_virement' => $lenderManager->getLenderPattern($client),
            'lien_fb'        => $faceBookSetting->getValue(),
            'lien_tw'        => $twitterSetting->getValue(),
            'annee'          => date('Y')
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-inscription-preteur', $varMail);
        $message->setTo($client->getEmail());
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    /**
     * @param Clients       $clientEntity
     * @param FormInterface $form
     */
    private function checkSecuritySection(Clients $clientEntity, FormInterface $form)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');


        if ($clientEntity->getEmail() !== $form->get('client')->get('emailConfirmation')->getData()) {
            $form->get('client')->get('email')->addError(new FormError($translator->trans('common-validator_email-address-invalid')));
        }

        if ($em->getRepository('UnilendCoreBusinessBundle:Clients')->existEmail($clientEntity->getEmail())
        ) {
            $form->get('client')->get('email')->addError(new FormError($translator->trans('lender-profile_security-identification-error-existing-email')));
        }

        if ($clientEntity->getPassword() !== $form->get('client')->get('passwordConfirmation')->getData()) {
            $form->get('client')->get('passwordConfirmation')->addError(new FormError($translator->trans('common-validator_password-not-equal')));
        }
        if (false === $ficelle->password_fo($clientEntity->getPassword(), 6)) {
            $form->get('client')->get('password')->addError(new FormError($translator->trans('common-validator_password-invalid')));
        }
    }

    /**
     * @param ClientsAdresses $clientAddressEntity
     * @param FormInterface   $form
     */
    private function checkPostalAddressSection(ClientsAdresses $clientAddressEntity, FormInterface $form)
    {
        /** @var TranslatorInterface $translator */
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
     * @Route("inscription_preteur/etape2/{clientHash}", name="lender_subscription_documents", requirements={"clientHash": "[0-9a-f-]{32,36}"})
     *
     * @param string  $clientHash
     * @param Request $request
     * @return Response
     */
    public function documentsAction($clientHash, Request $request)
    {
        $response = $this->checkProgressAndRedirect($request, $clientHash);
        if ($response instanceof RedirectResponse){
            return $response;
        }

        $client        = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->findOneByHash($clientHash);
        $clientAddress = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneByIdClient($client->getIdClient());
        $bankAccount   = new BankAccount();
        $formManager   = $this->get('unilend.frontbundle.service.form_manager');
        $form          = $formManager->getBankInformationForm($bankAccount, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->saveClientHistoryAction($client, $request, Clients::SUBSCRIPTION_STEP_DOCUMENTS);
            if ($form->isValid()) {
                $isValid = $this->handleDocumentsForm($client, $bankAccount, $clientAddress, $form);
                if ($isValid) {
                    return $this->redirectToRoute('lender_subscription_money_deposit', ['clientHash' => $client->getHash()]);
                }
            }
        }

        $template = [
            'client'         => $client,
            'isLivingAbroad' => $clientAddress->getIdPaysFiscal() > \pays_v2::COUNTRY_FRANCE,
            'fundsOrigin'    => $this->getFundsOrigin($client->getType()),
            'form'           => $form->createView()
        ];

        if (in_array($client->getType(), [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
            $template['company'] = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Companies')->findOneByIdClientOwner($client);
        }

        return $this->render('pages/lender_subscription/documents.html.twig', $template);
    }


    /**
     * @param Clients         $client
     * @param BankAccount     $bankAccount
     * @param ClientsAdresses $clientAddress
     * @param FormInterface   $form
     *
     * @return bool
     */
    private function handleDocumentsForm(Clients $client, BankAccount $bankAccount, ClientsAdresses $clientAddress, FormInterface $form)
    {
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lenderAccount->get($client->getIdClient(), 'id_client_owner');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        if ('FR' !== strtoupper(substr($bankAccount->getIban(), 0, 2))) {
            $form->get('bankAccount')->get('iban')->addError(new FormError($translator->trans('lender-subscription_documents-iban-not-french-error-message')));
        }

        $fundsOrigin = $this->getFundsOrigin($client->getType());
        if (empty($client->getFundsOrigin()) || empty($fundsOrigin[$client->getFundsOrigin()])) {
            $form->get('client')->get('fundsOrigin')->addError(new FormError($translator->trans('lender-subscription-documents_wrong-funds-origin')));
        }

        if (isset($_FILES['rib']) && false === empty($_FILES['rib']['name'])) {
            $attachmentIdRib = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::RIB, 'rib');
            if (false === is_numeric($attachmentIdRib)) {
                $form->get('bankAccount')->addError(new FormError($translator->trans('lender-subscription_documents-upload-files-error-message')));
            }
        } else {
            $form->addError(new FormError($translator->trans('lender-subscription_documents-missing-rib')));
        }

        if (in_array($client->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
            $this->validateAttachmentsPerson($form, $lenderAccount, $clientAddress);
        } else {
            $company = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Companies')->findOneByIdClientOwner($client);
            $this->validateAttachmentsLegalEntity($form, $lenderAccount, $company);
        }

        if ($form->isValid()) {
            $client->setEtapeInscriptionPreteur(Clients::SUBSCRIPTION_STEP_DOCUMENTS);
            $this->get('doctrine.orm.entity_manager')->flush();

            /** @var BankAccountManager $bankAccountManager */
            $bankAccountManager = $this->get('unilend.service.bank_account_manager');
            $bankAccountManager->saveBankInformation($client, $bankAccount->getBic(), $bankAccount->getIban());

            /** @var ClientStatusManager $clientStatusManager */
            $clientStatusManager = $this->get('unilend.service.client_status_manager');
            $clientStatusManager->addClientStatus($client, \users::USER_ID_FRONT, \clients_status::TO_BE_CHECKED);

            $this->get('unilend.service.notification_manager')->generateDefaultNotificationSettings($client);
            $this->sendFinalizedSubscriptionConfirmationEmail($client);

            return true;
        }
        return false;
    }

    /**
     * @param FormInterface     $form
     * @param \lenders_accounts $lenderAccount
     * @param ClientsAdresses   $clientAddress
     */
    private function validateAttachmentsPerson(FormInterface $form, \lenders_accounts $lenderAccount, ClientsAdresses $clientAddress)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        $uploadErrorMessage = $translator->trans('lender-subscription_documents-upload-files-error-message');

        if (isset($_FILES['id_recto']) && false === empty($_FILES['id_recto']['name'])) {
            $attachmentIdRecto = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE, 'id_recto');
            if (false === is_numeric($attachmentIdRecto)) {
                $form->addError(new FormError($uploadErrorMessage));
            }
        } else {
            $form->addError(new FormError($translator->trans('lender-subscription_documents-person-missing-id')));
        }

        if (isset($_FILES['id_verso']) && false === empty($_FILES['id_verso']['name'])) {
            $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE_VERSO, 'id_verso');
            if (false === is_numeric($attachmentIdVerso)) {
                $form->addError(new FormError($uploadErrorMessage));
            }
        }

        if ($clientAddress->getIdPaysFiscal() > \pays_v2::COUNTRY_FRANCE) {
            if (isset($_FILES['tax-certificate']) && false === empty($_FILES['tax-certificate']['name'])) {
                if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::JUSTIFICATIF_FISCAL, 'tax-certificate'))) {
                    $form->addError(new FormError($uploadErrorMessage));
                }
            } else {
                $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-person-missing-tax-certificate'));
            }
        }

        if (isset($_FILES['housing-certificate']) && false === empty($_FILES['housing-certificate']['name'])) {
            if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::JUSTIFICATIF_DOMICILE, 'housing-certificate'))) {
                $form->addError(new FormError($uploadErrorMessage));
            }
        } else {
            $form->addError(new FormError($translator->trans('lender-subscription_documents-person-missing-housing-certificate')));
        }

        if (false === empty($form->get('housedByThirdPerson')->getData())){
            if (isset($_FILES['housed-by-third-person-declaration']) && false === empty($_FILES['housed-by-third-person-declaration']['name'])){
                if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::ATTESTATION_HEBERGEMENT_TIERS, 'housed-by-third-person-declaration'))) {
                    $form->addError(new FormError($uploadErrorMessage));
                }
            } else {
                $form->addError(new FormError($translator->trans('lender-subscription_documents-person-missing-housed-by-third-person-declaration')));
            }

            if (isset($_FILES['id-third-person-housing']) && false === empty($_FILES['housed-by-third-person-declaration']['name'])){
                if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT, 'id-third-person-housing'))) {
                    $form->addError(new FormError($uploadErrorMessage));
                }
            } else {
                $form->addError(new FormError($translator->trans('lender-subscription_documents-person-missing-id-third-person-housing')));
            }
        }
    }

    /**
     * @param FormInterface     $form
     * @param \lenders_accounts $lenderAccount
     * @param Companies         $company
     */
    private function validateAttachmentsLegalEntity(FormInterface $form, \lenders_accounts $lenderAccount, Companies $company)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        $uploadErrorMessage = $translator->trans('lender-subscription_documents-upload-files-error-message');

        if (isset($_FILES['id_recto']) && false === empty($_FILES['id_recto']['name'])) {
            $attachmentIdRecto = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE_DIRIGEANT, 'id_recto');
            if (false === is_numeric($attachmentIdRecto)) {
                $form->addError(new FormError($uploadErrorMessage));
            }
        } else {
            $form->addError(new FormError($translator->trans('lender-subscription_documents-legal-entity-missing-director-id')));
        }

        if (isset($_FILES['id_verso']) && false === empty($_FILES['id_verso']['name'])) {
            $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE_VERSO, 'id_verso');
            if (false === is_numeric($attachmentIdVerso)) {
                $form->addError(new FormError($uploadErrorMessage));
            }
        }

        if (isset($_FILES['company-registration']) && false === empty($_FILES['company-registration']['name'])) {
            $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::KBIS, 'company-registration');
            if (false === is_numeric($attachmentIdVerso)) {
                $form->addError(new FormError($uploadErrorMessage));
            }
        } else {
            $form->addError(new FormError($translator->trans('lender-subscription_documents-legal-entity-missing-company-registration')));
        }

        if ($company->getStatusClient() > \companies::CLIENT_STATUS_MANAGER) {
            if (isset($_FILES['delegation-of-authority']) && false === empty($_FILES['delegation-of-authority']['name'])) {
                $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::DELEGATION_POUVOIR, 'delegation-of-authority');
                if (false === is_numeric($attachmentIdVerso)) {
                    $form->addError(new FormError($uploadErrorMessage));
                }
            } else {
                $form->addError(new FormError($translator->trans('lender-subscription_documents-legal-entity-missing-delegation-of-authority')));
            }
        }
    }

    /**
     * @Route("inscription_preteur/etape3/{clientHash}", name="lender_subscription_money_deposit", requirements={"clientHash": "[0-9a-f-]{32,36}"})
     * @Method("GET")
     *
     * @param string  $clientHash
     * @param Request $request
     * @return Response
     */
    public function moneyDepositAction($clientHash, Request $request)
    {
        $response = $this->checkProgressAndRedirect($request, $clientHash);
        if ($response instanceof RedirectResponse){
            return $response;
        }
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($clientHash, 'hash');

        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lenderAccount->get($client->id_client, 'id_client_owner');

        $client->etape_inscription_preteur = 3;
        $client->update();

        $template = [
            'client'           => $client->select('id_client = ' . $client->id_client)[0],
            'lenderAccount'    => $lenderAccount->select('id_lender_account = ' . $lenderAccount->id_lender_account)[0],
            'maxDepositAmount' => LenderWalletController::MAX_DEPOSIT_AMOUNT,
            'minDepositAmount' => LenderWalletController::MIN_DEPOSIT_AMOUNT,
            'lenderBankMotif'  => $client->getLenderPattern($client->id_client)
        ];

        return $this->render('pages/lender_subscription/money_deposit.html.twig', $template);
    }

    /**
     * @Route("inscription_preteur/etape3/{clientHash}", name="lender_subscription_money_deposit_form", requirements={"clientHash": "[0-9a-f-]{32,36}"})
     * @Method("POST")
     *
     * @param string  $clientHash
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function moneyDepositFormAction($clientHash, Request $request)
    {
        $response = $this->checkProgressAndRedirect($request, $clientHash);
        if ($response instanceof RedirectResponse){
            return $response;
        }
        $post = $request->request->all();
        $this->get('session')->set('subscriptionStep3WalletData', $post);

        if (isset($post['amount'])) {
            /** @var \ficelle $ficelle */
            $ficelle = Loader::loadLib('ficelle');
            $amount  = $ficelle->cleanFormatedNumber($post['amount']);

            if (is_numeric($amount) && $amount >= LenderWalletController::MIN_DEPOSIT_AMOUNT && $amount <= LenderWalletController::MAX_DEPOSIT_AMOUNT) {
                $em = $this->get('doctrine.orm.entity_manager');
                $client = $em->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $clientHash]);
                $wallet = $em->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->getIdClient(), WalletType::LENDER);
                $successUrl = $this->generateUrl('lender_subscription_money_transfer', ['clientHash' => $wallet->getIdClient()->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);
                $cancelUrl = $this->generateUrl('lender_subscription_money_deposit', ['clientHash' => $wallet->getIdClient()->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);

                $redirectUrl = $this->get('unilend.service.payline_manager')->pay($amount, $wallet, $successUrl, $cancelUrl);

                if (false !== $redirectUrl) {
                    return $this->redirect($redirectUrl);
                }
            }
        }

        return $this->redirectToRoute('lender_subscription_money_deposit', ['clientHash' => $clientHash]);
    }

    /**
     * @Route("inscription_preteur/payment/{clientHash}", name="lender_subscription_money_transfer")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function paymentAction(Request $request, $clientHash)
    {
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        if ($client->get($clientHash, 'hash')) {
            $token = $request->get('token');
            $version = $request->get('version', Backpayline::WS_DEFAULT_VERSION);

            if (true === empty($token)) {
                $logger->error('Payline token not found, id_client=' . $client->id_client, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
                return $this->redirectToRoute('lender_wallet', ['depositResult' => true]);
            }

            $paylineManager = $this->get('unilend.service.payline_manager');
            $paidAmountInCent = $paylineManager->handlePaylineReturn($token, $version);

            if (false !== $paidAmountInCent) {
                /** @var \clients_history $clientHistory */
                $clientHistory            = $this->get('unilend.service.entity_manager')->getRepository('clients_history');
                $clientHistory->id_client = $client->id_client;
                $clientHistory->status    = \clients_history::STATUS_ACTION_ACCOUNT_CREATION;
                $clientHistory->create();

                $paidAmount = bcdiv($paidAmountInCent, 100, 2);
                $this->sendInternalMoneyTransferNotification($client, $paidAmount); //todo: can be deleted after being confirmed by internal control team.
                $this->addFlash(
                    'moneyTransferSuccess',
                    $translator->trans('lender-subscription_money-transfer-success-message', ['%depositAmount%' => $ficelle->formatNumber($paidAmount, 2)])
                );
            } else {
                $this->addFlash('moneyTransferError', $translator->trans('lender-subscription_money-transfer-error-message'));
            }

            return $this->redirectToRoute('lender_subscription_money_deposit', ['clientHash' => $client->hash]);
        }

        return $this->redirectToRoute('home_lender');
    }


    /**
     * @Route("devenir-preteur-lp", name="lender_landing_page")
     * @Method("GET")
     */
    public function landingPageAction()
    {
        /** @var ContentManager $contentManager */
        $contentManager = $this->get('unilend.frontbundle.service.content_manager');
        return $this->render('pages/lender_subscription/landing_page.html.twig', ['partners' => $contentManager->getFooterPartners()]);
    }

    /**
     * @Route("/figaro/", name="figaro_landing_page")
     * @Method("GET")
     * @return Response
     */
    public function figaroLandingPageAction()
    {
        /** @var ContentManager $contentManager */
        $contentManager = $this->get('unilend.frontbundle.service.content_manager');
        return $this->render('pages/lender_subscription/partners/figaro.html.twig', ['partners' => $contentManager->getFooterPartners()]);
    }

    /**
     * @Route("/devenir-preteur-lp-form", name="lender_landing_page_form_only")
     * @Method("GET")
     * @return Response
     */
    public function landingPageFormOnlyAction()
    {
        return $this->render('pages/lender_subscription/landing_page_form_only.html.twig');
    }

    /**
     * Scheme and host are absolute to make partners LPs work
     * @Route("/devenir-preteur-lp", schemes="https", host="%url.host_default%", name="lender_landing_page_form")
     * @Method("POST")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function landingPageFormAction(Request $request)
    {
        /** @var \clients $clients */
        $clients = $this->get('unilend.service.entity_manager')->getRepository('clients');
        /** @var \prospects $prospect */
        $prospect  = $this->get('unilend.service.entity_manager')->getRepository('prospects');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');;

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

        if (isset($post['prospect_email'])){
            $email = filter_var($post['prospect_email'], FILTER_VALIDATE_EMAIL);
            if (false === $email) {
                $this->addFlash('landingPageErrors', $translator->trans('lender-landing-page_error-email'));
            }
        } else {
            $this->addFlash('landingPageErrors', $translator->trans('lender-landing-page_error-email'));
        }

        if (false === empty($email) && $clients->existEmail($email) && $clients->get($email, 'email')){
            $response = $this->checkProgressAndRedirect($request, $clients->hash);
            if ($response instanceof RedirectResponse){
                return $response;
            }
        }

        if (false === $this->get('session')->getFlashBag()->has('landingPageErrors')) {
            if (false === $prospect->exist($post['prospect_email'], 'email') && isset($name, $firstName, $email)) {
                /** @var SourceManager $sourceManager */
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
     * @param Request $request
     * @param null    $clientHash
     *
     * @return RedirectResponse
     */
    private function checkProgressAndRedirect(Request $request, $clientHash = null)
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
                /** @var Clients $clientEntity */
                $clientEntity = $clientRepository->find($this->getUser()->getClientId());
                /** @var ClientStatusManager $clientStatusManager */
                $clientStatusManager = $this->get('unilend.service.client_status_manager');
                $lastStatus = $clientStatusManager->getLastClientStatus($clientEntity);
                if (false === empty($lastStatus) && $lastStatus >= \clients_status::MODIFICATION){
                    return $this->redirectToRoute('lender_dashboard');
                }
            } else {
                $clientEntity = $clientRepository->findOneBy(['hash' => $clientHash]);

                if (
                    Clients::STATUS_ONLINE !== $clientEntity->getStatus()
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
    }

    /**
     * @param Clients $client
     * @param Request $request
     * @param int     $step
     */
    private function saveClientHistoryAction(Clients $client, Request $request, $step)
    {
        $formId     = '';
        $clientType = in_array($client->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER]) ? 'particulier' : 'entreprise';

        $formManager = $this->get('unilend.frontbundle.service.form_manager');
        $form = $formManager->cleanPostData($request->request->all());

        switch ($step) {
            case 1:
                $post['client_password']              = md5($form['password']);
                $post['client_password_confirmation'] = md5($form['client_password_confirmation']);
                $post['client_secret_response']       = md5($form['client_secret_answer']);
                $formId                               = 14;
                break;
            case 2:
                $formId = in_array($client->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER]) ? 17 : 19;
                break;
        }

        /** @var \clients_history_actions $clientHistoryActions */
        $clientHistoryActions = $this->get('unilend.service.entity_manager')->getRepository('clients_history_actions');
        $clientHistoryActions->histo(
            $formId,
            'inscription etape ' . $step . ' ' . $clientType,
            $client->getIdClient(), serialize(['id_client' => $client->getIdClient(), 'post' => $form, 'files' => $_FILES])
        );
    }

    /**
     * @param integer $lenderAccountId
     * @param integer $attachmentType
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
     * @Route("/inscription_preteur/ajax/birth_place", name="lender_subscription_ajax_birth_place")
     * @Method("GET")
     */
    public function getBirthPlaceAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            /** @var LocationManager $locationManager */
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
            /** @var LocationManager $locationManager */
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
            /** @var LocationManager $locationManager */
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
            $dates = Loader::loadLib('dates');
            /** @var TranslatorInterface $translator */
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

    private function sendFinalizedSubscriptionConfirmationEmail(Clients $client)
    {
        /** @var \settings $settings */
        $settings =  $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Facebook', 'type');
        $lien_fb = $settings->value;
        $settings->get('Twitter', 'type');
        $lien_tw = $settings->value;

        /** @var Wallet $wallet */
        $wallet = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->getIdClient(), WalletType::LENDER);

        $varMail = [
            'surl'           => $this->get('assets.packages')->getUrl(''),
            'url'            => $this->get('assets.packages')->getUrl(''),
            'prenom'         => $client->getPrenom(),
            'email_p'        => $client->getEmail(),
            'motif_virement' => $wallet->getWireTransferPattern(),
            'lien_fb'        => $lien_fb,
            'lien_tw'        => $lien_tw
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-inscription-preteur-etape-3', $varMail);
        $message->setTo($client->getEmail());
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    private function sendInternalMoneyTransferNotification(\clients $client, $amount)
    {
        /** @var \settings $settings */
        $settings =  $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Adresse notification nouveau versement preteur', 'type');
        $destinataire = $settings->value;

        $varMail = array(
            'surl'        => $this->get('assets.packages')->getUrl(''),
            'url'         => $this->get('assets.packages')->getUrl(''),
            '$id_preteur' => $client->id_client,
            '$nom'        => $client->nom,
            '$prenom'     => $client->prenom,
            '$montant'    => $amount
        );

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-nouveau-versement-dun-preteur', $varMail, false);
        $message->setTo($destinataire);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    /**
     * @Route("/inscription_preteur/ajax/check-city", name="lender_subscription_ajax_check_city")
     * @Method("GET")
     */
    public function checkCityAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $get = $request->query->all();

            if (false === empty($get['country']) && \pays_v2::COUNTRY_FRANCE != $get['country']) {
                return $this->json(['status' => true]);
            }

            if (empty($get['zip'])) {
                $get['zip'] = null;
            }

            if (false === empty($get['city'])) {
                /** @var LocationManager $locationManager */
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

            if (false === empty($country) && \pays_v2::COUNTRY_FRANCE != $country) {
                return $this->json(['status' => true]);
            }

            if (false === empty($inseeCode)) {
                /** @var LocationManager $locationManager */
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
        /** @var SourceManager $sourceManager */
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
