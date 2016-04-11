<?php

class inscription_preteurController extends bootstrap
{
    /**
     * @var attachment_helper
     */
    private $attachmentHelper;

    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

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

        $this->emprunteurCreatePreteur = false;
        $this->preteurOnline           = false;
        $this->hash_client             = '';

        if (isset($_SESSION['landing_client']) && count($_SESSION['landing_client']) > 0) {
            $this->clients->prenom = $_SESSION['landing_client']['prenom'];
            $this->clients->nom    = $_SESSION['landing_client']['nom'];
            $this->clients->email  = $_SESSION['landing_client']['email'];
        }

        $this->checkSession();
        //while there is no system to create a lender from an existing borrower account, if it is a borrower (tested in checkSession()), we redirect to the project page.
        if ($this->emprunteurCreatePreteur) {
            $this->clients->type           = \clients::TYPE_LEGAL_ENTITY;
            header('Location:' . $this->lurl . '/projects');
            die;
        }

        $this->reponse_email = '';
        if (isset($_SESSION['reponse_email']) && $_SESSION['reponse_email'] != '') {
            $this->reponse_email = $_SESSION['reponse_email'];
            unset($_SESSION['reponse_email']);
        }
        $this->reponse_age = '';
        if (isset($_SESSION['reponse_age']) && $_SESSION['reponse_age'] != '') {
            $this->reponse_age = $_SESSION['reponse_age'];
            unset($_SESSION['reponse_age']);
        }

        $this->messageDeuxiemeCompte = '';
        if (isset($_SESSION['messageDeuxiemeCompte']) && $_SESSION['messageDeuxiemeCompte'] != '') {
            $this->messageDeuxiemeCompte = $_SESSION['messageDeuxiemeCompte'];
            unset($_SESSION['messageDeuxiemeCompte']);
        }

        $this->modif = $this->emprunteurCreatePreteur || $this->preteurOnline;

        if ($this->emprunteurCreatePreteur
            || (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash'))
            || (isset($_SESSION['client']) && $this->clients->get($_SESSION['client']['id_client'], 'id_client'))
        ) {
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            if (in_array($this->clients->type, array(
                \clients::TYPE_LEGAL_ENTITY,
                \clients::TYPE_LEGAL_ENTITY_FOREIGNER
            ))) {
                $this->companies->get($this->clients->id_client, 'id_client_owner');
            } elseif (in_array($this->clients->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER))) {
                $nais        = explode('-', $this->clients->naissance);
                $this->jour  = $nais[2];
                $this->mois  = $nais[1];
                $this->annee = $nais[0];
            }

            $this->etranger = 0;
            if ($this->clients->id_nationalite <= \nationalites_v2::NATIONALITY_FRENCH && $this->clients_adresses->id_pays_fiscal > \pays_v2::COUNTRY_FRANCE) {
                $this->etranger = 1;
            } elseif ($this->clients->id_nationalite > \nationalites_v2::NATIONALITY_FRENCH && $this->clients_adresses->id_pays_fiscal > \pays_v2::COUNTRY_FRANCE) {
                $this->etranger = 2;
            }

            $this->modif       = true;
            $this->email       = $this->clients->email;
            $this->hash_client = $this->clients->hash;
        }

        if ($this->clients->telephone != '') {
            $this->clients->telephone = trim(chunk_split(str_replace(' ', '', $this->clients->telephone), 2, ' '));
        }
        if ($this->companies->phone != '') {
            $this->companies->phone = trim(chunk_split(str_replace(' ', '', $this->companies->phone), 2, ' '));
        }
        if ($this->companies->phone_dirigeant != '') {
            $this->companies->phone_dirigeant = trim(chunk_split(str_replace(' ', '', $this->companies->phone_dirigeant), 2, ' '));
        }

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

        $this->emprunteurCreatePreteur = false;
        $this->preteurOnline           = false;
        $this->hash_client             = '';

        $this->checkSession();

        if ($this->emprunteurCreatePreteur || $this->preteurOnline || isset($this->params[0]) && $this->clients->get($this->params[0], 'status = 1 AND etape_inscription_preteur < 3 AND hash')) {
            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');
            $this->clients_adresses->get($this->clients->id_client, 'id_client');
            $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);

            $this->iban1 = empty($this->lenders_accounts->iban) ? 'FR...' : substr($this->lenders_accounts->iban, 0, 4);
            $this->iban2 = empty($this->lenders_accounts->iban) ? '' : substr($this->lenders_accounts->iban, 4, 4);
            $this->iban3 = empty($this->lenders_accounts->iban) ? '' : substr($this->lenders_accounts->iban, 8, 4);
            $this->iban4 = empty($this->lenders_accounts->iban) ? '' : substr($this->lenders_accounts->iban, 12, 4);
            $this->iban5 = empty($this->lenders_accounts->iban) ? '' : substr($this->lenders_accounts->iban, 16, 4);
            $this->iban6 = empty($this->lenders_accounts->iban) ? '' : substr($this->lenders_accounts->iban, 20, 4);
            $this->iban7 = empty($this->lenders_accounts->iban) ? '' : substr($this->lenders_accounts->iban, 24, 3);

