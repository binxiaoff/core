<?php

class profileController extends bootstrap
{
    /**
     * @var attachment_helper
     */
    private $attachmentHelper;

    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
        $this->setHeader('header_account');

        if (! $this->clients->checkAccess()) {
            header('Location: ' . $this->lurl);
            die;
        }
        $this->clients->checkAccessLender();

        //Recuperation des element de traductions
        $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);
        $this->lng['profile']         = $this->ln->selectFront('preteur-profile', $this->language, $this->App);

        // Heure fin periode funding
        $this->settings->get('Heure fin periode funding', 'type');
        $this->heureFinFunding = $this->settings->value;
        $this->page = 'profile';
    }

    public function _default()
    {
        $oLenderAccount = $this->loadData('lenders_accounts');
        $oLenderAccount->get($this->clients->id_client, 'id_client_owner');
        /** @var \Unilend\Service\AutoBidSettingsManager $oAutoBidSettingsManager */
        $oAutoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');
        $this->bIsAllowedToSeeAutobid = $oAutoBidSettingsManager->isQualified($oLenderAccount);

        if (in_array($this->clients->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER))) {
            $this->_particulier();

        } elseif (in_array($this->clients->type, array(\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY))) {
            $this->_societe();
        }
    }

    public function _particulier()
    {
        $this->lng['etape1']          = $this->ln->selectFront('inscription-preteur-etape-1', $this->language, $this->App);
        $this->lng['etape2']          = $this->ln->selectFront('inscription-preteur-etape-2', $this->language, $this->App);
        $this->lng['gestion-alertes'] = $this->ln->selectFront('preteur-profile-gestion-alertes', $this->language, $this->App);

        $this->unLoadCss('default/custom-theme/jquery-ui-1.10.3.custom');
        $this->loadCss('default/preteurs/new-style');

        $this->unLoadJs('default/functions');
        $this->unLoadJs('default/main');
        $this->unLoadJs('default/ajax');
        $this->loadJs('default/preteurs/functions');
        $this->loadJs('default/main');
        $this->loadJs('default/ajax');

        $this->pays                          = $this->loadData('pays_v2');
        $this->nationalites                  = $this->loadData('nationalites_v2');
        $this->lenders_accounts              = $this->loadData('lenders_accounts');
        $this->clients_status                = $this->loadData('clients_status');
        $this->clients_status_history        = $this->loadData('clients_status_history');
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_type_notif    = $this->loadData('clients_gestion_type_notif');
        $this->attachment                    = $this->loadData('attachment');
        $this->attachment_type               = $this->loadData('attachment_type');

        $this->clients_status->getLastStatut($this->clients->id_client);

        $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');
        $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);

        /** @var \Unilend\Service\AutoBidSettingsManager $oAutoBidSettingsManager */
        $oAutoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');
        $this->bIsAllowedToSeeAutobid = $oAutoBidSettingsManager->isQualified($this->lenders_accounts);

        $this->lPays = $this->pays->select('', 'ordre ASC');
        $this->lNatio = $this->nationalites->select('', 'ordre ASC');

        $nais        = explode('-', $this->clients->naissance);
        $this->jour  = $nais[2];
        $this->mois  = $nais[1];
        $this->annee = $nais[0];

        $this->email = $this->clients->email;

        $this->settings->get("Liste deroulante origine des fonds", 'type');
        $this->origine_fonds = explode(';', $this->settings->value);

        if ($this->lenders_accounts->iban != '') {
            $this->iban1 = substr($this->lenders_accounts->iban, 0, 4);
            $this->iban2 = substr($this->lenders_accounts->iban, 4, 4);
            $this->iban3 = substr($this->lenders_accounts->iban, 8, 4);
            $this->iban4 = substr($this->lenders_accounts->iban, 12, 4);
            $this->iban5 = substr($this->lenders_accounts->iban, 16, 4);
            $this->iban6 = substr($this->lenders_accounts->iban, 20, 4);
            $this->iban7 = substr($this->lenders_accounts->iban, 24, 3);
        } else {
            $this->iban1 = 'FR...';
        }

        $this->etranger = 0;

        if ($this->clients->id_nationalite == \nationalites_v2::NATIONALITY_FRENCH && $this->clients_adresses->id_pays_fiscal > \pays_v2::COUNTRY_FRANCE) {
            $this->etranger = 1;
        } elseif ($this->clients->id_nationalite != \nationalites_v2::NATIONALITY_FRENCH && $this->clients_adresses->id_pays_fiscal > \pays_v2::COUNTRY_FRANCE) {
            $this->etranger = 2;
        }
        $this->infosNotifs['vos-offres-et-vos-projets'] = array(
            \clients_gestion_type_notif::TYPE_NEW_PROJECT => array(
                'title' => $this->lng['gestion-alertes']['annonce-des-nouveaux-projets'],
                'info' => $this->lng['gestion-alertes']['annonce-des-nouveaux-projets-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_BID_PLACED => array(
                'title' => $this->lng['gestion-alertes']['offres-realisees'],
                'info' => $this->lng['gestion-alertes']['offres-realisees-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_BID_REJECTED => array(
                'title' => $this->lng['gestion-alertes']['offres-refusees'],
                'info' => $this->lng['gestion-alertes']['offres-refusees-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED => array(
                'title' => $this->lng['gestion-alertes']['offres-acceptees'],
                'info' => $this->lng['gestion-alertes']['offres-acceptees-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM => array(
                'title' => $this->lng['gestion-alertes']['incidents-projets-et-regularisation'],
                'info' => $this->lng['gestion-alertes']['incidents-projets-et-regularisation-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_AUTOBID_BALANCE_LOW => array(
                'title' => $this->lng['gestion-alertes']['autobid-balance-low'],
                'info' => $this->lng['gestion-alertes']['autobid-balance-low-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_AUTOBID_BALANCE_INSUFFICIENT => array(
                'title' => $this->lng['gestion-alertes']['autobid-balance-insufficient'],
                'info' => $this->lng['gestion-alertes']['autobid-balance-insufficient-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
        );
        $this->infosNotifs['vos-remboursements'] = array(
            \clients_gestion_type_notif::TYPE_REPAYMENT => array(
                'title' => $this->lng['gestion-alertes']['remboursements'],
                'info' => $this->lng['gestion-alertes']['remboursements-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
        );
        $this->infosNotifs['mouvements-sur-votre-compte'] = array(
            \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT => array(
                'title' => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-virement'],
                'info' => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-virement-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT   => array(
                'title' => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-carte-bancaire'],
                'info' => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-carte-bancaire-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_DEBIT => array(
                'title' => $this->lng['gestion-alertes']['retrait'],
                'info' => $this->lng['gestion-alertes']['retrait-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
        );

        $this->lTypeNotifs = $this->clients_gestion_type_notif->select();
        $this->lNotifs     = $this->clients_gestion_notifications->select('id_client = ' . $this->clients->id_client);
        $this->NotifC      = $this->clients_gestion_notifications->getNotifs($this->clients->id_client);

        $aMissingNotificationTypes = array_diff(array_column($this->lTypeNotifs, 'id_client_gestion_type_notif'), array_keys($this->NotifC));

        if (false === empty($aMissingNotificationTypes)) {
            foreach ($aMissingNotificationTypes as $iMissingNotificationType) {
                $this->clients_gestion_notifications->id_client        = $this->clients->id_client;
                $this->clients_gestion_notifications->id_notif         = $iMissingNotificationType;
                $this->clients_gestion_notifications->immediatement    = 1;
                $this->clients_gestion_notifications->quotidienne      = 0;
                $this->clients_gestion_notifications->hebdomadaire     = 0;
                $this->clients_gestion_notifications->mensuelle        = 0;
                $this->clients_gestion_notifications->uniquement_notif = 0;
                $this->clients_gestion_notifications->create(array('id_client' => $this->clients->id_client, 'id_notif' => $iMissingNotificationType));
            }

            $this->lNotifs = $this->clients_gestion_notifications->select('id_client = ' . $this->clients->id_client);
            $this->NotifC  = $this->clients_gestion_notifications->getNotifs($this->clients->id_client);
        }

        if (isset($_POST['send_gestion_alertes'])) {
            foreach ($this->lTypeNotifs as $n) {
                $id_notif = $n['id_client_gestion_type_notif'];

                if (false === empty($_POST['uniquement_notif_' . $id_notif])) {
                    $this->clients_gestion_notifications->immediatement    = 0;
                    $this->clients_gestion_notifications->quotidienne      = 0;
                    $this->clients_gestion_notifications->hebdomadaire     = 0;
                    $this->clients_gestion_notifications->mensuelle        = 0;
                    $this->clients_gestion_notifications->uniquement_notif = 1;
                    $this->clients_gestion_notifications->update(array('id_client' => $this->clients->id_client, 'id_notif' => $id_notif));
                } else {
                    $this->clients_gestion_notifications->immediatement    = empty($_POST['immediatement_' . $id_notif]) ? 0 : 1;
                    $this->clients_gestion_notifications->quotidienne      = in_array($id_notif, array(6, 7, 8)) || empty($_POST['quotidienne_' . $id_notif]) ? 0 : 1;
                    $this->clients_gestion_notifications->hebdomadaire     = in_array($id_notif, array(2, 3, 6, 7, 8)) || empty($_POST['hebdomadaire_' . $id_notif]) ? 0 : 1;
                    $this->clients_gestion_notifications->mensuelle        = in_array($id_notif, array(1, 2, 3, 6, 7, 8)) || empty($_POST['mensuelle_' . $id_notif]) ? 0 : 1;
                    $this->clients_gestion_notifications->uniquement_notif = 0;
                    $this->clients_gestion_notifications->update(array('id_client' => $this->clients->id_client, 'id_notif' => $id_notif));
                }
            }

            header('Location: ' . $this->lurl . '/profile/particulier/');
            die;
        }

        if (isset($_POST['send_form_particulier_perso'])) {
            $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST));
            $this->clients_history_actions->histo(4, 'info perso profile', $this->clients->id_client, $serialize);

            $this->etranger     = 0;
            if ($_POST['nationalite'] == \nationalites_v2::NATIONALITY_FRENCH && $_POST['pays1'] > \pays_v2::COUNTRY_FRANCE) {
                $this->etranger = 1;
            } elseif ($_POST['nationalite'] != \nationalites_v2::NATIONALITY_FRENCH && $_POST['pays1'] > \pays_v2::COUNTRY_FRANCE) {
                $this->etranger = 2;
            }

            $adresse_fiscal    = $this->clients_adresses->adresse_fiscal;
            $ville_fiscal      = $this->clients_adresses->ville_fiscal;
            $cp_fiscal         = $this->clients_adresses->cp_fiscal;
            $id_pays_fiscal    = $this->clients_adresses->id_pays_fiscal;
            $adresse1          = $this->clients_adresses->adresse1;
            $ville             = $this->clients_adresses->ville;
            $cp                = $this->clients_adresses->cp;
            $id_pays           = $this->clients_adresses->id_pays;
            $civilite          = $this->clients->civilite;
            $nom_usage         = $this->clients->nom_usage;
            $email             = $this->clients->email;
            $telephone         = $this->clients->telephone;
            $id_pays_naissance = $this->clients->id_pays_naissance;
            $ville_naissance   = $this->clients->ville_naissance;
            $id_nationalite    = $this->clients->id_nationalite;
            $naissance         = $this->clients->naissance;

            $this->form_ok = true;
            $this->reponse_email = '';

            $this->clients_adresses->meme_adresse_fiscal = (empty($_POST['mon-addresse'])) ? 0 : 1;
            $this->clients_adresses->adresse_fiscal      = $_POST['adresse_inscription'];
            $this->clients_adresses->ville_fiscal        = $_POST['ville_inscription'];
            $this->clients_adresses->cp_fiscal           = $_POST['postal'];
            $this->clients_adresses->id_pays_fiscal      = $_POST['pays1'];
            $this->clients_adresses->adresse1            = (empty($_POST['mon-addresse'])) ? $_POST['adress2'] : $_POST['adresse_inscription'];
            $this->clients_adresses->ville               = (empty($_POST['mon-addresse'])) ? $_POST['ville2'] : $_POST['ville_inscription'];
            $this->clients_adresses->cp                  = (empty($_POST['mon-addresse'])) ? $_POST['postal2'] : $_POST['postal'];
            $this->clients_adresses->id_pays             = (empty($_POST['mon-addresse'])) ? $_POST['pays2'] : $_POST['pays1'];

            $this->clients->civilite = $_POST['sex'];
            $this->clients->nom_usage = (isset($_POST['nom-dusage']) && $_POST['nom-dusage'] != $this->lng['etape1']['nom-dusage']) ? $this->ficelle->majNom($_POST['nom-dusage']) : '';

            //Get the insee code for birth place: if in France, city insee code; if overseas, country insee code
            $sCodeInsee = '';
            if (\pays_v2::COUNTRY_FRANCE == $_POST['pays3']) { // if France
                //Check birth city
                if (!isset($_POST['insee_birth']) || '' === $_POST['insee_birth']) {
                    /** @var villes $oVilles */
                    $oVilles = $this->loadData('villes');
                    //for France, the code insee is empty means that the city is not verified with table "villes", check again here.
                    if (false === $oVilles->get($_POST['naissance'], 'ville')) {
                        $this->form_ok = false;
                    } else {
                        $sCodeInsee = $oVilles->insee;
                    }
                    unset($oVilles);
                } else {
                    $sCodeInsee = $_POST['insee_birth'];
                }
            } else {
                /** @var pays_v2 $oPays */
                $oPays = $this->loadData('pays_v2');
                /** @var insee_pays $oInseePays */
                $oInseePays = $this->loadData('insee_pays');

                if ($oPays->get($_POST['pays3']) && $oInseePays->getByCountryIso(trim($oPays->iso))) {
                    $sCodeInsee = $oInseePays->COG;
                } else {
                    $this->form_ok = false;
                }
                unset($oPays, $oInseePays);
            }

            $this->clients->email             = $_POST['email'];
            $this->clients->telephone         = str_replace(' ', '', $_POST['phone']);
            $this->clients->id_pays_naissance = $_POST['pays3'];
            $this->clients->ville_naissance   = $_POST['naissance'];
            $this->clients->insee_birth       = $sCodeInsee;
            $this->clients->id_nationalite    = $_POST['nationalite'];
            $this->clients->naissance         = $_POST['annee_naissance'] . '-' . $_POST['mois_naissance'] . '-' . $_POST['jour_naissance'];

            if ($this->etranger > 0) {
                if (isset($_POST['check_etranger']) && $_POST['check_etranger'] == false) {
                    $this->form_ok = false;
                }
            }
            if ($this->dates->ageplus18($this->clients->naissance) == false) {
                $this->form_ok           = false;
                $_SESSION['reponse_age'] = $this->lng['etape1']['erreur-age'];
            }
            if (! isset($_POST['email']) || $_POST['email'] == $this->lng['etape1']['email']) {
                $this->form_ok = false;
            } elseif (isset($_POST['email']) && $this->ficelle->isEmail($_POST['email']) == false) {
                $this->form_ok = false;
            } elseif ($_POST['email'] != $_POST['conf_email']) {
                $this->form_ok = false;
            } elseif ($this->clients->existEmail($_POST['email']) == false) {
                if ($_POST['email'] != $this->email) {
                    $this->reponse_email       = $this->lng['etape1']['erreur-email'];
                    $this->form_ok             = false;
                    $_SESSION['reponse_email'] = $this->reponse_email;
                }
            }
            if (! isset($_POST['adresse_inscription']) || $_POST['adresse_inscription'] == $this->lng['etape1']['adresse']) {
                $this->form_ok = false;
            }
            if (! isset($_POST['ville_inscription']) || $_POST['ville_inscription'] == $this->lng['etape1']['ville']) {
                $this->form_ok = false;
            }
            if (! isset($_POST['postal']) || $_POST['postal'] == $this->lng['etape1']['code-postal']) {
                $this->form_ok = false;
            } else {
                /** @var villes $oVilles */
                $oVilles = $this->loadData('villes');
                //Check cp
                if (isset($_POST['pays1']) && \pays_v2::COUNTRY_FRANCE == $_POST['pays1']) {
                    //for France, check post code here.
                    if (false === $oVilles->exist($_POST['postal'], 'cp')) {
                        $this->form_ok = false;
                    }
                }
                unset($oVilles);
            }
            if (! isset($_POST['phone']) || $_POST['phone'] == $this->lng['etape1']['telephone']) {
                $this->form_ok = false;
            }
            if ($this->clients_adresses->meme_adresse_fiscal == 0) {
                if (! isset($_POST['adress2']) || $_POST['adress2'] == $this->lng['etape1']['adresse']) {
                    $this->form_ok = false;
                }
                if (! isset($_POST['ville2']) || $_POST['ville2'] == $this->lng['etape1']['ville']) {
                    $this->form_ok = false;
                }
                if (! isset($_POST['postal2']) || $_POST['postal2'] == $this->lng['etape1']['code-postal']) {
                    $this->form_ok = false;
                }
            }
            /////////////////////// PARTIE BANQUE /////////////////////////////
            $bRibUpdated = false;
            $fichier_rib = isset($this->attachments[attachment_type::RIB]['id']) ? $this->attachments[attachment_type::RIB]['id'] : null;

            if (isset($_FILES['rib']) && $_FILES['rib']['name'] != '') {
                $fichier_rib = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::RIB);
                if (is_numeric($fichier_rib)) {
                    $bRibUpdated = true;
                }
            }
            $bic_old  = $this->lenders_accounts->bic;
            $iban_old = $this->lenders_accounts->iban;

            $this->lenders_accounts->bic  = trim(strtoupper($_POST['bic']));
            $this->lenders_accounts->iban = '';
            for ($i = 1; $i <= 7; $i++) {
                $this->lenders_accounts->iban .= trim(strtoupper($_POST['iban-' . $i]));
            }
            $origine_des_fonds_old = $this->lenders_accounts->origine_des_fonds;

            $this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
            if ($_POST['preciser'] != $this->lng['etape2']['autre-preciser'] && $_POST['origine_des_fonds'] == 1000000) {
                $this->lenders_accounts->precision = $_POST['preciser'];
            } else {
                $this->lenders_accounts->precision = '';
            }
            $this->form_ok       = true;
            $this->error_fichier = false;
            $this->error_rib     = false;

            if (! isset($_POST['bic']) || $_POST['bic'] == $this->lng['etape2']['bic'] || $_POST['bic'] == '') {
                $this->form_ok = false;
            } elseif (isset($_POST['bic']) && $this->ficelle->swift_validate(trim($_POST['bic'])) == false) {
                $this->form_ok = false;
            }
            if (strlen($this->lenders_accounts->iban) < 27) {
                $this->form_ok = false;
            } elseif ($this->lenders_accounts->iban != '' && $this->ficelle->isIBAN($this->lenders_accounts->iban) != 1) {
                $this->form_ok = false;
            }
            if (! isset($_POST['origine_des_fonds']) || $_POST['origine_des_fonds'] == 0) {
                $this->form_ok = false;
            } elseif ($_POST['origine_des_fonds'] == 1000000 && in_array($_POST['preciser'], array($this->lng['etape2']['autre-preciser'], ''))) {
                $this->form_ok = false;
            }
            if (false === is_numeric($fichier_rib)) {
                $this->form_ok   = false;
                $this->error_rib = true;
            }
            if ($this->form_ok == true) {
                $bDocumentFiscalUpdated              = false;
                $bCniPasseportUpdated                = false;
                $bCniPasseporVersotUpdated           = false;
                $bJustificatifDomicileUpdated        = false;
                $bAttestationHebergementTiersUpdated = false;
                $bCniPassportTiersHebergeantIUpdated = false;
                $bAutreUpdated                       = false;

                if ($this->etranger == 1 || $this->etranger == 2) {
                    if (isset($_FILES['document_fiscal']) && $_FILES['document_fiscal']['name'] != '') {
                        $fichier_document_fiscal = $this->uploadAttachment($this->lenders_accounts->id_lender_account, \attachment_type::JUSTIFICATIF_FISCAL);
                        if (is_numeric($fichier_document_fiscal)) {
                            $bDocumentFiscalUpdated = true;
                        }
                    }
                }
                if (isset($_FILES['cni_passeport']) && $_FILES['cni_passeport']['name'] != '') {
                    $fichier_cni_passeport = $this->uploadAttachment($this->lenders_accounts->id_lender_account, \attachment_type::CNI_PASSPORTE);
                    if (is_numeric($fichier_cni_passeport)) {
                        $bCniPasseportUpdated = true;
                    }
                }
                if (isset($_FILES['cni_passeport_verso']) && $_FILES['cni_passeport_verso']['name'] != '') {
                    $fichier_cni_passeport_verso = $this->uploadAttachment($this->lenders_accounts->id_lender_account, \attachment_type::CNI_PASSPORTE_VERSO);
                    if (is_numeric($fichier_cni_passeport_verso)) {
                        $bCniPasseporVersotUpdated = true;
                    }
                }
                if (isset($_FILES['justificatif_domicile']) && $_FILES['justificatif_domicile']['name'] != '') {
                    $fichier_justificatif_domicile = $this->uploadAttachment($this->lenders_accounts->id_lender_account, \attachment_type::JUSTIFICATIF_DOMICILE);
                    if (is_numeric($fichier_justificatif_domicile)) {
                        $bJustificatifDomicileUpdated = true;
                    }
                }
                if (isset($_FILES['attestation_hebergement_tiers']) && $_FILES['attestation_hebergement_tiers']['name'] != '') {
                    $fichier_attestation_hebergement_tiers = $this->uploadAttachment($this->lenders_accounts->id_lender_account, \attachment_type::ATTESTATION_HEBERGEMENT_TIERS);
                    if (is_numeric($fichier_attestation_hebergement_tiers)) {
                        $bAttestationHebergementTiersUpdated = true;
                    }
                }
                if (isset($_FILES['cni_passport_tiers_hebergeant']) && $_FILES['cni_passport_tiers_hebergeant']['name'] != '') {
                    $fichier_cni_passport_tiers_hebergeant = $this->uploadAttachment($this->lenders_accounts->id_lender_account, \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT);
                    if (is_numeric($fichier_cni_passport_tiers_hebergeant)) {
                        $bCniPassportTiersHebergeantIUpdated = true;
                    }
                }
                if (isset($_FILES['autre1']) && $_FILES['autre1']['name'] != '') {
                    $fichier_autre = $this->uploadAttachment($this->lenders_accounts->id_lender_account, \attachment_type::AUTRE1);
                    if (is_numeric($fichier_autre)) {
                        $bAutreUpdated = true;
                    }
                }
                $this->clients->id_langue = 'fr';
                if ($this->reponse_email != '') {
                    $this->clients->email      = $this->email;
                    $_SESSION['reponse_email'] = $this->reponse_email;
                }

                $this->clients->update();
                $this->clients_adresses->update();
                $this->lenders_accounts->update();
                $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);

                //********************************************//
                //*** ENVOI DU MAIL NOTIFICATION notification-nouveaux-preteurs ***//
                //********************************************//

                $dateDepartControlPays = strtotime('2014-07-31 18:00:00');
                if (
                    $adresse_fiscal != $this->clients_adresses->adresse_fiscal ||
                    $ville_fiscal != $this->clients_adresses->ville_fiscal ||
                    $cp_fiscal != $this->clients_adresses->cp_fiscal ||
                    ! in_array($this->clients_adresses->id_pays_fiscal, array(0, $id_pays_fiscal)) && strtotime($this->clients->added) >= $dateDepartControlPays ||
                    $nom_usage != $this->clients->nom_usage ||
                    $id_pays_naissance != $this->clients->id_pays_naissance && strtotime($this->clients->added) >= $dateDepartControlPays ||
                    $id_nationalite != $this->clients->id_nationalite && strtotime($this->clients->added) >= $dateDepartControlPays ||
                    $naissance != $this->clients->naissance ||
                    $bCniPasseportUpdated === true ||
                    $bJustificatifDomicileUpdated === true ||
                    $bCniPasseporVersotUpdated === true ||
                    $bAttestationHebergementTiersUpdated === true ||
                    $bCniPassportTiersHebergeantIUpdated === true ||
                    $bAutreUpdated === true ||
                    $this->etranger > 0 && $bDocumentFiscalUpdated === true ||
                    $origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds ||
                    $bic_old != $this->lenders_accounts->bic ||
                    $iban_old != $this->lenders_accounts->iban ||
                    $bRibUpdated === true
                ) {
                    $contenu = '<ul>';
                    if ($adresse_fiscal != $this->clients_adresses->adresse_fiscal) {
                        $contenu .= '<li>adresse fiscale</li>';
                    }
                    if ($ville_fiscal != $this->clients_adresses->ville_fiscal) {
                        $contenu .= '<li>ville fiscale</li>';
                    }
                    if ($cp_fiscal != $this->clients_adresses->cp_fiscal) {
                        $contenu .= '<li>cp fiscal</li>';
                    }
                    if (! in_array($this->clients_adresses->id_pays_fiscal, array(0, $id_pays_fiscal)) && strtotime($this->clients->added) >= $dateDepartControlPays) {
                        $contenu .= '<li>pays fiscal</li>';
                    }
                    if ($adresse1 != $this->clients_adresses->adresse1) {
                        $contenu .= '<li>adresse</li>';
                    }
                    if ($ville != $this->clients_adresses->ville) {
                        $contenu .= '<li>ville</li>';
                    }
                    if ($cp != $this->clients_adresses->cp) {
                        $contenu .= '<li>cp</li>';
                    }
                    if ($id_pays != $this->clients_adresses->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays) {
                        $contenu .= '<li>pays</li>';
                    }
                    if ($civilite != $this->clients->civilite) {
                        $contenu .= '<li>civilite</li>';
                    }
                    if ($nom_usage != $this->clients->nom_usage) {
                        $contenu .= '<li>nom_usage</li>';
                    }
                    if ($email != $this->clients->email) {
                        $contenu .= '<li>email</li>';
                    }
                    if ($telephone != $this->clients->telephone) {
                        $contenu .= '<li>telephone</li>';
                    }
                    if ($id_pays_naissance != $this->clients->id_pays_naissance && strtotime($this->clients->added) >= $dateDepartControlPays) {
                        $contenu .= '<li>pays naissance</li>';
                    }
                    if ($ville_naissance != $this->clients->ville_naissance) {
                        $contenu .= '<li>ville naissance</li>';
                    }
                    if ($id_nationalite != $this->clients->id_nationalite && strtotime($this->clients->added) >= $dateDepartControlPays) {
                        $contenu .= '<li>nationalite</li>';
                    }
                    if ($naissance != $this->clients->naissance) {
                        $contenu .= '<li>date naissance</li>';
                    }
                    if ($bCniPasseportUpdated) {
                        $contenu .= '<li>fichier cni passeport</li>';
                    }
                    if ($bCniPasseporVersotUpdated) {
                        $contenu .= '<li>fichier cni passeport verso</li>';
                    }
                    if ($bJustificatifDomicileUpdated) {
                        $contenu .= '<li>fichier justificatif domicile</li>';
                    }
                    if ($bAttestationHebergementTiersUpdated) {
                        $contenu .= '<li>fichier attestation hebergement tiers</li>';
                    }
                    if ($bCniPassportTiersHebergeantIUpdated) {
                        $contenu .= '<li>fichier cni passeport du tiers hebergeant</li>';
                    }
                    if ($bAutreUpdated) {
                        $contenu .= '<li>fichier autre</li>';
                    }
                    if ($bDocumentFiscalUpdated) {
                        $contenu .= '<li>fichier document fiscal</li>';
                    }
                    if ($origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds) {
                        $contenu .= '<li>Origine des fonds</li>';
                    }
                    if ($bic_old != $this->lenders_accounts->bic) {
                        $contenu .= '<li>BIC</li>';
                    }
                    if ($iban_old != $this->lenders_accounts->iban) {
                        $contenu .= '<li>IBAN</li>';
                    }
                    if ($bRibUpdated) {
                        $contenu .= '<li>Fichier RIB</li>';
                    }
                    $contenu .= '</ul>';

                    /** @var \Unilend\Service\ClientManager $oClientManager */
                    $oClientManager = $this->get('unilend.service.client_manager');
                    $oClientManager->changeClientStatusTriggeredByClientAction($this->clients->id_client, $contenu);

                    /** @var \settings $oSettings */
                    $oSettings = $this->loadData('settings');
                    $oSettings->get('Adresse notification modification preteur', 'type');
                    $destinataire = $oSettings->value;
                    $lemois = utf8_decode($this->dates->tableauMois[$this->language][date('n')]);

                    $varsMail = array(
                        '$surl'         => $this->surl,
                        '$url'          => $this->lurl,
                        '$id_preteur'   => $this->clients->id_client,
                        '$nom'          => utf8_decode($this->clients->nom),
                        '$prenom'       => utf8_decode($this->clients->prenom),
                        '$montant'      => $this->solde . ' euros',
                        '$date'         => date('d') . ' ' . $lemois . ' ' . date('Y'),
                        '$heure_minute' => date('H:i'),
                        '$email'        => $this->clients->email,
                        '$lien'         => $this->aurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account
                    );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-modification-preteurs', $varsMail, false);
                    $message->setTo($destinataire);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);

                    $this->sendAccountModificationEmail($this->clients);
                }
                $_SESSION['reponse_profile_perso'] = $this->lng['profile']['titre-1'] . ' ' . $this->lng['profile']['sauvegardees'];
                header('Location: ' . $this->lurl . '/profile/particulier/#info_perso');
                die;
            }
        } elseif (isset($_POST['send_form_mdp'])) {
            $serialize = serialize(array('id_client' => $this->clients->id_client, 'newmdp' => md5($_POST['passNew'])));
            $this->clients_history_actions->histo(7, 'change mdp', $this->clients->id_client, $serialize);

            $this->form_ok = true;

            if (! isset($_POST['passOld']) || $_POST['passOld'] == '' || $_POST['passOld'] == $this->lng['etape1']['ancien-mot-de-passe']) {
                $this->form_ok                          = false;
                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['ancien-mot-de-passe-incorrect'];
            } elseif (isset($_POST['passOld']) && md5($_POST['passOld']) != $this->clients->password) {
                $this->form_ok = false;
                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['ancien-mot-de-passe-incorrect'];
                header('Location: ' . $this->lurl . '/profile/particulier/2');
                die;
            }

            if (! isset($_POST['passNew']) || $_POST['passNew'] == '' || $_POST['passNew'] == $this->lng['etape1']['nouveau-mot-de-passe']) {
                $this->form_ok                          = false;
                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
            } elseif (isset($_POST['passNew']) && $this->ficelle->password_fo($_POST['passNew'], 6) == false) {
                $this->form_ok                          = false;
                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
            }

            if (! isset($_POST['passNew2']) || $_POST['passNew2'] == '' || $_POST['passNew2'] == $this->lng['etape1']['confirmation-nouveau-mot-de-passe']) {
                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
                $this->form_ok                          = false;
            }
            if (isset($_POST['passNew']) && isset($_POST['passNew2']) && $_POST['passNew'] != $_POST['passNew2']) {
                $this->form_ok                          = false;
                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
            }
            if ($this->form_ok == true) {
                $this->clients->password        = md5($_POST['passNew']);
                $_SESSION['client']['password'] = $this->clients->password;
                $this->clients->update();

                $this->sendPasswordModificationEmail($this->clients);

                $_SESSION['reponse_profile_secu'] = $this->lng['profile']['votre-mot-de-passe-a-bien-ete-change'];
            }

            header('Location: ' . $this->lurl . '/profile/particulier/2');
            die;
        } elseif (isset($_POST['send_form_question'])) {
            $serialize = serialize(array(
                'id_client' => $this->clients->id_client,
                'question'  => isset($_POST['secret-question']) ? $_POST['secret-question'] : '',
                'response'  => isset($_POST['secret-response']) ? md5($_POST['secret-response']) : ''
            ));
            $this->clients_history_actions->histo(20, 'change secret question', $this->clients->id_client, $serialize);

            if (
                false === empty($_POST['secret-question'])
                && false === empty($_POST['secret-response'])
                && $_POST['secret-question'] != $this->lng['etape1']['question-secrete']
                && $_POST['secret-response'] != $this->lng['etape1']['question-response']
            ) {
                $this->clients->secrete_question = $_POST['secret-question'];
                $this->clients->secrete_reponse = md5($_POST['secret-response']);
                $this->clients->update();

                $_SESSION['reponse_profile_secu_question'] = $this->lng['profile']['votre-question-secrete-a-bien-ete-changee'];
            } else {
                $_SESSION['reponse_profile_secu_question_error'] = $this->lng['profile']['question-reponse-invalide'];
            }

            header('Location: ' . $this->lurl . '/profile/particulier/2');
            die;
        }
    }

    public function _societe()
    {
        $this->lng['etape1']          = $this->ln->selectFront('inscription-preteur-etape-1', $this->language, $this->App);
        $this->lng['etape2']          = $this->ln->selectFront('inscription-preteur-etape-2', $this->language, $this->App);
        $this->lng['profile']         = $this->ln->selectFront('preteur-profile', $this->language, $this->App);
        $this->lng['gestion-alertes'] = $this->ln->selectFront('preteur-profile-gestion-alertes', $this->language, $this->App);

        $this->unLoadCss('default/custom-theme/jquery-ui-1.10.3.custom');
        $this->loadCss('default/preteurs/new-style');

        $this->unLoadJs('default/functions');
        $this->unLoadJs('default/main');
        $this->unLoadJs('default/ajax');
        $this->loadJs('default/preteurs/functions');
        $this->loadJs('default/main');
        $this->loadJs('default/ajax');

        $this->pays                          = $this->loadData('pays_v2');
        $this->nationalites                  = $this->loadData('nationalites_v2');
        $this->companies                     = $this->loadData('companies');
        $this->lenders_accounts              = $this->loadData('lenders_accounts');
        $this->clients_status                = $this->loadData('clients_status');
        $this->clients_status_history        = $this->loadData('clients_status_history');
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_type_notif    = $this->loadData('clients_gestion_type_notif');
        $this->attachment                    = $this->loadData('attachment');
        $this->attachment_type               = $this->loadData('attachment_type');

        $this->lPays = $this->pays->select('', 'ordre ASC');
        $this->settings->get("Liste deroulante conseil externe de l'entreprise", 'type');
        $this->conseil_externe = $this->ficelle->explodeStr2array($this->settings->value);

        $this->companies->get($this->clients->id_client, 'id_client_owner');
        $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');
        $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);
        $this->clients_status->getLastStatut($this->clients->id_client);

        /** @var \Unilend\Service\AutoBidSettingsManager $oAutoBidSettingsManager */
        $oAutoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');
        $this->bIsAllowedToSeeAutobid = $oAutoBidSettingsManager->isQualified($this->lenders_accounts);

        $this->settings->get("Liste deroulante origine des fonds societe", 'status = 1 AND type');
        $this->origine_fonds_E = explode(';', $this->settings->value);

        if ($this->lenders_accounts->iban != '') {
            $this->iban1 = substr($this->lenders_accounts->iban, 0, 4);
            $this->iban2 = substr($this->lenders_accounts->iban, 4, 4);
            $this->iban3 = substr($this->lenders_accounts->iban, 8, 4);
            $this->iban4 = substr($this->lenders_accounts->iban, 12, 4);
            $this->iban5 = substr($this->lenders_accounts->iban, 16, 4);
            $this->iban6 = substr($this->lenders_accounts->iban, 20, 4);
            $this->iban7 = substr($this->lenders_accounts->iban, 24, 3);
        } else {
            $this->iban1 = 'FR...';
        }

        $this->infosNotifs['vos-offres-et-vos-projets'] = array(
            \clients_gestion_type_notif::TYPE_NEW_PROJECT => array(
                'title' => $this->lng['gestion-alertes']['annonce-des-nouveaux-projets'],
                'info' => $this->lng['gestion-alertes']['annonce-des-nouveaux-projets-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_BID_PLACED => array(
                'title' => $this->lng['gestion-alertes']['offres-realisees'],
                'info' => $this->lng['gestion-alertes']['offres-realisees-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_BID_REJECTED => array(
                'title' => $this->lng['gestion-alertes']['offres-refusees'],
                'info' => $this->lng['gestion-alertes']['offres-refusees-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED => array(
                'title' => $this->lng['gestion-alertes']['offres-acceptees'],
                'info' => $this->lng['gestion-alertes']['offres-acceptees-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_AUTOBID_BALANCE_LOW => array(
                'title' => $this->lng['gestion-alertes']['autobid-balance-low'],
                'info' => $this->lng['gestion-alertes']['autobid-balance-low-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_AUTOBID_BALANCE_INSUFFICIENT => array(
                'title' => $this->lng['gestion-alertes']['autobid-balance-insufficient'],
                'info' => $this->lng['gestion-alertes']['autobid-balance-insufficient-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
        );
        $this->infosNotifs['vos-remboursements'] = array(
            \clients_gestion_type_notif::TYPE_REPAYMENT => array(
                'title' => $this->lng['gestion-alertes']['remboursements'],
                'info' => $this->lng['gestion-alertes']['remboursements-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_DAILY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_WEEKLY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_MONTHLY,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM => array(
                'title' => $this->lng['gestion-alertes']['incidents-projets-et-regularisation'],
                'info' => $this->lng['gestion-alertes']['incidents-projets-et-regularisation-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
        );
        $this->infosNotifs['mouvements-sur-votre-compte'] = array(
            \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT => array(
                'title' => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-virement'],
                'info' => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-virement-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT   => array(
                'title' => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-carte-bancaire'],
                'info' => $this->lng['gestion-alertes']['alimentation-de-votre-compte-par-carte-bancaire-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
            \clients_gestion_type_notif::TYPE_DEBIT => array(
                'title' => $this->lng['gestion-alertes']['retrait'],
                'info' => $this->lng['gestion-alertes']['retrait-info'],
                'available_types' => array(
                    \clients_gestion_notifications::TYPE_NOTIFICATION_IMMEDIATE,
                    \clients_gestion_notifications::TYPE_NOTIFICATION_NO_MAIL
                ),
            ),
        );

        $this->lTypeNotifs = $this->clients_gestion_type_notif->select();
        $this->lNotifs     = $this->clients_gestion_notifications->select('id_client = ' . $this->clients->id_client);
        $this->NotifC      = $this->clients_gestion_notifications->getNotifs($this->clients->id_client);

        $aMissingNotificationTypes = array_diff(array_column($this->lTypeNotifs, 'id_client_gestion_type_notif'), array_keys($this->NotifC));

        if (false === empty($aMissingNotificationTypes)) {
            foreach ($aMissingNotificationTypes as $iMissingNotificationType) {
                $this->clients_gestion_notifications->id_client        = $this->clients->id_client;
                $this->clients_gestion_notifications->id_notif         = $iMissingNotificationType;
                $this->clients_gestion_notifications->immediatement    = 1;
                $this->clients_gestion_notifications->quotidienne      = 0;
                $this->clients_gestion_notifications->hebdomadaire     = 0;
                $this->clients_gestion_notifications->mensuelle        = 0;
                $this->clients_gestion_notifications->uniquement_notif = 0;
                $this->clients_gestion_notifications->create(array('id_client' => $this->clients->id_client, 'id_notif' => $iMissingNotificationType));
            }

            $this->lNotifs = $this->clients_gestion_notifications->select('id_client = ' . $this->clients->id_client);
            $this->NotifC  = $this->clients_gestion_notifications->getNotifs($this->clients->id_client);
        }

        if (isset($_POST['send_gestion_alertes'])) {
            foreach ($this->lTypeNotifs as $n) {
                $id_notif = $n['id_client_gestion_type_notif'];

                if (false === empty($_POST['uniquement_notif_' . $id_notif])) {
                    $this->clients_gestion_notifications->immediatement    = 0;
                    $this->clients_gestion_notifications->quotidienne      = 0;
                    $this->clients_gestion_notifications->hebdomadaire     = 0;
                    $this->clients_gestion_notifications->mensuelle        = 0;
                    $this->clients_gestion_notifications->uniquement_notif = 1;
                    $this->clients_gestion_notifications->update(array('id_client' => $this->clients->id_client, 'id_notif' => $id_notif));
                } else {
                    $this->clients_gestion_notifications->immediatement    = empty($_POST['immediatement_' . $id_notif]) ? 0 : 1;
                    $this->clients_gestion_notifications->quotidienne      = in_array($id_notif, array(6, 7, 8)) || empty($_POST['quotidienne_' . $id_notif]) ? 0 : 1;
                    $this->clients_gestion_notifications->hebdomadaire     = in_array($id_notif, array(2, 3, 6, 7, 8)) || empty($_POST['hebdomadaire_' . $id_notif]) ? 0 : 1;
                    $this->clients_gestion_notifications->mensuelle        = in_array($id_notif, array(1, 2, 3, 6, 7, 8)) || empty($_POST['mensuelle_' . $id_notif]) ? 0 : 1;
                    $this->clients_gestion_notifications->uniquement_notif = 0;
                    $this->clients_gestion_notifications->update(array('id_client' => $this->clients->id_client, 'id_notif' => $id_notif));
                }
            }
            header('Location: ' . $this->lurl . '/profile/societe/');
            die;
        }

        if (isset($_POST['send_form_societe_perso'])) {
            $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST));
            $this->clients_history_actions->histo(4, 'info perso profile', $this->clients->id_client, $serialize);

            $this->email_temp = $this->clients->email;
            $this->form_ok = true;

            $name    = $this->companies->name;
            $forme   = $this->companies->forme;
            $capital = $this->companies->capital;
            $siret   = $this->companies->siret;
            $phone   = $this->companies->phone;

            $this->companies->name    = $_POST['raison_sociale_inscription'];
            $this->companies->forme   = $_POST['forme_juridique_inscription'];
            $this->companies->capital = str_replace(' ', '', $_POST['capital_social_inscription']);
            $this->companies->siret   = $_POST['siret_inscription'];
            $this->companies->phone = str_replace(' ', '', $_POST['phone_inscription']);

            if ($_POST['mon-addresse'] != false) {
                $this->companies->status_adresse_correspondance = '1';
            } else {
                $this->companies->status_adresse_correspondance = '0';
            }

            $adresse_fiscal            = $this->companies->adresse1;
            $ville_fiscal              = $this->companies->city;
            $cp_fiscal                 = $this->companies->zip;
            $pays_fiscal               = $this->companies->id_pays;
            $adresse1                  = $this->clients_adresses->adresse1;
            $ville                     = $this->clients_adresses->ville;
            $cp                        = $this->clients_adresses->cp;
            $id_pays                   = $this->clients_adresses->id_pays;
            $this->companies->adresse1 = $_POST['adresse_inscriptionE'];
            $this->companies->city     = $_POST['ville_inscriptionE'];
            $this->companies->zip      = $_POST['postalE'];
            $this->companies->id_pays  = $_POST['pays1E'];

            if ($this->companies->status_adresse_correspondance == 0) {
                $this->clients_adresses->adresse1 = $_POST['adress2E'];
                $this->clients_adresses->ville    = $_POST['ville2E'];
                $this->clients_adresses->cp       = $_POST['postal2E'];
                $this->clients_adresses->id_pays  = $_POST['pays2E'];
            } else {
                $this->clients_adresses->adresse1 = $_POST['adresse_inscriptionE'];
                $this->clients_adresses->ville    = $_POST['ville_inscriptionE'];
                $this->clients_adresses->cp       = $_POST['postalE'];
                $this->companies->id_pays         = $_POST['pays1E'];
            }

            $this->companies->status_client = $_POST['enterprise'];

            $civilite  = $this->clients->civilite;
            $nom       = $this->clients->nom;
            $prenom    = $this->clients->prenom;
            $fonction  = $this->clients->fonction;
            $telephone = $this->clients->telephone;

            $this->clients->civilite  = $_POST['genre1'];
            $this->clients->nom       = $this->ficelle->majNom($_POST['nom_inscription']);
            $this->clients->prenom    = $this->ficelle->majNom($_POST['prenom_inscription']);
            $this->clients->fonction  = $_POST['fonction_inscription'];
            $this->clients->telephone = str_replace(' ', '', $_POST['phone_new_inscription']);

            $civilite_dirigeant = $this->companies->civilite_dirigeant;
            $nom_dirigeant      = $this->companies->nom_dirigeant;
            $prenom_dirigeant   = $this->companies->prenom_dirigeant;
            $fonction_dirigeant = $this->companies->fonction_dirigeant;
            $email_dirigeant    = $this->companies->email_dirigeant;
            $phone_dirigeant    = $this->companies->phone_dirigeant;

            $status_conseil_externe_entreprise   = $this->companies->status_conseil_externe_entreprise;
            $preciser_conseil_externe_entreprise = $this->companies->preciser_conseil_externe_entreprise;

            if ($this->companies->status_client == \companies::CLIENT_STATUS_DELEGATION_OF_POWER || $this->companies->status_client == \companies::CLIENT_STATUS_EXTERNAL_CONSULTANT) {
                $this->companies->civilite_dirigeant = $_POST['genre2'];
                $this->companies->nom_dirigeant      = $this->ficelle->majNom($_POST['nom2_inscription']);
                $this->companies->prenom_dirigeant   = $this->ficelle->majNom($_POST['prenom2_inscription']);
                $this->companies->fonction_dirigeant = $_POST['fonction2_inscription'];
                $this->companies->email_dirigeant    = $_POST['email2_inscription'];
                $this->companies->phone_dirigeant    = str_replace(' ', '', $_POST['phone_new2_inscription']);

                if ($this->companies->status_client == \companies::CLIENT_STATUS_EXTERNAL_CONSULTANT) {
                    $this->companies->status_conseil_externe_entreprise   = $_POST['external-consultant'];
                    $this->companies->preciser_conseil_externe_entreprise = $_POST['autre_inscription'];
                }
            }

            if (! isset($_POST['raison_sociale_inscription']) || $_POST['raison_sociale_inscription'] == $this->lng['etape1']['raison-sociale']) {
                $this->form_ok = false;
            }
            if (! isset($_POST['forme_juridique_inscription']) || $_POST['forme_juridique_inscription'] == $this->lng['etape1']['forme-juridique']) {
                $this->form_ok = false;
            }
            if (! isset($_POST['capital_social_inscription']) || $_POST['capital_social_inscription'] == $this->lng['etape1']['capital-sociale']) {
                $this->form_ok = false;
            }
            if (! isset($_POST['siret_inscription']) || $_POST['siret_inscription'] == $this->lng['etape1']['siret']) {
                $this->form_ok = false;
            }
            if (! isset($_POST['phone_inscription']) || $_POST['phone_inscription'] == $this->lng['etape1']['telephone']) {
                $this->form_ok = false;
            } elseif (strlen($_POST['phone_inscription']) < 9 || strlen($_POST['phone_inscription']) > 14) {
                $this->form_ok = false;
            }
            if (! isset($_POST['adresse_inscriptionE']) || $_POST['adresse_inscriptionE'] == $this->lng['etape1']['adresse']) {
                $this->form_ok = false;
            }
            if (! isset($_POST['ville_inscriptionE']) || $_POST['ville_inscriptionE'] == $this->lng['etape1']['ville']) {
                $this->form_ok = false;
            }
            if (! isset($_POST['postalE']) || $_POST['postalE'] == $this->lng['etape1']['code-postal']) {
                $this->form_ok = false;
            } else {
                /** @var villes $oVilles */
                $oVilles = $this->loadData('villes');
                //Check cp
                if (isset($_POST['pays1E']) && \pays_v2::COUNTRY_FRANCE == $_POST['pays1E']) {
                    //for France, check post code here.
                    if (false === $oVilles->exist($_POST['postalE'], 'cp')) {
                        $this->form_ok = false;
                    }
                }
                unset($oVilles);
            }

            if ($this->companies->status_adresse_correspondance == 0) {
                if (! isset($_POST['adress2E']) || $_POST['adress2E'] == $this->lng['etape1']['adresse']) {
                    $this->form_ok = false;
                }
                if (! isset($_POST['ville2E']) || $_POST['ville2E'] == $this->lng['etape1']['ville']) {
                    $this->form_ok = false;
                }
                if (! isset($_POST['postal2E']) || $_POST['postal2E'] == $this->lng['etape1']['postal']) {
                    $this->form_ok = false;
                }
                if (! isset($_POST['pays2E']) || $_POST['pays2E'] == $this->lng['etape1']['pays']) {
                    $this->form_ok = false;
                }
            }

            if (! isset($_POST['nom_inscription']) || $_POST['nom_inscription'] == $this->lng['etape1']['nom']) {
                $this->form_ok = false;
            }
            if (! isset($_POST['prenom_inscription']) || $_POST['prenom_inscription'] == $this->lng['etape1']['prenom']) {
                $this->form_ok = false;
            }
            if (! isset($_POST['fonction_inscription']) || $_POST['fonction_inscription'] == $this->lng['etape1']['fonction']) {
                $this->form_ok = false;
            }
            if (! isset($_POST['email_inscription']) || $_POST['email_inscription'] == $this->lng['etape1']['email']) {
                $this->form_ok = false;
            } elseif (isset($_POST['email_inscription']) && $this->ficelle->isEmail($_POST['email_inscription']) == false) {
                $this->form_ok = false;
            } elseif ($_POST['email_inscription'] != $_POST['conf_email_inscription']) {
                $this->form_ok = false;
            } elseif ($this->clients->existEmail($_POST['email_inscription']) == false) {
                if ($_POST['email_inscription'] != $this->email_temp) {
                    $this->reponse_email = $this->lng['etape1']['erreur-email'];
                } else {
                    $this->clients->email = $_POST['email_inscription'];
                }
            } else {
                $this->clients->email = $_POST['email_inscription'];
            }
            if (! isset($_POST['phone_new_inscription']) || $_POST['phone_new_inscription'] == $this->lng['etape1']['telephone']) {
                $this->form_ok = false;

            } elseif (strlen($_POST['phone_new_inscription']) < 9 || strlen($_POST['phone_new_inscription']) > 14) {
                $this->form_ok = false;
            }
            if ($this->companies->status_client == \companies::CLIENT_STATUS_DELEGATION_OF_POWER || $this->companies->status_client == \companies::CLIENT_STATUS_EXTERNAL_CONSULTANT) {
                if (! isset($_POST['nom2_inscription']) || $_POST['nom2_inscription'] == $this->lng['etape1']['nom']) {
                    $this->form_ok = false;
                }
                if (! isset($_POST['prenom2_inscription']) || $_POST['prenom2_inscription'] == $this->lng['etape1']['prenom']) {
                    $this->form_ok = false;
                }
                if (! isset($_POST['fonction2_inscription']) || $_POST['fonction2_inscription'] == $this->lng['etape1']['fonction']) {
                    $this->form_ok = false;
                }
                if (! isset($_POST['email2_inscription']) || $_POST['email2_inscription'] == $this->lng['etape1']['email']) {
                    $this->form_ok = false;
                } elseif (isset($_POST['email2_inscription']) && $this->ficelle->isEmail($_POST['email2_inscription']) == false) {
                    $this->form_ok = false;
                }
                if (! isset($_POST['phone_new2_inscription']) || $_POST['phone_new2_inscription'] == $this->lng['etape1']['telephone']) {
                    $this->form_ok = false;
                } elseif (strlen($_POST['phone_new2_inscription']) < 9 || strlen($_POST['phone_new2_inscription']) > 14) {
                    $this->form_ok = false;
                }
                if ($this->companies->status_client == \companies::CLIENT_STATUS_EXTERNAL_CONSULTANT) {
                    if (! isset($_POST['external-consultant']) || $_POST['external-consultant'] == '') {
                        $this->form_ok = false;
                    }
                }
            }

            $this->error_fichier       = false;
            $bCniDirigeantUpdated      = false;
            $bKbisUpdated              = false;
            $bRibUdated                = false;
            $bCniPasseportVersoUpdated = false;
            $bDelegationPouvoirUpdated = false;

            $fichier_cni_dirigeant = isset($this->attachments[attachment_type::CNI_PASSPORTE_DIRIGEANT]['id']) ? $this->attachments[attachment_type::CNI_PASSPORTE_DIRIGEANT]['id'] : null;
            if (isset($_FILES['cni_passeport_dirigeant']) && $_FILES['cni_passeport_dirigeant']['name'] != '') {
                $fichier_cni_dirigeant = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE_DIRIGEANT);
                if (is_numeric($fichier_cni_dirigeant)) {
                    $bCniDirigeantUpdated = true;
                }
            }
            $fichier_kbis = isset($this->attachments[attachment_type::KBIS]['id']) ? $this->attachments[attachment_type::KBIS]['id'] : null;
            if (isset($_FILES['extrait_kbis']) && $_FILES['extrait_kbis']['name'] != '') {
                $fichier_kbis = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::KBIS);
                if (is_numeric($fichier_kbis)) {
                    $bKbisUpdated = true;
                }
            }
            $fichier_rib = isset($this->attachments[attachment_type::RIB]['id']) ? $this->attachments[attachment_type::RIB]['id'] : null;
            if (isset($_FILES['rib']) && $_FILES['rib']['name'] != '') {
                $fichier_rib = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::RIB);
                if (is_numeric($fichier_rib)) {
                    $bRibUdated = true;
                }
            }
            $fichier_cni_passeport_verso = isset($this->attachments[attachment_type::CNI_PASSPORTE_VERSO]['id']) ? $this->attachments[attachment_type::CNI_PASSPORTE_VERSO]['id'] : null;
            if (isset($_FILES['cni_passeport_verso']) && $_FILES['cni_passeport_verso']['name'] != '') {
                $fichier_cni_passeport_verso = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE_VERSO);
                if (is_numeric($fichier_cni_passeport_verso)) {
                    $bCniPasseportVersoUpdated = true;
                }

            }
            $fichier_delegation_pouvoir = isset($this->attachments[attachment_type::DELEGATION_POUVOIR]['id']) ? $this->attachments[attachment_type::DELEGATION_POUVOIR]['id'] : null;
            if (isset($_FILES['delegation_pouvoir']) && $_FILES['delegation_pouvoir']['name'] != '') {
                $fichier_delegation_pouvoir = $this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::DELEGATION_POUVOIR);
                if (is_numeric($fichier_delegation_pouvoir)) {
                    $bDelegationPouvoirUpdated = true;
                }
            }

            $bic_old  = $this->lenders_accounts->bic;
            $iban_old = $this->lenders_accounts->iban;

            $this->lenders_accounts->bic  = trim(strtoupper($_POST['bic']));
            $this->lenders_accounts->iban = '';
            for ($i = 1; $i <= 7; $i++) {
                $this->lenders_accounts->iban .= trim(strtoupper($_POST['iban-' . $i]));
            }

            $origine_des_fonds_old = $this->lenders_accounts->origine_des_fonds;

            $this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
            if ($_POST['preciser'] != $this->lng['etape2']['autre-preciser'] && $_POST['origine_des_fonds'] == 1000000) {
                $this->lenders_accounts->precision = $_POST['preciser'];
            } else {
                $this->lenders_accounts->precision = '';
            }

            $this->form_ok = true;

            if (! isset($_POST['bic']) || $_POST['bic'] == $this->lng['etape2']['bic'] || $_POST['bic'] == '') {
                $this->form_ok = false;
            } elseif (isset($_POST['bic']) && $this->ficelle->swift_validate(trim($_POST['bic'])) == false) {
                $this->form_ok = false;
            }
            if (strlen($this->lenders_accounts->iban) < 27) {
                $this->form_ok = false;
            } elseif ($this->lenders_accounts->iban != '' && $this->ficelle->isIBAN($this->lenders_accounts->iban) != 1) {
                $this->form_ok = false;
            }
            if (! isset($_POST['origine_des_fonds']) || $_POST['origine_des_fonds'] == 0) {
                $this->form_ok = false;
            } elseif ($_POST['origine_des_fonds'] == 1000000 && in_array($_POST['preciser'], array($this->lng['etape2']['autre-preciser'], ''))) {
                $this->form_ok = false;
            }

            if ($this->form_ok == true) {
                $this->clients->slug = $this->bdd->generateSlug($this->clients->prenom . '-' . $this->clients->nom);
                $this->clients->update();
                $this->clients_adresses->update();
                $this->companies->update();
                $this->lenders_accounts->update();
                $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);

                $dateDepartControlPays = strtotime('2014-07-31 18:00:00');
                if (
                    $adresse_fiscal != $this->companies->adresse1 ||
                    $ville_fiscal != $this->companies->city ||
                    $cp_fiscal != $this->companies->zip ||
                    $pays_fiscal != $this->companies->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays ||
                    $name != $this->companies->name ||
                    $forme != $this->companies->forme ||
                    $capital != $this->companies->capital ||
                    $siret != $this->companies->siret ||
                    $nom != $this->clients->nom ||
                    $prenom != $this->clients->prenom ||
                    $nom_dirigeant != $this->companies->nom_dirigeant ||
                    $prenom_dirigeant != $this->companies->prenom_dirigeant ||
                    $status_conseil_externe_entreprise != $this->companies->status_conseil_externe_entreprise ||
                    $preciser_conseil_externe_entreprise != $this->companies->preciser_conseil_externe_entreprise ||
                    $origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds ||
                    $bic_old != $this->lenders_accounts->bic ||
                    $iban_old != $this->lenders_accounts->iban ||
                    $bCniDirigeantUpdated == true ||
                    $bKbisUpdated == true ||
                    $bRibUdated == true ||
                    $bCniPasseportVersoUpdated == true ||
                    $bDelegationPouvoirUpdated == true
                ) {
                    $contenu = '<ul>';
                    if ($name != $this->companies->name) {
                        $contenu .= '<li>Raison sociale</li>';
                    }
                    if ($forme != $this->companies->forme) {
                        $contenu .= '<li>Forme juridique</li>';
                    }
                    if ($capital != $this->companies->capital) {
                        $contenu .= '<li>Capital social</li>';
                    }
                    if ($siret != $this->companies->siret) {
                        $contenu .= '<li>SIRET</li>';
                    }
                    if ($phone != $this->companies->phone) {
                        $contenu .= '<li>Téléphone entreprise</li>';
                    }
                    if ($adresse_fiscal != $this->companies->adresse1) {
                        $contenu .= '<li>Adresse fiscale</li>';
                    }
                    if ($ville_fiscal != $this->companies->city) {
                        $contenu .= '<li>Ville fiscale</li>';
                    }
                    if ($cp_fiscal != $this->companies->zip) {
                        $contenu .= '<li>CP fiscal</li>';
                    }
                    if ($pays_fiscal != $this->companies->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays) {
                        $contenu .= '<li>Pays fiscal</li>';
                    }
                    if ($adresse1 != $this->clients_adresses->adresse1) {
                        $contenu .= '<li>Adresse</li>';
                    }
                    if ($ville != $this->clients_adresses->ville) {
                        $contenu .= '<li>Ville</li>';
                    }
                    if ($cp != $this->clients_adresses->cp) {
                        $contenu .= '<li>CP</li>';
                    }
                    if ($id_pays != $this->clients_adresses->id_pays && strtotime($this->clients->added) >= $dateDepartControlPays) {
                        $contenu .= '<li>Pays</li>';
                    }
                    if ($civilite != $this->clients->civilite) {
                        $contenu .= '<li>Civilite</li>';
                    }
                    if ($nom != $this->clients->nom) {
                        $contenu .= '<li>Nom</li>';
                    }
                    if ($prenom != $this->clients->prenom) {
                        $contenu .= '<li>Prenom</li>';
                    }
                    if ($fonction != $this->clients->fonction) {
                        $contenu .= '<li>Fonction</li>';
                    }
                    if ($telephone != $this->clients->telephone) {
                        $contenu .= '<li>Telephone</li>';
                    }
                    if ($civilite_dirigeant != $this->companies->civilite_dirigeant) {
                        $contenu .= '<li>Civilité dirigeant</li>';
                    }
                    if ($nom_dirigeant != $this->companies->nom_dirigeant) {
                        $contenu .= '<li>Nom dirigeant</li>';
                    }
                    if ($prenom_dirigeant != $this->companies->prenom_dirigeant) {
                        $contenu .= '<li>Prenom dirigeant</li>';
                    }
                    if ($fonction_dirigeant != $this->companies->fonction_dirigeant) {
                        $contenu .= '<li>Fonction dirigeant</li>';
                    }
                    if ($email_dirigeant != $this->companies->email_dirigeant) {
                        $contenu .= '<li>Email dirigeant</li>';
                    }
                    if ($phone_dirigeant != $this->companies->phone_dirigeant) {
                        $contenu .= '<li>Telephone dirigeant</li>';
                    }
                    if ($status_conseil_externe_entreprise != $this->companies->status_conseil_externe_entreprise) {
                        $contenu .= '<li>Conseil externe</li>';
                    }
                    if ($preciser_conseil_externe_entreprise != $this->companies->preciser_conseil_externe_entreprise) {
                        $contenu .= '<li>Precision conseil externe</li>';
                    }
                    if ($origine_des_fonds_old != $this->lenders_accounts->origine_des_fonds) {
                        $contenu .= '<li>Origine des fonds</li>';
                    }
                    if ($bic_old != $this->lenders_accounts->bic) {
                        $contenu .= '<li>BIC</li>';
                    }
                    if ($iban_old != $this->lenders_accounts->iban) {
                        $contenu .= '<li>IBAN</li>';
                    }
                    if ($bCniDirigeantUpdated == true) {
                        $contenu .= '<li>Fichier cni passeport dirigent</li>';
                    }
                    if ($bKbisUpdated == true) {
                        $contenu .= '<li>Fichier extrait kbis</li>';
                    }
                    if ($bRibUdated == true) {
                        $contenu .= '<li>Fichier RIB</li>';
                    }
                    if ($bCniPasseportVersoUpdated == true) {
                        $contenu .= '<li>Fichier cni passeport verso</li>';
                    }
                    if ($bDelegationPouvoirUpdated == true) {
                        $contenu .= '<li>Fichier delegation de pouvoir</li>';
                    }
                    $contenu .= '</ul>';

                    /** @var \Unilend\Service\ClientManager $oClientManager */
                    $oClientManager = $this->get('unilend.service.client_manager');
                    $oClientManager->changeClientStatusTriggeredByClientAction($this->clients->id_client, $contenu);

                    /** @var \settings $oSettings */
                    $oSettings = $this->loadData('settings');
                    $oSettings->get('Adresse notification modification preteur', 'type');
                    $destinataire = $oSettings->value;
                    $lemois = utf8_decode($this->dates->tableauMois[$this->language][date('n')]);

                    $varsMail = array(
                        '$surl'         => $this->surl,
                        '$url'          => $this->lurl,
                        '$id_preteur'   => $this->clients->id_client,
                        '$nom'          => utf8_decode($this->clients->nom),
                        '$prenom'       => utf8_decode($this->clients->prenom),
                        '$montant'      => $this->solde . ' euros',
                        '$date'         => date('d') . ' ' . $lemois . ' ' . date('Y'),
                        '$heure_minute' => date('H:i'),
                        '$email'        => $this->clients->email,
                        '$lien'         => $this->aurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account
                    );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-modification-preteurs', $varsMail, false);
                    $message->setTo($destinataire);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);

                    $this->sendAccountModificationEmail($this->clients);
                }
                if ($this->reponse_email != '') {
                    $_SESSION['reponse_email'] = $this->reponse_email;
                }
                $_SESSION['reponse_profile_perso'] = $this->lng['profile']['sauvegardees'];
                header('Location: ' . $this->lurl . '/profile/societe/3');
                die;
            }

        } elseif (isset($_POST['send_form_mdp'])) {
            $serialize = serialize(array('id_client' => $this->clients->id_client, 'newmdp' => md5($_POST['passNew'])));
            $this->clients_history_actions->histo(7, 'change mdp', $this->clients->id_client, $serialize);

            $this->form_ok = true;
            if (! isset($_POST['passOld']) || $_POST['passOld'] == '' || $_POST['passOld'] == $this->lng['etape1']['ancien-mot-de-passe']) {
                $this->form_ok                          = false;
                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['ancien-mot-de-passe-incorrect'];
            } elseif (isset($_POST['passOld']) && md5($_POST['passOld']) != $this->clients->password) {
                $this->form_ok = false;
                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['ancien-mot-de-passe-incorrect'];
                header('Location: ' . $this->lurl . '/profile/particulier/2');
                die;
            }
            if (! isset($_POST['passNew']) || $_POST['passNew'] == '' || $_POST['passNew'] == $this->lng['etape1']['nouveau-mot-de-passe']) {
                $this->form_ok                          = false;
                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
            } elseif (isset($_POST['passNew']) && $this->ficelle->password_fo($_POST['passNew'], 6) == false) {
                $this->form_ok                          = false;
                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
            }
            if (! isset($_POST['passNew2']) || $_POST['passNew2'] == '' || $_POST['passNew2'] == $this->lng['etape1']['confirmation-nouveau-mot-de-passe']) {
                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
                $this->form_ok                          = false;
            }
            if (isset($_POST['passNew']) && isset($_POST['passNew2']) && $_POST['passNew'] != $_POST['passNew2']) {
                $this->form_ok                          = false;
                $_SESSION['reponse_profile_secu_error'] = $this->lng['profile']['nouveau-mdp-incorrect'];
            }

            if ($this->form_ok == true) {
                $this->clients->password        = md5($_POST['passNew']);
                $_SESSION['client']['password'] = $this->clients->password;
                $this->clients->update();

                $this->sendPasswordModificationEmail($this->clients);

                $_SESSION['reponse_profile_secu'] = $this->lng['profile']['votre-mot-de-passe-a-bien-ete-change'];
            }
            header('Location: ' . $this->lurl . '/profile/societe/2');
            die;
        } elseif (isset($_POST['send_form_question'])) {
            $serialize = serialize(array(
                'id_client' => $this->clients->id_client,
                'question'  => isset($_POST['secret-question']) ? $_POST['secret-question'] : '',
                'response'  => isset($_POST['secret-response']) ? md5($_POST['secret-response']) : ''
            ));
            $this->clients_history_actions->histo(20, 'change secret question', $this->clients->id_client, $serialize);

            if (
                false === empty($_POST['secret-question'])
                && false === empty($_POST['secret-response'])
                && $_POST['secret-question'] != $this->lng['etape1']['question-secrete']
                && $_POST['secret-response'] != $this->lng['etape1']['question-response']
            ) {
                $this->clients->secrete_question = $_POST['secret-question'];
                $this->clients->secrete_reponse = md5($_POST['secret-response']);
                $this->clients->update();

                $_SESSION['reponse_profile_secu_question'] = $this->lng['profile']['votre-question-secrete-a-bien-ete-changee'];
            } else {
                $_SESSION['reponse_profile_secu_question_error'] = $this->lng['profile']['question-reponse-invalide'];
            }

            header('Location: ' . $this->lurl . '/profile/societe/2');
            die;
        }
    }

    public function _gestion_alertes()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;
    }

    public function _particulier_doc()
    {
        if (in_array($this->clients->type, array(\clients::TYPE_LEGAL_ENTITY, clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
            header('Location: ' . $this->lurl . '/profile/societe_doc');
            die;
        }

        $this->lng['etape1']  = $this->ln->selectFront('inscription-preteur-etape-1', $this->language, $this->App);
        $this->lng['etape2']  = $this->ln->selectFront('inscription-preteur-etape-2', $this->language, $this->App);
        $this->lng['profile'] = $this->ln->selectFront('preteur-profile', $this->language, $this->App);

        $this->unLoadCss('default/custom-theme/jquery-ui-1.10.3.custom');
        $this->loadCss('default/preteurs/new-style');

        $this->unLoadJs('default/functions');
        $this->unLoadJs('default/main');
        $this->unLoadJs('default/ajax');
        $this->loadJs('default/preteurs/functions');
        $this->loadJs('default/main');
        $this->loadJs('default/ajax');

        /** @var \clients_status $oClientStatus */
        $oClientStatus          = $this->loadData('clients_status');
        /** @var \clients_status_history $oClientStatusHistory */
        $oClientStatusHistory   = $this->loadData('clients_status_history');
        /** @var \attachment_type $oAttachementType */
        $oAttachementType       = $this->loadData('attachment_type');

        $oClientStatus->getLastStatut($this->clients->id_client);

        $sCompletenessRequestContent = $oClientStatusHistory->getCompletnessRequestContent($this->clients);
        $this->aAttachmentTypes      = $oAttachementType->getAllTypesForLender($this->language);
        $this->sAttachmentList       = '';

        if (false === empty($sCompletenessRequestContent)) {
            $oDOMElement = new DOMDocument();
            $oDOMElement->loadHTML($sCompletenessRequestContent);
            $oList = $oDOMElement->getElementsByTagName('ul');
            if ($oList->length > 0 && $oList->item(0)->childNodes->length > 0) {
                $this->sAttachmentList = $oList->item(0)->C14N();
            }
        }

        if (isset($_POST['send_form_upload_doc'])) {
            $this->validateCompletenessForm();
        }
    }

    public function _societe_doc()
    {
        if (in_array($this->clients->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER))) {
            header('Location: ' . $this->lurl . '/profile/particulier_doc');
            die;
        }

        $this->lng['etape1']  = $this->ln->selectFront('inscription-preteur-etape-1', $this->language, $this->App);
        $this->lng['etape2']  = $this->ln->selectFront('inscription-preteur-etape-2', $this->language, $this->App);
        $this->lng['profile'] = $this->ln->selectFront('preteur-profile', $this->language, $this->App);

        $this->unLoadCss('default/custom-theme/jquery-ui-1.10.3.custom');
        $this->loadCss('default/preteurs/new-style');

        $this->unLoadJs('default/functions');
        $this->unLoadJs('default/main');
        $this->unLoadJs('default/ajax');
        $this->loadJs('default/preteurs/functions');
        $this->loadJs('default/main');
        $this->loadJs('default/ajax');

        /** @var \clients_status $oClientStatus */
        $oClientStatus          = $this->loadData('clients_status');
        /** @var \clients_status_history $oClientStatusHistory */
        $oClientStatusHistory   = $this->loadData('clients_status_history');
        /** @var \attachment_type $oAttachementType */
        $oAttachementType       = $this->loadData('attachment_type');

        $oClientStatus->getLastStatut($this->clients->id_client);

        $sCompletenessRequestContent = $oClientStatusHistory->getCompletnessRequestContent($this->clients);
        $this->aAttachmentTypes      = $oAttachementType->getAllTypesForLender($this->language);
        $this->sAttachmentList       = '';

        if (false === empty($sCompletenessRequestContent)) {
            $oDOMElement = new DOMDocument();
            $oDOMElement->loadHTML($sCompletenessRequestContent);
            $oList = $oDOMElement->getElementsByTagName('ul');
            if ($oList->length > 0 && $oList->item(0)->childNodes->length > 0) {
                $this->sAttachmentList = $oList->item(0)->C14N();
            }
        }

        if (isset($_POST['send_form_upload_doc'])) {
            $this->validateCompletenessForm();
        }
    }

    /**
     * @param integer $lenderAccountId
     * @param integer $attachmentType
     * @return bool
     */
    private function uploadAttachment($lenderAccountId, $attachmentType, $sFieldName = null)
    {
        if (false === isset($this->upload) || false === $this->upload instanceof upload) {
            $this->upload = $this->loadLib('upload');
        }

        if (false === isset($this->attachment) || false === $this->attachment instanceof attachment) {
            $this->attachment = $this->loadData('attachment');
        }

        if (false === isset($this->attachment_type) || false === $this->attachment_type instanceof attachment_type) {
            $this->attachment_type = $this->loadData('attachment_type');
        }

        if (false === isset($this->attachmentHelper) || false === $this->attachmentHelper instanceof attachment_helper) {
            $this->attachmentHelper = $this->loadLib('attachment_helper', array($this->attachment, $this->attachment_type, $this->path));;
        }

        if (null === $sFieldName) {
            switch($attachmentType) {
                case attachment_type::CNI_PASSPORTE :
                    $sFieldName = 'cni_passeport';
                    break;
                case attachment_type::CNI_PASSPORTE_VERSO :
                    $sFieldName = 'cni_passeport_verso';
                    break;
                case attachment_type::JUSTIFICATIF_DOMICILE :
                    $sFieldName = 'justificatif_domicile';
                    break;
                case attachment_type::RIB :
                    $sFieldName = 'rib';
                    break;
                case attachment_type::ATTESTATION_HEBERGEMENT_TIERS :
                    $sFieldName = 'attestation_hebergement_tiers';
                    break;
                case attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT :
                    $sFieldName = 'cni_passport_tiers_hebergeant';
                    break;
                case attachment_type::CNI_PASSPORTE_DIRIGEANT :
                    $sFieldName = 'cni_passeport_dirigeant';
                    break;
                case attachment_type::DELEGATION_POUVOIR :
                    $sFieldName = 'delegation_pouvoir';
                    break;
                case attachment_type::KBIS :
                    $sFieldName = 'extrait_kbis';
                    break;
                case attachment_type::JUSTIFICATIF_FISCAL :
                    $sFieldName = 'document_fiscal';
                    break;
                case attachment_type::AUTRE1 :
                    $sFieldName = 'autre1';
                    break;
                case attachment_type::AUTRE2 :
                    $sFieldName = 'autre2';
                    break;
                case attachment_type::AUTRE3:
                    $sFieldName = 'autre3';
                    break;
                case attachment_type::AUTRE4:
                    $sFieldName = 'autre4';
                    break;
                default :
                    return false;
            }
        }

        if (false === isset($this->oGreenPointAttachment) || false === $this->oGreenPointAttachment instanceof greenpoint_attachment) {
            /** @var greenpoint_attachment oGreenPointAttachment */
            $this->oGreenPointAttachment = $this->loadData('greenpoint_attachment');
        }
        $mResult = $this->attachmentHelper->attachmentExists($this->attachment, $lenderAccountId, attachment::LENDER, $iAttachmentType);
        if (is_numeric($mResult)) {
            $this->oGreenPointAttachment->get($mResult, 'id_attachment');
            $this->oGreenPointAttachment->revalidate   = 1;
            $this->oGreenPointAttachment->final_status = 0;
            $this->oGreenPointAttachment->update();
        }
        $resultUpload = $this->attachmentHelper->upload($lenderAccountId, attachment::LENDER, $attachmentType, $sFieldName, $this->upload);

        if (false === $resultUpload || is_null($resultUpload)) {
            $this->form_ok       = false;
            $this->error_fichier = true;
        }

        return $resultUpload;
    }

    private function sendAccountModificationEmail(\clients $oClient)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->loadData('settings');
        $oSettings->get('Facebook', 'type');
        $lien_fb = $oSettings->value;
        $oSettings->get('Twitter', 'type');
        $lien_tw = $oSettings->value;

        $varMail = array(
            'surl'    => $this->surl,
            'url'     => $this->lurl,
            'prenom'  => $oClient->prenom,
            'lien_fb' => $lien_fb,
            'lien_tw' => $lien_tw
        );

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-modification-compte', $varMail);
        $message->setTo($oClient->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    private function validateCompletenessForm()
    {
        /** @var \clients_history_actions $oClientHistoryActions */
        $oClientHistoryActions  = $this->loadData('clients_history_actions');
        /** @var \clients_status $oClientStatus */
        $oClientStatus          = $this->loadData('clients_status');
        /** @var \textes $oTextes */
        $oTextes                = new \textes($this->bdd);
        $aTranslations          = $oTextes->selectFront('projet', $this->language);

        $oLenderAccount         = $this->loadData('lenders_accounts');
        $oLenderAccount->get($this->clients->id_client, 'id_client_owner');
        $oClientStatus->getLastStatut($this->clients->id_client);

        $sSerialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST));
        $oClientHistoryActions->histo(12, 'upload doc profile', $this->clients->id_client, $sSerialize);
        $sContentForHistory = '';

        if (false === empty($_POST) || false === empty($_FILES)) {
            $sContentForHistory = '<ul>';
            foreach (array_keys($_FILES) as $iAttachmentType) {
                if (is_numeric($this->uploadAttachment($this->lenders_accounts->id_lender_account, $iAttachmentType, $iAttachmentType))) {
                    $sContentForHistory .= '<li>' .$aTranslations['document-type-' . $iAttachmentType] . '</li>';
                }
            }
            $sContentForHistory .= '</ul>';
        }

        if (false !== strpos($sContentForHistory, '<li>')) {
            /** @var \Unilend\Service\ClientManager $oClientManager */
            $oClientManager = $this->get('unilend.service.client_manager');
            $oClientManager->changeClientStatusTriggeredByClientAction($this->clients->id_client, $sContentForHistory);
            $this->sendAccountModificationEmail($this->clients);
            $_SESSION['form_profile_doc']['answer_upload'] = $this->lng['profile']['message-completness-document-upload'];
        }

        if (in_array($this->clients->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER))) {
            header('Location: ' . $this->lurl . '/profile/particulier_doc');
            die;
        } else {
            header('Location: ' . $this->lurl . '/profile/societe_doc');
            die;
        }
    }

    public function _autolend()
    {
        /** @var \Unilend\Service\AutoBidSettingsManager $oAutoBidSettingsManager */
        $oAutoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');
        $this->oLendersAccounts  = $this->loadData('lenders_accounts');

        $this->oLendersAccounts->get($this->clients->id_client, 'id_client_owner');

        if (false === $oAutoBidSettingsManager->isQualified($this->oLendersAccounts)) {
            header('Location: ' . $this->lurl . '/profile');
            die;
        }

        $this->loadCss('default/autobid');
        $this->loadJs('default/main');

        $oClientStatus  = $this->loadData('clients_status');
        $oSettings      = $this->loadData('settings');
        $oAutoBidPeriod = $this->loadData('autobid_periods');
        $oBid           = $this->loadData('bids');
        $oProject       = $this->loadData('projects');

        $this->lng['autobid'] = $this->ln->selectFront('autobid', $this->language, $this->App);

        $oClientStatus->getLastStatut($this->clients->id_client);

        $oSettings->get('Auto-bid step', 'type');
        $this->fAutoBidStep = $oSettings->value;
        $oSettings->get('pret min', 'type');
        $this->iMinimumBidAmount = (int)$oSettings->value;

        $this->fAverageRateUnilend = round($oProject->getAvgRate(), 1);
        $this->sAcceptationRate    = json_encode($oBid->getAcceptationPossibilityRounded());

        $this->aAutoBidSettings = array();
        $aAutoBidSettings       = $oAutoBidSettingsManager->getSettings($this->oLendersAccounts->id_lender_account, null, null, array(\autobid::STATUS_ACTIVE, \autobid::STATUS_INACTIVE), 'ap.min ASC, evaluation DESC');
        foreach ($aAutoBidSettings as $aSetting) {
            $aPeriod = $oAutoBidPeriod->getDurations($aSetting['id_autobid_period']);
            if ($aPeriod) {
                $aSetting['AverageRateUnilend']                           = $this->projects->getAvgRate($aSetting['evaluation'], $aPeriod['min'], $aPeriod['max']);
                $aSetting['period_min']                                   = $aPeriod['min'];
                $aSetting['period_max']                                   = $aPeriod['max'];
                $aSetting['note']                                         = constant('\projects::RISK_' . $aSetting['evaluation']);
                $this->aAutoBidSettings[$aSetting['id_autobid_period']][] = $aSetting;
            }
        }

        $aSettingsSubmitted       = isset($_SESSION['forms']['autobid-param-submit']['values']) ? $_SESSION['forms']['autobid-param-submit']['values'] : array();
        $this->aErrors            = isset($_SESSION['forms']['autobid-param-submit']['errors']) ? $_SESSION['forms']['autobid-param-submit']['errors'] : array();
        $this->aSettingsSubmitted = array(
            'amount' => isset($aSettingsSubmitted['amount']) ? $aSettingsSubmitted['amount'] : isset($this->aAutoBidSettings[1][0]['amount']) ? $this->aAutoBidSettings[1][0]['amount'] : '',
            'simple-taux-min' => isset($aSettingsSubmitted['simple']['autobid-param-simple-taux-min']) ? $aSettingsSubmitted['simple']['autobid-param-simple-taux-min'] : isset($this->aAutoBidSettings[1][0]['rate_min']) ? $this->aAutoBidSettings[1][0]['rate_min'] : '',
            'aAutobidSettings' => isset($aSettingsSubmitted['expert']) ? $aSettingsSubmitted['expert'] : (false === empty($this->aAutoBidSettings)) ? $this->aAutoBidSettings : ''
        );

        unset($_SESSION['forms']['autobid-param-submit']);

        if (isset($_POST['send-form-autobid-param-simple'])) {
            if (empty($_POST['autobid-amount']) ||
                false === is_numeric($_POST['autobid-amount']) ||
                $_POST['autobid-amount'] < $this->iMinimumBidAmount
            ) {
                $_SESSION['forms']['autobid-param-submit']['errors']['amount'] = true;
                $_SESSION['forms']['autobid-param-submit']['values']['amount'] = $_POST['autobid-amount'];
            }
            if (empty($_POST['autobid-param-simple-taux-min']) ||
                false === is_numeric($_POST['autobid-param-simple-taux-min']) ||
                $_POST['autobid-param-simple-taux-min'] < \bids::BID_RATE_MIN ||
                $_POST['autobid-param-simple-taux-min'] > \bids::BID_RATE_MAX
            ) {
                $_SESSION['forms']['autobid-param-submit']['errors']['taux-min'] = true;
            }

            if (empty($_SESSION['forms']['autobid-param-submit']['errors'])) {
                if (false === $oAutoBidSettingsManager->isOn($this->oLendersAccounts)) {
                    $oAutoBidSettingsManager->on($this->oLendersAccounts);
                }
                $oAutoBidSettingsManager->saveNoviceSetting($this->oLendersAccounts->id_lender_account, $_POST['autobid-param-simple-taux-min'], $_POST['autobid-amount']);
                header('Location: ' . $this->lurl . '/profile/autolend#parametrage');
                die;
            } else {
                $_SESSION['forms']['autobid-param-submit']['values']['simple'] = $_POST;
            }
        }
    }

    public function _autoBidExpertForm()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var $oAutoBidSettingsManager */
        $oAutoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');

        $oLendersAccounts = $this->loadData('lenders_accounts');
        $oSettings        = $this->loadData('settings');
        $oAutoBidPeriod   = $this->loadData('autobid_periods');
        $oProject         = $this->loadData('projects');

        $oSettings->get('pret min', 'type');
        $this->iMinimumBidAmount = (int)$oSettings->value;

        foreach ($oAutoBidPeriod->select('status = ' . \autobid_periods::STATUS_ACTIVE) as $aPeriod) {
            $aAutoBidPeriods[] = $aPeriod['id_period'];
        }
        $aRiskValues           = $oProject->getAvailableRisks();
        $iNumberOfSettingLines = count($aRiskValues) * count($aAutoBidPeriods);

        if (isset($_POST['validate_settings_expert'])) {
            $oLendersAccounts->get($_POST['id_client'], 'id_client_owner');
            $aSettingsFromPOST = array();

            foreach ($_POST as $sSettingType => $sValue) {
                $aSettingTypeExploded = explode('-', $sSettingType);
                if (count($aSettingTypeExploded) >= 4 && is_numeric($aSettingTypeExploded[0])) {
                    $aSettingsFromPOST[$aSettingTypeExploded[0]][$aSettingTypeExploded[3]] = $sValue;
                }
            }

            if ($iNumberOfSettingLines != count($aSettingsFromPOST)) {
                $_SESSION['forms']['autobid-param-submit']['errors']['general-error'] = true;
            }

            if (empty($_POST['autobid-amount']) || false === is_numeric($_POST['autobid-amount']) || $_POST['autobid-amount'] < $this->iMinimumBidAmount) {
                $_SESSION['forms']['autobid-param-submit']['errors']['amount'] = true;
                $_SESSION['forms']['autobid-param-submit']['values']['amount'] = $_POST['autobid-amount'];
            }

            foreach ($aSettingsFromPOST as $aSetting) {
                if (false === in_array($aSetting['evaluation'], $aRiskValues) || false === in_array($aSetting['period'], $aAutoBidPeriods)) {
                    $_SESSION['forms']['autobid-param-submit']['errors']['general-error'] = true;
                }

                if (false === is_numeric($aSetting['value']) || $aSetting['value'] < \bids::BID_RATE_MIN || $aSetting['value'] > \bids::BID_RATE_MAX) {
                    $_SESSION['forms']['autobid-param-submit']['errors']['rate'] = true;
                }
            }

            if (empty($_SESSION['forms']['autobid-param-submit']['errors'])) {
                if (false === $oAutoBidSettingsManager->isOn($oLendersAccounts)) {
                    $oAutoBidSettingsManager->on($oLendersAccounts);
                }
                $iAmount = $_POST['autobid-amount'];
                foreach ($aSettingsFromPOST as $sIndex => $aSetting) {
                    $oAutoBidSettingsManager->saveSetting($oLendersAccounts->id_lender_account, $aSetting['evaluation'], $aSetting['period'], $aSetting['value'], $iAmount);
                    $oAutoBidSettingsManager->activateDeactivateSetting($oLendersAccounts->id_lender_account, $aSetting['evaluation'], $aSetting['period'], $aSetting['switch']);
                }
                echo 'settings_saved';
            } else {
                $_SESSION['forms']['autobid-param-submit']['values']['expert'] = $aSettingsFromPOST;
                echo 'error';
            }
        }
    }

    public function _AutoBidSettingOff()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount          = $this->loadData('lenders_accounts');
        $oClientSettings         = $this->loadData('client_settings');
        $oAutoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');
        $sInstruction            = '';

        if (false === empty($_POST['setting']) && $oLenderAccount->get($_POST['id_lender'])) {
            if (\client_settings::AUTO_BID_ON == $oClientSettings->getSetting($oLenderAccount->id_client_owner, \client_setting_type::TYPE_AUTO_BID_SWITCH)) {
                $oAutoBidSettingsManager->off($oLenderAccount);
                $sInstruction = 'update_off_success';
            }
        }
        echo $sInstruction;
    }

    public function _autobidDetails()
    {
        $this->hideDecoration();
        $this->autoFireView = true;
        /** @var \Unilend\Service\AutoBidSettingsManager $oAutoBidSettingsManager */
        $oAutoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');
        $oLendersAccounts        = $this->loadData('lenders_accounts');
        $oClientStatus           = $this->loadData('clients_status');
        $oClientStatus->getLastStatut($this->clients->id_client);

        $aResponse = array('success' => false, 'info' => array());

        if (isset($this->params[0]) && $oLendersAccounts->get($this->params[0]) && $this->clients->id_client == $oLendersAccounts->id_client_owner) {
            $oValidateTime = $oAutoBidSettingsManager->getValidationDate($oLendersAccounts);

            $aResponse['success']                 = true;
            $aResponse['info']['autobid_on']      = $oAutoBidSettingsManager->isOn($oLendersAccounts);
            $aResponse['info']['lender_active']   = in_array($oClientStatus->status, array(\clients_status::VALIDATED));
            $aResponse['info']['is_qualified']    = $oAutoBidSettingsManager->isQualified($oLendersAccounts);
            $aResponse['info']['never_activated'] = false === $oAutoBidSettingsManager->hasAutoBidActivationHistory($oLendersAccounts);
            $aResponse['info']['is_novice']       = $oAutoBidSettingsManager->isNovice($oLendersAccounts);
            $aResponse['info']['validation_date'] = strftime('%d %B %G', $oValidateTime->format('U'));
        }

        echo json_encode($aResponse);
    }

    private function sendPasswordModificationEmail(\clients $oClient)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->loadData('settings');
        $oSettings->get('Facebook', 'type');
        $lien_fb = $oSettings->value;
        $oSettings->get('Twitter', 'type');
        $lien_tw = $oSettings->value;

        $varMail = array(
            'surl'     => $this->surl,
            'url'      => $this->lurl,
            'login'    => $oClient->email,
            'prenom_p' => $oClient->prenom,
            'mdp'      => '',
            'lien_fb'  => $lien_fb,
            'lien_tw'  => $lien_tw
        );

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('generation-mot-de-passe', $varMail);
        $message->setTo($oClient->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }
}
