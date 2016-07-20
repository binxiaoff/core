<?php


namespace Unilend\Bundle\FrontBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Unilend\core\Loader;

class LenderProfileController extends Controller
{

    /**
     * @Route("/synthese", name="lender_dashboard")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function showDashboardAction()
    {

        return $this->render('pages/user_preter_dashboard.twig',
            array()
        );
    }

    /**
     * @Route("/profile", name="lender_profile")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function showLenderInformationAction(Request $request)
    {
        $templateVariables = [];

        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount  = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->get('unilend.service.entity_manager')->getRepository('clients_adresses');
        $client->get($this->getUser()->getClientId());
        $lenderAccount->get($client->id_client, 'id_client_owner');
        $clientAddress->get($client->id_client, 'id_client');

        $successMessage['identity'] = (false == empty($request->getSession()->get('identityFormSuccessMessage'))) ? $request->getSession()->get('identityFormSuccessMessage') : '';
        $successMessage['fiscal']   = (false == empty($request->getSession()->get('fiscalAddressFormSuccessMessage'))) ? $request->getSession()->get('fiscalAddressFormSuccessMessage') : '';
        $successMessage['postal']   = (false == empty($request->getSession()->get('postalAddressFormSuccessMessage'))) ? $request->getSession()->get('postalAddressFormSuccessMessage') : '';
        $formData['fiscal']         = (false == empty($request->getSession()->get('fiscalAddressFormData'))) ? $request->getSession()->get('fiscalAddressFormData') : [];
        $formData['postal']         = (false == empty($request->getSession()->get('postalAddressFormData'))) ? $request->getSession()->get('postalAddressFormData') : [];
        $formData['identity']       = (false == empty($request->getSession()->get('identityFormData'))) ? $request->getSession()->get('identityFormData') : [];
        $formErrors['identity']     = (false == empty($request->getSession()->get('identityFormErrors'))) ? $request->getSession()->get('identityFormErrors') : '';
        $formErrors['fiscal']       = (false == empty($request->getSession()->get('fiscalAddressFormErrors'))) ? $request->getSession()->get('fiscalAddressFormErrors') : [];

        $request->getSession()->remove('identityFormSuccessMessage');
        $request->getSession()->remove('fiscalAddressFormSuccessMessage');
        $request->getSession()->remove('postalAddressFormSuccessMessage');
        $request->getSession()->remove('fiscalAddressFormData');
        $request->getSession()->remove('postalAddressFormData');
        $request->getSession()->remove('identityFormData');
        $request->getSession()->remove('identityFormErrorMessage');
        $request->getSession()->remove('identityFormErrors');
        $request->getSession()->remove('fiscalAddressFormErrors');

        $identityFormData = [
            'civilite' => isset($formData['identity']['civilite']) ? $formData['identity']['civilite'] : $client->civilite,
            'nom_usage' => isset($formData['identity']['nom_usage']) ? $formData['identity']['nom_usage'] : $client->nom_usage,
            'nationalite' => isset($formData['identity']['nationalite']) ? $formData['identity']['nationalite'] : $client->id_nationalite
        ];

        $fiscalAddressFormData = [
            'street'            => isset($formData['fiscal']['street']) ? $formData['fiscal']['street'] : $clientAddress->adresse_fiscal,
            'code'              => isset($formData['fiscal']['code']) ? $formData['fiscal']['code'] : $clientAddress->cp_fiscal,
            'ville'             => isset($formData['fiscal']['ville']) ? $formData['fiscal']['ville'] : $clientAddress->ville_fiscal,
            'pays'              => isset($formData['fiscal']['pays']) ? $formData['fiscal']['pays'] : $clientAddress->id_pays_fiscal,
            'mobile'            => isset($formData['fiscal']['mobile']) ? $formData['fiscal']['mobile'] : $client->mobile,
            'is_correspondence' => isset($formData['fiscal']['is_correspondence']) ? $formData['fiscal']['is_correspondence'] : (bool) $clientAddress->meme_adresse_fiscal
        ];

        $postalAddressFormData = [
            'street' => isset($formData['postal']['street']) ? $formData['postal']['street'] : $clientAddress->adresse1,
            'code'   => isset($formData['postal']['code']) ? $formData['postal']['code'] : $clientAddress->cp,
            'ville'  => isset($formData['postal']['ville']) ? $formData['postal']['ville'] : $clientAddress->ville,
            'pays'   => isset($formData['postal']['pays']) ? $formData['postal']['pays'] : $clientAddress->id_pays,
        ];

        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Liste deroulante origine des fonds', 'type');
        $templateVariables['originOfFunds'] = explode(';', $settings->value); //TODO use twig filter for that, as it is a setting

        $templateVariables['client']              = $client->select('id_client = ' . $client->id_client)[0];
        $templateVariables['lenderAccount']       = $lenderAccount->select('id_lender_account = ' . $lenderAccount->id_lender_account)[0];
        $templateVariables['clientAddresses']     = $clientAddress->select('id_client = ' . $client->id_client)[0];
        $templateVariables['identityAttachments'] = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [
            \attachment_type::CNI_PASSPORTE,
            \attachment_type::CNI_PASSPORTE_VERSO
        ]);

        $templateVariables['residenceAttachments'] = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [
            \attachment_type::JUSTIFICATIF_DOMICILE,
            \attachment_type::ATTESTATION_HEBERGEMENT_TIERS,
            \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT
        ]);
        $templateVariables['isLivingAbroad'] = ($clientAddress->id_pays_fiscal > \pays_v2::COUNTRY_FRANCE);
        $templateVariables['formData'] = [
            'fiscal'         => $fiscalAddressFormData,
            'postal'         => $postalAddressFormData,
            'identity'       => $identityFormData,
            'errors'         => $formErrors,
            'successMessage' => $successMessage
        ];

        return $this->render('pages/lender_profile/lender_info.html.twig', $templateVariables);
    }

    /**
     * @Route("/profile/info/update", name="profile_info_update")
     * @Method("POST")
     */
    public function changeIdentityDataAction(Request $request)
    {
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($this->getUser()->getClientId());
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount  = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lenderAccount->get($client->id_client, 'id_client_owner');

        /** @var \clients_history_actions $clientHistoryActions */
        $clientHistoryActions = $this->get('unilend.service.entity_manager')->getRepository('clients_history_actions');

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        $lenderIdRecto = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [\attachment_type::CNI_PASSPORTE])[0];
        $lenderIdVerso = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [\attachment_type::CNI_PASSPORTE_VERSO])[0];

        /** @var array $formData */
        $formData  = $request->request->get('identity');

        $serialize = serialize(array('id_client' => $client->id_client, 'post' => $formData, 'files' => $_FILES));
        $clientHistoryActions->histo(4, 'info perso profile', $client->id_client, $serialize);

        /** @var array $errorMessages */
        $errorMessages = [];
        /** @var string $contentForClientStatusHistory */
        $contentForClientStatusHistory = '<ul>';

        if ($client->nom_usage != $formData['nom_usage']) {
            $client->nom_usage = $ficelle->majNom($_POST['nom-dusage']);
            $request->getSession()->set('identityFormSuccessMessage', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-update-success-message'));
        }

        if (isset($_FILES['id_recto']) && $_FILES['id_recto']['name'] != '' && $_FILES['id_recto']['name'] != $lenderIdRecto['path']) {
            $attachmentIdRecto = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE, 'id_recto');
            if (false === is_numeric($attachmentIdRecto)) {
                $errorMessages[] = $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-upload-files-error-message');
            } else {
                $contentForClientStatusHistory .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::CNI_PASSPORTE) .'</li>';
            }
        }

        if (isset($_FILES['id_verso']) && $_FILES['id_verso']['name'] != '' && $_FILES['id_verso']['name'] != $lenderIdVerso['path']) {
            $attachmentIdVerso = $this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORTE_VERSO, 'id_verso');
            if (false === is_numeric($attachmentIdVerso)) {
                $errorMessages[] = $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-upload-files-error-message');
            } else {
                $contentForClientStatusHistory .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::CNI_PASSPORTE_VERSO) .'</li>';
            }
        }

        if ($client->id_nationalite != $formData['nationalite']) {
            if (isset($attachmentIdRecto)) {
                $client->id_nationalite = $formData['nationalite'];
                $contentForClientStatusHistory .= '<li>'. $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-nationality-label') .'</li>';
            } else {
                $errorMessages[] = $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-change-ID-warning-message');
            }
        }

        if ($client->civilite != $formData['civilite']) {
            if (isset($attachmentIdRecto)){
                $client->civilite = $formData['civilite'];
                $contentForClientStatusHistory .= '<li>'. $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-form-of-address-label') .'</li>';
            } else {
                $errorMessages[] = $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-change-ID-warning-message');
            }
        }

        $contentForClientStatusHistory .= '</ul>';

        if (false === empty($errorMessages)){
            $request->getSession()->set('identityFormErrors', $errorMessages);
            $request->getSession()->set('identityFormData', $formData);
        } elseif (false !== strpos($contentForClientStatusHistory, '<li>')) {
            /** @var ClientManager $clientManager */
            $clientManager = $this->get('unilend.service.client_manager');
            $clientManager->changeClientStatusTriggeredByClientAction($client->id_client, $contentForClientStatusHistory);
            $this->sendAccountModificationEmail($client);
            $request->getSession()->set('identityFormSuccessMessage', $translationManager->selectTranslation('lender-profile', 'information-tab-identity-section-files-update-success-message'));
            $client->update();

        }

        $this->redirectToRoute('lender_profile');
    }

    /**
     * @Route("/profile/address/update", name="profile_address_update")
     * @Method("POST")
     */
    public function changeAddressAction(Request $request)
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
        /** @var array $clientHousingAttachments */
        $clientHousingAttachments = $lenderAccount->getAttachments($lenderAccount->id_lender_account, [
            \attachment_type::JUSTIFICATIF_DOMICILE,
            \attachment_type::ATTESTATION_HEBERGEMENT_TIERS,
            \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT
        ]);
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        /** @var \clients_history_actions $clientHistoryActions */
        $clientHistoryActions = $this->get('unilend.service.entity_manager')->getRepository('clients_history_actions');
        /** @var array $formDataFiscalAddress */
        $formDataFiscalAddress = isset($request->request->get('identity')['address']) ? $request->request->get('identity')['address'] : '';
        /** @var array $formDataPostalAddress */
        $formDataPostalAddress = isset($request->request->get('identity')['correspondence']) ? $request->request->get('identity')['correspondence'] : '';

        $serialize = serialize(array('id_client' => $client->id_client, 'post' => ['fiscalAddress' => $formDataFiscalAddress, 'postalAddress' => $formDataPostalAddress], 'files' => $_FILES));
        $clientHistoryActions->histo(4, 'info perso profile', $client->id_client, $serialize);

        /** @var string $contentForClientStatusHistory */
        $contentForClientStatusHistory = '<ul>';

        $fiscalFormErrors = [];
        $postalFormErrors = [];

        if (false === empty($formDataFiscalAddress)) {
            if ($client->mobile != $formDataFiscalAddress['mobile']) {
                $client->mobile = str_replace(' ', '', $formDataFiscalAddress['mobile']);
                $client->update();
            }

            if ($clientAddress->adresse_fiscal != $formDataFiscalAddress['street']) {
                $clientAddress->adresse_fiscal = $formDataFiscalAddress['street'];
                $contentForClientStatusHistory .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-address-label') . '</li>';
            }

            if ($clientAddress->cp_fiscal != $formDataFiscalAddress['code']) {
                if (\pays_v2::COUNTRY_FRANCE == $formDataFiscalAddress['pays']) {
                    /** @var \villes $cities */
                    $cities = $this->get('unilend.service.entity_manager')->getRepository('villes');
                    if ($cities->exist($formDataFiscalAddress['pays'], 'cp')) {
                        $clientAddress->cp_fiscal = $formDataFiscalAddress['code'];
                        $contentForClientStatusHistory .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-zip-label') . '</li>';
                        unset($cities);
                    } else {
                        $fiscalFormErrors[] = $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-unknown-zip-code-error-message');
                    }
                } else {
                    $clientAddress->cp_fiscal = $formDataFiscalAddress['code'];
                    $contentForClientStatusHistory .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-zip-label') . '</li>';
                }
            }

            if ($clientAddress->ville_fiscal != $formDataFiscalAddress['ville']) {
                $clientAddress->ville_fiscal = $formDataFiscalAddress['ville'];
                $contentForClientStatusHistory .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-city-label') . '</li>';
            }

            if ($clientAddress->id_pays_fiscal != $formDataFiscalAddress['pays']) {
                $clientAddress->id_pays_fiscal = $formDataFiscalAddress['pays'];
                $contentForClientStatusHistory .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-country-label') . '</li>';
            }

            if (isset($formDataFiscalAddress['is_correspondence']) && (bool)$clientAddress->meme_adresse_fiscal != $formDataFiscalAddress['is_correspondence']) {
                if (false == $formDataFiscalAddress['is_correspondence'] && empty($formDataPostalAddress)) {
                    $fiscalFormErrors[] = $translationManager->selectTranslation('lender-profile', 'information-tab-postal-address-missing-data');
                } else {
                    $clientAddress->meme_adresse_fiscal = ($formDataFiscalAddress['is_correspondence'] == true) ? 1 : 0 ;
                    $contentForClientStatusHistory .= '<li>' . $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-postal-checkbox') . '</li>';
                    $formDataPostalAddress = $formDataFiscalAddress;
                }
            }

            if ($clientAddress->id_pays_fiscal > \pays_v2::COUNTRY_FRANCE) {
                if (isset($formDataFiscalAddress['no_us_person']) && $formDataFiscalAddress['no_us_person'] == false) {
                    $contentForClientStatusHistory .= '<li>'. $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-us-person-checkbox-label') .'</li>';
                }
            }

            if (false === $formDataFiscalAddress['pays'] > \pays_v2::COUNTRY_FRANCE) {
                $previousTaxStatement = (isset($clientHousingAttachments[\attachment_type::JUSTIFICATIF_FISCAL]['path'])) ? $clientHousingAttachments[\attachment_type::JUSTIFICATIF_FISCAL]['path'] : '';
                if (isset($_FILES['tax-domicile'])
                    && $_FILES['tax-domicile']['name'] != $previousTaxStatement
                    && $_FILES['tax-domicile']['name'] != '') {
                    if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::JUSTIFICATIF_FISCAL, 'tax-domicile'))) {
                        $fiscalFormErrors[] = $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-upload-files-error-message');
                    } else {
                        $contentForClientStatusHistory .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::JUSTIFICATIF_FISCAL) .'</li>';
                    }
                } else {
                    $fiscalFormErrors[] = $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-missing-tax-certificate');
                }
            }

            if (isset($_FILES['housing-certificate'])
                && $_FILES['housing-certificate']['name'] != $clientHousingAttachments[\attachment_type::JUSTIFICATIF_DOMICILE]['path']
                && $_FILES['housing-certificate']['name'] != '') {
                if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::JUSTIFICATIF_DOMICILE, 'housing-certificate'))) {
                    $fiscalFormErrors[] = $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-upload-files-error-message');
                } else {
                    $contentForClientStatusHistory .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::JUSTIFICATIF_DOMICILE) .'</li>';
                }
            }

            if (isset($formDataFiscalAddress['third_person'])){
                $previousHousedByThirdPerson = isset($clientHousingAttachments[\attachment_type::ATTESTATION_HEBERGEMENT_TIERS]['path']) ? $clientHousingAttachments[\attachment_type::ATTESTATION_HEBERGEMENT_TIERS]['path'] : '';
                $previousThirdPersonID       = isset($clientHousingAttachments[\attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT]['path']) ? $clientHousingAttachments[\attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT]['path'] : '';

                if (isset($_FILES['housed-by-third-person-declaration'])){
                    if ($previousHousedByThirdPerson != $_FILES['housed-by-third-person-declaration']['name'] && $_FILES['housed-by-third-person-declaration']['name'] != '') {
                        if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::ATTESTATION_HEBERGEMENT_TIERS, 'housed-by-third-person-declaration'))) {
                            $fiscalFormErrors[] = $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-upload-files-error-message');
                        } else {
                            $contentForClientStatusHistory .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::ATTESTATION_HEBERGEMENT_TIERS) .'</li>';
                        }
                    }
                } else {
                    $fiscalFormErrors[] = $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-missing-housed-by-third-person-declaration');
                }

                if (isset($_FILES['id-third-person-housing'])){
                    if ($previousThirdPersonID != $_FILES['id-third-person-housing']['name'] && $_FILES['housed-by-third-person-declaration']['name'] != '') {
                        if (false === is_numeric($this->uploadAttachment($lenderAccount->id_lender_account, \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT, 'id-third-person-housing'))) {
                            $fiscalFormErrors[] = $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-section-upload-files-error-message');
                        } else {
                            $contentForClientStatusHistory .= '<li>'. $translationManager->selectTranslation('projet', 'document-type-' . \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT) .'</li>';
                        }
                    }
                } else {
                    $fiscalFormErrors[] = $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-missing-id-third-person-housing');
                }
            }
        }

        /** @var bool $clientAddressModified */
        $clientAddressModified = false;

        if (false === empty($formDataPostalAddress)) {
            if ($clientAddress->adresse1 != $formDataPostalAddress['street']) {
                $clientAddress->adresse1 = $formDataPostalAddress['street'];
                $clientAddressModified = true;
            }

            if ($clientAddress->cp != $formDataPostalAddress['code']) {
                $clientAddress->cp = $formDataPostalAddress['code'];
                $clientAddressModified = true;
            }

            if ($clientAddress->ville != $formDataPostalAddress['ville']) {
                $clientAddress->ville = $formDataPostalAddress['ville'];
                $clientAddressModified = true;
            }

            if ($clientAddress->id_pays != $formDataPostalAddress['pays']) {
                $clientAddress->id_pays = $formDataPostalAddress['pays'];
                $clientAddressModified = true;
            }

            if ($clientAddressModified) {
                $clientAddress->update();
                $request->getSession()->set('postalAddressFormSuccessMessage', $translationManager->selectTranslation('lender-profile', 'information-tab-postal-address-form-success-message'));
            }
        }

        $contentForClientStatusHistory .= '</ul>';

        if (false === empty($fiscalFormErrors) || false === empty($postalFormErrors)){
            $request->getSession()->set('fiscalAddressFormData', $formDataFiscalAddress);
            $request->getSession()->set('postalAddressFormData', $formDataPostalAddress);
            $request->getSession()->set('fiscalAddressFormErrors', $fiscalFormErrors);
        } elseif (false !== strpos($contentForClientStatusHistory, '<li>')) {
            $clientAddress->update();
            /** @var ClientManager $clientManager */
            $clientManager = $this->get('unilend.service.client_manager');
            $clientManager->changeClientStatusTriggeredByClientAction($client->id_client, $contentForClientStatusHistory);
            $this->sendAccountModificationEmail($client);
            $request->getSession()->set('fiscalAddressFormSuccessMessage', $translationManager->selectTranslation('lender-profile', 'information-tab-fiscal-address-form-success-message'));
        }

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
        $attachmentHelper = Loader::loadLib('attachment_helper', array($attachments, $attachmentTypes, $this->get('kernel')->getRootDir() . '/../'));

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

    private function sendAccountModificationEmail(\clients $client)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $oSettings->get('Facebook', 'type');
        $lien_fb = $oSettings->value;
        $oSettings->get('Twitter', 'type');
        $lien_tw = $oSettings->value;

        $varMail = array(
            'surl'    => $this->get('assets.packages')->getUrl(''),
            'url'     => $this->get('assets.packages')->getUrl(''),
            'prenom'  => $client->prenom,
            'lien_fb' => $lien_fb,
            'lien_tw' => $lien_tw
        );

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-modification-compte', $varMail);
        $message->setTo($client->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

}