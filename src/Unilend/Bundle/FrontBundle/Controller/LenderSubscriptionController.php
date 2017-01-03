<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Bic;
use Symfony\Component\Validator\Constraints\Iban;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccountUsageType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Settings;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;
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

        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        /** @var array $template */
        $template = [];

        $settings->get("Liste deroulante conseil externe de l'entreprise", 'type');
        $template['externalCounselList'] = json_decode($settings->value, true);

        /** @var LocationManager $locationManager */
        $locationManager = $this->get('unilend.service.location_manager');

        $template['countries']             = $locationManager->getCountries();
        $template['nationalities']         = $locationManager->getNationalities();
        $template['termsOfUseLegalEntity'] = $this->generateUrl('lenders_terms_of_sales', ['type' => 'morale']);
        $template['termsOfUsePerson']      = $this->generateUrl('lenders_terms_of_sales');

        $formData = $request->getSession()->get('subscriptionPersonalInformationFormData', []);
        $request->getSession()->remove('subscriptionPersonalInformationFormData');

        $landingPageData = $this->get('session')->get('landingPageData', []);
        $this->get('session')->remove('landingPageData');

        $template['formData'] = [
            'client_form_of_address'           => isset($formData['client_form_of_address']) ? $formData['client_form_of_address'] : '',
            'client_name'                      => isset($formData['client_name']) ? $formData['client_name'] : (isset($landingPageData['prospect_name']) ? $landingPageData['prospect_name'] : ''),
            'client_first_name'                => isset($formData['client_first_name']) ? $formData['client_first_name'] : (isset($landingPageData['prospect_first_name']) ? $landingPageData['prospect_first_name'] : ''),
            'client_used_name'                 => isset($formData['client_used_name']) ? $formData['client_used_name'] : '',
            'client_email'                     => isset($formData['client_email']) ? $formData['client_email'] : (isset($landingPageData['prospect_email']) ? $landingPageData['prospect_email'] : ''),
            'client_secret_question'           => isset($formData['client_secret_question']) ? $formData['client_secret_question'] : '',
            'client_secret_answer'             => isset($formData['client_secret_answer']) ? $formData['client_secret_answer'] : '',
            'fiscal_address_street'            => isset($formData['fiscal_address_street']) ? $formData['fiscal_address_street'] : '',
            'fiscal_address_zip'               => isset($formData['fiscal_address_zip']) ? $formData['fiscal_address_zip'] : '',
            'fiscal_address_city'              => isset($formData['fiscal_address_city']) ? $formData['fiscal_address_city'] : '',
            'fiscal_address_country'           => isset($formData['fiscal_address_country']) ? $formData['fiscal_address_country'] : \pays_v2::COUNTRY_FRANCE,
            'client_mobile'                    => isset($formData['client_mobile']) ? $formData['client_mobile'] : '',
            'same_postal_address'              => isset($formData['same_postal_address']) ? true : (empty($formData['postal_address_street'])) ? true : false,
            'postal_address_street'            => isset($formData['postal_address_street']) ? $formData['postal_address_street'] : '',
            'postal_address_zip'               => isset($formData['postal_address_zip']) ? $formData['postal_address_zip'] : '',
            'postal_address_city'              => isset($formData['postal_address_city']) ? $formData['postal_address_city'] : '',
            'postal_address_country'           => isset($formData['postal_address_country']) ? $formData['postal_address_country'] : \pays_v2::COUNTRY_FRANCE,
            'client_day_of_birth'              => isset($formData['client_day_of_birth']) ? $formData['client_day_of_birth'] : '',
            'client_month_of_birth'            => isset($formData['client_month_of_birth']) ? $formData['client_month_of_birth'] : '',
            'client_year_of_birth'             => isset($formData['client_year_of_birth']) ? $formData['client_year_of_birth'] : '',
            'client_nationality'               => isset($formData['client_nationality']) ? $formData['client_nationality'] : \nationalites_v2::NATIONALITY_FRENCH,
            'client_country_of_birth'          => isset($formData['client_country_of_birth']) ? $formData['client_country_of_birth'] : \pays_v2::COUNTRY_FRANCE,
            'client_place_of_birth'            => isset($formData['client_place_of_birth']) ? $formData['client_place_of_birth'] : '',
            'client_insee_place_of_birth'      => isset($formData['client_insee_place_of_birth']) ? $formData['client_insee_place_of_birth'] : '',
            'client_no_us_person'              => isset($formData['client_no_us_person']) ? $formData['client_no_us_person'] : '',
            'client_type'                      => isset($formData['client_type']) ? $formData['client_type'] : '',
            'company_name'                     => isset($formData['company_name']) ? $formData['company_name'] : '',
            'company_legal_form'               => isset($formData['company_legal_form']) ? $formData['company_legal_form'] : '',
            'company_social_capital'           => isset($formData['company_social_capital']) ? $formData['company_social_capital'] : '',
            'company_siren'                    => isset($formData['company_siren']) ? $formData['company_siren'] : '',
            'company_phone'                    => isset($formData['company_phone']) ? $formData['company_phone'] : '',
            'company_client_status'            => isset($formData['company_client_status']) ? $formData['company_client_status'] : \companies::CLIENT_STATUS_MANAGER,
            'company_external_counsel'         => isset($formData['company_external_counsel']) ? $formData['company_external_counsel'] : '',
            'company_external_counsel_other'   => isset($formData['company_external_counsel_other']) ? $formData['company_external_counsel_other'] : '',
            'client_position'                  => isset($formData['client_position']) ? $formData['client_position'] : '',
            'company_director_form_of_address' => isset($formData['company_director_form_of_address']) ? $formData['company_director_form_of_address'] : '',
            'company_director_name'            => isset($formData['company_director_name']) ? $formData['company_director_name'] : '',
            'company_director_first_name'      => isset($formData['company_director_first_name']) ? $formData['company_director_first_name'] : '',
            'company_director_position'        => isset($formData['company_director_position']) ? $formData['company_director_position'] : '',
            'company_director_phone'           => isset($formData['company_director_phone']) ? $formData['company_director_phone'] : '',
            'company_director_email'           => isset($formData['company_director_email']) ? $formData['company_director_email'] : ''
        ];

        return $this->render('pages/lender_subscription/personal_information.html.twig', $template);
    }

    /**
     * @Route("inscription_preteur/etape1/person", name="lender_subscription_personal_information_person_form")
     * @Method("POST")
     */
    public function personalInformationPersonFormAction(Request $request)
    {
        $response = $this->checkProgressAndRedirect($request);
        if ($response instanceof RedirectResponse){
            return $response;
        }

        /** @var \dates $dates */
        $dates = Loader::loadLib('dates');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var LocationManager $locationManager */
        $locationManager = $this->get('unilend.service.location_manager');

        /** @var array $post */
        $post = $request->request->all();

        if (false === $dates->ageplus18($post['client_year_of_birth'] . '-' . $post['client_month_of_birth'] . '-' . $post['client_day_of_birth'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-age'));
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
        if (empty($post['client_no_us_person'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-us-person'));
        }
        if (empty($post['terms_of_use'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-terms-of-use'));
        }
        if (false === isset($post['client_mobile']) || false === is_numeric(str_replace([' ', '.'], '', $post['client_mobile']))) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-wrong-mobile-format'));
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
                //for France, check post code here.
                if (false === $cities->exist($post['fiscal_address_zip'], 'cp')) {
                    $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-fiscal-address-wrong-zip'));
                }
            }
            unset($cities);
        }
        if (false === empty($post['same_postal_address'])) {
            $this->checkPostalAddressSectionPost($post);
        }

        $bCountryCheckOk = true;
        /** @var \villes $cities */
        $cities = $this->get('unilend.service.entity_manager')->getRepository('villes');
        /** @var \pays_v2 $countries */
        $countries = $this->get('unilend.service.entity_manager')->getRepository('pays_v2');
        /** @var \nationalites_v2 $nationalities */
        $nationalities = $this->get('unilend.service.entity_manager')->getRepository('nationalites_v2');
        /** @var string $inseePlaceOfBirth */
        $inseePlaceOfBirth = '';
        /** @var string $placeOfBirth */
        $placeOfBirth = '';

        if (false === isset($post['client_nationality']) || false === $nationalities->get($post['client_nationality'], 'id_nationalite')) {
            $bCountryCheckOk = false;
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-wrong-nationality'));
        }
        if (false === isset($post['client_country_of_birth']) || false === $countries->get($post['client_country_of_birth'], 'id_pays')) {
            $bCountryCheckOk = false;
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-wrong-birth-country'));
        }
        if (false === isset($post['client_place_of_birth'])
            || \pays_v2::COUNTRY_FRANCE == $countries->id_pays && false === isset($post['client_insee_place_of_birth'])) {
            $bCountryCheckOk = false;
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-wrong-birth-place'));
        }

        if ($bCountryCheckOk) {
            if (\pays_v2::COUNTRY_FRANCE == $post['client_country_of_birth'] && false === empty($post['client_insee_place_of_birth']))  {
                $inseeExists       = $locationManager->checkFrenchCityInsee($post['client_insee_place_of_birth']);
                $placeOfBirth      = preg_replace(['/[0-9]+/', '/\(\)/'], '', $post['client_place_of_birth']);
                $cityExists        = $locationManager->checkFrenchCity($placeOfBirth);
                $inseeAndCityMatch = $cities->exist($post['client_insee_place_of_birth'], 'ville = "' . $placeOfBirth . '" AND insee');
                $inseePlaceOfBirth = ($inseeExists && $cityExists && $inseeAndCityMatch) ? $post['client_insee_place_of_birth'] : $post['client_place_of_birth'];
            } else {
                /** @var \insee_pays $inseeCountries */
                $inseeCountries = $this->get('unilend.service.entity_manager')->getRepository('insee_pays');
                if ($countries->get($post['client_country_of_birth']) && $inseeCountries->getByCountryIso(trim($countries->iso))) {
                    $inseePlaceOfBirth = $inseeCountries->COG;
                    $placeOfBirth =  $post['client_place_of_birth'];
                }
                unset($countries, $inseeCountries);
            }
        }

        $this->checkSecuritySectionPost($post);

        if ($this->get('session')->getFlashBag()->has('personalInformationErrors')) {
            $request->getSession()->set('subscriptionPersonalInformationFormData', $post);
            return $this->redirectToRoute('lender_subscription_personal_information');
        } else {
            $clientEntity = new Clients();
            $usedName     = isset($post['client_used_name']) ? $ficelle->majNom($post['client_used_name']) : '';
            $clientType   = ($post['client_country_of_birth'] == \nationalites_v2::NATIONALITY_FRENCH) ? \clients::TYPE_PERSON : \clients::TYPE_PERSON_FOREIGNER;
            $birthDate    = new \DateTime($post['client_year_of_birth'] . '-' . $post['client_month_of_birth'] . '-' . $post['client_day_of_birth']);

            $clientEntity
                ->setCivilite($post['client_form_of_address'])
                ->setNom($post['client_name'])
                ->setPrenom($post['client_first_name'])
                ->setNomUsage($usedName)
                ->setEmail($post['client_email'])
                ->setSecreteQuestion($post['client_secret_question'])
                ->setSecreteReponse(md5($post['client_secret_answer']))
                ->setPassword(password_hash($post['client_password'], PASSWORD_DEFAULT)) // TODO: use the Symfony\Component\Security\Core\Encoder\UserPasswordEncoder (need TECH-108)
                ->setMobile($post['client_mobile'])
                ->setVilleNaissance($placeOfBirth)
                ->setInseeBirth($inseePlaceOfBirth)
                ->setIdPaysNaissance($post['client_country_of_birth'])
                ->setIdNationalite($post['client_country_of_birth'])
                ->setNaissance($birthDate)
                ->setIdLangue('fr')
                ->setSlug($ficelle->generateSlug($post['client_first_name'] . '-' . $post['client_name']))
                ->setStatus(\clients::STATUS_ONLINE)
                ->setStatusInscriptionPreteur(1)
                ->setEtapeInscriptionPreteur(1)
                ->setType($clientType);

            $this->addClientSources($clientEntity);

            $clientAddressEntity = new ClientsAdresses();

            $postalAddress = $post['same_postal_address'] ? $post['fiscal_address_street'] : $post['postal_address_street'];
            $postalCity    = $post['same_postal_address'] ? $post['fiscal_address_city'] : $post['postal_address_city'];
            $postalZip     = $post['same_postal_address'] ? $post['fiscal_address_zip'] : $post['postal_address_zip'];
            $postalCountry = $post['same_postal_address'] ? $post['fiscal_address_country'] : $post['postal_address_country'];
            $sameAddress   = $post['same_postal_address'] ? 1 : 0;

            $clientAddressEntity->setAdresseFiscal($post['fiscal_address_street'])
                ->setVilleFiscal($post['fiscal_address_city'])
                ->setCpFiscal($post['fiscal_address_zip'])
                ->setIdPaysFiscal($post['fiscal_address_country'])
                ->setMemeAdresseFiscal($sameAddress)
                ->setAdresse1($postalAddress)
                ->setVille($postalCity)
                ->setCp($postalZip)
                ->setIdPays($postalCountry);

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
                $em->commit();
            } catch (Exception $exception) {
                $em->getConnection()->rollBack();
                $this->get('logger')->error('An error occurred while creating client ' [['class' => __CLASS__, 'function' => __FUNCTION__]]);
            }

            $this->get('unilend.service.client_manager')->acceptLastTos($clientEntity);
            $this->addClientToDataLayer($request, $clientEntity);
            $this->saveClientHistoryAction($clientEntity, $post);
            $this->sendSubscriptionStartConfirmationEmail($clientEntity);

            return $this->redirectToRoute('lender_subscription_documents', ['clientHash' => $clientEntity->getHash()]);
        }
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
            $this->checkPostalAddressSectionPost($post);
        }

        $this->checkSecuritySectionPost($post);

        if (empty($post['terms_of_use'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-terms-of-use'));
        }

        if ($this->get('session')->getFlashBag()->has('personalInformationErrors')) {
            $request->getSession()->set('subscriptionPersonalInformationFormData', $post);
            return $this->redirectToRoute('lender_subscription_personal_information');
        } else {
            /** @var \ficelle $ficelle */
            $ficelle       = Loader::loadLib('ficelle');
            /** @var ClientManager $clientManager */
            $clientManager = $this->get('unilend.service.client_manager');

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
     * @param array $post
     */
    private function checkSecuritySectionPost($post)
    {
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');

        if ((false === isset($post['client_email']) && false !== filter_var($post['client_email'], FILTER_VALIDATE_EMAIL)) || $post['client_email'] != $post['client_email_confirmation']) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-identity-wrong-email'));
        }

        if ($client->existEmail($post['client_email'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-existing-email'));
        }

        if (empty($post['client_password'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-identity-missing-password'));
        }

        if (empty($post['client_password_confirmation'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-identity-missing-password-confirmation'));
        }

        if (isset($post['client_password']) && isset($post['client_password_confirmation']) && $post['client_password'] != $post['client_password_confirmation']) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-passwords-dont-match'));
        }

        if (false === empty($post['client_password']) && false === $ficelle->password_fo($post['client_password'], 6)) {
            $this->addFlash('personalInformationErrors', $translator->trans('common-validator_password-invalid'));
        }

        if (empty($post['client_secret_question'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-secret-qestion-missing'));
        }

        if (empty($post['client_secret_answer'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-secret-answer-missing'));
        }
    }

    /**
     * @param array $post
     */
    private function checkPostalAddressSectionPost($post)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        if (false === isset($post['postal_address_street'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-missing-postal-address'));
        }
        if (false === isset($post['postal_address_city'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-missing-postal-address-city'));
        }
        if (false === isset($post['postal_address_zip'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-missing-postal-address-zip'));
        }
        if (false === isset($post['postal_address_country'])) {
            $this->addFlash('personalInformationErrors', $translator->trans('lender-subscription_personal-information-error-missing-postal-address-country'));
        }
    }



    /**
     * @Route("inscription_preteur/etape2/{clientHash}", name="lender_subscription_documents")
     * @Method("GET")
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
     * @Route("inscription_preteur/etape2/{clientHash}", name="lender_subscription_documents_form")
     * @Method("POST")
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
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
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
            $bankAccountManager->saveBankInformation($clientEntity, $bic, $iban, BankAccountUsageType::LENDER_DEFAULT);

            $clientStatusManager->addClientStatus($client, \users::USER_ID_FRONT, \clients_status::TO_BE_CHECKED);
            $this->saveClientHistoryAction($clientEntity, $post);
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
     * @Route("inscription_preteur/etape3/{clientHash}", name="lender_subscription_money_deposit")
     * @Method("GET")
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
     * @Route("inscription_preteur/etape3/{clientHash}", name="lender_subscription_money_deposit_form")
     * @Method("POST")
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
                /** @var ClientsRepository $clientRepo */
                $clientRepo = $em->getRepository('UnilendCoreBusinessBundle:Clients');
                $client = $clientRepo->findOneBy(['hash' => $clientHash]);
                $wallet = $clientRepo->getWalletByType($client->getIdClient(), WalletType::LENDER);
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
     * @Route("devenir-preteur-lp-form", name="lender_landing_page_form_only")
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

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') || false === is_null($clientHash)) {
            if ($this->get('security.authorization_checker')->isGranted('ROLE_BORROWER')) {
                return $this->redirectToRoute('projects_list');
            }

            if ($this->get('security.authorization_checker')->isGranted('ROLE_LENDER')) {
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
            $redirectPath = $this->getSubscriptionStepRedirectRoute($clientEntity->getEtapeInscriptionPreteur(), $clientHash);
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
     * @param $alreadyCompletedStep
     * @param null $clientHash
     * @return string
     */
    private function getSubscriptionStepRedirectRoute($alreadyCompletedStep, $clientHash = null)
    {
        switch($alreadyCompletedStep){
            case 1 :
                $redirectRoute = $this->generateUrl('lender_subscription_documents', ['clientHash' => $clientHash]);
                break;
            case 2 :
            case 3 :
                $redirectRoute = $this->generateUrl('lender_subscription_money_deposit', ['clientHash' => $clientHash]);
                break;
            default :
                $redirectRoute = $this->generateUrl('projects_list');
        }

        return $redirectRoute;
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
     * @param Request $request
     * @param Clients $clientEntity
     */
    private function addClientToDataLayer(Request $request, Clients $clientEntity)
    {
        $request->getSession()->set(DataLayerCollector::SESSION_KEY_CLIENT_EMAIL, $clientEntity->getEmail());
        $request->getSession()->set(DataLayerCollector::SESSION_KEY_LENDER_CLIENT_ID, $clientEntity->getIdClient());
    }

}
