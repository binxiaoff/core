<?php

namespace Unilend\Bundle\FrontBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Unilend\core\Loader;

class LenderSubscriptionController extends Controller
{
    /**
     * @Route("inscription_preteur/etape1", name="lender_subscription_step_1")
     */
    public function lenderSubscriptionStep1ShowAction(Request $request)
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') && $this->get('security.authorization_checker')->isGranted('ROLE_LENDER') && $this->getUser()->getSubscriptionStep() > 1) {
            return $this->redirectToRoute('lender_subscription_step_2');
        }

        $template = [];
        $formData = $request->getSession()->get('personSubscriptionStep1FormData', '');
        $request->getSession()->remove('personSubscriptionStep1FormData');
        $template['formData'] = [
            'client_form_of_address'      => isset($formData['client_form_of_address']) ? $formData['client_form_of_address'] : '',
            'client_name'                 => isset($formData['client_name']) ? $formData['client_name'] : '',
            'client_first_name'           => isset($formData['client_first_name']) ? $formData['client_first_name'] : '',
            'client_used_name'            => isset($formData['client_used_name']) ? $formData['client_used_name'] : '',
            'client_email'                => isset($formData['client_email']) ? $formData['client_email'] : '',
            'client_secret_question'      => isset($formData['client_secret_question']) ? $formData['client_secret_question'] : '',
            'fiscal_address_street'       => isset($formData['fiscal_address_street']) ? $formData['fiscal_address_street'] : '',
            'fiscal_address_zip'          => isset($formData['fiscal_address_zip']) ? $formData['fiscal_address_zip'] : '',
            'fiscal_address_city'         => isset($formData['fiscal_address_city']) ? $formData['fiscal_address_city'] : '',
            'fiscal_address_country'      => isset($formData['fiscal_address_country']) ? $formData['fiscal_address_country'] : \pays_v2::COUNTRY_FRANCE,
            'client_mobile'               => isset($formData['client_mobile']) ? $formData['client_mobile'] : '',
            'same_postal_address'         => isset($formData['same_postal_address']) ? $formData['same_postal_address'] : '',
            'postal_address_street'       => isset($formData['postal_address_street']) ? $formData['postal_address_street'] : '',
            'postal_address_zip'          => isset($formData['postal_address_zip']) ? $formData['postal_address_zip'] : '',
            'postal_address_city'         => isset($formData['postal_address_city']) ? $formData['postal_address_city'] : '',
            'postal_address_country'      => isset($formData['postal_address_country']) ? $formData['postal_address_country'] : \pays_v2::COUNTRY_FRANCE,
            'client_day_of_birth'         => isset($formData['client_day_of_birth']) ? $formData['client_day_of_birth'] : '',
            'client_month_of_birth'       => isset($formData['client_month_of_birth']) ? $formData['client_month_of_birth'] : 1,
            'client_year_of_birth'        => isset($formData['client_year_of_birth']) ? $formData['client_year_of_birth'] : '',
            'client_nationality'          => isset($formData['client_nationality']) ? $formData['client_nationality'] : \nationalites_v2::NATIONALITY_FRENCH,
            'client_country_of_birth'     => isset($formData['client_country_of_birth']) ? $formData['client_country_of_birth'] : \pays_v2::COUNTRY_FRANCE,
            'client_place_of_birth'       => isset($formData['client_place_of_birth']) ? $formData['client_place_of_birth'] : '',
            'client_insee_place_of_birth' => isset($formData['client_insee_place_of_birth']) ? $formData['client_insee_place_of_birth'] : '',
            'client_no_us_person'         => isset($formData['client_no_us_person']) ? $formData['client_no_us_person'] : '',
            'client_type'                 => isset($formData['client_type']) ? $formData['client_type'] : ''
        ];

        return $this->render('pages/lender_subscription/lender_subscription_step_1.html.twig', $template);
    }

    /**
     * @Route("inscription_preteur/person-submit-step-1", name="lender_subscription_person_submit_step_1")
     * @Method("POST")
     */
    public function lenderSubscriptionStep1SaveAction(Request $request)
    {
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->get('unilend.service.entity_manager')->getRepository('clients_adresses');
        /** @var \clients_history_actions $clientHistoryActions */
        $clientHistoryActions = $this->get('unilend.service.entity_manager')->getRepository('clients_history_actions');
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        /** @var \dates $dates */
        $dates = Loader::loadLib('dates');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        /** @var array $post */
        $post = $request->request->all();//var_dump($post);die;
        /** @var bool $clientModification */
        $clientModification = false;

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') && $this->get('security.authorization_checker')->isGranted('ROLE_LENDER') && $this->getUser()->getSubscriptionStep() < 3) {
            $clientModification = true;
        }

        if (false === $dates->ageplus18($post['client_year_of_birth'] . '-' . $post['client_month_of_birth'] . '-' . $post['client_day_of_birth'])) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-age'));
        }

        if (empty($post['client_name'])) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-identity-missing-name'));
        }

        if (empty($post['client_first_name'])) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-identity-missing-first-name'));
        }

        if ((false === isset($post['client_email']) && false === $ficelle->isEmail($post['client_email'])) || $post['client_email'] != $post['client_email_confirmation']) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-identity-wrong-email'));
        }

        if (false === $client->existEmail($post['client_email']) && (false === $clientModification || $clientModification && ($post['client_email'] != $client->email))) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-existing-email'));
        }

        if (empty($post['client_password'])) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-identity-missing-password'));
        }

        if (empty($post['client_password_confirmation'])) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-identity-missing-password-confirmation'));
        }

        if (isset($post['client_password']) && isset($post['client_password_confirmation']) && $post['client_password'] != $post['client_password_confirmation']) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-passwords-dont-match'));
        }

        if (empty($post['client_secret_question'])) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-secret-qestion-missing'));
        }

        if (empty($post['client_secret_answer'])) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-secret-answer-missing'));
        }

        if (empty($post['fiscal_address_street'])) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-fiscal-address-missing'));
        }

        if (empty($post['fiscal_address_city'])) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-fiscal-address-city-missing'));
        }

        if (empty($post['fiscal_address_zip'])) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-fiscal-address-zip-missing'));
        } else {
            /** @var \villes $cities */
            $cities = $this->get('unilend.service.entity_manager')->getRepository('villes');
            if (isset($post['fiscal_address_country']) && \pays_v2::COUNTRY_FRANCE == $post['fiscal_address_country']) {
                //for France, check post code here.
                if (false === $cities->exist($post['fiscal_address_zip'], 'cp')) {
                    $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-fiscal-address-wrong-zip'));
                }
            }
            unset($cities);
        }

        if (false === isset($post['client_mobile']) || false === is_numeric($post['client_mobile'])) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-wrong-mobile-format'));
        }

        if (false === empty($post['same_postal_address'])) {
            if (false === isset($post['postal_address_street'])) {
                $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-missing-postal-address'));

            }
            if (false === isset($post['postal_address_city'])) {
                $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-missing-postal-address-city'));

            }
            if (false === isset($post['postal_address_zip'])) {
                $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-missing-postal-address-zip'));
            }
        }

        if (empty($post['client_no_us_person'])) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-us-person'));
        }

        if (empty($post['terms_of_use'])) {
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-terms-of-use'));
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

        if (false === isset($post['client_nationality']) || false === $nationalities->get($post['client_nationality'], 'id_nationalite')) {
            $bCountryCheckOk = false;
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-wrong-nationality'));
        }

        if (false === isset($post['client_country_of_birth']) || false === $countries->get($post['client_country_of_birth'], 'id_pays')) {
            $bCountryCheckOk = false;
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-wrong-birth-country'));
        }
        if (false === isset($post['client_place_of_birth']) || \pays_v2::COUNTRY_FRANCE == $countries->id_pays && false === $cities->exist($post['client_place_of_birth'], 'ville')) {
            $bCountryCheckOk = false;
            $this->addFlash('personStep1Errors', $translationManager->selectTranslation('lender-subscription', 'step-1-error-wrong-birth-place'));
        }

        if ($bCountryCheckOk) {
            if (\pays_v2::COUNTRY_FRANCE == $post['client_country_of_birth']) {
                $inseePlaceOfBirth = (false === isset($post['client_insee_place_of_birth']) || '' === $post['client_insee_place_of_birth']) ? $cities->insee : $post['client_insee_place_of_birth'];
                unset($cities);
            } else {
                /** @var \insee_pays $inseeCountries */
                $inseeCountries = $this->get('unilend.service.entity_manager')->getRepository('insee_pays');
                if ($countries->get($post['client_country_of_birth']) && $inseeCountries->getByCountryIso(trim($countries->iso))) {
                    $inseePlaceOfBirth = $inseeCountries->COG;
                }
                unset($countries, $inseeCountries);
            }
        }

        if ($this->get('session')->getFlashBag()->has('personStep1Errors')) {
            $request->getSession()->set('personSubscriptionStep1FormData', $post);
            return $this->redirectToRoute('lender_subscription_step_1');
        } else {
            $client->civilite          = $post['client_form_of_address'];
            $client->nom               = $ficelle->majNom($post['client_name']);
            $client->nom_usage         = isset($post['client_used_name']) ? $ficelle->majNom($post['client_used_name']) : '';
            $client->prenom            = $ficelle->majNom($post['client_first_name']);
            $client->email             = $post['client_email'];
            $client->secrete_question  = $post['client_secret_question'];
            $client->secrete_reponse   = md5($post['client_secret_answer']);
            $client->password          = password_hash($post['client_password'], PASSWORD_DEFAULT);
            $client->mobile            = str_replace(' ', '', $post['client_mobile']);
            $client->ville_naissance   = $post['client_place_of_birth'];
            $client->insee_birth       = $inseePlaceOfBirth;
            $client->id_pays_naissance = $post['client_country_of_birth'];
            $client->id_nationalite    = $post['client_nationality'];
            $client->naissance         = $post['client_year_of_birth'] . '-' . $post['client_month_of_birth'] . '-' . $post['client_day_of_birth'];
            $client->id_langue         = 'fr';
            $client->type              = ($client->id_nationalite == \nationalites_v2::NATIONALITY_FRENCH) ? \clients::TYPE_PERSON : \clients::TYPE_PERSON_FOREIGNER;
            $client->slug              = $ficelle->generateSlug($client->prenom . '-' . $client->nom);

            $clientAddress->adresse_fiscal      = $post['fiscal_address_street'];
            $clientAddress->ville_fiscal        = $post['fiscal_address_city'];
            $clientAddress->cp_fiscal           = $post['fiscal_address_zip'];
            $clientAddress->id_pays_fiscal      = $post['fiscal_address_country'];
            $clientAddress->meme_adresse_fiscal = $post['same_postal_address'] ? 1 : 0;
            $clientAddress->adresse1            = $post['same_postal_address'] ? $post['fiscal_address_street'] : $post['postal_address_street'];
            $clientAddress->ville               = $post['same_postal_address'] ? $post['fiscal_address_city'] : $post['postal_address_city'];
            $clientAddress->cp                  = $post['same_postal_address'] ? $post['fiscal_address_zip'] : $post['postal_address_zip'];
            $clientAddress->id_pays             = $post['same_postal_address'] ? $post['fiscal_address_country'] : $post['postal_address_country'];

            $post['client_password']              = md5($post['client_password']);
            $post['client_password_confirmation'] = md5($post['client_password_confirmation']);
            $post['client_secret_response']       = md5($post['client_secret_answer']);

            if ($clientModification) {
                $client->update();
                $clientAddress->update();
                $lenderAccount->update();
                $clientHistoryActions->histo(13, 'edition inscription etape 1 particulier', $client->id_client, serialize([
                    'id_client' => $client->id_client,
                    'post'      => $post
                ]));
            } else {
                $client->status                     = \clients::STATUS_ONLINE;
                $client->status_inscription_preteur = 1;
                $client->etape_inscription_preteur  = 1;
                $client->create();

                $clientAddress->id_client = $client->id_client;
                $clientAddress->create();

                $lenderAccount->id_client_owner = $client->id_client;
                $lenderAccount->status          = \lenders_accounts::LENDER_STATUS_ONLINE;
                $lenderAccount->create();

                $clientHistoryActions->histo(14, 'inscription etape 1 particulier', $client->id_client, serialize([
                    'id_client' => $client->id_client,
                    'post'      => $post
                ]));
                $this->sendSubscriptionConfirmationEmail($client);
            }

            $settings->get('Lien conditions generales inscription preteur particulier', 'type');
            $termsOfUsePerson = $settings->value;

            if (isset($post['terms_of_use']) && false === empty($post['terms_of_use'])) {
                /** @var \acceptations_legal_docs $termsOfUse */
                $termsOfUse = $this->get('unilend.service.entity_manager')->getRepository('acceptations_legal_docs');
                if ($termsOfUse->get($termsOfUsePerson, 'id_client = "' . $client->id_client . '" AND id_legal_doc')) {
                    $termsOfUse->id_legal_doc = $termsOfUsePerson;
                    $termsOfUse->id_client    = $client->id_client;
                    $termsOfUse->update();
                } else {
                    $termsOfUse->id_legal_doc = $termsOfUsePerson;
                    $termsOfUse->id_client    = $client->id_client;
                    $termsOfUse->create();
                }
            }

            $clientHistoryActions->histo(13, 'edition inscription etape 1 particulier', $client->id_client, serialize([
                'id_client' => $client->id_client,
                'post'      => $post
            ]));

            return $this->redirectToRoute('lender_subscription_step_2', [$client->hash]);
        }
    }


    /**
     *
     */
    public function lenderSubscriptionLegalEntity()
    {
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Lien conditions generales inscription preteur societe', 'type');
        $termsOfUseLegalEntity = $settings->value;
    }

    public function lenderSubscriptionStep2ShowAction()
    {
        $aPageData = [];

        return $this->render('pages/lender_subscription/lender_subscription_step_2.html.twig', $aPageData);

    }

    /**
     * @param \clients $client
     */
    private function sendSubscriptionConfirmationEmail(\clients $client)
    {
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Facebook', 'type');
        $lien_fb = $settings->value;
        $settings->get('Twitter', 'type');
        $lien_tw = $settings->value;

        $varMail = array(
            'surl'           => $this->get('assets.packages')->getUrl(''),
            'url'            => $this->get('assets.packages')->getUrl(''),
            'prenom'         => $client->prenom,
            'email_p'        => $client->email,
            'motif_virement' => $client->getLenderPattern($client->id_client),
            'lien_fb'        => $lien_fb,
            'lien_tw'        => $lien_tw,
            'annee'          => date('Y')
        );

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-inscription-preteur', $varMail);
        $message->setTo($client->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }


}
