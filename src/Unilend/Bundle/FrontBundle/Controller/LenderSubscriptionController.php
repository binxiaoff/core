<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;
use Unilend\Bundle\FrontBundle\Service\DataLayerCollector;
use Unilend\Bundle\FrontBundle\Service\PaylineManager;
use Unilend\Bundle\FrontBundle\Service\SourceManager;
use Unilend\core\Loader;

class LenderSubscriptionController extends Controller
{
    /**
     * @Route("inscription_preteur/etape1", name="lender_subscription_personal_information")
     */
    public function personalInformationAction(Request $request)
    {
        /** @var \clients $client */
        $client   = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $response = $this->checkProgressAndRedirect($client, $request->getUri());

        if (false === $response instanceof \clients){
            return $response;
        }

        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        /** @var array $template */
        $template = [];

        $settings->get("Liste deroulante conseil externe de l'entreprise", 'type');
        $template['externalCounselList'] = json_decode($settings->value, true);

        /** @var LocationManager $locationManager */
        $locationManager           = $this->get('unilend.service.location_manager');
        $template['countries']     = $locationManager->getCountries();
        $template['nationalities'] = $locationManager->getNationalities();

        /** @var \tree $tree */
        $tree = $this->get('unilend.service.entity_manager')->getRepository('tree');
        $settings->get('Lien conditions generales inscription preteur societe', 'type');
        $tree->get(['id_tree' => $settings->value]);
        $template['termsOfUseLegalEntity'] = $this->generateUrl($tree->slug);

        $settings->get('Lien conditions generales inscription preteur particulier', 'type');
        $tree->get(['id_tree' => $settings->value]);
        $template['termsOfUsePerson'] = $this->generateUrl($tree->slug);

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
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $response = $this->checkProgressAndRedirect($client, $request->getPathInfo());
        if (false === $response instanceof \clients){
            return $response;
        }

        /** @var \dates $dates */
        $dates = Loader::loadLib('dates');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

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
            if (\pays_v2::COUNTRY_FRANCE == $post['client_country_of_birth'] ) {
                $inseePlaceOfBirth = (false === empty($post['client_insee_place_of_birth']) && $cities->get($post['client_insee_place_of_birth'], 'insee')) ? $cities->insee : $post['client_place_of_birth'];
                $placeOfBirth = $cities->ville;
                unset($cities);
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
            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
            /** @var \clients_adresses $clientAddress */
            $clientAddress = $this->get('unilend.service.entity_manager')->getRepository('clients_adresses');
            /** @var SourceManager $sourceManager */
            $sourceManager = $this->get('unilend.frontbundle.service.source_manager');

            $client->source                     = $sourceManager->getSource(SourceManager::SOURCE1);
            $client->source2                    = $sourceManager->getSource(SourceManager::SOURCE2);
            $client->source3                    = $sourceManager->getSource(SourceManager::SOURCE3);
            $client->slug_origine               = $sourceManager->getSource(SourceManager::ENTRY_SLUG);
            $client->civilite                   = $post['client_form_of_address'];
            $client->nom                        = $ficelle->majNom($post['client_name']);
            $client->nom_usage                  = isset($post['client_used_name']) ? $ficelle->majNom($post['client_used_name']) : '';
            $client->prenom                     = $ficelle->majNom($post['client_first_name']);
            $client->email                      = $post['client_email'];
            $client->secrete_question           = $post['client_secret_question'];
            $client->secrete_reponse            = md5($post['client_secret_answer']);
            // TODO: use the Symfony\Component\Security\Core\Encoder\UserPasswordEncoder (need TECH-108)
            $client->password                   = password_hash($post['client_password'], PASSWORD_DEFAULT);
            $client->mobile                     = str_replace([' ', '.'], '', $post['client_mobile']);
            $client->ville_naissance            = $placeOfBirth;
            $client->insee_birth                = $inseePlaceOfBirth;
            $client->id_pays_naissance          = $post['client_country_of_birth'];
            $client->id_nationalite             = $post['client_nationality'];
            $client->naissance                  = $post['client_year_of_birth'] . '-' . $post['client_month_of_birth'] . '-' . $post['client_day_of_birth'];
            $client->id_langue                  = 'fr';
            $client->type                       = ($client->id_nationalite == \nationalites_v2::NATIONALITY_FRENCH) ? \clients::TYPE_PERSON : \clients::TYPE_PERSON_FOREIGNER;
            $client->slug                       = $ficelle->generateSlug($client->prenom . '-' . $client->nom);
            $client->status                     = \clients::STATUS_ONLINE;
            $client->status_inscription_preteur = 1;
            $client->etape_inscription_preteur  = 1;
            $client->create();

            $request->getSession()->set(DataLayerCollector::SESSION_KEY_CLIENT_EMAIL, $client->email);
            $request->getSession()->set(DataLayerCollector::SESSION_KEY_LENDER_CLIENT_ID, $client->id_client);

            $clientAddress->adresse_fiscal      = $post['fiscal_address_street'];
            $clientAddress->ville_fiscal        = $post['fiscal_address_city'];
            $clientAddress->cp_fiscal           = $post['fiscal_address_zip'];
            $clientAddress->id_pays_fiscal      = $post['fiscal_address_country'];
            $clientAddress->meme_adresse_fiscal = $post['same_postal_address'] ? 1 : 0;
            $clientAddress->adresse1            = $post['same_postal_address'] ? $post['fiscal_address_street'] : $post['postal_address_street'];
            $clientAddress->ville               = $post['same_postal_address'] ? $post['fiscal_address_city'] : $post['postal_address_city'];
            $clientAddress->cp                  = $post['same_postal_address'] ? $post['fiscal_address_zip'] : $post['postal_address_zip'];
            $clientAddress->id_pays             = $post['same_postal_address'] ? $post['fiscal_address_country'] : $post['postal_address_country'];
            $clientAddress->id_client           = $client->id_client;
            $clientAddress->create();

            $lenderAccount->id_client_owner = $client->id_client;
            $lenderAccount->status          = \lenders_accounts::LENDER_STATUS_ONLINE;
            $lenderAccount->create();

            $this->saveClientHistoryAction($client, $post);
            $this->sendSubscriptionStartConfirmationEmail($client);

            return $this->redirectToRoute('lender_subscription_documents', ['clientHash' => $client->hash]);
        }
    }

