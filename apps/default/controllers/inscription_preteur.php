<?php

class inscription_preteurController extends bootstrap
{
    /**
     * @var attachment_helper
     */
    private $attachmentHelper;

    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
        $this->lng['inscription-preteur-etape-header'] = $this->ln->selectFront('inscription-preteur-etape-header', $this->language, $this->App);
        $this->navigateurActive = 2;
    }

    public function _default()
    {
        header('Location: ' . $this->lurl . '/inscription_preteur/etape1');
        die;
    }

    public function _etape1()
    {
        $this->unLoadCss('default/custom-theme/jquery-ui-1.10.3.custom');
        $this->loadCss('default/preteurs/new-style');

        $this->unLoadJs('default/functions');
        $this->unLoadJs('default/main');
        $this->unLoadJs('default/ajax');

        $this->loadJs('default/preteurs/functions');
        $this->loadJs('default/main', 0, date('Ymd'));
        $this->loadJs('default/ajax', 0, date('Ymd'));

        $this->pays                    = $this->loadData('pays_v2');
        $this->nationalites            = $this->loadData('nationalites_v2');
        $this->companies               = $this->loadData('companies');
        $this->lenders_accounts        = $this->loadData('lenders_accounts');
        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');

        $this->page_preteur = 1;

        $this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1', $this->language, $this->App);
        $this->lng['etape3'] = $this->ln->selectFront('inscription-preteur-etape-3', $this->language, $this->App);
        $this->lPays         = $this->pays->select('', 'ordre ASC');
        $this->lNatio        = $this->nationalites->select('', 'ordre ASC');

        $this->settings->get('Lien conditions generales inscription preteur societe', 'type');
        $this->lienConditionsGeneralesSociete = $this->settings->value;
        $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
        $this->lienConditionsGeneralesParticulier = $this->settings->value;
        $this->settings->get("Liste deroulante conseil externe de l'entreprise", 'type');
        $this->conseil_externe = $this->ficelle->explodeStr2array($this->settings->value);

        $this->checkSession();
        //variables used in the views only
        $this->modif = false;
        $this->emprunteurCreatePreteur = false;

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash') || false === empty($this->clients->id_client)) {
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            if (in_array($this->clients->type, array(\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
                $this->companies->get($this->clients->id_client, 'id_client_owner');
            }
        }

        $aFormPhysicalPerson        = isset($_SESSION['forms']['lender_subscription_step_1']['particulier']['values']) ? $_SESSION['forms']['lender_subscription_step_1']['particulier']['values'] : array();
        $aFormLegalEntity           = isset($_SESSION['forms']['lender_subscription_step_1']['societe']['values']) ? $_SESSION['forms']['lender_subscription_step_1']['societe']['values'] : array();
        $this->aLanding             = array(
            'email'  => isset($_SESSION['landing_client']['email']) ? $_SESSION['landing_client']['email'] : null,
            'prenom' => isset($_SESSION['landing_client']['prenom']) ? $_SESSION['landing_client']['prenom'] : null,
            'nom'    => isset($_SESSION['landing_client']['nom']) ? $_SESSION['landing_client']['nom'] : null
        );
        $this->aForm['particulier'] = array(
            'sex'                 => isset($aFormPhysicalPerson['sex']) ? $aFormPhysicalPerson['sex'] : $this->clients->civilite,
            'nom-famille'         => isset($aFormPhysicalPerson['nom-famille']) ? $aFormPhysicalPerson['nom-famille'] : $this->clients->nom,
            'nom-dusage'          => isset($aFormPhysicalPerson['nom-dusage']) ? $aFormPhysicalPerson['nom-dusage'] : $this->clients->nom_usage,
            'prenom'              => isset($aFormPhysicalPerson['prenom']) ? $aFormPhysicalPerson['prenom'] : $this->clients->prenom,
            'jour_naissance'      => isset($aFormPhysicalPerson['jour_naissance']) ? $aFormPhysicalPerson['jour_naissance'] : substr($this->clients->naissance, -2, 2),
            'mois_naissance'      => isset($aFormPhysicalPerson['mois_naissance']) ? $aFormPhysicalPerson['mois_naissance'] : substr($this->clients->naissance, 5, 2),
            'annee_naissance'     => isset($aFormPhysicalPerson['annee_naissance']) ? $aFormPhysicalPerson['annee_naissance'] : substr($this->clients->naissance, 0, 4),
            'naissance'           => isset($aFormPhysicalPerson['naissance']) ? $aFormPhysicalPerson['naissance'] : $this->clients->ville_naissance,
            'insee_birth'         => isset($aFormPhysicalPerson['insee_birth']) ? $aFormPhysicalPerson['insee_birth'] : $this->clients->insee_birth,
            'pays3'               => isset($aFormPhysicalPerson['pays3']) ? $aFormPhysicalPerson['pays3'] : $this->clients->id_pays_naissance,
            'nationalite'         => isset($aFormPhysicalPerson['nationalite']) ? $aFormPhysicalPerson['nationalite'] : $this->clients->id_nationalite,
            'email'               => isset($aFormPhysicalPerson['email']) ? $aFormPhysicalPerson['email'] : $this->clients->email,
            'conf_email'          => isset($aFormPhysicalPerson['conf_email']) ? $aFormPhysicalPerson['conf_email'] : $this->clients->email,
            'phone'               => isset($aFormPhysicalPerson['phone']) ? $aFormPhysicalPerson['phone'] : $this->clients->telephone,
            'mon-addresse'        => isset($aFormPhysicalPerson['mon-addresse']) ? (false === empty($aFormPhysicalPerson['mon-addresse']) ? 1 : 0) : (empty($this->clients_adresses->meme_adresse_fiscal) ? 1 : 0),
            'adresse_inscription' => isset($aFormPhysicalPerson['adresse_inscription']) ? $aFormPhysicalPerson['adresse_inscription'] : $this->clients_adresses->adresse_fiscal,
            'postal'              => isset($aFormPhysicalPerson['postal']) ? $aFormPhysicalPerson['postal'] : $this->clients_adresses->cp_fiscal,
            'ville_inscription'   => isset($aFormPhysicalPerson['ville_inscription']) ? $aFormPhysicalPerson['ville_inscription'] : $this->clients_adresses->ville_fiscal,
            'pays1'               => isset($aFormPhysicalPerson['pays1']) ? $aFormPhysicalPerson['pays1'] : $this->clients_adresses->id_pays_fiscal,
            'adress2'             => isset($aFormPhysicalPerson['adress2']) ? $aFormPhysicalPerson['adress2'] : $this->clients_adresses->adresse1,
            'postal2'             => isset($aFormPhysicalPerson['postal2']) ? $aFormPhysicalPerson['postal2'] : $this->clients_adresses->cp,
            'ville2'              => isset($aFormPhysicalPerson['ville2']) ? $aFormPhysicalPerson['ville2'] : $this->clients_adresses->ville,
            'pays2'               => isset($aFormPhysicalPerson['pays2']) ? $aFormPhysicalPerson['pays2'] : $this->clients_adresses->id_pays,
            'secret-question'     => isset($aFormPhysicalPerson['secret-question']) ? $aFormPhysicalPerson['secret-question'] : $this->clients->secrete_question,
            'bIsPhysicalPerson'   => isset($aFormPhysicalPerson['form_inscription_preteur_particulier_etape_1']) || in_array($this->clients->type, array(
                    clients::TYPE_PERSON,
                    clients::TYPE_PERSON_FOREIGNER
                ))
        );
        $this->aForm['societe'] = array(
            'raison_sociale_inscription'  => isset($aFormLegalEntity['raison_sociale_inscription']) ? $aFormLegalEntity['raison_sociale_inscription'] : $this->companies->name,
            'forme_juridique_inscription' => isset($aFormLegalEntity['forme_juridique_inscription']) ? $aFormLegalEntity['forme_juridique_inscription'] : $this->companies->forme,
            'siren_inscription'           => isset($aFormLegalEntity['siren_inscription']) ? $aFormLegalEntity['siren_inscription'] : $this->companies->siren,
            'capital_social_inscription'  => isset($aFormLegalEntity['capital_social_inscription']) ? $aFormLegalEntity['capital_social_inscription'] : $this->companies->capital,
            'phone_inscription'           => isset($aFormLegalEntity['phone_inscription']) ? $aFormLegalEntity['phone_inscription'] : $this->companies->phone,
            'enterprise'                  => isset($aFormLegalEntity['enterprise']) ? $aFormLegalEntity['enterprise'] : $this->companies->status_client,
            'external-consultant'         => isset($aFormLegalEntity['external-consultant']) ? $aFormLegalEntity['external-consultant'] : $this->companies->status_conseil_externe_entreprise,
            'autre_inscription'           => isset($aFormLegalEntity['autre_inscription']) ? $aFormLegalEntity['autre_inscription'] : $this->companies->preciser_conseil_externe_entreprise,
            'genre1'                      => isset($aFormLegalEntity['genre1']) ? $aFormLegalEntity['genre1'] : $this->clients->civilite,
            'nom_inscription'             => isset($aFormLegalEntity['nom_inscription']) ? $aFormLegalEntity['nom_inscription'] : $this->clients->nom,
            'prenom_inscription'          => isset($aFormLegalEntity['prenom_inscription']) ? $aFormLegalEntity['prenom_inscription'] : $this->clients->prenom,
            'fonction_inscription'        => isset($aFormLegalEntity['fonction_inscription']) ? $aFormLegalEntity['fonction_inscription'] : $this->clients->fonction,
            'genre2'                      => isset($aFormLegalEntity['genre2']) ? $aFormLegalEntity['genre2'] : $this->companies->civilite_dirigeant,
            'nom2_inscription'            => isset($aFormLegalEntity['nom2_inscription']) ? $aFormLegalEntity['nom2_inscription'] : $this->companies->nom_dirigeant,
            'prenom2_inscription'         => isset($aFormLegalEntity['prenom2_inscription']) ? $aFormLegalEntity['prenom2_inscription'] : $this->companies->prenom_dirigeant,
            'fonction2_inscription'       => isset($aFormLegalEntity['fonction2_inscription']) ? $aFormLegalEntity['fonction2_inscription'] : $this->companies->fonction_dirigeant,
            'email2_inscription'          => isset($aFormLegalEntity['email2_inscription']) ? $aFormLegalEntity['email2_inscription'] : $this->companies->email_dirigeant,
            'phone_new2_inscription'      => isset($aFormLegalEntity['phone_new2_inscription']) ? $aFormLegalEntity['phone_new2_inscription'] : $this->companies->phone_dirigeant,
            'mon-addresse'                => isset($aFormLegalEntity['mon-addresse']) ? (false === empty($aFormLegalEntity['mon-addresse']) ? 1 : 0) : (empty($this->clients_adresses->meme_adresse_fiscal) ? 1 : 0),
            'adresse_inscriptionE'        => isset($aFormLegalEntity['adresse_inscriptionE']) ? $aFormLegalEntity['adresse_inscriptionE'] : $this->companies->adresse1,
            'postalE'                     => isset($aFormLegalEntity['postalE']) ? $aFormLegalEntity['postalE'] : $this->companies->zip,
            'ville_inscriptionE'          => isset($aFormLegalEntity['ville_inscriptionE']) ? $aFormLegalEntity['ville_inscriptionE'] : $this->companies->city,
            'pays1E'                      => isset($aFormLegalEntity['pays1E']) ? $aFormLegalEntity['pays1E'] : $this->companies->id_pays,
            'address2E'                   => isset($aFormLegalEntity['address2E']) ? $aFormLegalEntity['address2E'] : $this->clients_adresses->adresse1,
            'postal2E'                    => isset($aFormLegalEntity['postal2E']) ? $aFormLegalEntity['postal2E'] : $this->clients_adresses->cp,
            'ville2E'                     => isset($aFormLegalEntity['ville2E']) ? $aFormLegalEntity['ville2E'] : $this->clients_adresses->ville,
            'pays2E'                      => isset($aFormLegalEntity['pays2E']) ? $aFormLegalEntity['pays2E'] : $this->clients_adresses->id_pays,
            'email_inscription'           => isset($aFormLegalEntity['email_inscription']) ? $aFormLegalEntity['email_inscription'] : $this->clients->email,
            'conf_email_inscription'      => isset($aFormLegalEntity['conf_email_inscription']) ? $aFormLegalEntity['conf_email_inscription'] : $this->clients->email,
            'phone_new_inscription'       => isset($aFormLegalEntity['phone_new_inscription']) ? $aFormLegalEntity['phone_new_inscription'] : $this->clients->telephone,
            'secret-questionE'            => isset($aFormLegalEntity['secret-questionE']) ? $aFormLegalEntity['secret-questionE'] : $this->clients->secrete_question,
            'bIsLegalEntity'              => isset($aFormLegalEntity['send_form_inscription_preteur_societe_etape_1']) || in_array($this->clients->type, array(
                    clients::TYPE_LEGAL_ENTITY,
                    clients::TYPE_LEGAL_ENTITY_FOREIGNER
                ))
        );
        $this->aErrors              = isset($_SESSION['forms']['lender_subscription_step_1']['errors']) ? $_SESSION['forms']['lender_subscription_step_1']['errors'] : array();

        unset($_SESSION['forms']['lender_subscription_step_1']);

        if (isset($_POST['form_inscription_preteur_particulier_etape_1'])) {
            $this->validStep1PhysicalPerson();
        } elseif (isset($_POST['send_form_inscription_preteur_societe_etape_1'])) {
            $this->validStep1LegalEntity();
        }
    }

    public function _etape2()
    {
        $this->unLoadCss('default/custom-theme/jquery-ui-1.10.3.custom');
        $this->loadCss('default/preteurs/new-style');

        $this->unLoadJs('default/functions');
        $this->unLoadJs('default/main');
        $this->unLoadJs('default/ajax');

        $this->loadJs('default/preteurs/functions');
        $this->loadJs('default/main');
        $this->loadJs('default/ajax');

        $this->lenders_accounts        = $this->loadData('lenders_accounts');
        $this->clients_status_history  = $this->loadData('clients_status_history');
        $this->clients_status          = $this->loadData('clients_status');
        $this->clients_history_actions = $this->loadData('clients_history_actions');
        $this->attachment              = $this->loadData('attachment');
        $this->attachment_type         = $this->loadData('attachment_type');

        $this->lng['etape1'] = $this->ln->selectFront('inscription-preteur-etape-1', $this->language, $this->App);
        $this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2', $this->language, $this->App);

        $this->page_preteur = 2;

        $this->settings->get("Liste deroulante origine des fonds", 'status = 1 AND type');
        $this->origine_fonds = $this->settings->value;
        $this->origine_fonds = explode(';', $this->origine_fonds);

        $this->settings->get("Liste deroulante origine des fonds societe", 'status = 1 AND type');
        $this->origine_fonds_E = explode(';', $this->settings->value);

        $this->preteurOnline           = false;
        $this->hash_client             = '';

        $this->checkSession();

        if (isset($_SESSION['forms']['step-2']['error'])) {
                $this->error_rib = $_SESSION['forms']['step-2']['error']['error_rib'];
                $this->error_cni = $_SESSION['forms']['step-2']['error']['error_cni'];
                $this->error_cni_verso = $_SESSION['forms']['step-2']['error']['error_cni_verso'];
                $this->error_justificatif_domicile = $_SESSION['forms']['step-2']['error']['error_justificatif_domicile'];
                $this->error_attestation_hebergement = $_SESSION['forms']['step-2']['error']['error_attestation_hebergement'];
                $this->error_document_fiscal = $_SESSION['forms']['step-2']['error']['error_document_fiscal'];
            unset($_SESSION['forms']['step-2']['error']);
        }

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'status = 1 AND etape_inscription_preteur < 3 AND hash') || false === empty($this->clients->id_client)) {
            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');
            $this->clients_adresses->get($this->clients->id_client, 'id_client');
            $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);

            $this->ibanPlaceholder = 'FR..';
            $this->iban1 = empty($this->lenders_accounts->iban) ? '' : substr($this->lenders_accounts->iban, 0, 4);
            $this->iban2 = empty($this->lenders_accounts->iban) ? '' : substr($this->lenders_accounts->iban, 4, 4);
            $this->iban3 = empty($this->lenders_accounts->iban) ? '' : substr($this->lenders_accounts->iban, 8, 4);
            $this->iban4 = empty($this->lenders_accounts->iban) ? '' : substr($this->lenders_accounts->iban, 12, 4);
            $this->iban5 = empty($this->lenders_accounts->iban) ? '' : substr($this->lenders_accounts->iban, 16, 4);
            $this->iban6 = empty($this->lenders_accounts->iban) ? '' : substr($this->lenders_accounts->iban, 20, 4);
            $this->iban7 = empty($this->lenders_accounts->iban) ? '' : substr($this->lenders_accounts->iban, 24, 3);

            $this->etranger = 0;
            if ($this->clients->id_nationalite <= \nationalites_v2::NATIONALITY_FRENCH && $this->clients_adresses->id_pays_fiscal > \pays_v2::COUNTRY_FRANCE) {
                $this->etranger = 1;
            } elseif ($this->clients->id_nationalite > \nationalites_v2::NATIONALITY_FRENCH && $this->clients_adresses->id_pays_fiscal > \pays_v2::COUNTRY_FRANCE) {
                $this->etranger = 2;
            }
            if (isset($_POST['send_form_inscription_preteur_particulier_etape_2'])) {
                $this->validStep2PhysicalPerson();
            } elseif (isset($_POST['send_form_inscription_preteur_societe_etape_2'])) {
                $this->validStep2LegalEntity();
            }
        } else {
            header('location:' . $this->lurl . '/inscription_preteur/etape1/');
            die;
        }
    }

    public function _etape3()
    {
        // CSS
        $this->unLoadCss('default/custom-theme/jquery-ui-1.10.3.custom');
        $this->loadCss('default/preteurs/new-style');
        $this->loadCss('default/preteurs/print');

        // JS
        $this->unLoadJs('default/functions');
        $this->unLoadJs('default/main');
        $this->unLoadJs('default/ajax');

        $this->loadJs('default/preteurs/functions');
        $this->loadJs('default/main');
        $this->loadJs('default/ajax');

        $this->page_preteur = 3;

        $this->lng['etape3'] = $this->ln->selectFront('inscription-preteur-etape-3', $this->language, $this->App);

        require_once $this->path . 'librairies/payline/include.php';

        $this->lenders_accounts       = $this->loadData('lenders_accounts');
        $this->clients_adresses       = $this->loadData('clients_adresses');
        $this->transactions           = $this->loadData('transactions');
        $this->backpayline            = $this->loadData('backpayline');
        $this->clients_status         = $this->loadData('clients_status');
        $this->clients_status_history = $this->loadData('clients_status_history');

        $this->settings->get('Virement - aide par banque', 'type');
        $this->aide_par_banque = $this->settings->value;

        $this->settings->get('Virement - IBAN', 'type');
        $iban = strtoupper($this->settings->value);

        $this->settings->get('Virement - BIC', 'type');
        $this->bic = strtoupper($this->settings->value);

        $this->settings->get('Virement - domiciliation', 'type');
        $this->domiciliation = $this->settings->value;

        $this->settings->get('Virement - titulaire du compte', 'type');
        $this->titulaire = $this->settings->value;

        /////////////////////////////
        // Initialisation variable //
        $this->emprunteurCreatePreteur = false;
        $this->preteurOnline           = false;
        $this->hash_client             = '';

        // Si on a une session active
        if (isset($_SESSION['client'])) {
            // On recup le mec
            $this->clients->get($_SESSION['client']['id_client'], 'id_client');

            // preteur ayant deja crée son compte
            if ($this->bIsLender && $this->clients->etape_inscription_preteur == 3) {
                header('Location: ' . $this->lurl . '/inscription_preteur/etape1');
                die;
            } // preteur n'ayant pas terminé la création de son compte
            elseif ($this->bIsLender && $this->clients->etape_inscription_preteur < 3) {
                $this->preteurOnline = true;
            } // Emprunteur/preteur n'ayant pas terminé la création de son compte
            elseif ($this->bIsBorrowerAndLender && $this->clients->etape_inscription_preteur < 3) {
                $this->emprunteurCreatePreteur = true;
            }
        }
        //////////////////////////////////

        if ($this->emprunteurCreatePreteur == true) {
            $conditionOk = true;
        } elseif ($this->preteurOnline == true) {
            $conditionOk = true;
        } elseif (isset($this->params[0]) && $this->clients->get($this->params[0], 'status = 1 AND etape_inscription_preteur < 3 AND hash')) {
            $conditionOk = true;
        } else {
            $conditionOk = false;
        }

        // On recupere le client
        if ($conditionOk) {
            $this->clients_adresses->get($this->clients->id_client, 'id_client');
            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

            $this->hash_client = $this->clients->hash;

            // Motif virement
            $this->motif = $this->clients->getLenderPattern($this->clients->id_client);

            $_SESSION['motif'] = $this->motif;

            if ($iban != '') {
                $this->iban1 = substr($iban, 0, 4);
                $this->iban2 = substr($iban, 4, 4);
                $this->iban3 = substr($iban, 8, 4);
                $this->iban4 = substr($iban, 12, 4);
                $this->iban5 = substr($iban, 16, 4);
                $this->iban6 = substr($iban, 20, 4);
                $this->iban7 = substr($iban, 24, 3);

                $this->etablissement = substr($iban, 4, 5);
                $this->guichet       = substr($iban, 9, 5);
                $this->compte        = substr($iban, 14, 11);
                $this->cle           = substr($iban, 25, 2);
            }

            // paiement CB
            if (isset($_POST['send_form_preteur_cb'])) {
                $amount = $this->ficelle->cleanFormatedNumber($_POST['amount']);

                if (is_numeric($amount) && $amount >= 20 && $amount <= 10000) {
                    $amount                                 = (number_format($amount, 2, '.', '') * 100);
                    $this->lenders_accounts->fonds          = $amount;
                    $this->lenders_accounts->motif          = $this->motif;
                    $this->lenders_accounts->type_transfert = 2; // cb
                    $this->lenders_accounts->update();

                    $this->transactions->id_client        = $this->clients->id_client;
                    $this->transactions->montant          = $amount;
                    $this->transactions->id_langue        = 'fr';
                    $this->transactions->date_transaction = date('Y-m-d h:i:s');
                    $this->transactions->status           = '0';
                    $this->transactions->etat             = '0';
                    $this->transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                    $this->transactions->civilite_fac     = $this->clients->civilite;
                    $this->transactions->nom_fac          = $this->clients->nom;
                    $this->transactions->prenom_fac       = $this->clients->prenom;
                    if ($this->clients->type == 2) {
                        $this->transactions->societe_fac = $this->companies->name;
                    }
                    $this->transactions->adresse1_fac     = $this->clients_adresses->adresse1;
                    $this->transactions->cp_fac           = $this->clients_adresses->cp;
                    $this->transactions->ville_fac        = $this->clients_adresses->ville;
                    $this->transactions->id_pays_fac      = $this->clients_adresses->id_pays;
                    $this->transactions->type_transaction = 1; // on signal que c'est un solde pour l'inscription
                    $this->transactions->transaction      = 1; // transaction physique
                    $this->transactions->id_transaction   = $this->transactions->create();

                    $array                    = array();
                    $payline                  = new paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
                    $payline->returnURL       = $this->lurl . '/inscription_preteur/payment/' . $this->clients->hash . '/';
                    $payline->cancelURL       = $this->lurl . '/inscription_preteur/payment/' . $this->clients->hash . '/';
                    $payline->notificationURL = NOTIFICATION_URL;

                    // PAYMENT
                    $array['payment']['amount']   = $amount;
                    $array['payment']['currency'] = ORDER_CURRENCY;
                    $array['payment']['action']   = PAYMENT_ACTION;
                    $array['payment']['mode']     = PAYMENT_MODE;

                    // ORDER
                    $array['order']['ref']      = $this->transactions->id_transaction;
                    $array['order']['amount']   = $amount;
                    $array['order']['currency'] = ORDER_CURRENCY;

                    // CONTRACT NUMBERS
                    $array['payment']['contractNumber'] = CONTRACT_NUMBER;
                    $contracts                          = explode(";", CONTRACT_NUMBER_LIST);
                    $array['contracts']                 = $contracts;
                    $secondContracts                    = explode(";", SECOND_CONTRACT_NUMBER_LIST);
                    $array['secondContracts']           = $secondContracts;

                    // EXECUTE
                    $result = $payline->doWebPayment($array);

                    // On enregistre le tableau retourné
                    $this->transactions->get($this->transactions->id_transaction, 'id_transaction');
                    $this->transactions->serialize_payline = serialize($result);
                    $this->transactions->update();

                    // si on retourne quelque chose
                    if (isset($result)) {
                        if ($result['result']['code'] == '00000') {
                            header("location:" . $result['redirectURL']);
                            exit();
                        } // Si erreur on envoie sur mon mail
                        elseif (isset($result)) {
                            header('location:' . $this->lurl . '/inscription_preteur/erreur/' . $this->clients->hash);
                            die;
                        }
                    }
                }

            } elseif (isset($_POST['send_form_preteur_virement'])) {// Virement
                $this->clients->etape_inscription_preteur = 3; // etape 3 ok

                // type de versement virement
                $this->lenders_accounts->fonds          = 0;
                $this->lenders_accounts->motif          = $this->motif;
                $this->lenders_accounts->type_transfert = 1;
                // on enregistre les infos
                $this->lenders_accounts->update();

                // Enregistrement
                $this->clients->update();

                /** @var \settings $oSettings */
                $oSettings = $this->loadData('settings');
                // FB
                $oSettings->get('Facebook', 'type');
                $lien_fb = $oSettings->value;

                // Twitter
                $oSettings->get('Twitter', 'type');
                $lien_tw = $oSettings->value;

                // Variables du mailing
                $varMail = array(
                    'surl'           => $this->surl,
                    'url'            => $this->lurl,
                    'prenom'         => $this->clients->prenom,
                    'email_p'        => $this->clients->email,
                    'mdp'            => $_POST['pass'],
                    'motif_virement' => $this->motif,
                    'lien_fb'        => $lien_fb,
                    'lien_tw'        => $lien_tw
                );

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-inscription-preteur-etape-3', $varMail);
                $message->setTo($this->clients->email);
                $mailer = $this->get('mailer');
                $mailer->send($message);

                header('Location: ' . $this->lurl . '/inscription_preteur/confirmation/' . $this->clients->hash . '/v/');
                die;
            }
        } else {
            header('Location: ' . $this->lurl . '/inscription_preteur/etape1');
            die;
        }

    }

    public function _payment()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireView   = false;
        $this->autoFireFooter = false;

        require_once $this->path . 'librairies/payline/include.php';

        $this->transactions           = $this->loadData('transactions');
        $this->backpayline            = $this->loadData('backpayline');
        $this->lenders_accounts       = $this->loadData('lenders_accounts');
        $this->bank_lines             = $this->loadData('bank_lines');
        $this->wallets_lines          = $this->loadData('wallets_lines');
        $this->clients_status         = $this->loadData('clients_status');
        $this->clients_status_history = $this->loadData('clients_status_history');

        // Prêteur n'ayant pas terminé son inscription
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash') && $this->clients->etape_inscription_preteur < 3) {
            $conditionOk = true;
        } else {
            $conditionOk = false;
        }
        // On recupere le client
        if ($conditionOk == true) {

            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

            $array   = array();
            $payline = new paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);

            // GET TOKEN
            if (isset($_POST['token'])) {
                $array['token'] = $_POST['token'];
            } elseif (isset($_GET['token'])) {
                $array['token'] = $_GET['token'];
            } else {
                header('location:' . $this->lurl . '/inscription_preteur/etape3/' . $this->clients->hash);
                die;
            }

            // VERSION
            if (isset($_POST['version'])) {
                $array['version'] = $_POST['version'];
            } else {
                $array['version'] = '3';
            }

            // RESPONSE FORMAT
            $response = $payline->getWebPaymentDetails($array);
            if (isset($response)) {
                // On enregistre le resultat payline
                $this->backpayline->code           = $response['result']['code'];
                $this->backpayline->token          = $array['token'];
                $this->backpayline->id             = $response['transaction']['id'];
                $this->backpayline->date           = $response['transaction']['date'];
                $this->backpayline->amount         = $response['payment']['amount'];
                $this->backpayline->serialize      = serialize($response);
                $this->backpayline->id_backpayline = $this->backpayline->create();

                // Paiement approuvé
                if ($response['result']['code'] == '00000') {
                    if ($this->transactions->get($response['order']['ref'], 'status = 0 AND etat = 0 AND id_transaction')) {
                        $this->transactions->id_backpayline   = $this->backpayline->id_backpayline;
                        $this->transactions->montant          = $response['payment']['amount'];
                        $this->transactions->id_langue        = 'fr';
                        $this->transactions->date_transaction = date('Y-m-d h:i:s');
                        $this->transactions->status           = '1';
                        $this->transactions->etat             = '1';
                        $this->transactions->type_paiement    = ($response['extendedCard']['type'] == 'VISA' ? '0' : ($response['extendedCard']['type'] == 'MASTERCARD' ? '3' : ''));
                        $this->transactions->update();

                        // On enrgistre la transaction dans le wallet
                        $this->wallets_lines->id_lender                = $this->lenders_accounts->id_lender_account;
                        $this->wallets_lines->type_financial_operation = 10; // Inscription preteur
                        $this->wallets_lines->id_transaction           = $this->transactions->id_transaction;
                        $this->wallets_lines->status                   = 1;
                        $this->wallets_lines->type                     = 1;
                        $this->wallets_lines->amount                   = $response['payment']['amount'];
                        $this->wallets_lines->id_wallet_line           = $this->wallets_lines->create();

                        // Transaction physique donc on enregistre aussi dans la bank lines
                        $this->bank_lines->id_wallet_line    = $this->wallets_lines->id_wallet_line;
                        $this->bank_lines->id_lender_account = $this->lenders_accounts->id_lender_account;
                        $this->bank_lines->status            = 1;
                        $this->bank_lines->amount            = $response['payment']['amount'];
                        $this->bank_lines->create();

                        // Historique client
                        $this->clients_history->id_client = $this->clients->id_client;
                        $this->clients_history->status    = 2; // statut creation compte preteur
                        $this->clients_history->create();

                        //********************************************//
                        //*** ENVOI DU MAIL NOTIFICATION VERSEMENT ***//
                        //********************************************//

                        /** @var \settings $oSettings */
                        $oSettings = $this->loadData('settings');
                        $oSettings->get('Adresse notification nouveau versement preteur', 'type');
                        $destinataire = $oSettings->value;

                        $varMail = array(
                            '$surl'       => $this->surl,
                            '$url'        => $this->lurl,
                            '$id_preteur' => $this->clients->id_client,
                            '$nom'        => $this->clients->nom,
                            '$prenom'     => $this->clients->prenom,
                            '$montant'    => ($response['payment']['amount'] / 100)
                        );

                        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-nouveau-versement-dun-preteur', $varMail, false);
                        $message->setTo($destinataire);
                        $mailer = $this->get('mailer');
                        $mailer->send($message);

                        //******************************************************//
                        //*** ENVOI DU MAIL CONFIRMATION INSCRIPTION PRETEUR ***//
                        //******************************************************//

                        $oSettings->get('Facebook', 'type');
                        $lien_fb = $oSettings->value;
                        $oSettings->get('Twitter', 'type');
                        $lien_tw = $oSettings->value;

                        // Variables du mailing
                        $varMail = array(
                            'surl'           => $this->surl,
                            'url'            => $this->lurl,
                            'prenom'         => $this->clients->prenom,
                            'email_p'        => $this->clients->email,
                            'mdp'            => $_POST['pass'],
                            'motif_virement' => $this->clients->getLenderPattern($this->clients->id_client),
                            'lien_fb'        => $lien_fb,
                            'lien_tw'        => $lien_tw
                        );

                        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-inscription-preteur-etape-3', $varMail);
                        $message->setTo($this->clients->email);
                        $mailer = $this->get('mailer');
                        $mailer->send($message);

                        $this->clients->etape_inscription_preteur = 3;
                        $this->clients->update();

                        // connection au compte
                        // mise en session
                        $client             = $this->clients->select('id_client = ' . $this->clients->id_client);
                        $_SESSION['auth']   = true;
                        $_SESSION['token']  = md5(md5(time() . $this->clients->securityKey));
                        $_SESSION['client'] = $client[0];
                        // fin mise en session
                        header('location:' . $this->lurl . '/inscription_preteur/confirmation/' . $this->clients->hash . '/cb/' . $this->transactions->id_transaction);
                        die;
                    } else { // si infos pas good
                        header('location:' . $this->lurl . '/inscription_preteur/etape3/' . $this->clients->hash);
                        die;
                    }
                } // Paiement annulé
                elseif ($response['result']['code'] == '02319') {
                    $this->transactions->get($response['order']['ref'], 'id_transaction');
                    $this->transactions->id_backpayline = $this->backpayline->id_backpayline;
                    $this->transactions->statut         = '0';
                    $this->transactions->etat           = '3';
                    $this->transactions->update();

                    header('location:' . $this->lurl . '/inscription_preteur/etape3/' . $this->clients->hash);
                    die;
                } // Si erreur
                else {
                    header('location:' . $this->lurl . '/inscription_preteur/erreur/' . $this->clients->hash);
                    die;
                }
            }
        }
    }

    public function _confirmation()
    {
        $this->emprunteurCreatePreteur = false;

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash')) {
            $this->page_preteur = 3;

            if (isset($this->params[1]) && $this->params[1] == 'v') {
                header('location:' . $this->lurl . '/' . $this->tree->getSlug(16, $this->language) . '/' . $this->params[0]);
            } elseif (isset($this->params[1]) && $this->params[1] == 'cb') {
                // on rajoute l'id transaction en params 2
                header('location:' . $this->lurl . '/' . $this->tree->getSlug(130, $this->language) . '/' . $this->params[0] . '/' . $this->params[2] . '/');
            } else {
                header('location:' . $this->lurl . '/inscription_preteur/etape1/');
                die;
            }
        } else {
            header('location:' . $this->lurl . '/inscription_preteur/etape1/');
            die;
        }
    }

    public function _template()
    {

    }

    public function _erreur()
    {
        $this->emprunteurCreatePreteur = false;

        if (isset($_SESSION['client'])) {
            $this->clients->get($_SESSION['client']['id_client'], 'id_client');
            if ($this->bIsBorrower) {
                $this->emprunteurCreatePreteur = true;
                $this->clients->type           = \clients::TYPE_LEGAL_ENTITY;
            } else {
                header('Location:' . $this->lurl . '/inscription_preteur/etape1');
                die;
            }
        }

        if ($this->emprunteurCreatePreteur == true) {
            $conditionOk = true;
        } elseif (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash')) {
            $conditionOk = true;
        } else {
            $conditionOk = false;
        }

        if ($conditionOk == true) {
            $this->page_preteur  = 3;
            $this->lng['etape3'] = $this->ln->selectFront('inscription-preteur-etape-3', $this->language, $this->App);
        } else {
            header('Location:' . $this->lurl . '/inscription_preteur/etape1/');
            die;
        }
    }

    public function _contact_form()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        //Recuperation des element de traductions
        $this->lng['contact'] = $this->ln->selectFront('contact', $this->language, $this->App);

        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash')) {

        }
    }

    public function _particulier_etape_1()
    {
        $this->hideDecoration();
    }

    public function _societe_etape_1()
    {
        $this->hideDecoration();
    }

    public function _particulier_etape_2()
    {
        $this->hideDecoration();
    }

    public function _societe_etape_2()
    {
        $this->hideDecoration();
    }

    public function _print()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;

        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        // CSS
        $this->unLoadCss('default/custom-theme/jquery-ui-1.10.3.custom');
        $this->unLoadCss('default/colorbox');
        $this->unLoadCss('default/jquery.c2selectbox');
        $this->loadCss('default/preteurs/new-style');
        $this->loadCss('default/preteurs/print');

        // JS
        $this->unLoadJs('default/functions');
        $this->unLoadJs('default/bootstrap-tooltip');
        $this->unLoadJs('default/jquery.carouFredSel-6.2.1-packed');
        $this->unLoadJs('default/jquery.c2selectbox');
        $this->unLoadJs('default/livevalidation_standalone.compressed');
        $this->unLoadJs('default/jquery.colorbox-min');
        $this->unLoadJs('default/jqueryui-1.10.3.min');
        $this->unLoadJs('default/ui.datepicker-fr');
        $this->unLoadJs('default/highcharts.src');
        $this->unLoadJs('default/main');
        $this->unLoadJs('default/ajax');


        $this->page_preteur = 3;

        $this->lng['etape3'] = $this->ln->selectFront('inscription-preteur-etape-3', $this->language, $this->App);

        $this->settings->get('Virement - aide par banque', 'type');
        $this->aide_par_banque = $this->settings->value;

        $this->settings->get('Virement - IBAN', 'type');
        $this->iban = strtoupper($this->settings->value);

        $this->settings->get('Virement - BIC', 'type');
        $this->bic = strtoupper($this->settings->value);

        $this->settings->get('Virement - domiciliation', 'type');
        $this->domiciliation = $this->settings->value;

        $this->settings->get('Virement - titulaire du compte', 'type');
        $this->titulaire = $this->settings->value;

        $this->motif = $_SESSION['motif'];
    }


    private function validStep2PhysicalPerson()
    {
        $bFormOk = true;

        $this->lenders_accounts->bic  = trim(strtoupper($_POST['bic']));
        $this->lenders_accounts->iban = '';
        for ($i = 1; $i <= 7; $i++) {
            $this->lenders_accounts->iban .= trim(strtoupper($_POST['iban-' . $i]));
        }

        if (false === empty($this->lenders_accounts->iban)) {
            $this->iban1 = substr($this->lenders_accounts->iban, 0, 4);
            $this->iban2 = substr($this->lenders_accounts->iban, 4, 4);
            $this->iban3 = substr($this->lenders_accounts->iban, 8, 4);
            $this->iban4 = substr($this->lenders_accounts->iban, 12, 4);
            $this->iban5 = substr($this->lenders_accounts->iban, 16, 4);
            $this->iban6 = substr($this->lenders_accounts->iban, 20, 4);
            $this->iban7 = substr($this->lenders_accounts->iban, 24, 3);
        }

        $this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
        $this->lenders_accounts->precision = ($_POST['preciser'] != $this->lng['etape2']['autre-preciser'] && $_POST['origine_des_fonds'] == 1000000) ? $_POST['preciser'] : '' ;

        if (false === isset($_POST['bic']) || $_POST['bic'] == $this->lng['etape2']['bic'] || empty($_POST['bic'])
            || (isset($_POST['bic']) && false === $this->ficelle->swift_validate(trim($_POST['bic'])))
        ) {
            $bFormOk = false;
        }

        if (strlen($this->lenders_accounts->iban) < 27
            ||(empty($this->lenders_accounts->iban) && false == $this->ficelle->isIBAN($this->lenders_accounts->iban))
        ) {
            $bFormOk = false;
        }

        if (false === isset($_POST['origine_des_fonds']) || 0 == $_POST['origine_des_fonds']
            || ($_POST['origine_des_fonds'] == 1000000 && in_array($_POST['preciser'], array( $this->lng['etape2']['autre-preciser'], '')))
        ) {
            $bFormOk = false;
        }
        if (false === isset($_FILES['rib']) || false === is_numeric($this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::RIB))) {
            $bFormOk         = false;
            $this->error_rib = true;
        }
        if (false === isset($_FILES['cni_passeport']) || false === is_numeric($this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE))) {
            $bFormOk         = false;
            $this->error_cni = true;
        }
        if (false === isset($_FILES['cni_passeport_verso']) || false === is_numeric($this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE_VERSO))) {
            $bFormOk               = false;
            $this->error_cni_verso = true;
        }
        $this->lenders_accounts->cni_passeport = 1;

        if (false === isset($_FILES['justificatif_domicile']) || false === is_numeric($this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::JUSTIFICATIF_DOMICILE))) {
            $bFormOk                           = false;
            $this->error_justificatif_domicile = true;
        }

        if (false === empty($_FILES['attestation_hebergement_tiers']['name']) && false === empty($_FILES['cni_passport_tiers_hebergeant']['name'])) {
            if (false === is_numeric($this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::ATTESTATION_HEBERGEMENT_TIERS))
                || false === is_numeric($this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT))
            ){
                $bFormOk                             = false;
                $this->error_attestation_hebergement = true;
            }
        }

        if ($this->etranger > 0) {
            if (false === isset($_FILES['document_fiscal']) || false === is_numeric($this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::JUSTIFICATIF_FISCAL))) {
                $bFormOk                     = false;
                $this->error_document_fiscal = true;
            }
        }

        if ($bFormOk) {
            $this->clients->etape_inscription_preteur = 2;
            $this->lenders_accounts->update();
            $this->clients->update();

            if ($this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = 1') <= 0) {
                $this->clients_status_history->addStatus(\users::USER_ID_FRONT, \clients_status::TO_BE_CHECKED, $this->clients->id_client);
                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST));
                $this->clients_history_actions->histo(17, 'inscription etape 2 particulier', $this->clients->id_client, $serialize);

            } else {
                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST));
                $this->clients_history_actions->histo(18, 'edition inscription etape 2 particulier', $this->clients->id_client, $serialize);
            }
            header('location:' . $this->lurl . '/inscription_preteur/etape3/' . $this->clients->hash);
            die;
        } else {
            $_SESSION['forms']['step-2']['error']['error_rib']                     = isset($this->error_rib) ? $this->error_rib : false ;
            $_SESSION['forms']['step-2']['error']['error_cni']                     = isset($this->error_cni) ? $this->error_cni : false;
            $_SESSION['forms']['step-2']['error']['error_cni_verso']               = $this->error_cni_verso;
            $_SESSION['forms']['step-2']['error']['error_justificatif_domicile']   = $this->error_justificatif_domicile;
            $_SESSION['forms']['step-2']['error']['error_attestation_hebergement'] = $this->error_attestation_hebergement;
            $_SESSION['forms']['step-2']['error']['error_document_fiscal']         = $this->error_document_fiscal;
        }
    }

    private function validStep2LegalEntity()
    {
        $bFormOk       = true;

        $this->lenders_accounts->bic  = trim(strtoupper($_POST['bic']));
        $this->lenders_accounts->iban = '';
        for ($i = 1; $i <= 7; $i++) {
            $this->lenders_accounts->iban .= trim(strtoupper($_POST['iban-' . $i]));
        }

        if (false === empty($this->lenders_accounts->iban)) {
            $this->iban1 = substr($this->lenders_accounts->iban, 0, 4);
            $this->iban2 = substr($this->lenders_accounts->iban, 4, 4);
            $this->iban3 = substr($this->lenders_accounts->iban, 8, 4);
            $this->iban4 = substr($this->lenders_accounts->iban, 12, 4);
            $this->iban5 = substr($this->lenders_accounts->iban, 16, 4);
            $this->iban6 = substr($this->lenders_accounts->iban, 20, 4);
            $this->iban7 = substr($this->lenders_accounts->iban, 24, 3);
        }

        $this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
        $this->lenders_accounts->precision         = ($_POST['preciser'] != $this->lng['etape2']['autre-preciser'] && $_POST['origine_des_fonds'] == 1000000) ? $_POST['preciser'] : '';

        if ( ! isset($_POST['bic']) || $_POST['bic'] == $this->lng['etape2']['bic'] || $_POST['bic'] == ''
            || (isset($_POST['bic']) && $this->ficelle->swift_validate(trim($_POST['bic'])) == false)
        ) {
            $bFormOk = false;
        }

        if (strlen($this->lenders_accounts->iban) < 27
            || (false === empty($this->lenders_accounts->iban) && false === $this->ficelle->isIBAN($this->lenders_accounts->iban))
        ) {
            $bFormOk = false;
        }

        if ( ! isset($_POST['origine_des_fonds']) || $_POST['origine_des_fonds'] == 0
            || ($_POST['origine_des_fonds'] == 1000000 && in_array($_POST['preciser'], array($this->lng['etape2']['autre-preciser'], '')))
        ) {
            $bFormOk = false;
        }

        if (false === isset($_FILES['rib']) || false === is_numeric($this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::RIB))) {
            $this->error_rib = true;
            $bFormOk         = false;
        }

        if (false === isset($_FILES['extrait_kbis']) || false === is_numeric($this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::KBIS))) {
            $this->error_extrait_kbis = true;
            $bFormOk                  = false;
        }

        if (false === isset($_FILES['delegation_pouvoir']) || false === is_numeric($this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::DELEGATION_POUVOIR))) {
            $this->error_delegation_pouvoir = true;
            $bFormOk                        = false;
        }

        if (false === isset($_FILES['cni_passeport_dirigeant']) || false === is_numeric($this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE_DIRIGEANT))) {
            $this->error_cni_passeport_dirigeant = true;
            $bFormOk                             = false;
        }

        if (false === isset($_FILES['cni_passeport_verso']) || false === is_numeric($this->uploadAttachment($this->lenders_accounts->id_lender_account, attachment_type::CNI_PASSPORTE_VERSO))) {
            $this->error_cni_passeport_verso = true;
            $bFormOk                         = false;
        }

        if ($bFormOk) {
            $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST));
            $this->clients_history_actions->histo(19, 'inscription etape 2 entreprise', $this->clients->id_client, $serialize);

            $this->clients->etape_inscription_preteur = 2;
            $this->lenders_accounts->update();
            $this->clients->update();

            if ($this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = 1') <= 0) {
                $this->clients_status_history->addStatus(\users::USER_ID_FRONT, \clients_status::TO_BE_CHECKED, $this->clients->id_client);
            }
            header('location:' . $this->lurl . '/inscription_preteur/etape3/' . $this->clients->hash);
            die;
        }
    }
}