            $this->hash_client = $this->clients->hash;

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

        //Recuperation des element de traductions
        $this->lng['etape3'] = $this->ln->selectFront('inscription-preteur-etape-3', $this->language, $this->App);

        // On recup la lib et le reste payline
        require_once($this->path . 'protected/payline/include.php');

        // Chargement des datas
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

                    //***************//
                    //*** PAYLINE ***//
                    //***************//

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

                //******************************************************//
                //*** ENVOI DU MAIL CONFIRMATION INSCRIPTION PRETEUR ***//
                //******************************************************//

                // Recuperation du modele de mail
                $this->mails_text = $this->loadData('mails_text');
                $this->mails_text->get('confirmation-inscription-preteur-etape-3', 'lang = "' . $this->language . '" AND type');

                // Variables du mailing
                $surl = $this->surl;
                $url  = $this->lurl;

                // FB
                $this->settings->get('Facebook', 'type');
                $lien_fb = $this->settings->value;

                // Twitter
                $this->settings->get('Twitter', 'type');
                $lien_tw = $this->settings->value;

                // Variables du mailing
                $varMail = array(
                    'surl'           => $surl,
                    'url'            => $url,
                    'prenom'         => $this->clients->prenom,
                    'email_p'        => $this->clients->email,
                    'mdp'            => $_POST['pass'],
                    'motif_virement' => $this->motif,
                    'lien_fb'        => $lien_fb,
                    'lien_tw'        => $lien_tw
                );

                // Construction du tableau avec les balises EMV
                $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                // Attribution des données aux variables
                $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                // Envoi du mail
                $this->email = $this->loadLib('email', array());
                $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                $this->email->setSubject(stripslashes($sujetMail));
                $this->email->setHTMLBody(stripslashes($texteMail));

                if ($this->Config['env'] == 'prod') {
                    Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                    $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                } else {
                    $this->email->addRecipient(trim($this->clients->email));
                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                }
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

        // On recup la lib et le reste payline
        require_once($this->path . 'protected/payline/include.php');

        // Chargement des datas
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

                        $this->settings->get('Adresse notification nouveau versement preteur', 'type');
                        $destinataire = $this->settings->value;

                        // Recuperation du modele de mail
                        $this->mails_text->get('notification-nouveau-versement-dun-preteur', 'lang = "' . $this->language . '" AND type');

                        // Variables du mailing
                        $surl       = $this->surl;
                        $url        = $this->lurl;
                        $id_preteur = $this->clients->id_client;
                        $nom        = utf8_decode($this->clients->nom);
                        $prenom     = utf8_decode($this->clients->prenom);
                        $montant    = ($response['payment']['amount'] / 100);

                        // Attribution des données aux variables
                        $sujetMail = htmlentities($this->mails_text->subject);
                        eval("\$sujetMail = \"$sujetMail\";");

                        $texteMail = $this->mails_text->content;
                        eval("\$texteMail = \"$texteMail\";");

                        $exp_name = $this->mails_text->exp_name;
                        eval("\$exp_name = \"$exp_name\";");

                        // Nettoyage de printemps
                        $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
                        $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

                        // Envoi du mail
                        $this->email = $this->loadLib('email', array());
                        $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                        $this->email->addRecipient(trim($destinataire));

                        $this->email->setSubject('=?UTF-8?B?' . base64_encode(html_entity_decode($sujetMail)) . '?=');
                        $this->email->setHTMLBody($texteMail);
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        // fin mail

                        // email inscription preteur //

                        //******************************************************//
                        //*** ENVOI DU MAIL CONFIRMATION INSCRIPTION PRETEUR ***//
                        //******************************************************//

                        // Recuperation du modele de mail
                        $this->mails_text->get('confirmation-inscription-preteur-etape-3', 'lang = "' . $this->language . '" AND type');

                        // Motif virement
                        $this->motif = $this->clients->getLenderPattern($this->clients->id_client);

                        // Variables du mailing
                        $surl = $this->surl;
                        $url  = $this->lurl;

                        // FB
                        $this->settings->get('Facebook', 'type');
                        $lien_fb = $this->settings->value;

                        // Twitter
                        $this->settings->get('Twitter', 'type');
                        $lien_tw = $this->settings->value;

                        // Variables du mailing
                        $varMail = array(
                            'surl'           => $surl,
                            'url'            => $url,
                            'prenom'         => $this->clients->prenom,
                            'email_p'        => $this->clients->email,
                            'mdp'            => $_POST['pass'],
                            'motif_virement' => $this->motif,
                            'lien_fb'        => $lien_fb,
                            'lien_tw'        => $lien_tw
                        );

