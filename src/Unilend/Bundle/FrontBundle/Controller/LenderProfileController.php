<?php


namespace Unilend\Bundle\FrontBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Unilend\core\Loader;

class LenderProfileController extends Controller
{
    /**
     * @Route("/profile", name="lender_profile")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function showLenderInformationAction(Request $request)
    {
        /** @var array $templateData */
        $templateData = [];
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount  = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->get('unilend.service.entity_manager')->getRepository('clients_adresses');
        $client->get($this->getUser()->getClientId());
        $lenderAccount->get($client->id_client, 'id_client_owner');
        $clientAddress->get($client->id_client, 'id_client');

        $form = $this->getSessionFormData($request);

        if (in_array($client->type, [\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
            /** @var \companies $company */
            $company = $this->get('unilend.service.entity_manager')->getRepository('companies');
            /** @var \settings $settings */
            $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
            $company->get($client->id_client, 'id_client_owner');
            $templateData['company'] = $company->select('id_client_owner = ' . $client->id_client)[0];
            $templateData['companyIdAttachments'] = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [
                \attachment_type::CNI_PASSPORTE_DIRIGEANT,
                \attachment_type::CNI_PASSPORTE_VERSO
            ]);
            $templateData['companyOtherAttachments'] = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [
                \attachment_type::KBIS,
                \attachment_type::DELEGATION_POUVOIR
            ]);
            $settings->get("Liste deroulante conseil externe de l'entreprise", 'type');
            $templateData['externalCounselList'] = json_decode($settings->value, true);

            $templateData['formData']['legalEntity'] = [
                'company_name'                     => isset($form['legalEntity']['company_name']) ? $form['legalEntity']['company_name'] : $company->name,
                'company_legal_form'               => isset($form['legalEntity']['company_legal_form']) ? $form['legalEntity']['company_legal_form'] : $company->forme,
                'company_social_capital'           => isset($form['legalEntity']['company_social_capital']) ? $form['legalEntity']['company_social_capital'] : $company->capital,
                'company_phone'                    => isset($form['legalEntity']['company_phone']) ? $form['legalEntity']['company_phone'] : $company->phone,
                'company_client_status'            => isset($form['legalEntity']['company_client_status']) ? $form['legalEntity']['company_client_status'] : $company->status_client,
                'company_external_counsel'         => isset($form['legalEntity']['company_external_counsel']) ? $form['legalEntity']['company_external_counsel'] : $company->status_conseil_externe_entreprise,
                'company_external_counsel_other'   => isset($form['legalEntity']['company_external_counsel_other']) ? $form['legalEntity']['company_external_counsel_other'] : $company->preciser_conseil_externe_entreprise,
                'company_director_form_of_address' => isset($form['legalEntity']['company_director_form_of_address']) ? $form['legalEntity']['company_director_form_of_address'] : $company->civilite_dirigeant,
                'company_director_name'            => isset($form['legalEntity']['company_director_name']) ? $form['legalEntity']['company_director_name'] : $company->nom_dirigeant,
                'company_director_first_name'      => isset($form['legalEntity']['company_director_first_name']) ? $form['legalEntity']['company_director_first_name'] : $company->prenom_dirigeant,
                'company_director_phone'           => isset($form['legalEntity']['company_director_phone']) ? $form['legalEntity']['company_director_phone'] : $company->phone_dirigeant,
                'company_director_email'           => isset($form['legalEntity']['company_director_email']) ? $form['legalEntity']['company_director_email'] : $company->email_dirigeant,
                'client_form_of_address'           => isset($form['legalEntity']['client_form_of_address']) ? $form['legalEntity']['client_form_of_address'] : $client->civilite,
                'client_name'                      => isset($form['legalEntity']['client_name']) ? $form['legalEntity']['client_name'] : $client->nom_usage,
                'client_first_name'                => isset($form['legalEntity']['client_first_name']) ? $form['legalEntity']['client_first_name'] : $client->prenom,
                'client_position'                  => isset($form['legalEntity']['client_position']) ? $form['legalEntity']['client_position'] : $client->fonction,
                'fiscal_address_street'            => isset($form['legalEntityFiscal']['fiscal_address_street']) ? $form['legalEntityFiscal']['fiscal_address_street'] : $company->adresse1,
                'fiscal_address_zip'               => isset($form['legalEntityFiscal']['fiscal_address_zip']) ? $form['legalEntityFiscal']['fiscal_address_zip'] : $company->zip,
                'fiscal_address_city'              => isset($form['legalEntityFiscal']['fiscal_address_city']) ? $form['legalEntityFiscal']['fiscal_address_city'] : $company->city,
                'fiscal_address_country'           => isset($form['legalEntityFiscal']['fiscal_address_country']) ? $form['legalEntityFiscal']['fiscal_address_country'] : $company->id_pays,
                'same_postal_address'              => isset($form['legalEntityFiscal']['same_postal_address']) ? $form['legalEntityFiscal']['same_postal_address'] : (bool) $clientAddress->meme_adresse_fiscal,
            ];

        } else {
            $templateData['identityAttachments'] = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [
                \attachment_type::CNI_PASSPORTE,
                \attachment_type::CNI_PASSPORTE_VERSO
            ]);

