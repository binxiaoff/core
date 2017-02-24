<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Bic;
use Symfony\Component\Validator\Constraints\Iban;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Entity\Settings;
use Unilend\Bundle\CoreBusinessBundle\Entity\Villes;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PersonFiscalAddressType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PersonType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PostalAddressType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\SecurityQuestionType;
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

        $translator          = $this->get('translator');
        $clientEntity        = new Clients();
        $clientAddressEntity = new ClientsAdresses();

        if (false === empty($this->get('session')->get('landingPageData'))) {
            $landingPageData = $this->get('session')->get('landingPageData');
            $this->get('session')->remove('landingPageData');
            $clientEntity->setNom($landingPageData['prospect_name']);
            $clientEntity->setPrenom($landingPageData['prospect_first_name']);
            $clientEntity->setEmail($landingPageData['prospect_email']);
        }

        $identityForm = $this->createFormBuilder()
            ->add('client', PersonType::class, ['data' => $clientEntity])
            ->add('fiscalAddress', PersonFiscalAddressType::class, ['data' => $clientAddressEntity])
            ->add('postalAddress', PostalAddressType::class, ['data' => $clientAddressEntity])
            ->add('security', SecurityQuestionType::class, ['data' => $clientEntity])
            ->add('clientType', ChoiceType::class, [
                'choices'  => [
                    $translator->trans('lender-subscription_identity-client-type-person-label') => 'person',
                    $translator->trans('lender-subscription_identity-client-type-legal-entity-label')   => 'legalEntity'
                ],
                'expanded' => true,
                'multiple' => false,
                'data' => 'person'
            ])
            ->add('tos', CheckboxType::class)
            ->getForm();

        $identityForm->handleRequest($request);

        if ($identityForm->isSubmitted()) {
            //TODO client_history_actions
            if ($identityForm->isValid()) {
                $result = $this->handleIdentityPersonForm($clientEntity, $clientAddressEntity, $identityForm);
                if ($result) {
                    return $this->redirectToRoute('lender_subscription_documents', ['clientHash' => $clientEntity->getHash()]);
                }
            }
        }

        /** @var array $template */
        $template = [
            'termsOfUseLegalEntity' => $this->generateUrl('lenders_terms_of_sales', ['type' => 'morale']),
            'termsOfUsePerson' => $this->generateUrl('lenders_terms_of_sales'),
            'identityForm' => $identityForm->createView()
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
                $cityByCity = $em->getRepository('UnilendCoreBusinessBundle:Villes')->findOneByVille(trim(substr($clientEntity->getVilleNaissance(), 0, -4)));

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

        if ($form->isValid()) {
            $this->addClientSources($clientEntity);

            $clientType   = ($clientEntity->getIdPaysNaissance() == \nationalites_v2::NATIONALITY_FRENCH) ? \clients::TYPE_PERSON : \clients::TYPE_PERSON_FOREIGNER;
            $secretAnswer = md5($clientEntity->getSecreteReponse());
            $password     = password_hash($clientEntity->getPassword(), PASSWORD_DEFAULT);

            $clientEntity
                ->setPassword($password)
                ->setSecreteReponse($secretAnswer)
                ->setType($clientType)
                ->setIdLangue('fr')
                ->setSlug($ficelle->generateSlug($clientEntity->getPrenom() . '-' . $clientEntity->getNom()))
                ->setStatus(\clients::STATUS_ONLINE)
                ->setStatusInscriptionPreteur(1)
                ->setEtapeInscriptionPreteur(1)
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
     * @Route("inscription_preteur/etape1/entity", name="lender_subscription_personal_information_legal_entity_form")
     * @Method("POST")
     */
    public function personalInformationLegalEntityFormAction(Request $request)
    {
        $response = $this->checkProgressAndRedirect($request);
        if ($response instanceof RedirectResponse){
            return $response;
        }
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        /** @var array $post */
        $post = $request->request->all();

        if (empty($post['company_name'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-identity-missing-company-name'));
        }
        if (empty($post['company_legal_form'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-identity-missing-legal-form'));
        }
        if (empty($post['company_social_capital'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-identity-missing-social-capital'));
        }
        if (empty($post['company_siren'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-identity-missing-siren'));
        }
        if (empty($post['company_phone']) || (strlen($post['company_phone']) < 9 || strlen($post['company_phone']) > 14)) {
            $this->addFlash('personalInformationErrors', $translator->trans('common-validator_phone-number-invalid'));
        }
        if (empty($post['client_form_of_address'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-identity-missing-form-of-address'));
        }
        if (empty($post['client_name'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('common-validator_last-name-empty'));
        }
        if (empty($post['client_first_name'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-identity-missing-first-name'));
        }
        if (empty($post['client_position'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-identity-client-position'));
        }
        if (empty($post['client_mobile']) || strlen($post['client_mobile']) < 9 || strlen($post['client_mobile']) > 14) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-wrong-mobile-format'));
        }
        if (empty($post['company_client_status'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-identity-company-client-status-missing'));
        } else {
            if ($post['company_client_status'] == \companies::CLIENT_STATUS_DELEGATION_OF_POWER || $post['company_client_status'] == \companies::CLIENT_STATUS_EXTERNAL_CONSULTANT) {
                if (empty($post['company_director_form_of_address'])) {
                    $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-company-director-form-of-address-missing'));
                }
                if (empty($post['company_director_name'])) {
                    $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-company-director-name-missing'));
                }
                if (empty($post['company_director_first_name'])) {
                    $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-company-director-first-name-missing'));
                }
                if (empty($post['company_director_position'])) {
                    $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-company-director-position-missing'));
                }
                if (empty($post['company_director_email']) || (isset($post['company_director_email']) && false == filter_var($post['company_director_email'], FILTER_VALIDATE_EMAIL))) {
                    $this->addFlash('personalInformationErrors', $translator->trans('common_email-missing'));
                }
                if (empty($post['company_director_phone']) || strlen($post['company_director_phone']) < 9 || strlen($post['company_director_phone']) > 14) {
                    $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-company-director-phone-missing'));
                }
                if ($post['company_client_status'] == \companies::CLIENT_STATUS_EXTERNAL_CONSULTANT) {
                    if (empty($post['company_external_counsel'])) {
                        $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-company-external-counsel-error-message'));
                        if (3 == $post['company_external_counsel'] && empty($post['company_external_counsel_other'])) {
                            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-identity-company-missing-external-counsel-other'));
                        }
                    }
                }
            }
        }
        if (empty($post['fiscal_address_street'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-fiscal-address-missing'));
        }
        if (empty($post['fiscal_address_city'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-fiscal-address-city-missing'));
        }
        if (empty($post['fiscal_address_zip'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-fiscal-address-zip-missing'));
        } else {
            /** @var \villes $cities */
            $cities = $this->get('unilend.service.entity_manager')->getRepository('villes');
            if (isset($post['fiscal_address_country']) && \pays_v2::COUNTRY_FRANCE == $post['fiscal_address_country']) {
                if (false === $cities->exist($post['fiscal_address_zip'], 'cp')) {
                    $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-fiscal-address-wrong-zip'));
                }
            }
            unset($cities);
        }
        if (false === empty($post['same_postal_address'])) {
            $this->checkPostalAddressSection($clientAddress, $form);
        }

        $this->checkSecuritySection($client);

        if (empty($post['terms_of_use'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-terms-of-use'));
        }

        if ($this->get('session')->getFlashBag()->has('personalInformationErrors')) {
            $request->getSession()->set('subscriptionPersonalInformationFormData', $post);
            return $this->redirectToRoute('lender_subscription_personal_information');
        } else {
            /** @var \ficelle $ficelle */
            $ficelle       = Loader::loadLib('ficelle');

            $type = (\pays_v2::COUNTRY_FRANCE == $post['fiscal_address_country']) ? \clients::TYPE_LEGAL_ENTITY : \clients::TYPE_LEGAL_ENTITY_FOREIGNER;
            $client = new Clients();
            $client
                ->setCivilite($post['client_form_of_address'])
                ->setNom($post['client_name'])
                ->setPrenom($post['client_first_name'])
                ->setFonction($post['client_position'])
                ->setEmail($post['client_email'])
                ->setMobile($post['client_mobile'])
                ->setIdLangue('fr')
                ->setSlug($ficelle->generateSlug($post['client_first_name'] . '-' . $post['client_name']))
                ->setSecreteQuestion($post['client_secret_question'])
                ->setSecreteReponse(md5($post['client_secret_answer']))
                ->setPassword(password_hash($post['client_password'], PASSWORD_DEFAULT)) // TODO: use the Symfony\Component\Security\Core\Encoder\UserPasswordEncoder (need TECH-108)
                ->setStatus(\clients::STATUS_ONLINE)
                ->setStatusInscriptionPreteur(1)
                ->setEtapeInscriptionPreteur(1)
                ->setType($type);

            $this->addClientSources($client);

            $statusAdresseCorrespondance = isset($post['same_postal_address']) && false === empty($post['same_postal_address']) ? 1 : 0;
            $company = new Companies();
            $company->setName($post['company_name'])
                ->setForme($post['company_legal_form'])
                ->setCapital($post['company_social_capital'])
                ->setPhone($post['company_phone'])
                ->setSiren($post['company_siren'])
                ->setStatusAdresseCorrespondance($statusAdresseCorrespondance)
                ->setAdresse1($post['fiscal_address_street'])
                ->setCity($post['fiscal_address_city'])
                ->setZip($post['fiscal_address_zip'])
                ->setIdPays($post['fiscal_address_country'])
                ->setStatusClient($post['company_client_status']);

            if (\companies::CLIENT_STATUS_DELEGATION_OF_POWER == $post['company_client_status'] || \companies::CLIENT_STATUS_EXTERNAL_CONSULTANT == $post['company_client_status']) {
                $company->setCiviliteDirigeant($post['company_director_form_of_address'])
                    ->setNomDirigeant($post['company_director_name'])
                    ->setPrenomDirigeant($post['company_director_first_name'])
                    ->setFonctionDirigeant($post['company_director_position'])
                    ->setEmailDirigeant($post['company_director_email'])
                    ->setPhoneDirigeant($post['company_director_phone']);

                if (\companies::CLIENT_STATUS_EXTERNAL_CONSULTANT == $post['company_client_status']) {
                    $company->setStatusConseilExterneEntreprise($post['company_external_counsel']);
                    $company->setPreciserConseilExterneEntreprise($post['company_external_counsel_other']);
                }
            }

            $clientAddress1         = isset($post['same_postal_address']) && true == $post['same_postal_address'] ? $post['fiscal_address_street'] : $post['fiscal_address_street'];
            $clientAddressCity      = isset($post['same_postal_address']) && true == $post['same_postal_address'] ? $post['fiscal_address_city'] : $post['fiscal_address_city'];
            $clientAddressZip       = isset($post['same_postal_address']) && true == $post['same_postal_address'] ? $post['fiscal_address_zip'] : $post['fiscal_address_zip'];
            $clientAddressIdCountry = isset($post['same_postal_address']) && true == $post['same_postal_address'] ? $post['fiscal_address_country'] : $post['fiscal_address_country'];

            $clientAddress = new ClientsAdresses();
            $clientAddress->setAdresse1($clientAddress1)
                ->setVille($clientAddressCity)
                ->setCp($clientAddressZip)
                ->setIdPays($clientAddressIdCountry);

            /** @var EntityManager $em */
            $em = $this->get('doctrine.orm.entity_manager');
            $em->beginTransaction();
            try {
                $em->persist($client);
                $em->flush();
                $clientAddress->setIdClient($client->getIdClient());
                $em->persist($clientAddress);
                $company->setIdClientOwner($client->getIdClient());
                $em->persist($company);
                $em->flush();
                $this->get('unilend.service.wallet_creation_manager')->createWallet($client, WalletType::LENDER);
                $em->commit();
            } catch (Exception $exception) {
                $em->getConnection()->rollBack();
                $this->get('logger')->error('An error occurred while creating client ' [['class' => __CLASS__, 'function' => __FUNCTION__]]);
            }

            $this->get('unilend.service.client_manager')->acceptLastTos($client);
            $this->addClientToDataLayer($request, $client);
            $this->saveClientHistoryAction($client, $post);
            $this->sendSubscriptionStartConfirmationEmail($client);

            return $this->redirectToRoute('lender_subscription_documents', ['clientHash' => $client->getHash()]);
        }
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
     * @Method("GET")
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

        /** @var Clients $client */
        $client = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $clientHash]);
        /** @var ClientsAdresses $clientAddressEntity */
        $clientAddressEntity = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $client->getIdClient()]);

        $formData = $request->getSession()->get('subscriptionStep2FormData');
        $request->getSession()->remove('subscriptionStep2FormData');

        $template = [
            'client'         => $client,
            'isLivingAbroad' => $clientAddressEntity->getIdPaysFiscal() > \pays_v2::COUNTRY_FRANCE,
            'fundsOrigin'    => $this->getFundsOrigin($client->getType())
        ];

        $template['formData'] = [
            'bic' => isset($formData['bic']) ? $formData['bic'] : '',
            'iban' => isset($formData['iban']) ? $formData['iban'] : '',
            'fundsOrigin' => isset($formData['funds_origin']) ? $formData['funds_origin'] : ''
        ];

        if (in_array($client->getType(), [\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
            /** @var \companies $company */
            $company = $this->get('unilend.service.entity_manager')->getRepository('companies');
            $template['company'] = $company->select('id_client_owner = ' . $client->getIdClient())[0];
        }

        return $this->render('pages/lender_subscription/documents.html.twig', $template);
    }

    /**
     * @Route("inscription_preteur/etape2/{clientHash}", name="lender_subscription_documents_form", requirements={"clientHash": "[0-9a-f-]{32,36}"})
     * @Method("POST")
     *
     * @param string  $clientHash
     * @param Request $request
     * @return Response
     */
    public function documentsFormAction($clientHash, Request $request)
    {
        $response = $this->checkProgressAndRedirect($request, $clientHash);
        if ($response instanceof RedirectResponse){
            return $response;
        }
        /** @var ClientStatusManager $clientStatusManager */
        $clientStatusManager = $this->get('unilend.service.client_status_manager');

        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($clientHash, 'hash');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lenderAccount->get($client->id_client, 'id_client_owner');

        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->get('unilend.service.entity_manager')->getRepository('clients_adresses');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        $post = $request->request->all();

        $validator = $this->get('validator');
        $bic = $request->request->get('bic');
        $bicViolations = $validator->validate($bic, new Bic());
        if (0 !== $bicViolations->count()) {
            $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-bic-error-message'));
        }
        $iban = $request->request->get('iban');
        $ibanViolations = $validator->validate($iban, new Iban());
        if (0 !== $ibanViolations->count()) {
            $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-iban-error-message'));
        } elseif (strtoupper(substr($post['iban'], 0, 2)) !== 'FR') {
            $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-iban-not-french-error-message'));
        }

        $fundsOrigin = $this->getFundsOrigin($client->type);
        if (empty($post['funds_origin']) || empty($fundsOrigin[$post['funds_origin']])) {
            $this->addFlash('documentsErrors', $translator->trans('lender-subscription-documents_wrong-funds-origin'));
        }

        if (isset($_FILES['rib']) && $_FILES['rib']['name'] != '') {
            $attachmentIdRib = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::RIB, 'rib');
            if (false === is_numeric($attachmentIdRib)) {
                $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-upload-files-error-message'));
            }
        } else {
            $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-missing-rib'));
        }

        if (in_array($client->type, [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER])) {
            $this->validateAttachmentsPerson($post, $lenderAccount, $clientAddress, $translator);
        } else {
            /** @var \companies $company */
            $company = $this->get('unilend.service.entity_manager')->getRepository('companies');
            $company->get($client->id_client, 'id_client_owner');
            $this->validateAttachmentsLegalEntity($lenderAccount, $company, $translator);
        }

        if ($this->get('session')->getFlashBag()->has('documentsErrors')) {
            $request->getSession()->set('subscriptionStep2FormData', $post);
            return $this->redirectToRoute('lender_subscription_documents', ['clientHash' => $client->hash]);
        } else {
            /** @var Clients $clientEntity */
            $clientEntity = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
            $clientEntity->setEtapeInscriptionPreteur(2);
            $clientEntity->setFundsOrigin($post['funds_origin']);
            $this->get('doctrine.orm.entity_manager')->flush();

            /** @var BankAccountManager $bankAccountManager */
            $bankAccountManager = $this->get('unilend.service.bank_account_manager');
            $bic                = trim(strtoupper($post['bic']));
            $iban               = trim(strtoupper(str_replace(' ', '', $post['iban'])));
            $bankAccountManager->saveBankInformation($clientEntity, $bic, $iban);

            $clientStatusManager->addClientStatus($client, \users::USER_ID_FRONT, \clients_status::TO_BE_CHECKED);
            $this->saveClientHistoryAction($clientEntity, $post);
            $this->get('unilend.service.notification_manager')->generateDefaultNotificationSettings($client);
            $this->sendFinalizedSubscriptionConfirmationEmail($client);

            return $this->redirectToRoute('lender_subscription_money_deposit', ['clientHash' => $clientEntity->getHash()]);
        }
    }

    private function validateAttachmentsPerson($post, \lenders_accounts $lenderAccount, \clients_adresses $clientAddress, TranslatorInterface $translator)
    {
        $uploadErrorMessage = $translator->trans('lender-subscription_documents-upload-files-error-message');

        if (isset($_FILES['id_recto']) && $_FILES['id_recto']['name'] != '') {
            $attachmentIdRecto = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE, 'id_recto');
            if (false === is_numeric($attachmentIdRecto)) {
                $this->addFlash('documentsErrors', $uploadErrorMessage);
            }
        } else {
            $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-person-missing-id'));
        }

        if (isset($_FILES['id_verso']) && $_FILES['id_verso']['name'] != '') {
            $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE_VERSO, 'id_verso');
            if (false === is_numeric($attachmentIdVerso)) {
                $this->addFlash('documentsErrors', $uploadErrorMessage);
            }
        }

        if ($clientAddress->id_pays_fiscal > \pays_v2::COUNTRY_FRANCE) {
            if (isset($_FILES['tax-certificate']) && $_FILES['tax-certificate']['name'] != '') {
                if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::JUSTIFICATIF_FISCAL, 'tax-certificate'))) {
                    $this->addFlash('documentsErrors', $uploadErrorMessage);
                }
            } else {
                $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-person-missing-tax-certificate'));
            }
        }

        if (isset($_FILES['housing-certificate']) && $_FILES['housing-certificate']['name'] != '') {
            if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::JUSTIFICATIF_DOMICILE, 'housing-certificate'))) {
                $this->addFlash('documentsErrors', $uploadErrorMessage);
            }
        } else {
            $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-person-missing-housing-certificate'));
        }

        if (isset($post['housed_by_third_person']) && true == $post['housed_by_third_person']){
            if (isset($_FILES['housed-by-third-person-declaration']) && $_FILES['housed-by-third-person-declaration']['name'] != ''){
                if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::ATTESTATION_HEBERGEMENT_TIERS, 'housed-by-third-person-declaration'))) {
                    $this->addFlash('documentsErrors', $uploadErrorMessage);
                }
            } else {
                $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-person-missing-housed-by-third-person-declaration'));
            }

            if (isset($_FILES['id-third-person-housing']) && $_FILES['housed-by-third-person-declaration']['name'] != ''){
                if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT, 'id-third-person-housing'))) {
                    $this->addFlash('documentsErrors', $uploadErrorMessage);
                }
            } else {
                $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-person-missing-id-third-person-housing'));
            }
        }
    }

    private function validateAttachmentsLegalEntity(\lenders_accounts $lenderAccount, \companies $company, TranslatorInterface $translator)
    {
        $uploadErrorMessage = $translator->trans('lender-subscription_documents-upload-files-error-message');

        if (isset($_FILES['id_recto']) && $_FILES['id_recto']['name'] != '') {
            $attachmentIdRecto = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE_DIRIGEANT, 'id_recto');
            if (false === is_numeric($attachmentIdRecto)) {
                $this->addFlash('documentsErrors', $uploadErrorMessage);
            }
        } else {
            $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-legal-entity-missing-director-id'));
        }

        if (isset($_FILES['id_verso']) && $_FILES['id_verso']['name'] != '') {
            $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE_VERSO, 'id_verso');
            if (false === is_numeric($attachmentIdVerso)) {
                $this->addFlash('documentsErrors', $uploadErrorMessage);
            }
        }

        if (isset($_FILES['company-registration']) && $_FILES['company-registration']['name'] != '') {
            $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::KBIS, 'company-registration');
            if (false === is_numeric($attachmentIdVerso)) {
                $this->addFlash('documentsErrors', $uploadErrorMessage);
            }
        } else {
            $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-legal-entity-missing-company-registration'));
        }

        if ($company->status_client > \companies::CLIENT_STATUS_MANAGER) {
            if (isset($_FILES['delegation-of-authority']) && $_FILES['delegation-of-authority']['name'] != '') {
                $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::DELEGATION_POUVOIR, 'delegation-of-authority');
                if (false === is_numeric($attachmentIdVerso)) {
                    $this->addFlash('documentsErrors', $uploadErrorMessage);
                }
            } else {
                $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-legal-entity-missing-delegation-of-authority'));
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

        $post = $request->request->all();
        $this->get('session')->set('landingPageData', $post);

        if (false === isset($post['prospect_name']) || strlen($post['prospect_name']) > 255 || strlen($post['prospect_name']) <= 0) {
            $this->addFlash('landingPageErrors', $translator->trans('common-validator_last-name-empty'));
        }

        if (false === isset($post['prospect_first_name']) || strlen($post['prospect_first_name']) > 255 || strlen($post['prospect_first_name']) <= 0) {
            $this->addFlash('landingPageErrors', $translator->trans('common-validator_first-name-empty'));
        }

        if (empty($post['prospect_email']) || strlen($post['prospect_email']) > 255 || strlen($post['prospect_email']) <= 0
            || false == filter_var($post['prospect_email'], FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('landingPageErrors', $translator->trans('lender-landing-page_error-email'));
        }

        if (false === empty($post['prospect_email']) && $clients->existEmail($post['prospect_email']) && $clients->get($post['prospect_email'], 'email')){
            $response = $this->checkProgressAndRedirect($request, $clients->hash);
            if ($response instanceof RedirectResponse){
                return $response;
            }
        }

        if (false === $this->get('session')->getFlashBag()->has('landingPageErrors')) {
            if (false === $prospect->exist($post['prospect_email'], 'email')) {
                /** @var SourceManager $sourceManager */
                $sourceManager          = $this->get('unilend.frontbundle.service.source_manager');

                $prospect->source       = $sourceManager->getSource(SourceManager::SOURCE1);
                $prospect->source2      = $sourceManager->getSource(SourceManager::SOURCE2);
                $prospect->source3      = $sourceManager->getSource(SourceManager::SOURCE3);
                $prospect->slug_origine = $sourceManager->getSource(SourceManager::ENTRY_SLUG);
                $prospect->nom          = $post['prospect_name'];
                $prospect->prenom       = $post['prospect_first_name'];
                $prospect->email        = $post['prospect_email'];
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

                if (\clients::STATUS_ONLINE !== $clientEntity->getStatus() || $clientEntity->getEtapeInscriptionPreteur() > 3) {
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
     * @param $post
     */
    private function saveClientHistoryAction(Clients $client, $post)
    {
        $formId     = '';
        $clientType = in_array($client->getType(), [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER]) ? 'particulier' : 'entreprise';

        switch ($client->getEtapeInscriptionPreteur()) {
            case 1:
                $post['client_password']              = md5($post['client_password']);
                $post['client_password_confirmation'] = md5($post['client_password_confirmation']);
                $post['client_secret_response']       = md5($post['client_secret_answer']);
                $formId                               = 14;
                break;
            case 2:
                $formId = in_array($client->getType(), [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER]) ? 17 : 19;
                break;
        }

        /** @var \clients_history_actions $clientHistoryActions */
        $clientHistoryActions = $this->get('unilend.service.entity_manager')->getRepository('clients_history_actions');
        $clientHistoryActions->histo(
            $formId,
            'inscription etape ' . $client->getEtapeInscriptionPreteur() . ' ' . $clientType,
            $client->getIdClient(), serialize(['id_client' => $client->getIdClient(), 'post' => $post])
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

    private function sendFinalizedSubscriptionConfirmationEmail(\clients $client)
    {
        /** @var \settings $settings */
        $settings =  $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Facebook', 'type');
        $lien_fb = $settings->value;
        $settings->get('Twitter', 'type');
        $lien_tw = $settings->value;

        $varMail = array(
            'surl'        => $this->get('assets.packages')->getUrl(''),
            'url'         => $this->get('assets.packages')->getUrl(''),
            'prenom'         => $client->prenom,
            'email_p'        => $client->email,
            'motif_virement' => $client->getLenderPattern($client->id_client),
            'lien_fb'        => $lien_fb,
            'lien_tw'        => $lien_tw
        );

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-inscription-preteur-etape-3', $varMail);
        $message->setTo($client->email);
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
            case \clients::TYPE_PERSON:
            case \clients::TYPE_PERSON_FOREIGNER:
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