                        // Construction du tableau avec les balises EMV
                        $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                        // Attribution des données aux variables
                        $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                        $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                        $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                        // Envoi du mail
                        $this->email = $this->loadLib('email', array());
                        $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                        $this->email->setSubject(stripslashes($sujetMail));
                        $this->email->setHTMLBody(stripslashes($texteMail));

                        if ($this->Config['env'] == 'prod') // nmp
                        {
                            Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);

                            // Injection du mail NMP dans la queue
                            $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                        } else {
                            $this->email->addRecipient(trim($this->clients->email));
                            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        }
                        // fin mail

                        /// email inscription preteur ///

                        $this->clients->etape_inscription_preteur = 3; // etape 3 ok


                        // Enregistrement
                        $this->clients->update();

                        // connection au compte
                        // mise en session
                        $client             = $this->clients->select('id_client = ' . $this->clients->id_client);
                        $_SESSION['auth']   = true;
                        $_SESSION['token']  = md5(md5(mktime() . $this->clients->securityKey));
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
        $this->unLoadJs('default/jquery-ui-1.10.3.custom.min');
        $this->unLoadJs('default/jquery-ui-1.10.3.custom2');
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

    /**
     * @param integer $lenderAccountId
     * @param integer $attachmentType
     * @return bool|string
     */
    private function uploadAttachment($lenderAccountId, $attachmentType)
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
            $this->attachmentHelper = $this->loadLib('attachment_helper', array(
                $this->attachment,
                $this->attachment_type,
                $this->path
            ));;
        }

        switch ($attachmentType) {
            case attachment_type::CNI_PASSPORTE :
                $field = 'cni_passeport';
                break;
            case attachment_type::CNI_PASSPORTE_VERSO :
                $field = 'cni_passeport_verso';
                break;
            case attachment_type::JUSTIFICATIF_DOMICILE :
                $field = 'justificatif_domicile';
                break;
            case attachment_type::RIB :
                $field = 'rib';
                break;
            case attachment_type::ATTESTATION_HEBERGEMENT_TIERS :
                $field = 'attestation_hebergement_tiers';
                break;
            case attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT :
                $field = 'cni_passport_tiers_hebergeant';
                break;
            case attachment_type::CNI_PASSPORTE_DIRIGEANT :
                $field = 'cni_passeport_dirigeant';
                break;
            case attachment_type::DELEGATION_POUVOIR :
                $field = 'delegation_pouvoir';
                break;
            case attachment_type::KBIS :
                $field = 'extrait_kbis';
                break;
            case attachment_type::JUSTIFICATIF_FISCAL :
                $field = 'document_fiscal';
                break;
            case attachment_type::AUTRE1 :
                $field = 'autre1';
                break;
            case attachment_type::AUTRE2 :
                $field = 'autre2';
                break;
            case attachment_type::AUTRE3:
                $field = 'autre3';
                break;
            default :
                return false;
        }

        return $this->attachmentHelper->upload($lenderAccountId, attachment::LENDER, $attachmentType, $field, $this->upload);
    }

    private function sendSubscriptionConfirmationEmail(\clients $oClient)
    {
        $this->mails_text->get('confirmation-inscription-preteur', 'lang = "' . $this->language . '" AND type');

        $this->settings->get('Facebook', 'type');
        $lien_fb = $this->settings->value;
        $this->settings->get('Twitter', 'type');
        $lien_tw = $this->settings->value;

        $varMail = array(
            'surl'           => $this->surl,
            'url'            => $this->lurl,
            'prenom'         => $oClient->prenom,
            'email_p'        => $oClient->email,
            'mdp'            => $_POST['pass'],
            'motif_virement' => $oClient->getLenderPattern($oClient->id_client),
            'lien_fb'        => $lien_fb,
            'lien_tw'        => $lien_tw,
            'annee'          => date('Y')
        );

        $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

        $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
        $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
        $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

        if (false === isset($this->email) || false === $this->email instanceof email) {
            $this->email = $this->loadLib('email', array());
        }
        $this->email->setFrom($this->mails_text->exp_email, $exp_name);
        $this->email->setSubject(stripslashes($sujetMail));
        $this->email->setHTMLBody(stripslashes($texteMail));

        if ($this->Config['env'] == 'prod') {
            Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $oClient->email, $tabFiler);
            $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
        } else {
            $this->email->addRecipient(trim($oClient->email));
            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
        }
    }

    private function checkSession() {

        if (isset($_SESSION['client'])) {
            $this->clients->get($_SESSION['client']['id_client'], 'id_client');

            if ($this->bIsLender && $this->clients->etape_inscription_preteur == 3) {
                header('Location:' . $this->lurl . '/projects');
                die;
            } elseif ($this->bIsLender && $this->clients->etape_inscription_preteur < 3) {
                $this->preteurOnline = true;
            } elseif ($this->bIsBorrower) {
                $this->emprunteurCreatePreteur = true;
            }
        }
    }

    private function validStep1PhysicalPerson()
    {
        $bFormOk = true;

        $this->clients_adresses->adresse_fiscal      = $_POST['adresse_inscription'];
        $this->clients_adresses->ville_fiscal        = $_POST['ville_inscription'];
        $this->clients_adresses->cp_fiscal           = $_POST['postal'];
        $this->clients_adresses->id_pays_fiscal      = $_POST['pays1'];

        if (false === empty($_POST['mon-addresse'])) {
            $this->clients_adresses->meme_adresse_fiscal = 1;
            $this->clients_adresses->adresse1 = $_POST['adresse_inscription'];
            $this->clients_adresses->ville    = $_POST['ville_inscription'];
            $this->clients_adresses->cp       = $_POST['postal'];
            $this->clients_adresses->id_pays  = $_POST['pays1'];
        } else {
            $this->clients_adresses->meme_adresse_fiscal = 0;
            $this->clients_adresses->adresse1 = $_POST['adress2'];
            $this->clients_adresses->ville    = $_POST['ville2'];
            $this->clients_adresses->cp       = $_POST['postal2'];
            $this->clients_adresses->id_pays  = $_POST['pays2'];
        }

        $this->clients->civilite  = $_POST['sex'];
        $this->clients->nom       = $this->ficelle->majNom($_POST['nom-famille']);
        $this->clients->nom_usage = (isset($_POST['nom-dusage']) && $_POST['nom-dusage'] == $this->lng['etape1']['nom-dusage']) ? '' : $this->ficelle->majNom($_POST['nom-dusage']);
        $this->clients->prenom    = $this->ficelle->majNom($_POST['prenom']);
        $this->clients->email     = $_POST['email'];

        if ($this->emprunteurCreatePreteur == false) {
            $this->clients->secrete_question = $_POST['secret-question'];
            $this->clients->secrete_reponse  = md5($_POST['secret-response']);
        }

        //Get the insee code for birth place: if in France, city insee code; if overseas, country insee code
        $sCodeInsee = '';
        if (\pays_v2::COUNTRY_FRANCE == $_POST['pays3']) {
            if (! isset($_POST['insee_birth']) || '' === $_POST['insee_birth']) {
                /** @var villes $oVilles */
                $oVilles = $this->loadData('villes');
                if (false === $oVilles->get($_POST['naissance'], 'ville')) {
                    $bFormOk = false;
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
                $bFormOk = false;
            }
            unset($oPays, $oInseePays);
        }

        $this->clients->telephone         = str_replace(' ', '', $_POST['phone']);
        $this->clients->ville_naissance   = $_POST['naissance'];
        $this->clients->insee_birth       = $sCodeInsee;
        $this->clients->id_pays_naissance = $_POST['pays3'];
        $this->clients->id_nationalite    = $_POST['nationalite'];
        $this->clients->naissance         = $_POST['annee_naissance'] . '-' . $_POST['mois_naissance'] . '-' . $_POST['jour_naissance'];

        if (isset($_SESSION['client']) && $_SESSION['client']['etape_inscription_preteur'] > 3) {
            $bFormOk = false;
        }

        if (false === $this->dates->ageplus18($this->clients->naissance)) {
            $bFormOk                 = false;
            $_SESSION['reponse_age'] = $this->lng['etape1']['erreur-age'];
        }

        if (false === isset($_POST['nom-famille']) || $_POST['nom-famille'] == $this->lng['etape1']['nom-de-famille']) {
            $bFormOk = false;
        }

        if (false === isset($_POST['prenom']) || $_POST['prenom'] == $this->lng['etape1']['prenom']) {
            $bFormOk = false;
        }

        if ((false === isset($_POST['email']) || $_POST['email'] == $this->lng['etape1']['email'])
            || (isset($_POST['email']) && false === $this->ficelle->isEmail($_POST['email']))
            || $_POST['email'] != $_POST['conf_email']
        ) {
            $bFormOk = false;
        } elseif (false === $this->clients->existEmail($_POST['email'])) {
            if($this->modif == true){
                if($_POST['email'] != $this->email){
                    $this->reponse_email = $this->lng['etape1']['erreur-email'];
                    $this->error_email_exist = true;
                }
            } else{
                $this->reponse_email = $this->lng['etape1']['erreur-email'];
                $this->error_email_exist = true;
            }
        }

        // Emprunteur crée un compte preteur (pas en place donc on passe tout le temps de dans)
        if ($this->emprunteurCreatePreteur == false) {
            if (! isset($_POST['pass']) || $_POST['pass'] == '') {
                $bFormOk = false;
            }
            if (! isset($_POST['pass2']) || $_POST['pass2'] == '') {
                $bFormOk = false;
            }
            if (isset($_POST['pass']) && isset($_POST['pass2']) && $_POST['pass'] != $_POST['pass2']) {
                $bFormOk = false;
            }
            if (! isset($_POST['secret-question']) || $_POST['secret-question'] == $this->lng['etape1']['question-secrete']) {
                $bFormOk = false;
            }
            if (! isset($_POST['secret-response']) || $_POST['secret-response'] == $this->lng['etape1']['response']) {
                $bFormOk = false;
            }
        }

        if (! isset($_POST['adresse_inscription']) || $_POST['adresse_inscription'] == $this->lng['etape1']['adresse']) {
            $bFormOk = false;
        }
        if (! isset($_POST['ville_inscription']) || $_POST['ville_inscription'] == $this->lng['etape1']['ville']) {
            $bFormOk = false;
        }

        if (! isset($_POST['postal']) || $_POST['postal'] == $this->lng['etape1']['code-postal']) {
            $bFormOk = false;
        } else {
            /** @var villes $oVilles */
            $oVilles = $this->loadData('villes');
            //Check cp
            if (isset($_POST['pays1']) && \pays_v2::COUNTRY_FRANCE == $_POST['pays1']) {
                //for France, check post code here.
                if (false === $oVilles->exist($_POST['postal'], 'cp')) {
                    $bFormOk = false;
                }
            }
            unset($oVilles);
        }

        if ($this->clients_adresses->meme_adresse_fiscal == 0) {
            if (! isset($_POST['adress2']) || $_POST['adress2'] == $this->lng['etape1']['adresse']) {
                $bFormOk = false;
            }
            if (! isset($_POST['ville2']) || $_POST['ville2'] == $this->lng['etape1']['ville']) {
                $bFormOk = false;
            }
            if (! isset($_POST['postal2']) || $_POST['postal2'] == $this->lng['etape1']['postal']) {
                $bFormOk = false;
            }
        }

        // US Person
        if (isset($_POST['check_etranger']) && $_POST['check_etranger'] != 'on') {
            $bFormOk = false;
        }

        if ($bFormOk) {
            if ($this->emprunteurCreatePreteur == false) {
                $this->clients->password = md5($_POST['pass']);
            }

            $this->clients->id_langue = 'fr';
            $this->clients->type      = ($this->clients->id_nationalite == \nationalites_v2::NATIONALITY_FRENCH) ? \clients::TYPE_PERSON : \clients::TYPE_PERSON_FOREIGNER;

            $this->clients->fonction                  = ''; // pas de fonction pour les personnes physique
            $this->clients->slug                      = $this->bdd->generateSlug($this->clients->prenom . '-' . $this->clients->nom);
            $this->lenders_accounts->id_company_owner = 0; // pas de companie pour les personnes physique

            $aPost                    = $_POST;
            $aPost['pass']            = md5($_POST['pass']);
            $aPost['pass2']           = md5($_POST['pass2']);
            $aPost['secret-response'] = md5($_POST['secret-response']);


            if ($this->modif) {
                if (isset($this->error_email_exist) &&  $this->error_email_exist == true) {
                    $this->clients->email = $this->email;
                }

                $this->clients->update();
                $this->clients_adresses->update();
                $this->lenders_accounts->update();

                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $aPost));
                $this->clients_history_actions->histo(13, 'edition inscription etape 1 particulier', $this->clients->id_client, $serialize);

                if ($this->companies->get($this->clients->id_client, 'id_client_owner')) {
                    $this->companies->delete($this->companies->id_company, 'id_company');
                }
            } else {
                if (isset($this->error_email_exist) &&  $this->error_email_exist == true) {
                    $this->clients->email = '';
                }
                $this->setSource($this->clients);

                $this->clients->create();
                $this->clients_adresses->id_client = $this->clients->id_client;
                $this->clients_adresses->create();

                $this->lenders_accounts->id_client_owner = $this->clients->id_client;
                $this->lenders_accounts->create();

                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $aPost));
                $this->clients_history_actions->histo(14, 'inscription etape 1 particulier', $this->clients->id_client, $serialize);
            }

            if (isset($_POST['accept-cgu']) && false === empty($_POST['accept-cgu'])) {
                $bHasAlreadyAccepted = $this->acceptations_legal_docs->get($this->lienConditionsGeneralesParticulier, 'id_client = "' . $this->clients->id_client . '" AND id_legal_doc');

                $this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGeneralesParticulier;
                $this->acceptations_legal_docs->id_client    = $this->clients->id_client;

                if ($bHasAlreadyAccepted) {
                    $this->acceptations_legal_docs->update();
                } else {
                    $this->acceptations_legal_docs->create();
                }
            }

            if (empty($this->reponse_email)) {
                if ($this->emprunteurCreatePreteur == false && $this->modif == false
                    || $this->modif == true && $this->clients->etape_inscription_preteur == 0
                ) {
                    $this->sendSubscriptionConfirmationEmail($this->clients);

                    $this->clients->status                     = \clients::STATUS_ONLINE;
                    $this->clients->status_inscription_preteur = 1; // inscription terminé
                    $this->clients->etape_inscription_preteur  = 1; // etape 1 ok
                    $this->clients->type = $this->clients->id_nationalite == \nationalites_v2::NATIONALITY_FRENCH ? \clients::TYPE_PERSON : \clients::TYPE_PERSON_FOREIGNER;
                    $this->clients->update();

                    $this->lenders_accounts->status = \lenders_accounts::LENDER_STATUS_ONLINE;
                    $this->lenders_accounts->update();
                }
                header('Location: ' . $this->lurl . '/inscription_preteur/etape2/' . $this->clients->hash);
                die;
            } else {
                $_SESSION['reponse_email'] = $this->reponse_email;
                header('Location: ' . $this->lurl . '/inscription_preteur/etape1/' . $this->clients->hash);
                die;
            }
        } else {
            header('Location: ' . $this->lurl . '/inscription_preteur/etape1/' . $this->params[0]);
            die;
        }
    }

    private function validStep1LegalEntity()
    {
        $bFormOk = true;

        $this->companies->name    = $_POST['raison_sociale_inscription'];
        $this->companies->forme   = $_POST['forme_juridique_inscription'];
        $this->companies->capital = str_replace(' ', '', $_POST['capital_social_inscription']);
        $this->companies->phone   = str_replace(' ', '', $_POST['phone_inscription']);
        $this->companies->siren   = $_POST['siren_inscription'];

        if (isset($_POST['mon-addresse']) && false === empty($_POST['mon-addresse'])) {
            $this->companies->status_adresse_correspondance = '1';
            $this->clients_adresses->adresse1 = $_POST['adresse_inscriptionE'];
            $this->clients_adresses->ville    = $_POST['ville_inscriptionE'];
            $this->clients_adresses->cp       = $_POST['postalE'];
            $this->companies->id_pays         = $_POST['pays1E'];
        } else {
            $this->companies->status_adresse_correspondance = '0';
            $this->clients_adresses->adresse1 = $_POST['adress2E'];
            $this->clients_adresses->ville    = $_POST['ville2E'];
            $this->clients_adresses->cp       = $_POST['postal2E'];
            $this->clients_adresses->id_pays  = $_POST['pays2E'];
        }

        $this->companies->adresse1 = $_POST['adresse_inscriptionE'];
        $this->companies->city     = $_POST['ville_inscriptionE'];
        $this->companies->zip      = $_POST['postalE'];
        $this->companies->id_pays  = $_POST['pays1E'];

        $this->companies->status_client = $_POST['enterprise'];

        $this->clients->civilite  = $_POST['genre1'];
        $this->clients->nom       = $this->ficelle->majNom($_POST['nom_inscription']);
        $this->clients->prenom    = $this->ficelle->majNom($_POST['prenom_inscription']);
        $this->clients->fonction  = $_POST['fonction_inscription'];
        $this->clients->email     = $_POST['email_inscription'];
        $this->clients->telephone = str_replace(' ', '', $_POST['phone_new_inscription']);

        if (\companies::CLIENT_STATUS_DELEGATION_OF_POWER == $this->companies->status_client|| \companies::CLIENT_STATUS_EXTERNAL_CONSULTANT == $this->companies->status_client) {
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

        if ($this->emprunteurCreatePreteur == false) {
            if (! isset($_POST['passE']) || $_POST['passE'] == '') {
                $bFormOk = false;
            }
            if (! isset($_POST['passE2']) || $_POST['passE2'] == '') {
                $bFormOk = false;
            }
            if (isset($_POST['passE']) && isset($_POST['passE2']) && $_POST['passE'] != $_POST['passE2']) {
                $bFormOk = false;
            }
            if (! isset($_POST['secret-questionE']) || $_POST['secret-questionE'] == $this->lng['etape1']['question-secrete']) {
                $bFormOk = false;
            }
            if (! isset($_POST['secret-responseE']) || $_POST['secret-responseE'] == $this->lng['etape1']['response']) {
                $bFormOk = false;
            }
        }

        if (! isset($_POST['raison_sociale_inscription']) || $_POST['raison_sociale_inscription'] == $this->lng['etape1']['raison-sociale']) {
            $bFormOk = false;
        }
        if (! isset($_POST['forme_juridique_inscription']) || $_POST['forme_juridique_inscription'] == $this->lng['etape1']['forme-juridique']) {
            $bFormOk = false;
        }
        if (! isset($_POST['capital_social_inscription']) || $_POST['capital_social_inscription'] == $this->lng['etape1']['capital-sociale']) {
            $bFormOk = false;
        }
        if (! isset($_POST['siren_inscription']) || $_POST['siren_inscription'] == $this->lng['etape1']['placeholder-field-siren']) {
            $bFormOk = false;
        }
        if (! isset($_POST['phone_inscription']) || $_POST['phone_inscription'] == $this->lng['etape1']['telephone']) {
            $bFormOk = false;
        } elseif (strlen($_POST['phone_inscription']) < 9 || strlen($_POST['phone_inscription']) > 14) {
            $bFormOk = false;
        }
        if (! isset($_POST['adresse_inscriptionE']) || $_POST['adresse_inscriptionE'] == $this->lng['etape1']['adresse']) {
            $bFormOk = false;
        }
        if (! isset($_POST['ville_inscriptionE']) || $_POST['ville_inscriptionE'] == $this->lng['etape1']['ville']) {
            $bFormOk = false;
        }
        if (! isset($_POST['postalE']) || $_POST['postalE'] == $this->lng['etape1']['code-postal']) {
            $bFormOk = false;
        } else {
            /** @var villes $oVilles */
            $oVilles = $this->loadData('villes');
            //Check cp
            if (isset($_POST['pays1E']) && 1 == $_POST['pays1E']) {
                //for France, check post code here.
                if (false === $oVilles->exist($_POST['postalE'], 'cp')) {
                    $bFormOk = false;
                }
            }
            unset($oVilles);
        }

        if ($this->companies->status_adresse_correspondance == 0) {
            if (! isset($_POST['adress2E']) || $_POST['adress2E'] == $this->lng['etape1']['adresse']) {
                $bFormOk = false;
            }
            if (! isset($_POST['ville2E']) || $_POST['ville2E'] == $this->lng['etape1']['ville']) {
                $bFormOk = false;
            }
            if (! isset($_POST['postal2E']) || $_POST['postal2E'] == $this->lng['etape1']['code-postal']) {
                $bFormOk = false;
            }
            if (! isset($_POST['pays2E']) || $_POST['pays2E'] == $this->lng['etape1']['pays']) {
                $bFormOk = false;
            }
        }

        if (! isset($_POST['nom_inscription']) || $_POST['nom_inscription'] == $this->lng['etape1']['nom']) {
            $bFormOk = false;
        }
        if (! isset($_POST['prenom_inscription']) || $_POST['prenom_inscription'] == $this->lng['etape1']['prenom']) {
            $bFormOk = false;
        }
        if (! isset($_POST['fonction_inscription']) || $_POST['fonction_inscription'] == $this->lng['etape1']['fonction']) {
            $bFormOk = false;
        }

        if ((false === isset($_POST['email_inscription']) || $_POST['email_inscription'] == $this->lng['etape1']['email'])
            || (isset($_POST['email_inscription']) && false === $this->ficelle->isEmail($_POST['email_inscription']))
            || $_POST['email_inscription'] != $_POST['conf_email_inscription']
        ) {
            $bFormOk = false;
        } elseif (false === $this->clients->existEmail($_POST['email_inscription'])) {
            if ($this->modif == true) {
                if ($_POST['email_inscription'] != $this->email) {
                    $this->reponse_email     = $this->lng['etape1']['erreur-email'];
                    $this->error_email_exist = true;
                }
            } else {
                $this->reponse_email     = $this->lng['etape1']['erreur-email'];
                $this->error_email_exist = true;
            }
        }

        if (! isset($_POST['phone_new_inscription']) || $_POST['phone_new_inscription'] == $this->lng['etape1']['telephone']) {
            $bFormOk = false;

        } elseif (strlen($_POST['phone_new_inscription']) < 9 || strlen($_POST['phone_new_inscription']) > 14) {
            $bFormOk = false;
        }

        if ($this->companies->status_client == \companies::CLIENT_STATUS_DELEGATION_OF_POWER || $this->companies->status_client == \companies::CLIENT_STATUS_EXTERNAL_CONSULTANT) {
            if (! isset($_POST['nom2_inscription']) || $_POST['nom2_inscription'] == $this->lng['etape1']['nom']) {
                $bFormOk = false;
            }
            if (! isset($_POST['prenom2_inscription']) || $_POST['prenom2_inscription'] == $this->lng['etape1']['prenom']) {
                $bFormOk = false;
            }
            if (! isset($_POST['fonction2_inscription']) || $_POST['fonction2_inscription'] == $this->lng['etape1']['fonction']) {
                $bFormOk = false;
            }
            if (! isset($_POST['email2_inscription']) || $_POST['email2_inscription'] == $this->lng['etape1']['email']) {
                $bFormOk = false;
            } elseif (isset($_POST['email2_inscription']) && $this->ficelle->isEmail($_POST['email2_inscription']) == false) {
                $bFormOk = false;
            }
            if (! isset($_POST['phone_new2_inscription']) || $_POST['phone_new2_inscription'] == $this->lng['etape1']['telephone']) {
                $bFormOk = false;
            } elseif (strlen($_POST['phone_new2_inscription']) < 9 || strlen($_POST['phone_new2_inscription']) > 14) {
                $bFormOk = false;
            }
            if ($this->companies->status_client == \companies::CLIENT_STATUS_EXTERNAL_CONSULTANT) {
                if (! isset($_POST['external-consultant']) || $_POST['external-consultant'] == '') {
                    $bFormOk = false;
                }
            }
        }

        if ($bFormOk) {
            $this->clients->password = md5($_POST['passE']);

            $this->clients->id_langue       = 'fr';
            $this->clients->nom_usage       = '';
            $this->clients->naissance       = '0000-00-00';
            $this->clients->ville_naissance = '';
            $this->clients->slug            = $this->bdd->generateSlug($this->clients->prenom . '-' . $this->clients->nom);

            if ($this->emprunteurCreatePreteur == false) {
                $this->clients->secrete_question = $_POST['secret-questionE'];
                $this->clients->secrete_reponse  = md5($_POST['secret-responseE']);
            }

            $aPost = $_POST;
            $aPost['passE']            = md5($_POST['passE']);
            $aPost['passE2']           = md5($_POST['passE2']);
            $aPost['secret-responseE'] = md5($_POST['secret-responseE']);

            if ($this->modif) {
                if ($this->companies->exist($this->clients->id_client, 'id_client_owner')) {
                    $this->companies->update();
                } else {
                    $this->companies->id_client_owner = $this->clients->id_client;
                    $this->companies->id_company      = $this->companies->create();
                }

                $this->lenders_accounts->id_company_owner = $this->companies->id_company;

                if ($this->emprunteurCreatePreteur && false === $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner')) {
                    $this->lenders_accounts->id_client_owner = $this->clients->id_client;
                    $this->lenders_accounts->create();
                }

                $this->lenders_accounts->update();
                $this->clients->update();
                $this->clients_adresses->update();

                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $aPost ));
                $this->clients_history_actions->histo(14, 'inscription etape 1 entreprise', $this->clients->id_client, $serialize);

            } else {
                $this->setSource($this->clients);

                $this->clients->create();
                $this->clients_adresses->id_client = $this->clients->id_client;
                $this->clients_adresses->create();

                $this->companies->id_client_owner = $this->clients->id_client;
                $this->companies->create();

                $this->lenders_accounts->id_client_owner  = $this->clients->id_client;
                $this->lenders_accounts->id_company_owner = $this->companies->id_company;
                $this->lenders_accounts->create();

                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $aPost));
                $this->clients_history_actions->histo(16, 'edition inscription etape 1 entreprise', $this->clients->id_client, $serialize);
            }

            if (isset($_POST['accept-cgu']) && false === empty($_POST['accept-cgu-societe'])) {
                $bHasAlreadyAccepted = $this->acceptations_legal_docs->get($this->lienConditionsGeneralesSociete, 'id_client = "' . $this->clients->id_client . '" AND id_legal_doc');

                $this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGeneralesSociete;
                $this->acceptations_legal_docs->id_client    = $this->clients->id_client;

                if ($bHasAlreadyAccepted) {
                    $this->acceptations_legal_docs->update();
                } else {
                    $this->acceptations_legal_docs->create();
                }
            }

            if (false === empty($this->reponse_email)) {
                $_SESSION['reponse_email'] = $this->reponse_email;
                header('location:' . $this->lurl . '/inscription_preteur/etape1/' . $this->clients->hash);
                die;
            } else {
                if ($this->emprunteurCreatePreteur == false && $this->modif == false || $this->modif == true && $this->clients->etape_inscription_preteur == 0) {

                    $this->sendSubscriptionConfirmationEmail($this->clients);

                    $this->clients->status                     = \clients::STATUS_ONLINE;
                    $this->clients->status_inscription_preteur = 1;
                    $this->clients->etape_inscription_preteur  = 1;
                    $this->clients->type                       = (\pays_v2::COUNTRY_FRANCE == $this->companies->id_pays) ? \clients::TYPE_LEGAL_ENTITY : \clients::TYPE_LEGAL_ENTITY_FOREIGNER;
                    $this->lenders_accounts->status            = \lenders_accounts::LENDER_STATUS_ONLINE;

                    $this->clients->update();
                    $this->lenders_accounts->update();
                }
                header('location:' . $this->lurl . '/inscription_preteur/etape2/' . $this->clients->hash);
                die;
            }
        } else {
            header('location:' . $this->lurl . '/inscription_preteur/etape1/' . $this->params[0]);
            die;
        }
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