            $templateData['residenceAttachments'] = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [
                \attachment_type::JUSTIFICATIF_DOMICILE,
                \attachment_type::ATTESTATION_HEBERGEMENT_TIERS,
                \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT
            ]);
            $templateData['isLivingAbroad'] = ($clientAddress->id_pays_fiscal > \pays_v2::COUNTRY_FRANCE);

            $templateData['formData']['person'] = [
                'form_of_address' => isset($form['person']['form_of_address']) ? $form['person']['form_of_address'] : $client->civilite,
                'used_name'       => isset($form['person']['used_name']) ? $form['person']['used_name'] : $client->nom_usage,
                'nationality'     => isset($form['person']['nationality']) ? $form['person']['nationality'] : $client->id_nationalite,
                'first_name'      => isset($form['person']['first_name']) ? $form['person']['first_name'] : $client->prenom,
                'fiscal_address_street'  => isset($form['personFiscal']['fiscal_address_street']) ? $form['personFiscal']['fiscal_address_street'] : $clientAddress->adresse_fiscal,
                'fiscal_address_zip'     => isset($form['personFiscal']['fiscal_address_zip']) ? $form['personFiscal']['fiscal_address_zip'] : $clientAddress->cp_fiscal,
                'fiscal_address_city'    => isset($form['personFiscal']['fiscal_address_city']) ? $form['personFiscal']['fiscal_address_city'] : $clientAddress->ville_fiscal,
                'fiscal_address_country' => isset($form['personFiscal']['fiscal_address_country']) ? $form['personFiscal']['fiscal_address_country'] : $clientAddress->id_pays_fiscal,
                'client_mobile'          => isset($form['personFiscal']['client_mobile']) ? $form['personFiscal']['client_mobile'] : $client->mobile,
                'same_postal_address'    => isset($form['personFiscal']['same_postal_address']) ? $form['personFiscal']['same_postal_address'] : (bool) $clientAddress->meme_adresse_fiscal,
                'no_us_person'           => isset($form['personFiscal']['no_us_person']) ? $form['personFiscal']['no_us_person'] : true,
                'housed_by_third_person' => isset($form['personFiscal']['housed_by_third_person']) ? $form['personFiscal']['housed_by_third_person'] : false
            ];
        }

        $templateData['client']              = $client->select('id_client = ' . $client->id_client)[0];
        $templateData['lenderAccount']       = $lenderAccount->select('id_lender_account = ' . $lenderAccount->id_lender_account)[0];
        $templateData['clientAddresses']     = $clientAddress->select('id_client = ' . $client->id_client)[0];

        $templateData['formData']['postal'] = [
            'postal_address_street'  => isset($form['postal']['postal_address_street']) ? $form['postal']['postal_address_street'] : $clientAddress->adresse1,
            'postal_address_zip'     => isset($form['postal']['postal_address_zip']) ? $form['postal']['postal_address_zip'] : $clientAddress->cp,
            'postal_address_city'    => isset($form['postal']['postal_address_city']) ? $form['postal']['postal_address_city'] : $clientAddress->ville,
            'postal_address_country' => isset($form['postal']['postal_address_country']) ? $form['postal']['postal_address_country'] : $clientAddress->id_pays,
        ];
        /** @var LocationManager $locationManager */
        $locationManager = $this->get('unilend.service.location_manager');
        $templateData['countries'] = $locationManager->getCountries();
        $templateData['nationalities'] = $locationManager->getNationalities();

        return $this->render('pages/lender_profile/lender_info.html.twig', $templateData);
    }

    /**
     * @Route("/profile/person/identity-update", name="profile_person_identity_update")
     * @Method("POST")
     */
    public function updatePersonAction(Request $request)
    {
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($this->getUser()->getClientId());
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount  = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lenderAccount->get($client->id_client, 'id_client_owner');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        if ($request->request->get('person_identity_form')) {
            /** @var array $post */
            $post  = $request->request->all();
            /** @var string $historyContent */
            $historyContent = '<ul>';

            if ($client->prenom != $post['first_name']) {
                $client->prenom = $post['first_name'];
            }

            if ($client->nom_usage != $post['used_name']) {
                $client->nom_usage = $ficelle->majNom($_POST['used_name']);
            }

            if (isset($_FILES['id_recto']) && $_FILES['id_recto']['name'] != '') {
                $attachmentIdRecto = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE, 'id_recto');
                if (false === is_numeric($attachmentIdRecto)) {
                    $this->addFlash('personIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-upload-files-error-message'));
                } else {
                    $historyContent .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::CNI_PASSPORTE) .'</li>';
                }
            }

            if (isset($_FILES['id_verso']) && $_FILES['id_verso']['name'] != '') {
                $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE_VERSO, 'id_verso');
                if (false === is_numeric($attachmentIdVerso)) {
                    $this->addFlash('personIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-upload-files-error-message'));
                } else {
                    $historyContent .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::CNI_PASSPORTE_VERSO) .'</li>';
                }
            }

            if ($client->id_nationalite != $post['nationality'] || $client->civilite != $post['form_of_address']) {
                if (isset($attachmentIdRecto)) {
                    $client->id_nationalite = $post['nationality'];
                    $historyContent .= '<li>'. $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-nationality-label') .'</li>';
                } else {
                    $this->addFlash('personIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-change-ID-warning-message'));
                }
            }

            if ($client->civilite != $post['form_of_address']) {
                if (isset($attachmentIdRecto)){
                    $client->civilite = $post['form_of_address'];
                    $historyContent .= '<li>'. $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-form-of-address-label') .'</li>';
                } else {
                    $this->addFlash('personIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-change-ID-warning-message'));
                }
            }

            $historyContent .= '</ul>';

            if ($this->get('session')->getFlashBag()->has('personIdentityErrors')){
                $request->getSession()->set('personIdentityData', $post);
            } else {
                $client->update();
                $this->addFlash('personIdentitySuccess', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-files-update-success-message'));

                if (false !== strpos($historyContent, '<li>')) {
                    $this->updateClientStatusAndNotifyClient($client, $historyContent);
                }
            }
        }

        $this->saveClientActionHistory($client, serialize(['id_client' => $client->id_client, 'post' => $request->request->all(), 'files' => $_FILES]));
        return $this->redirectToRoute('lender_profile');
    }

    /**
     * @Route("/profile/legal-entity/identity-update", name="profile_legal_entity_identity_update")
     * @Method("POST")
     */
    public function updateLegalEntityAction(Request $request)
    {
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->get('unilend.service.entity_manager')->getRepository('clients_adresses');
        /** @var \companies $company */
        $company = $this->get('unilend.service.entity_manager')->getRepository('companies');
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $client->get($this->getUser()->getClientId());
        $lenderAccount->get($client->id_client, 'id_client_owner');
        $clientAddress->get($client->id_client, 'id_client');
        $company->get($client->id_client, 'id_client_owner');

        if ($request->request->get('legal_entity_info_form')){
            /** @var array $form */
            $form  = $request->request->all();
            /** @var string $historyContent */
            $historyContent = '<ul>';

            if ($company->name != $form['company_name']) {
                $company->name = $form['company_name'];
                $historyContent .= '<li>'. $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-name-label') .'</li>';
            }

            if ($company->forme != $form['company_legal_form']) {
                $company->forme = $form['company_legal_form'];
                $historyContent .= '<li>'. $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-legal-form-label') .'</li>';
            }

            if ($company->capital != $form['company_social_capital']) {
                $company->capital = str_replace(' ', '', $form['company_social_capital']);
                $historyContent .= '<li>'. $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-social-capital-label') .'</li>';
            }

            if ($company->phone != $form['company_phone'] && strlen($form['company_phone']) > 9 && strlen($form['company_phone']) < 14 ) {
                $company->phone = str_replace(' ', '', $form['company_phone']);
                $historyContent .= '<li>'. $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-phone-label') .'</li>';
            }

            if ($company->status_client != $form['company_client_status']) {
                $company->status_client = $form['company_client_status'];
                $historyContent .= '<li>'. $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-client-status-label') .'</li>';
            }

            if ($form['company_client_status'] > \companies::CLIENT_STATUS_MANAGER) {
                $directorSection = $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-director-title');

                if (empty($form['company_external_counsel']) || (3 == $form['company_external_counsel'] && empty($form['company_client_status_other']))) {
                    $this->addFlash('legalEntityIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-external-counsel-error-message'));
                } else {
                    $company->status_client                       = $form['company_client_status'];
                    $company->status_conseil_externe_entreprise   = $form['company_external_counsel'];
                    $company->preciser_conseil_externe_entreprise = $form['company_client_status_other'];
                    $historyContent .= '<li>'. $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-client-status-label') .'</li>';
                }

                if (empty($form['company_director_form_of_address'])) {
                    $this->addFlash('legalEntityIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-director-form-of-address-missing'));
                } else {
                    $company->civilite_dirigeant = $form['company_director_form_of_address'];
                    $historyContent .= '<li>'. $directorSection . ': ' . $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-form-of-address-label') . '</li>';
                }

                if (empty($form['company_director_name'])) {
                    $this->addFlash('legalEntityIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-director-name-missing'));
                } else {
                    $company->nom_dirigeant = $ficelle->majNom($form['company_director_name']);
                    $historyContent .= '<li>'. $directorSection . ': ' . $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-name-label') .'</li>';
                }

                if (empty($form['company_director_first_name'])) {
                    $this->addFlash('legalEntityIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-director-first-name-missing'));
                } else {
                    $company->prenom_dirigeant = $ficelle->majNom($form['company_director_first_name']);
                    $historyContent .= '<li>'. $directorSection . ': ' . $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-first-name-label') . '</li>';
                }

                if (empty($form['company_director_position'])) {
                    $this->addFlash('legalEntityIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-director-position-missing'));
                } else {
                    $company->fonction_dirigeant = $form['company_director_position'];
                    $historyContent .= '<li>'. $directorSection . ': ' . $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-first-name-label') . '</li>';
                }

                if (empty($form['company_director_phone']) || false === is_numeric($form['company_director_phone']) || strlen($form['company_director_phone']) < 9 || strlen($form['company_director_phone']) > 14) {
                    $this->addFlash('legalEntityIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-director-phone-missing'));
                } else {
                    $company->phone_dirigeant = $form['company_director_phone'];
                    $historyContent .= '<li>'. $directorSection . ': ' . $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-director-phone') .'</li>';
                }

                if (empty($form['company_director_email']) || false === filter_var($form['company_director_email'], FILTER_VALIDATE_EMAIL)) {
                    $this->addFlash('legalEntityIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-director-email-missing'));
                } else {
                    $company->email_dirigeant = $form['company_director_email'];
                    $historyContent .= '<li>'. $directorSection . ': ' . $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-director-email') . '</li>';
                }
            } else {
                $company->status_client                       = $form['company_client_status'];
                $company->status_conseil_externe_entreprise   = '';
                $company->preciser_conseil_externe_entreprise = '';
                $company->civilite_dirigeant                  = '';
                $company->nom_dirigeant                       = '';
                $company->prenom_dirigeant                    = '';
                $company->fonction_dirigeant                  = '';
                $company->phone_dirigeant                     = '';
                $company->email_dirigeant                     = '';
            }

            $representativeSection = $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-representative-title');

            if ($client->civilite != $form['client_form_of_address']) {
                $client->civilite = $form['client_form_of_address'];
                $historyContent .= '<li>' . $representativeSection . ' : ' . $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-form-of-address-label') .'</li>';
            }

            if ($client->nom != $form['client_name']) {
                $client->nom = $ficelle->majNom($form['client_name']);
                $historyContent .= '<li>' . $representativeSection . ' : ' . $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-name-label') .'</li>';
            }

            if ($client->prenom != $form['client_first_name']) {
                $client->prenom = $ficelle->majNom($form['client_first_name']);
                $historyContent .= '<li>' . $representativeSection . ' : ' . $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-first-name-label') .'</li>';
            }

            if ($client->fonction != $form['client_position']) {
                $client->fonction = $form['client_position'];
                $historyContent .= '<li>' . $representativeSection . ' : ' . $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-company-client-position-label') .'</li>';
            }

            $historyContent .= '</ul>';

            if (isset($_FILES['id_recto']) && $_FILES['id_recto']['name'] != '') {
                $attachmentIdRecto = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE_DIRIGEANT, 'id_recto');
                if (false === is_numeric($attachmentIdRecto)) {
                    $this->addFlash('legalEntityIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-upload-files-error-message'));
                } else {
                    $historyContent .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::CNI_PASSPORTE_DIRIGEANT) .'</li>';
                }
            }

            if (isset($_FILES['id_verso']) && $_FILES['id_verso']['name'] != '') {
                $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE_VERSO, 'id_verso');
                if (false === is_numeric($attachmentIdVerso)) {
                    $this->addFlash('legalEntityIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-upload-files-error-message'));
                } else {
                    $historyContent .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::CNI_PASSPORTE_VERSO) .'</li>';
                }
            }

            if (isset($_FILES['company-registration']) && $_FILES['company-registration']['name'] != '') {
                $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::KBIS, 'company-registration');
                if (false === is_numeric($attachmentIdVerso)) {
                    $this->addFlash('legalEntityIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-upload-files-error-message'));
                } else {
                    $historyContent .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::KBIS) .'</li>';
                }
            }

            if ($form['company_client_status'] > \companies::CLIENT_STATUS_MANAGER) {
                if (isset($_FILES['delegation_of_authority']) && $_FILES['delegation_of_authority']['name'] != '') {
                    $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::DELEGATION_POUVOIR, 'delegation_of_authority');
                    if (false === is_numeric($attachmentIdVerso)) {
                        $this->addFlash('legalEntityIdentityErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-upload-files-error-message'));
                    } else {
                        $historyContent .= '<li>' . $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::DELEGATION_POUVOIR) . '</li>';
                    }
                }
            }

            if ($this->get('session')->getFlashBag()->has('legalEntityIdentityErrors')){
                $request->getSession()->set('profileLegalEntityData', $form);
            } else {
                $company->update();
                $client->update();
                $this->addFlash('legalEntityIdentitySuccess', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-files-update-success-message'));

                if (false != strpos($historyContent, '<li>')) {
                    $this->updateClientStatusAndNotifyClient($client, $historyContent);
                }
            }
        }

        $this->saveClientActionHistory($client, serialize(['id_client' => $client->id_client, 'post' => $request->request->all(), 'files' => $_FILES]));
        return $this->redirectToRoute('lender_profile');
    }

    /**
     * @Route("/profile/person/fiscal-address-update", name="profile_person_fiscal_address_update")
     * @Method("POST")
     */
    public function updatePersonFiscalAddressAction(Request $request)
    {
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($this->getUser()->getClientId());
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount  = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lenderAccount->get($client->id_client, 'id_client_owner');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->get('unilend.service.entity_manager')->getRepository('clients_adresses');
        $clientAddress->get($client->id_client, 'id_client');

        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        /** @var string $historyContent */
        $historyContent = '<ul>';

        if ($request->request->get('person_fiscal_address_form')) {
            $post = $request->request->all();

            if ($client->mobile != $post['client_mobile']) {
                $client->mobile = str_replace(' ', '', $post['client_mobile']);
                $client->update();
            }

            if ($clientAddress->adresse_fiscal != $post['fiscal_address_street']) {
                $clientAddress->adresse_fiscal = $post['fiscal_address_street'];
                $historyContent .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-address-label') . '</li>';
            }

            if ($clientAddress->cp_fiscal != $post['fiscal_address_zip']) {
                if (\pays_v2::COUNTRY_FRANCE == $post['fiscal_address_country']) {
                    /** @var \villes $cities */
                    $cities = $this->get('unilend.service.entity_manager')->getRepository('villes');
                    if ($cities->exist($post['fiscal_address_zip'], 'cp')) {
                        $clientAddress->cp_fiscal = $post['fiscal_address_zip'];
                        $historyContent .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-zip-label') . '</li>';
                        unset($cities);
                    } else {
                        $this->addFlash('personFiscalAddressErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-unknown-zip-code-error-message'));
                    }
                } else {
                    $clientAddress->cp_fiscal = $post['fiscal_address_zip'];
                    $historyContent .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-zip-label') . '</li>';
                }
            }

            if ($clientAddress->ville_fiscal != $post['fiscal_address_city']) {
                $clientAddress->ville_fiscal = $post['fiscal_address_city'];
                $historyContent .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-city-label') . '</li>';
            }

            if ($clientAddress->id_pays_fiscal != $post['fiscal_address_country']) {
                $clientAddress->id_pays_fiscal = $post['fiscal_address_country'];
                $historyContent .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-country-label') . '</li>';
            }

            if (isset($post['same_postal_address']) && (bool)$clientAddress->meme_adresse_fiscal != $post['same_postal_address']) {
                if (false == $post['same_postal_address'] && empty($form['postal'])) {
                    $this->addFlash('personFiscalAddressErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-postal-address-missing-data'));
                } else {
                    $clientAddress->meme_adresse_fiscal = ($post['same_postal_address'] == true) ? 1 : 0;
                    $historyContent .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-postal-checkbox') . '</li>';
                    $clientAddress->adresse1 = $clientAddress->adresse_fiscal;
                    $clientAddress->cp       = $clientAddress->cp_fiscal;
                    $clientAddress->ville    = $clientAddress->ville_fiscal;
                    $clientAddress->id_pays  = $clientAddress->id_pays_fiscal;
                }
            }

            if ($clientAddress->id_pays_fiscal > \pays_v2::COUNTRY_FRANCE) {
                if (isset($post['no_us_person']) && false == $post['no_us_person']) {
                    $historyContent .= '<li>'. $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-us-person-checkbox-label') .'</li>';
                }
            }

            if ($post['fiscal_address_country'] > \pays_v2::COUNTRY_FRANCE) {
                if (isset($_FILES['tax-certificate']) && $_FILES['tax-certificate']['name'] != '') {
                    if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::JUSTIFICATIF_FISCAL, 'tax-certificate'))) {
                        $this->addFlash('personFiscalAddressErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-upload-files-error-message'));
                    } else {
                        $historyContent .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::JUSTIFICATIF_FISCAL) .'</li>';
                    }
                } else {
                    $this->addFlash('personFiscalAddressErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-missing-tax-certificate'));
                }
            }

            if (isset($_FILES['housing-certificate']) && $_FILES['housing-certificate']['name'] != '') {
                if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::JUSTIFICATIF_DOMICILE, 'housing-certificate'))) {
                    $this->addFlash('personFiscalAddressErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-upload-files-error-message'));
                } else {
                    $historyContent .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::JUSTIFICATIF_DOMICILE) .'</li>';
                }
            }

            if (isset($post['housed_by_third_person']) && true == $post['housed_by_third_person']){
                if (isset($_FILES['housed-by-third-person-declaration']) && $_FILES['housed-by-third-person-declaration']['name'] != ''){
                    if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::ATTESTATION_HEBERGEMENT_TIERS, 'housed-by-third-person-declaration'))) {
                        $this->addFlash('personFiscalAddressErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-upload-files-error-message'));
                    } else {
                        $historyContent .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::ATTESTATION_HEBERGEMENT_TIERS) .'</li>';
                    }
                } else {
                    $this->addFlash('personFiscalAddressErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-missing-housed-by-third-person-declaration'));
                }

                if (isset($_FILES['id-third-person-housing']) && $_FILES['housed-by-third-person-declaration']['name'] != ''){
                    if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT, 'id-third-person-housing'))) {
                        $this->addFlash('personFiscalAddressErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-upload-files-error-message'));
                    } else {
                        $historyContent .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT) .'</li>';
                    }
                } else {
                    $this->addFlash('personFiscalAddressErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-missing-id-third-person-housing'));
                }
            }

            $historyContent .= '</ul>';

            if ($this->get('session')->getFlashBag()->has('personFiscalAddressErrors')){
                $request->getSession()->set('personFiscalAddressData', $post);
            } else {
                $clientAddress->update();
                $this->addFlash('personFiscalAddressSuccess', $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-form-success-message'));

                if (false !== strpos($historyContent, '<li>')) {
                    $this->updateClientStatusAndNotifyClient($client, $historyContent);
                }
            }
        }

        $this->saveClientActionHistory($client, serialize(['id_client' => $client->id_client, 'post' => $request->request->all(), 'files' => $_FILES]));
        return $this->redirectToRoute('lender_profile');
    }

    /**
     * @Route("/profile/legal-entity/fiscal-address-update", name="profile_legal_entity_fiscal_address_update")
     * @Method("POST")
     */
    public function updateLegalEntityFiscalAddress(Request $request)
    {
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($this->getUser()->getClientId());
        /** @var \companies $company */
        $company = $this->get('unilend.service.entity_manager')->getRepository('companies');
        $company->get($client->id_client, 'id_client_owner');

        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        /** @var string $historyContent */
        $historyContent = '<ul>';

        if ($request->request->get('fiscal_address_company_form')) {
            $post = $request->request->all();

            if ($company->adresse1 != $post['fiscal_address_street']) {
                $company->adresse1 = $post['fiscal_address_street'];
                $historyContent .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-address-label') . '</li>';
            }

            if ($company->zip != $post['fiscal_address_zip']) {
                if (\pays_v2::COUNTRY_FRANCE == $post['fiscal_address_country']) {
                    /** @var \villes $cities */
                    $cities = $this->get('unilend.service.entity_manager')->getRepository('villes');
                    if ($cities->exist($post['fiscal_address_zip'], 'cp')) {
                        $company->zip = $post['fiscal_address_zip'];
                        $historyContent .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-zip-label') . '</li>';
                        unset($cities);
                    } else {
                        $this->addFlash('legalEntityFiscalAddressErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-unknown-zip-code-error-message'));
                    }
                } else {
                    $company->zip = $post['fiscal_address_zip'];
                    $historyContent .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-zip-label') . '</li>';
                }
            }

            if ($company->city != $post['fiscal_address_city']) {
                $company->city = $post['fiscal_address_city'];
                $historyContent .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-city-label') . '</li>';
            }

            if ($company->id_pays != $post['fiscal_address_country']) {
                $company->id_pays  = $post['fiscal_address_country'];
                $historyContent .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-country-label') . '</li>';
            }

            if (isset($post['same_postal_address']) && (bool)$company->status_adresse_correspondance != $post['same_postal_address']) {
                if (false == $post['same_postal_address'] && empty($form['postal'])) {
                    $this->addFlash('legalEntityFiscalAddressErrors', $translationManager->selectTranslation('lender-profile', 'information-tab-postal-address-missing-data'));
                } else {
                    $company->status_adresse_correspondance->meme_adresse_fiscal = ($post['same_postal_address'] == true) ? 1 : 0 ;
                    $historyContent .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-postal-checkbox') . '</li>';
                }
            }

            if ($this->get('session')->getFlashBag()->has('legalEntityFiscalAddressErrors')){
                $request->getSession()->set('legalEntityFiscalAddressData', $post);
            } else {
                $company->update();
                $this->addFlash('legalEntityFiscalAddressSuccess', $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-form-success-message'));

                if (false != strpos($historyContent, '<li>')) {
                    $this->updateClientStatusAndNotifyClient($client, $historyContent);
                }
            }
        }
        $this->saveClientActionHistory($client, serialize(['id_client' => $client->id_client, 'post' => $request->request->all()]));
        return $this->redirectToRoute('lender_profile');
    }

    /**
     * @Route("/profile/postal-address-update", name="profile_postal_address_update")
     * @Method("POST")
     */
    public function updatePostalAddress(Request $request)
    {
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($this->getUser()->getClientId());
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->get('unilend.service.entity_manager')->getRepository('clients_adresses');
        $clientAddress->get($client->id_client, 'id_client');

        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        /** @var bool $clientAddressModified */
        $clientAddressModified = false;

        if ($request->request->get('postal_address_form')) {
            $formPostalAddress = $request->request->all();
            if ($clientAddress->adresse1 != $formPostalAddress['postal_address_street']) {
                $clientAddress->adresse1 = $formPostalAddress['postal_address_street'];
                $clientAddressModified = true;
            }

            if ($clientAddress->cp != $formPostalAddress['postal_address_zip']) {
                $clientAddress->cp = $formPostalAddress['postal_address_zip'];
                $clientAddressModified = true;
            }

            if ($clientAddress->ville != $formPostalAddress['postal_address_city']) {
                $clientAddress->ville = $formPostalAddress['postal_address_city'];
                $clientAddressModified = true;
            }

            if ($clientAddress->id_pays != $formPostalAddress['postal_address_country']) {
                $clientAddress->id_pays = $formPostalAddress['postal_address_country'];
                $clientAddressModified = true;
            }

            if ($clientAddressModified) {
                $clientAddress->update();
                $this->addFlash('postalAddressSuccess', $translationManager->selectTranslation('lender-profile', 'information-tab-postal-address-form-success-message'));
            }
        }
        $this->saveClientActionHistory($client, serialize(['id_client' => $client->id_client, 'post' => $request->request->all()]));
        return $this->redirectToRoute('lender_profile');
    }

    /**
     * @Route("/profile/documents", name="lender_completeness")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function showLenderCompletenessForm()
    {
        return $this->render('Ici viendra le formulaire d\'upload des fichiers de complétude');
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
     * @param \clients $client
     * @param string $serialize
     */
    private function saveClientActionHistory(\clients $client, $serialize)
    {
        /** @var \clients_history_actions $clientHistoryActions */
        $clientHistoryActions = $this->get('unilend.service.entity_manager')->getRepository('clients_history_actions');
        $clientHistoryActions->histo(4, 'info perso profile', $client->id_client, $serialize);
    }

    /**
     * @param \clients $client
     * @param string $historyContent
     */
    private function updateClientStatusAndNotifyClient(\clients $client, $historyContent)
    {
        /** @var ClientManager $clientManager */
        $clientManager = $this->get('unilend.service.client_manager');
        $clientManager->changeClientStatusTriggeredByClientAction($client->id_client, $historyContent);
        $this->sendAccountModificationEmail($client);
    }

    /**
     * @param \clients $client
     */
    private function sendAccountModificationEmail(\clients $client)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $oSettings->get('Facebook', 'type');
        $lien_fb = $oSettings->value;
        $oSettings->get('Twitter', 'type');
        $lien_tw = $oSettings->value;

        $varMail = [
            'surl'    => $this->get('assets.packages')->getUrl(''),
            'url'     => $this->get('assets.packages')->getUrl(''),
            'prenom'  => $client->prenom,
            'lien_fb' => $lien_fb,
            'lien_tw' => $lien_tw
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-modification-compte', $varMail);
        $message->setTo($client->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    private function getSessionFormData(Request $request)
    {
        $form['person']            = $request->getSession()->get('personIdentityData', '');
        $form['legalEntity']       = $request->getSession()->get('legalEntityIdentityData', '');
        $form['personFiscal']      = $request->getSession()->get('personFiscalAddressData', '');
        $form['legalEntityFiscal'] = $request->getSession()->get('legalEntityFiscalAddressData', '');
        $form['postal']            = $request->getSession()->get('postalAddressData', '');

        $request->getSession()->remove('personFiscalAddressData');
        $request->getSession()->remove('legalEntityFiscalAddressData');
        $request->getSession()->remove('postalAddressData');
        $request->getSession()->remove('personIdentityData');
        $request->getSession()->remove('legalEntityIdentityData');

        return $form;
    }

    /**
     * @Route("/profile/ajax/zip", name="lender_profile_ajax_zip")
     * @Method("GET")
     */
    public function getZipAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            /** @var LocationManager $locationManager */
            $locationManager = $this->get('unilend.service.location_manager');
            return new JsonResponse($locationManager->getCities( $request->query->get('zip')));
        }

        return new Response('not an ajax request');
    }
}