    /**
     * @Route("inscription_preteur/etape1/entity", name="lender_subscription_personal_information_legal_entity_form")
     * @Method("POST")
     */
    public function personalInformationLegalEntityFormAction(Request $request)
    {
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $response = $this->checkProgressAndRedirect($client, $request->getPathInfo());
        if (false === $response instanceof \clients){
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
            /** @var \companies $company */
            $company = $this->get('unilend.service.entity_manager')->getRepository('companies');
            /** @var \clients_adresses $clientAddress */
            $clientAddress = $this->get('unilend.service.entity_manager')->getRepository('clients_adresses');
            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
            /** @var \ficelle $ficelle */
            $ficelle       = Loader::loadLib('ficelle');
            /** @var SourceManager $sourceManager */
            $sourceManager = $this->get('unilend.frontbundle.service.source_manager');

            $client->source                     = $sourceManager->getSource(SourceManager::SOURCE1);
            $client->source2                    = $sourceManager->getSource(SourceManager::SOURCE2);
            $client->source3                    = $sourceManager->getSource(SourceManager::SOURCE3);
            $client->slug_origine               = $sourceManager->getSource(SourceManager::ENTRY_SLUG);
            $client->civilite                   = $post['client_form_of_address'];
            $client->nom                        = $ficelle->majNom($post['client_name']);
            $client->prenom                     = $ficelle->majNom($post['client_first_name']);
            $client->fonction                   = $post['client_position'];
            $client->email                      = $post['client_email'];
            $client->mobile                     = str_replace([' ', '.'], '', $post['client_mobile']);
            $client->id_langue                  = 'fr';
            $client->nom_usage                  = '';
            $client->naissance                  = '0000-00-00';
            $client->ville_naissance            = '';
            $client->slug                       = $ficelle->generateSlug($client->prenom . '-' . $client->nom);
            $client->secrete_question           = $post['client_secret_question'];
            $client->secrete_reponse            = md5($post['client_secret_answer']);
            // TODO: use the Symfony\Component\Security\Core\Encoder\UserPasswordEncoder (need TECH-108)
            $client->password                   = password_hash($post['client_password'], PASSWORD_DEFAULT);
            $client->status                     = \clients::STATUS_ONLINE;
            $client->status_inscription_preteur = 1;
            $client->etape_inscription_preteur  = 1;
            $client->type                       = (\pays_v2::COUNTRY_FRANCE == $post['fiscal_address_country']) ? \clients::TYPE_LEGAL_ENTITY : \clients::TYPE_LEGAL_ENTITY_FOREIGNER;
            $client->create();

            $request->getSession()->set(DataLayerCollector::SESSION_KEY_CLIENT_EMAIL, $client->email);
            $request->getSession()->set(DataLayerCollector::SESSION_KEY_LENDER_CLIENT_ID, $client->id_client);

            $company->id_client_owner               = $client->id_client;
            $company->name                          = $post['company_name'];
            $company->forme                         = $post['company_legal_form'];
            $company->capital                       = str_replace([' ', '.'], '', $post['company_social_capital']);
            $company->phone                         = str_replace([' ', '.'], '', $post['company_phone']);
            $company->siren                         = $post['company_siren'];
            $company->status_adresse_correspondance = isset($post['same_postal_address']) && false === empty($post['same_postal_address']) ? 1 : 0;
            $company->adresse1                      = $post['fiscal_address_street'];
            $company->city                          = $post['fiscal_address_city'];
            $company->zip                           = $post['fiscal_address_zip'];
            $company->id_pays                       = $post['fiscal_address_country'];
            $company->status_client                 = $post['company_client_status'];

            if (\companies::CLIENT_STATUS_DELEGATION_OF_POWER == $post['company_client_status'] || \companies::CLIENT_STATUS_EXTERNAL_CONSULTANT == $post['company_client_status']) {
                $company->civilite_dirigeant = $post['company_director_form_of_address'];
                $company->nom_dirigeant      = $ficelle->majNom($post['company_director_name']);
                $company->prenom_dirigeant   = $ficelle->majNom($post['company_director_first_name']);
                $company->fonction_dirigeant = $post['company_director_position'];
                $company->email_dirigeant    = $post['company_director_email'];
                $company->phone_dirigeant    = str_replace([' ', '.'], '', $post['company_director_phone']);

                if (\companies::CLIENT_STATUS_EXTERNAL_CONSULTANT == $post['company_client_status']) {
                    $company->status_conseil_externe_entreprise   = $post['company_external_counsel'];
                    $company->preciser_conseil_externe_entreprise = $post['company_external_counsel_other'];
                }
            }
            $company->create();

            $clientAddress->adresse1  = isset($post['same_postal_address']) && true == $post['same_postal_address'] ? $post['fiscal_address_street'] : $post['fiscal_address_street'];
            $clientAddress->ville     = isset($post['same_postal_address']) && true == $post['same_postal_address'] ? $post['fiscal_address_city'] : $post['fiscal_address_city'];
            $clientAddress->cp        = isset($post['same_postal_address']) && true == $post['same_postal_address'] ? $post['fiscal_address_zip'] : $post['fiscal_address_zip'];
            $clientAddress->id_pays   = isset($post['same_postal_address']) && true == $post['same_postal_address'] ? $post['fiscal_address_country'] : $post['fiscal_address_country'];
            $clientAddress->id_client = $client->id_client;
            $clientAddress->create();

            $lenderAccount->status           = \lenders_accounts::LENDER_STATUS_ONLINE;
            $lenderAccount->id_client_owner  = $client->id_client;
            $lenderAccount->id_company_owner = $company->id_company;
            $lenderAccount->create();

            $this->saveTermsOfUse($client, 'legal_entity');
            $this->saveClientHistoryAction($client, $post);
            $this->sendSubscriptionStartConfirmationEmail($client);

            return $this->redirectToRoute('lender_subscription_documents', ['clientHash' => $client->hash]);
        }
    }

