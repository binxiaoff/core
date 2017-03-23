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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Entity\Settings;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\Villes;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository;
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
     * @param Clients         $client
     * @param ClientsAdresses $clientAddress
     * @param FormInterface   $form
     *
     * @return bool
     */
    private function handleIdentityPersonForm(Clients $client, ClientsAdresses $clientAddress, FormInterface $form)
    {
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (false === $this->isAtLeastEighteenYearsOld($client->getNaissance())) {
            $form->get('client')->get('naissance')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-age')));
        }

        if (\pays_v2::COUNTRY_FRANCE == $clientAddress->getIdPaysFiscal() && null === $entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneBy(['cp' => $clientAddress->getCpFiscal()])) {
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

        if (\pays_v2::COUNTRY_FRANCE == $client->getIdPaysNaissance() && empty($client->getInseeBirth())) {
            $countryCheck = false;
            $form->get('client')->get('villeNaissance')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-wrong-birth-place')));
        }

        if ($countryCheck) {
            if (\pays_v2::COUNTRY_FRANCE == $client->getIdPaysNaissance() && false === empty($client->getInseeBirth())) {
                /** @var Villes $cityByInsee */
                $cityByInsee = $entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneByInsee($client->getInseeBirth());

                if (null !== $cityByInsee) {
                    $client->setVilleNaissance($cityByInsee->getVille());
                }

            } else {
                /** @var PaysV2 $country */
                $country = $entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2')->find($client->getIdPaysNaissance());
                /** @var \insee_pays $inseeCountries */
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

        $this->checkSecuritySection($client, $form);

        if (false === $form->get('tos')->getData()) {
            $form->get('tos')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-terms-of-use')));
        }

        if ($form->isValid()) {
            $this->addClientSources($client);

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

            /** @var EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $entityManager->beginTransaction();
            try {
                $entityManager->persist($client);
                $clientAddress->setIdClient($client);
                $entityManager->persist($clientAddress);
                $entityManager->flush($clientAddress);
                $this->get('unilend.service.wallet_creation_manager')->createWallet($client, WalletType::LENDER);
                $this->get('unilend.service.client_manager')->acceptLastTos($client);
                $entityManager->commit();
            } catch (Exception $exception) {
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
     *
     * @return bool
     */
    private function handleLegalEntityForm(Clients $client, ClientsAdresses $clientAddress, Companies $company, FormInterface $form)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

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
            && \pays_v2::COUNTRY_FRANCE == $company->getIdPays()
            && null === $entityManager->getRepository('UnilendCoreBusinessBundle:Villes')->findOneByCp($company->getZip())
        ) {
            $form->get('fiscalAddress')->get('zip')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-fiscal-address-wrong-zip')));
        }

        if (false == $clientAddress->getMemeAdresseFiscal()) {
            $this->checkPostalAddressSection($clientAddress, $form);
        }

        $this->checkSecuritySection($client, $form);

        if (false === $form->get('tos')->getData()) {
            $form->get('tos')->addError(new FormError($translator->trans('lender-subscription_personal-information-error-terms-of-use')));
        }

        if ($form->isValid()){
            $this->addClientSources($client);

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

            /** @var EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $entityManager->beginTransaction();
            try {
                $entityManager->persist($client);
                $clientAddress->setIdClient($client);
                $entityManager->persist($clientAddress);
                $entityManager->flush($clientAddress);
                $company->setIdClientOwner($client->getIdClient());
                $entityManager->persist($company);
                $entityManager->flush($company);
                $this->get('unilend.service.wallet_creation_manager')->createWallet($client, WalletType::LENDER);
                $entityManager->commit();
            } catch (Exception $exception) {
                $entityManager->getConnection()->rollBack();
                $this->get('logger')->error('An error occurred while creating client ', [['class' => __CLASS__, 'function' => __FUNCTION__]]);
            }

            $this->get('unilend.service.client_manager')->acceptLastTos($client);
            $this->addClientToDataLayer($client);
            $this->sendSubscriptionStartConfirmationEmail($client);

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
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        if ($entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->existEmail($clientEntity->getEmail())) {
            $form->get('client')->get('email')->addError(new FormError($translator->trans('lender-profile_security-identification-error-existing-email')));
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
     * @param FileBag         $fileBag
     *
     * @return bool
     */
    private function handleDocumentsForm(ClientsAdresses $clientAddress, FormInterface $form, FileBag $fileBag)
    {
        /** @var TranslatorInterface $translator */
        $translator  = $this->get('translator');
        /** @var Clients $client */
        $client = $form->get('client')->getData();
        $iban   = $form->get('bankAccount')->get('iban')->getData();
        $bic    = $form->get('bankAccount')->get('bic')->getData();

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
                $this->upload($client, AttachmentType::RIB, $file);
            } catch (\Exception $exception) {
                $form->get('bankAccount')->addError(new FormError($translator->trans('lender-subscription_documents-upload-files-error-message')));
            }
        } else {
            $form->addError(new FormError($translator->trans('lender-subscription_documents-missing-rib')));
        }

        if (in_array($client->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
            $this->validateAttachmentsPerson($form, $client, $clientAddress, $fileBag);
        } else {
            $company = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Companies')->findOneByIdClientOwner($client);
            $this->validateAttachmentsLegalEntity($form, $client, $company, $fileBag);
        }

        if ($form->isValid()) {
            $client->setEtapeInscriptionPreteur(Clients::SUBSCRIPTION_STEP_DOCUMENTS);
            $this->get('doctrine.orm.entity_manager')->flush();

            /** @var BankAccountManager $bankAccountManager */
            $bankAccountManager = $this->get('unilend.service.bank_account_manager');
            $bankAccountManager->saveBankInformation($client, $bic, $iban);

            /** @var ClientStatusManager $clientStatusManager */
            $clientStatusManager = $this->get('unilend.service.client_status_manager');
            $clientStatusManager->addClientStatus($client, Users::USER_ID_FRONT, \clients_status::TO_BE_CHECKED);

            $this->get('unilend.service.notification_manager')->generateDefaultNotificationSettings($client);
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
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        $uploadErrorMessage = $translator->trans('lender-subscription_documents-upload-files-error-message');

        $files = [
            AttachmentType::CNI_PASSPORTE       => $fileBag->get('id_recto'),
            AttachmentType::CNI_PASSPORTE_VERSO => $fileBag->get('id_verso'),
            AttachmentType::JUSTIFICATIF_DOMICILE => $fileBag->get('housing-certificate'),
        ];
        if ($clientAddress->getIdPaysFiscal() > \pays_v2::COUNTRY_FRANCE) {
            $files[AttachmentType::JUSTIFICATIF_FISCAL] = $fileBag->get('tax-certificate');
        }
        if (false === empty($form->get('housedByThirdPerson')->getData())){
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
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
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

        $client->etape_inscription_preteur = Clients::SUBSCRIPTION_STEP_MONEY_DEPOSIT;
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
                $entityManager = $this->get('doctrine.orm.entity_manager');
                $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $clientHash]);
                $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->getIdClient(), WalletType::LENDER);
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

        /** @var ClientsRepository $clientRepo */
        $clientRepo = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients');

        if (
            false === empty($email)
            && $clientRepo->existEmail($email)
            && $clients->get($email, 'email')
        ){
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
        $post        = $formManager->cleanPostData($request->request->all());
        $files       = $request->files;

        switch ($step) {
            case 1:
                $post['form']['client']['password']['first']  = md5($post['form']['client']['password']['first']);
                $post['form']['client']['password']['second'] = md5($post['form']['client']['password']['second']);
                $post['form']['security']['secreteReponse']   = md5($post['form']['security']['secreteReponse']);
                $formId                                       = 14;
                break;
            case 2:
                $formId = in_array($client->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER]) ? 17 : 19;
                break;
        }


        if (false === empty($files)) {
            $post = array_merge($post, $formManager->getNamesOfFiles($files));
        }

        /** @var \clients_history_actions $clientHistoryActions */
        $clientHistoryActions = $this->get('unilend.service.entity_manager')->getRepository('clients_history_actions');
        $clientHistoryActions->histo(
            $formId,
            'inscription etape ' . $step . ' ' . $clientType,
            $client->getIdClient(), serialize(['id_client' => $client->getIdClient(), 'post' => $post])
        );
    }

    /**
     * @param Clients $client
     * @param $attachmentTypeId
     * @param UploadedFile $file
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