    /**
     * @param \clients $client
     */
    private function sendSubscriptionStartConfirmationEmail(\clients $client)
    {
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Facebook', 'type');
        $lien_fb = $settings->value;
        $settings->get('Twitter', 'type');
        $lien_tw = $settings->value;

        $varMail = [
            'surl'           => $this->get('assets.packages')->getUrl(''),
            'url'            => $this->get('assets.packages')->getUrl(''),
            'prenom'         => $client->prenom,
            'email_p'        => $client->email,
            'motif_virement' => $client->getLenderPattern($client->id_client),
            'lien_fb'        => $lien_fb,
            'lien_tw'        => $lien_tw,
            'annee'          => date('Y')
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-inscription-preteur', $varMail);
        $message->setTo($client->email);
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
     * @param \clients $client
     * @param $clientType
     */
    private function saveTermsOfUse(\clients $client, $clientType)
    {
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');

        if ($clientType == 'person') {
            $settings->get('Lien conditions generales inscription preteur particulier_type');
            $termsOfUseVersion = $settings->value;
        } else {
            $settings->get('Lien conditions generales inscription preteur societe_type');
            $termsOfUseVersion = $settings->value;
        }

        /** @var \acceptations_legal_docs $termsOfUse */
        $termsOfUse = $this->get('unilend.service.entity_manager')->getRepository('acceptations_legal_docs');
        $termsOfUse->id_legal_doc = $termsOfUseVersion;
        $termsOfUse->id_client    = $client->id_client;
        $termsOfUse->create();
    }

    /**
     * @Route("inscription_preteur/etape2/{clientHash}", name="lender_subscription_documents")
     * @Method("GET")
     */
    public function documentsAction($clientHash, Request $request)
    {
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $response = $this->checkProgressAndRedirect($client, $request->getPathInfo(), $clientHash);

        if (false === $response instanceof \clients){
            return $response;
        }
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->get('unilend.service.entity_manager')->getRepository('clients_adresses');
        $clientAddress->get($client->id_client, 'id_client');

        $formData = $request->getSession()->get('subscriptionStep2FormData_');
        $request->getSession()->remove('subscriptionStep2FormData');

        $template = [
            'client'         => $client->select('id_client = ' . $client->id_client)[0],
            'isLivingAbroad' => $clientAddress->id_pays_fiscal > \pays_v2::COUNTRY_FRANCE,
        ];

        $template['formData'] = [
            'bic' => isset($formData['bic']) ? $formData['bic'] : '',
            'iban' => isset($formData['iban']) ? $formData['iban'] : ''
        ];

        if (in_array($client->type, [\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
            /** @var \companies $company */
            $company = $this->get('unilend.service.entity_manager')->getRepository('companies');
            $template['company'] = $company->select('id_client_owner = ' . $client->id_client)[0];
        }

        return $this->render('pages/lender_subscription/documents.html.twig', $template);
    }

    /**
     * @Route("inscription_preteur/etape2/{clientHash}", name="lender_subscription_documents_form")
     * @Method("POST")
     */
    public function documentsFormAction($clientHash, Request $request)
    {
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $response = $this->checkProgressAndRedirect($client, $request->getPathInfo(), $clientHash);
        if (false === $response instanceof \clients){
            return $response;
        }

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

        if (empty($post['bic']) || (isset($post['bic']) && false === $ficelle->swift_validate(trim($post['bic'])))) {
            $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-bic-error-message'));
        }

        if (empty($post['iban']) || false == $ficelle->isIBAN($post['iban'])) {
            $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-iban-error-message'));
        } elseif (strtoupper(substr($post['iban'], 0, 2)) !== 'FR') {
            $this->addFlash('documentsErrors', $translator->trans('lender-subscription_documents-iban-not-french-error-message'));
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
            $lenderAccount->bic           = trim(strtoupper($post['bic']));
            $lenderAccount->iban          = trim(strtoupper(str_replace(' ', '', $post['iban'])));
            $lenderAccount->cni_passeport = 1;
            $lenderAccount->motif        = $client->getLenderPattern($client->id_client);
            $lenderAccount->update();

            $client->etape_inscription_preteur = 2;
            $client->update();

            /** @var \clients_status_history $clientStatusHistory */
            $clientStatusHistory = $this->get('unilend.service.entity_manager')->getRepository('clients_status_history');
            $clientStatusHistory->addStatus(\users::USER_ID_FRONT, \clients_status::TO_BE_CHECKED, $client->id_client);
            $this->saveClientHistoryAction($client, $post);
            $this->sendFinalizedSubscriptionConfirmationEmail($client);

            return $this->redirectToRoute('lender_subscription_money_deposit', ['clientHash' => $client->hash]);
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
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $response = $this->checkProgressAndRedirect($client, $request->getPathInfo(), $clientHash);
        if (false === $response instanceof \clients){
            return $response;
        }
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
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $response = $this->checkProgressAndRedirect($client, $request->getPathInfo(), $clientHash);
        if (false === $response instanceof \clients){
            return $response;
        }

        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');

        $post = $request->request->all();
        $this->get('session')->set('subscriptionStep3WalletData', $post);

        if (isset($post['amount']) && $post['clientId'] == $client->id_client) {
            /** @var \ficelle $ficelle */
            $ficelle = Loader::loadLib('ficelle');
            $amount  = $ficelle->cleanFormatedNumber($post['amount']);

            if (is_numeric($amount) && $amount >= LenderWalletController::MIN_DEPOSIT_AMOUNT && $amount <= LenderWalletController::MAX_DEPOSIT_AMOUNT) {
                $amount = (number_format($amount, 2, '.', '') * 100);
                /** @var \lenders_accounts $lenderAccount */
                $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
                $lenderAccount->get($client->id_client, 'id_client_owner');

                $lenderAccount->fonds          = $amount;
                $lenderAccount->type_transfert = \lenders_accounts::MONEY_TRANSFER_TYPE_CARD;
                $lenderAccount->update();

                /** @var \clients_adresses $clientAddresses */
                $clientAddresses = $this->get('unilend.service.entity_manager')->getRepository('clients_adresses');
                $clientAddresses->get($client->id_client, 'id_client');

                /** @var \transactions $transaction */
                $transaction                   = $this->get('unilend.service.entity_manager')->getRepository('transactions');
                $transaction->id_client        = $client->id_client;
                $transaction->montant          = $amount;
                $transaction->id_langue        = 'fr';
                $transaction->date_transaction = date('Y-m-d h:i:s');
                $transaction->status           = \transactions::STATUS_PENDING;
                $transaction->etat             = 0;
                $transaction->ip_client        = $request->server->get('REMOTE_ADDR');
                $transaction->type_transaction = \transactions_types::TYPE_LENDER_SUBSCRIPTION;
                $transaction->create();

                $paylineParameter = [];
                require_once $this->getParameter('path.payline') . 'include.php';
                /** @var \paylineSDK $payline */
                $payline                  = new \paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
                $payline->returnURL       = $this->get('assets.packages')->getUrl('') . $this->generateUrl('lender_subscription_money_transfer', ['hash' => $client->hash]);
                $payline->cancelURL       = $payline->returnURL;
                $payline->notificationURL = NOTIFICATION_URL;

                $paylineParameter['payment']['amount']   = $amount;
                $paylineParameter['payment']['currency'] = ORDER_CURRENCY;
                $paylineParameter['payment']['action']   = PAYMENT_ACTION;
                $paylineParameter['payment']['mode']     = PAYMENT_MODE;

                $paylineParameter['order']['ref']      = $transaction->id_transaction;
                $paylineParameter['order']['amount']   = $amount;
                $paylineParameter['order']['currency'] = ORDER_CURRENCY;

                $paylineParameter['payment']['contractNumber'] = CONTRACT_NUMBER;
                $contracts                                     = explode(";", CONTRACT_NUMBER_LIST);
                $paylineParameter['contracts']                 = $contracts;
                $secondContracts                               = explode(";", SECOND_CONTRACT_NUMBER_LIST);
                $paylineParameter['secondContracts']           = $secondContracts;

                $logger->info('Calling Payline::doWebPayment: return URL=' . $payline->returnURL . ' Transmetted data: ' . json_encode($paylineParameter), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);

                $result = $payline->doWebPayment($paylineParameter);
                $logger->info('Payline response : ' . json_encode(['$result']), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);

                $transaction->get($transaction->id_transaction, 'id_transaction');
                $transaction->serialize_payline = serialize($result);
                $transaction->update();

                if (isset($result)) {
                    if ($result['result']['code'] == '00000') {
                        return $this->redirect($result['redirectURL']);
                    } elseif (isset($result)) {
                        mail('alertesit@unilend.fr', 'unilend erreur payline', 'alimentation preteur (client : ' . $client->id_client . ') | ERROR : ' . $result['result']['code'] . ' ' . $result['result']['longMessage']);
                    }
                }
            }
        }

        return $this->redirectToRoute('lender_subscription_money_deposit', ['clientHash' => $client->hash]);
    }

    /**
     * @Route("inscription_preteur/payment", name="lender_subscription_money_transfer")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function paymentAction(Request $request)
    {
        require_once $this->getParameter('path.payline') . 'include.php';

        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');;

        if ($client->get($request->query->get('hash'), 'hash')) {
            $paylineParameter = [];
            /** @var \paylineSDK $payline */
            $payline = new \paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
            $paylineParameter['token'] = $request->request->get('token', $request->query->get('token'));

            if (true === empty($paylineParameter['token'])) {
                $logger->error('Payline token not found, id_client=' . $client->id_client, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
                return $this->redirectToRoute('lender_wallet', ['depositResult' => true]);
            }

            $paylineParameter['version'] = $request->request->get('version', '3');
            $response                    = $payline->getWebPaymentDetails($paylineParameter);
            $partnerId                   = $request->getSession()->get('id_partenaire', '');

            if (false === empty($response)) {
                /** @var PaylineManager $paylineManager */
                $paylineManager = $this->get('unilend.frontbundle.service.payline_manager');
                $paylineManager->setLogger($logger);

                if ($paylineManager->handlePaylineReturn($client, $response, $paylineParameter, $partnerId, PaylineManager::PAYMENT_LOCATION_LENDER_SUBSCRIPTION)) {

                    /** @var \clients_history $clientHistory */
                    $clientHistory            = $this->get('unilend.service.entity_manager')->getRepository('clients_history');
                    $clientHistory->id_client = $client->id_client;
                    $clientHistory->status    = \clients_history::STATUS_ACTION_ACCOUNT_CREATION;
                    $clientHistory->create();

                    $this->sendInternalMoneyTransferNotification($client, $response);
                    $this->addFlash('moneyTransferSuccess', $translator->trans('lender-subscription_money-transfer-success-message', [
                        '%depositAmount%' => bcdiv($response['payment']['amount'], 100, 2)]));
                    $this->redirectToRoute('lender_subscription_money_deposit', [ 'clientHash' => $client->hash,
                    ]);
                }
                $this->addFlash('moneyTransferError', $translator->trans('lender-subscription_money-transfer-error-message'));
                return $this->redirectToRoute('lender_subscription_money_deposit', ['clientHash' => $client->hash]);
            }
        }
        return $this->redirectToRoute('lender_subscription_money_deposit');
    }


    /**
     * @Route("devenir-preteur-lp", name="lender_landing_page")
     * @Method("GET")
     */
    public function landingPageAction()
    {
        /** @var \blocs $block */
        $block = $client = $this->get('unilend.service.entity_manager')->getRepository('blocs');
        /** @var \blocs_elements $blockElement */
        $blockElement = $client = $this->get('unilend.service.entity_manager')->getRepository('blocs_elements');
        /** @var \elements $elements */
        $elements = $client = $this->get('unilend.service.entity_manager')->getRepository('elements');

        $partners = [];
        if ($block->get('partenaires', 'slug')) {
            $elementsId = array_column($elements->select('status = 1 AND id_bloc = ' . $block->id_bloc, 'ordre ASC'), 'id_element');
            foreach ($blockElement->select('status = 1 AND id_bloc = ' . $block->id_bloc, 'FIELD(id_element, ' . implode(', ', $elementsId) . ') ASC') as $element) {
                $partners[] = [
                    'alt' => $element['complement'],
                    'src' => $element['value']
                ];
            }
        }

        return $this->render('pages/lender_subscription/landing_page.html.twig', ['partners' => $partners]);
    }

    /**
     * @Route("/figaro/", name="figaro_landing_page")
     * @Method("GET")
     * @return Response
     */
    public function figaroLandingPageAction()
    {
        /** @var \blocs $block */
        $block = $client = $this->get('unilend.service.entity_manager')->getRepository('blocs');
        /** @var \blocs_elements $blockElement */
        $blockElement = $client = $this->get('unilend.service.entity_manager')->getRepository('blocs_elements');
        /** @var \elements $elements */
        $elements = $client = $this->get('unilend.service.entity_manager')->getRepository('elements');

        $partners = [];
        if ($block->get('partenaires', 'slug')) {
            $elementsId = array_column($elements->select('status = 1 AND id_bloc = ' . $block->id_bloc, 'ordre ASC'), 'id_element');
            foreach ($blockElement->select('status = 1 AND id_bloc = ' . $block->id_bloc, 'FIELD(id_element, ' . implode(', ', $elementsId) . ') ASC') as $element) {
                $partners[] = [
                    'alt' => $element['complement'],
                    'src' => $element['value']
                ];
            }
        }

        return $this->render('pages/lender_subscription/partners/figaro.html.twig', ['partners' => $partners]);
    }

    /**
     * @Route("/capital/", name="capital_landing_page")
     * @Method("GET")
     * @return Response
     */
    public function capitalLandingPageAction()
    {
        /** @var \blocs $block */
        $block = $client = $this->get('unilend.service.entity_manager')->getRepository('blocs');
        /** @var \blocs_elements $blockElement */
        $blockElement = $client = $this->get('unilend.service.entity_manager')->getRepository('blocs_elements');
        /** @var \elements $elements */
        $elements = $client = $this->get('unilend.service.entity_manager')->getRepository('elements');

        $partners = [];
        if ($block->get('partenaires', 'slug')) {
            $elementsId = array_column($elements->select('status = 1 AND id_bloc = ' . $block->id_bloc, 'ordre ASC'), 'id_element');
            foreach ($blockElement->select('status = 1 AND id_bloc = ' . $block->id_bloc, 'FIELD(id_element, ' . implode(', ', $elementsId) . ') ASC') as $element) {
                $partners[] = [
                    'alt' => $element['complement'],
                    'src' => $element['value']
                ];
            }
        }

        $xml     = new \SimpleXMLElement(file_get_contents('http://www.capital.fr/wrapper-unilend.xml'));
        $content = explode('<!--CONTENT_ZONE-->', (string) $xml->content);

        $header = str_replace(array('<!--TITLE_ZONE_HEAD-->', '<!--TITLE_ZONE-->'), array('Financement Participatif  : Prêtez aux entreprises françaises & Recevez des intérêts chaque mois', 'Financement participatif'), $content[0]);
        $footer = str_replace('<!--XITI_ZONE-->', 'Unilend-accueil', $content[1]);
        return $this->render('pages/lender_subscription/partners/capital.html.twig', ['header' => $header, 'footer' => $footer, 'partners' => $partners]);
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
            $response = $this->checkProgressAndRedirect($clients, $request->getPathInfo());
            if (false === $response instanceof \clients){
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
     * @param \clients $client
     * @param string $requestPathInfo
     * @param string|null $clientHash
     * @return \clients|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function checkProgressAndRedirect(\clients &$client, $requestPathInfo, $clientHash = null)
    {
        if (false === empty($client->id_client) || (false === is_null($clientHash) && $client->get($clientHash, 'hash'))) {
            if (\clients::STATUS_ONLINE == $client->status && $client->etape_inscription_preteur >= 1 && $client->etape_inscription_preteur <= 3) {
                $redirectRoute = $this->getSubscriptionStepRedirectRoute($client->etape_inscription_preteur,$client->hash);
                if ($requestPathInfo !== $redirectRoute) {
                    return $this->redirect($redirectRoute);
                }
            } else {
                return $this->redirectToRoute('login');
            }
        }

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {

            if ($this->get('security.authorization_checker')->isGranted('ROLE_BORROWER')) {
                return $this->redirectToRoute('projects_list');
            }

            if ($this->get('security.authorization_checker')->isGranted('ROLE_LENDER')) {
                if (false === is_null($clientHash) && $client->get($clientHash, 'hash') && $client->id_client != $this->getUser()->getClientId()) {
                    return $this->redirectToRoute('projects_list');
                }

                $client->get($this->getUser()->getClientId());
                /** @var \clients_status $clientStatus */
                $clientStatus = $this->get('unilend.service.entity_manager')->getRepository('clients_status');
                $clientStatus->getLastStatut($client->id_client);

                if ($clientStatus->status >= \clients_status::MODIFICATION){
                    return $this->redirectToRoute('lender_dashboard');
                }

                $redirectRoute = $this->getSubscriptionStepRedirectRoute($client->etape_inscription_preteur, $client->hash);
                if ($requestPathInfo !== $redirectRoute) {
                    return $this->redirect($redirectRoute);
                }
            }
        }

        return $client;
    }

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
                $redirectRoute = $this->generateUrl('project_list');
        }

        return $redirectRoute;
    }

    /**
     * @param \clients $client
     * @param $post
     */
    private function saveClientHistoryAction(\clients $client, $post)
    {
        $formId     = '';
        $clientType = in_array($client->type, [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER]) ? 'particulier' : 'entreprise';

        switch ($client->etape_inscription_preteur) {
            case 1:
                $post['client_password']              = md5($post['client_password']);
                $post['client_password_confirmation'] = md5($post['client_password_confirmation']);
                $post['client_secret_response']       = md5($post['client_secret_answer']);
                $formId                               = 14;
                break;
            case 2:
                $formId = in_array($client->type, [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER]) ? 17 : 19;
                break;
        }

        /** @var \clients_history_actions $clientHistoryActions */
        $clientHistoryActions = $this->get('unilend.service.entity_manager')->getRepository('clients_history_actions');
        $clientHistoryActions->histo(
            $formId,
            'inscription etape ' . $client->etape_inscription_preteur . ' ' . $clientType,
            $client->id_client, serialize(['id_client' => $client->id_client, 'post' => $post])
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
            $greenPointAttachment->revalidate   = 1;
            $greenPointAttachment->final_status = 0;
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
        if ($request->isXMLHttpRequest()) {
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
        if ($request->isXMLHttpRequest()) {
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
        if ($request->isXMLHttpRequest()) {
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
        if ($request->isXMLHttpRequest()) {
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

    private function sendInternalMoneyTransferNotification(\clients $client, $response)
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
            '$montant'    => bcdiv($response['payment']['amount'], 100, 2)
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
        if ($request->isXMLHttpRequest()) {
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
}
