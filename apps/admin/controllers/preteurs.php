<?php

use Unilend\librairies\ULogger;

class preteursController extends bootstrap
{
    /**
     * @var attachment_helper
     */
    private $attachmentHelper;

    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        include $this->path . '/apps/default/controllers/pdf.php';

        $this->catchAll = true;

        $this->users->checkAccess('preteurs');

        $this->menu_admin = 'preteurs';
    }

    /**
     * @todo we load to many things here in all cases. Avoid this
     */
    public function loadGestionData()
    {
        $this->clients                = $this->loadData('clients');
        $this->clients_adresses       = $this->loadData('clients_adresses');
        $this->clients_mandats        = $this->loadData('clients_mandats');
        $this->clients_status         = $this->loadData('clients_status');
        $this->clients_status_history = $this->loadData('clients_status_history');
        $this->lenders_accounts       = $this->loadData('lenders_accounts');
        $this->transactions           = $this->loadData('transactions');
        $this->loans                  = $this->loadData('loans');
        $this->bids                   = $this->loadData('bids');
        $this->companies              = $this->loadData('companies');
        $this->projects               = $this->loadData('projects');
        $this->wallets_lines          = $this->loadData('wallets_lines');
        $this->echeanciers            = $this->loadData('echeanciers');
        $this->attachment             = $this->loadData('attachment');
        $this->attachment_type        = $this->loadData('attachment_type');
    }

    public function _default()
    {
        // On remonte la page dans l'arborescence
        if (isset($this->params[0]) && $this->params[0] == 'up') {
            $this->tree->moveUp($this->params[1]);

            header('Location:' . $this->lurl . '/preteurs');
            die;
        }

        // On descend la page dans l'arborescence
        if (isset($this->params[0]) && $this->params[0] == 'down') {
            $this->tree->moveDown($this->params[1]);

            header('Location:' . $this->lurl . '/preteurs');
            die;
        }

        // On supprime la page et ses dependances
        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            $this->tree->deleteCascade($this->params[1]);

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Suppression d\'une page';
            $_SESSION['freeow']['message'] = 'La page et ses enfants ont bien &eacute;t&eacute; supprim&eacute;s !';

            header('Location:' . $this->lurl . '/preteurs');
            die;
        }
    }

    public function _gestion()
    {
        $this->loadGestionData();

        // Partie delete
        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            // client a delete
            if ($this->clients->get($this->params[1], 'id_client') && $this->clients->status == 0) {
                // on verif si y a des infos lender
                if ($this->lenders_accounts->get($this->clients->id_client, 'id_client_owner')) ;
                {

                }
                // on verif dans companie
                if ($this->companies->get($this->clients->id_client, 'id_client_owner')) {
                    // on verif les autres table comapnie
                    $companies_actif_passif = $this->loadData('companies_actif_passif');
                    $companies_bilans       = $this->loadData('companies_bilans');
                    $companies_details      = $this->loadData('companies_details');

                    if ($companies_actif_passif->get($this->companies->id_company, 'id_company')) {
                        // On supp
                        $companies_actif_passif->delete($this->companies->id_company, 'id_company');
                    }
                    if ($companies_bilans->get($this->companies->id_company, 'id_company')) {
                        // On supp
                        $companies_bilans->delete($this->companies->id_company, 'id_company');
                    }
                    if ($companies_details->get($this->companies->id_company, 'id_company')) {
                        // On supp
                        $companies_details->delete($this->companies->id_company, 'id_company');
                    }
                    // On supp
                    $this->companies->delete($this->clients->id_client, 'id_client_owner');

                }

                // On supp
                $this->lenders_accounts->delete($this->clients->id_client, 'id_client_owner');

                // ON verif si il est dans adresses
                if ($this->clients_adresses->get($this->clients->id_client, 'id_client')) ;
                {
                    // On supp
                    $this->clients_adresses->delete($this->clients->id_client, 'id_client');
                }


                // Histo user //
                $serialize = serialize(array('id_client' => $this->clients->id_client));
                $this->users_history->histo(2, 'delete preteur inactif', $_SESSION['user']['id_user'], $serialize);
                ////////////////

                $this->clients->delete($this->clients->id_client, 'id_client');

                if (isset($this->params[2]) && $this->params[2] == 'activation') {
                    header('location:' . $this->lurl . '/preteurs/activation');
                    die;
                } else {
                    header('location:' . $this->lurl . '/preteurs/gestion/delete');
                    die;
                }
            }

            // Si on delete on met une session pour raffichier la liste avec les nonvalides
            $_SESSION['deletePreteur'] = 1;
        } // si pas en mode suppression on vire la session
        else {
            unset($_SESSION['deletePreteur']);
        }


        if (isset($_POST['form_search_preteur'])) {
            // check si on affcihe les preteurs non valides
            if (isset($_POST['nonValide']) && $_POST['nonValide'] != false) $nonValide = 1;
            else $nonValide = '';

            // Recuperation de la liste des clients searchPreteurs
            $this->lPreteurs = $this->clients->searchPreteursV2($_POST['id'], $_POST['nom'], $_POST['email'], $_POST['prenom'], $_POST['raison_sociale'], $nonValide);

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Recherche d\'un prêteur';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        } else {
            if (isset($_SESSION['deletePreteur']) && $_SESSION['deletePreteur'] == 1) $nonValide = 1;
            else $nonValide = '';

            // On recupera les 10 derniers clients
            $this->lPreteurs = $this->clients->searchPreteursV2('', '', '', '', '', $nonValide, '', '0', '300');
        }

        if (isset($this->params[0]) && $this->params[0] == 'status') {
            $this->clients->get($this->params[1], 'id_client');
            $this->clients->status = ($this->params[2] == 0 ? 1 : 0);
            $this->clients->update();


            // Histo user //
            $serialize = serialize(array('id_client' => $this->params[1], 'status' => $this->clients->status));
            $this->users_history->histo(1, 'status preteur', $_SESSION['user']['id_user'], $serialize);
            ////////////////

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Statut du preteur';
            $_SESSION['freeow']['message'] = 'Le statut du preteur a bien &eacute;t&eacute; modifi&eacute; !';

            header('location:' . $this->lurl . '/preteurs/gestion');
            die;
        }

        //preteur sans mouvement
        $a       = count($this->clients->selectPreteursByStatus('60', 'c.status = 1 AND status_inscription_preteur = 1 AND (SELECT COUNT(t.id_transaction) FROM transactions t WHERE t.type_transaction IN (1,3,4,5,7,8,14) AND t.status = 1 AND t.etat = 1 AND t.id_client = c.id_client) < 1'));
        $this->z = $a;
        //preteur "hors ligne"
        $this->y = $this->clients->counter('status = 0 AND status_inscription_preteur = 1 AND status_pre_emp IN(1,3)');
        //preteur "total"
        $this->x = $this->clients->counter('status_inscription_preteur = 1  AND status_pre_emp IN(1,3)');
    }

    public function _search()
    {
        // On affiche les Head, header et footer originaux plus le debug
        $this->autoFireHeader = true;
        $this->autoFireHead   = true;
        $this->autoFireFooter = true;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;
    }

    public function _search_non_inscripts()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;
    }

    public function _edit()
    {
        $this->loadGestionData();


        // On recup les infos du client
        $this->lenders_accounts->get($this->params[0], 'id_lender_account');

        // On recup les infos du client
        $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');

        $this->clients_adresses->get($this->clients->id_client, 'id_client');

        if (in_array($this->clients->type, array(2, 4))) {
            $this->companies->get($this->lenders_accounts->id_company_owner, 'id_company');
        }

        $this->nb_pret        = $this->loans->counter('id_lender = "' . $this->lenders_accounts->id_lender_account . '" AND status = 0');
        $this->txMoyen        = $this->loans->getAvgPrets('id_lender = "' . $this->lenders_accounts->id_lender_account . '" AND status = 0');
        $this->solde          = $this->transactions->getSolde($this->clients->id_client);
        $this->SumDepot       = $this->wallets_lines->getSumDepot($this->lenders_accounts->id_lender_account, '10,30');
        $this->SumInscription = $this->wallets_lines->getSumDepot($this->lenders_accounts->id_lender_account, '10');
        $this->sumPrets       = $this->loans->sumPrets($this->lenders_accounts->id_lender_account);
        $this->sumRembInte    = $this->echeanciers->getSumRemb($this->lenders_accounts->id_lender_account, 'interets');
        $sumRembMontant       = $this->echeanciers->getSumRembV2($this->lenders_accounts->id_lender_account);
        $this->sumRembMontant = $sumRembMontant['montant'];
        $this->avgPreteur     = $this->bids->getAvgPreteur($this->lenders_accounts->id_lender_account, 'amount', '1,2');
        $this->nextRemb       = $this->echeanciers->getNextRemb($this->lenders_accounts->id_lender_account);

        if (isset($this->params[1])) {
            $this->lEncheres = $this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND YEAR(added) = ' . $this->params[1] . ' AND status = 0');
        } else {
            $this->lEncheres = $this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND YEAR(added) = YEAR(CURDATE()) AND status = 0');
        }

        $this->clients_mandats->get($this->clients->id_client, 'id_client');

        $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);
        $this->aAttachmentTypes = $this->attachment_type->getAllTypesForLender();

        $this->lBids          = $this->bids->select('id_lender_account = ' . $this->lenders_accounts->id_lender_account . ' AND status = 0', 'added DESC');
        $this->NbBids         = count($this->lBids);
        $this->sumBidsEncours = $this->bids->sumBidsEncours($this->lenders_accounts->id_lender_account);

        $this->soldeRetrait = $this->transactions->sum('status = 1 AND etat = 1 AND transaction = 1 AND type_transaction = 8 AND id_client = ' . $this->clients->id_client, 'montant');
        $this->soldeRetrait = str_replace('-', '', $this->soldeRetrait / 100);

        $this->lng['profile']                           = $this->ln->selectFront('preteur-profile', $this->language, $this->App);
        $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);

        $year = date('Y');

        $this->lTrans = $this->transactions->select('type_transaction IN (1,3,4,5,7,8,14,16,17,19,20,22,23) AND status = 1 AND etat = 1 AND id_client = ' . $this->clients->id_client . ' AND YEAR(date_transaction) = ' . $year, 'added DESC');

        $this->lesStatuts = array(
            1  => $this->lng['profile']['versement-initial'],
            3  => $this->lng['profile']['alimentation-cb'],
            4  => $this->lng['profile']['alimentation-virement'],
            5  => 'Remboursement',
            7  => $this->lng['profile']['alimentation-prelevement'],
            8  => $this->lng['profile']['retrait'],
            14 => 'Régularisation prêteur',
            16 => 'Offre de bienvenue',
            17 => 'Retrait offre de bienvenue',
            19 => $this->lng['preteur-operations-vos-operations']['gain-filleul'],
            20 => $this->lng['preteur-operations-vos-operations']['gain-parrain'],
            22 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe'],
            23 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe-preteur']);

        // statut client
        $this->clients_status->getLastStatut($this->clients->id_client);

        // histo actions
        $this->lActions = $this->clients_status_history->select('id_client = ' . $this->clients->id_client, 'added DESC');
        if ($this->lActions[0]['added'] != false) $timeCreate = strtotime($this->lActions[0]['added']);
        else $timeCreate = strtotime($this->clients->added);
        $this->timeCreate = $timeCreate;

    }

    public function _edit_preteur()
    {
        $this->loadJs('default/jquery-ui-1.10.3.custom.min');

        $this->loadGestionData();

        $this->nationalites            = $this->loadData('nationalites_v2');
        $this->pays                    = $this->loadData('pays_v2');
        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');

        $this->lNatio = $this->nationalites->select();
        $this->lPays  = $this->pays->select('', 'ordre ASC');

        // wording completude
        $lElements = $this->blocs_elements->select('id_bloc = 9 AND id_langue = "' . $this->language . '"');
        foreach ($lElements as $b_elt) {
            $this->elements->get($b_elt['id_element']);
            $this->completude_wording[$this->elements->slug] = $b_elt['value'];
        }
        $this->nbWordingCompletude = count($this->completude_wording);

        $this->settings->get("Liste deroulante conseil externe de l'entreprise", 'type');
        $this->conseil_externe = $this->ficelle->explodeStr2array($this->settings->value);

        $this->lenders_accounts->get($this->params[0], 'id_lender_account');
        $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');
        $this->clients_adresses->get($this->clients->id_client, 'id_client');

        // Societe
        if (in_array($this->clients->type, array(2, 4))) {
            $this->companies->get($this->lenders_accounts->id_company_owner, 'id_company');

            $this->meme_adresse_fiscal = $this->companies->status_adresse_correspondance;
            $this->adresse_fiscal      = $this->companies->adresse1;
            $this->city_fiscal         = $this->companies->city;
            $this->zip_fiscal          = $this->companies->zip;

            $this->settings->get("Liste deroulante origine des fonds societe", 'status = 1 AND type');
            $this->origine_fonds = $this->settings->value;
            $this->origine_fonds = explode(';', $this->origine_fonds);
        } else { // Particulier
            $this->meme_adresse_fiscal = $this->clients_adresses->meme_adresse_fiscal;
            $this->adresse_fiscal      = $this->clients_adresses->adresse_fiscal;
            $this->city_fiscal         = $this->clients_adresses->ville_fiscal;
            $this->zip_fiscal          = $this->clients_adresses->cp_fiscal;

            $debut_exo       = explode('-', $this->lenders_accounts->debut_exoneration);
            $this->debut_exo = $debut_exo[2] . '/' . $debut_exo[1] . '/' . $debut_exo[0];

            $fin_exo       = explode('-', $this->lenders_accounts->fin_exoneration);
            $this->fin_exo = $fin_exo[2] . '/' . $fin_exo[1] . '/' . $fin_exo[0];

            $this->settings->get("Liste deroulante origine des fonds", 'status = 1 AND type');
            $this->origine_fonds = $this->settings->value;
            $this->origine_fonds = explode(';', $this->origine_fonds);
        }

        $naiss           = explode('-', $this->clients->naissance);
        $j               = $naiss['2'];
        $m               = $naiss['1'];
        $y               = $naiss['0'];
        $this->naissance = $j . '/' . $m . '/' . $y;

        if ($this->lenders_accounts->iban != '') {
            $this->iban1 = substr($this->lenders_accounts->iban, 0, 4);
            $this->iban2 = substr($this->lenders_accounts->iban, 4, 4);
            $this->iban3 = substr($this->lenders_accounts->iban, 8, 4);
            $this->iban4 = substr($this->lenders_accounts->iban, 12, 4);
            $this->iban5 = substr($this->lenders_accounts->iban, 16, 4);
            $this->iban6 = substr($this->lenders_accounts->iban, 20, 4);
            $this->iban7 = substr($this->lenders_accounts->iban, 24, 3);
        }

        if ($this->clients->telephone != '') $this->clients->telephone = trim(chunk_split($this->clients->telephone, 2, ' '));
        if ($this->companies->phone != '') $this->companies->phone = trim(chunk_split($this->companies->phone, 2, ' '));
        if ($this->companies->phone_dirigeant != '') $this->companies->phone_dirigeant = trim(chunk_split($this->companies->phone_dirigeant, 2, ' '));

        $this->clients_status->getLastStatut($this->clients->id_client);

        $this->lActions = $this->clients_status_history->select('id_client = ' . $this->clients->id_client . ' AND id_client_status IN(1,2,4,5,6) ', 'added DESC');

        if ($this->lActions[0]['added'] != false) {
            $timeCreate = strtotime($this->lActions[0]['added']);
        } else {
            $timeCreate = strtotime($this->clients->added);
        }

        $this->timeCreate = $timeCreate;

        $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);
        $this->aAttachmentTypes = $this->attachment_type->getAllTypesForLender();

        $this->lAcceptCGV = $this->acceptations_legal_docs->select('id_client = ' . $this->clients->id_client);

        if (isset($_POST['send_completude'])) {
            $this->mails_text->get('completude', 'lang = "' . $this->language . '" AND type');

            $surl = $this->surl;
            $url  = $this->lurl;

            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            if (in_array($this->clients->type, array(1, 3))) {
                $lapage = 'particulier_doc';
            } else {
                $lapage = 'societe_doc';
            }

            $month = $this->dates->tableauMois['fr'][date('n', $timeCreate)];

            $varMail = array(
                'furl'          => $this->furl,
                'surl'          => $surl,
                'url'           => $url,
                'prenom_p'      => $this->clients->prenom,
                'date_creation' => date('d', $timeCreate) . ' ' . $month . ' ' . date('Y', $timeCreate),
                'content'       => utf8_encode($_SESSION['content_email_completude'][$this->clients->id_client]),
                'lien_upload'   => $this->furl . '/profile/' . $lapage,
                'lien_fb'       => $lien_fb,
                'lien_tw'       => $lien_tw
            );
            $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

            $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
            $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
            $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

            $this->email = $this->loadLib('email');
            $this->email->setFrom($this->mails_text->exp_email, $exp_name);
            $this->email->setSubject(stripslashes($sujetMail));
            $this->email->setHTMLBody(stripslashes($texteMail));

            if ($this->Config['env'] === 'prod') {
                Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
            } else {
                $this->email->addRecipient(trim($this->clients->email));
                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
            }

            $this->clients_status_history->addStatus($_SESSION['user']['id_user'], '20', $this->clients->id_client, utf8_encode($_SESSION['content_email_completude'][$this->clients->id_client]));

            unset($_SESSION['content_email_completude'][$this->clients->id_client]);

            $_SESSION['email_completude_confirm'] = true;

            header('location:' . $this->lurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account);
            die;
        } elseif (isset($_POST['send_edit_preteur'])) {

            // particulier
            if (in_array($this->clients->type, array(1, 3))) {
                ////////////////////////////////////
                // On verifie meme adresse ou pas //
                ////////////////////////////////////
                if ($_POST['meme-adresse'] != false)
                    $this->clients_adresses->meme_adresse_fiscal = 1; // la meme
                else
                    $this->clients_adresses->meme_adresse_fiscal = 0; // pas la meme

                // adresse fiscal
                $this->clients_adresses->adresse_fiscal = $_POST['adresse'];
                $this->clients_adresses->ville_fiscal   = $_POST['ville'];
                $this->clients_adresses->cp_fiscal      = $_POST['cp'];
                $this->clients_adresses->id_pays_fiscal = $_POST['id_pays_fiscal'];

                // pas la meme
                if ($this->clients_adresses->meme_adresse_fiscal == 0) {
                    // adresse client
                    $this->clients_adresses->adresse1 = $_POST['adresse2'];
                    $this->clients_adresses->ville    = $_POST['ville2'];
                    $this->clients_adresses->cp       = $_POST['cp2'];
                    $this->clients_adresses->id_pays  = $_POST['id_pays'];
                } // la meme
                else {
                    // adresse client
                    $this->clients_adresses->adresse1 = $_POST['adresse'];
                    $this->clients_adresses->ville    = $_POST['ville'];
                    $this->clients_adresses->cp       = $_POST['cp'];
                    $this->clients_adresses->id_pays  = $_POST['id_pays_fiscal'];
                }
                ////////////////////////////////////////

                $this->clients->civilite  = $_POST['civilite'];
                $this->clients->nom       = $this->ficelle->majNom($_POST['nom-famille']);
                $this->clients->nom_usage = $this->ficelle->majNom($_POST['nom-usage']);
                $this->clients->prenom    = $this->ficelle->majNom($_POST['prenom']);

                //// check doublon mail ////
                $checkEmailExistant = $this->clients->selectPreteursByStatus('10,20,30,40,50,60', 'email = "' . $_POST['email'] . '" AND id_client != ' . $this->clients->id_client);
                if (count($checkEmailExistant) > 0) {
                    $les_id_client_email_exist = '';
                    foreach ($checkEmailExistant as $checkEmailEx) {
                        $les_id_client_email_exist .= ' ' . $checkEmailEx['id_client'];
                    }

                    $_SESSION['error_email_exist'] = 'Impossible de modifier l\'adresse email. Cette adresse est déjà utilisé par le compte id ' . $les_id_client_email_exist;
                } else $this->clients->email = $_POST['email'];

                //// fin check doublon mail ////

                $this->clients->telephone       = str_replace(' ', '', $_POST['phone']);
                $this->clients->ville_naissance = $_POST['com-naissance'];
                $this->clients->insee_birth     = $_POST['insee_birth'];

                // Naissance
                $naissance                        = explode('/', $_POST['naissance']);
                $j                                = $naissance[0];
                $m                                = $naissance[1];
                $y                                = $naissance[2];
                $this->clients->naissance         = $y . '-' . $m . '-' . $j;
                $this->clients->id_pays_naissance = $_POST['id_pays_naissance'];

                // id nationalite
                $this->clients->id_nationalite = $_POST['nationalite'];

                // On créer le client
                $this->clients->id_langue = 'fr';
                $this->clients->type      = 1;
                $this->clients->fonction  = '';

                $this->lenders_accounts->id_company_owner = 0;


                $this->lenders_accounts->bic = str_replace(' ', '', strtoupper($_POST['bic']));

                $iban = '';
                for ($i = 1; $i <= 7; $i++) {
                    $iban .= strtoupper($_POST['iban' . $i]);
                }
                $this->lenders_accounts->iban = str_replace(' ', '', $iban);

                $this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
                if ($this->lenders_accounts->origine_des_fonds == '1000000') $this->lenders_accounts->precision = $_POST['preciser'];
                else $this->lenders_accounts->precision = '';

                // debut fichiers //
                foreach ($_FILES as $field => $file) {
                    //We made the field name = attachment type id
                    $iAttachmentType = $field;
                    if ('' !== $file['name']) {
                        $this->uploadAttachment($this->lenders_accounts->id_lender_account, $field, $iAttachmentType);
                    }
                }

                // Mandat
                if (isset($_FILES['mandat']) && $_FILES['mandat']['name'] != '') {
                    if ($this->clients_mandats->get($this->clients->id_client, 'id_client')) $create = false;
                    else $create = true;

                    $this->upload->setUploadDir($this->path, 'protected/pdf/mandat/');
                    if ($this->upload->doUpload('mandat')) {
                        if ($this->clients_mandats->name != '') @unlink($this->path . 'protected/pdf/mandat/' . $this->clients_mandats->name);
                        $this->clients_mandats->name          = $this->upload->getName();
                        $this->clients_mandats->id_client     = $this->clients->id_client;
                        $this->clients_mandats->id_universign = 'no_universign';
                        $this->clients_mandats->url_pdf       = '/pdf/mandat/' . $this->clients->hash . '/';
                        $this->clients_mandats->status        = 1;

                        if ($create == true) $this->clients_mandats->create();
                        else $this->clients_mandats->update();

                    }
                }

                $old_exonere                     = $this->lenders_accounts->exonere;
                $this->lenders_accounts->exonere = $_POST['exonere'];
                $new_exonere                     = $this->lenders_accounts->exonere;

                /////////////////////////// EXONERATION MISE A JOUR SUR LES ECHEANCES ////////////////////////////////////////
                //if($old_exonere != $new_exonere){

                $this->lenders_imposition_history = $this->loadData('lenders_imposition_history');
                $this->echeanciers                = $this->loadData('echeanciers');

                // EQ-Acompte d'impôt sur le revenu
                $this->settings->get("EQ-Acompte d'impôt sur le revenu", 'type');
                $prelevements_obligatoires = $this->settings->value;

                $this->etranger = 0;
                // fr/resident etranger
                if ($this->clients->id_nationalite <= 1 && $this->clients_adresses->id_pays_fiscal > 1) {
                    $this->etranger = 1;
                } // no fr/resident etranger
                elseif ($this->clients->id_nationalite > 1 && $this->clients_adresses->id_pays_fiscal > 1) {
                    $this->etranger = 2;
                }

                // On garde une trace de l'action
                $this->lenders_imposition_history->id_lender         = $this->lenders_accounts->id_lender_account;
                $this->lenders_imposition_history->exonere           = $new_exonere;
                $this->lenders_imposition_history->resident_etranger = $this->etranger;
                $this->lenders_imposition_history->id_pays           = $this->clients_adresses->id_pays;
                $this->lenders_imposition_history->id_user           = $_SESSION['user']['id_user'];
                // KLE,BT 17712 on ne veut plus ajouter de ligne sur la sauvegarde, seulement sur la validation du preteur
                // $this->lenders_imposition_history->create();

                if ($this->etranger == 0) {
                    // on retire les prelevements sur les futures echeances
                    if ($new_exonere == 1) {

                        if (isset($_POST['debut']) && $_POST['debut'] != '') {
                            $debut     = explode('/', $_POST['debut']);
                            $debut_exo = $debut[2] . '-' . $debut[1] . '-' . $debut[0];
                        } else $debut_exo = '';

                        if (isset($_POST['fin']) && $_POST['fin'] != '') {
                            $fin     = explode('/', $_POST['fin']);
                            $fin_exo = $fin[2] . '-' . $fin[1] . '-' . $fin[0];
                        } else $fin_exo = '';


                        $this->lenders_accounts->debut_exoneration = $debut_exo;
                        $this->lenders_accounts->fin_exoneration   = $fin_exo;

                        $this->echeanciers->update_prelevements_obligatoires($this->lenders_accounts->id_lender_account, 1, '', $debut_exo, $fin_exo);
                    } // on ajoute les prelevements sur les futures echeances
                    elseif ($new_exonere == 0) {

                        $this->lenders_accounts->debut_exoneration = '0000-00-00';
                        $this->lenders_accounts->fin_exoneration   = '0000-00-00';

                        $this->echeanciers->update_prelevements_obligatoires($this->lenders_accounts->id_lender_account, 0, $prelevements_obligatoires);
                    }

                }
                //}
                ///////////////////////////////////////////////////////////////////


                $this->lenders_accounts->exonere = $_POST['exonere'];

                // mise a jour
                $this->clients->update();
                $this->clients_adresses->update();
                $this->lenders_accounts->update();
                $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);

                // Si on a une entreprise reliée, on la supprime car elle n'a plus rien a faire ici. on est un particulier.
                if ($this->companies->get($this->clients->id_client, 'id_client_owner')) {
                    $this->companies->delete($this->companies->id_company, 'id_company');
                }

                // On recup le client
                $this->clients->get($this->clients->id_client, 'id_client');


                // Histo user //
                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST, 'files' => $_FILES));
                $this->users_history->histo(3, 'modif info preteur', $_SESSION['user']['id_user'], $serialize);
                ////////////////

                if (isset($_POST['statut_valider_preteur']) && $_POST['statut_valider_preteur'] == 1) {
                    // On check si on a deja eu le compte validé au moins une fois. si c'est pas le cas on check l'offre
                    if ($this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = 6') == 0) {
                        ///////////// OFFRE DE BIENVENUE /////////////
                        $this->create_offre_bienvenue($this->clients->id_client); /// <------------------------------
                        /////////// FIN OFFRE DE BIENVENUE ///////////
                    }

                    $this->clients_status_history->addStatus($_SESSION['user']['id_user'], '60', $this->clients->id_client);


                    // modif ou inscription
                    if ($this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = 5') > 0) $modif = true;
                    else $modif = false;

                    // Validation inscription
                    if ($modif == false) {
                        ///////////// OFFRE DE BIENVENUE /////////////
                        //$this->create_offre_bienvenue($this->clients->id_client); /// <------------------------------
                        /////////// FIN OFFRE DE BIENVENUE ///////////
                    }

                    // gestion alert notification //
                    $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
                    $this->clients_gestion_type_notif    = $this->loadData('clients_gestion_type_notif');

                    ////// Liste des notifs //////
                    $this->lTypeNotifs = $this->clients_gestion_type_notif->select();
                    $this->lNotifs     = $this->clients_gestion_notifications->select('id_client = ' . $this->clients->id_client);

                    if ($this->lNotifs == false) {
                        foreach ($this->lTypeNotifs as $n) {
                            $this->clients_gestion_notifications->id_client = $this->clients->id_client;
                            $this->clients_gestion_notifications->id_notif  = $n['id_client_gestion_type_notif'];
                            $id_notif                                       = $n['id_client_gestion_type_notif'];
                            // immediatement
                            if (in_array($id_notif, array(3, 6, 7, 8)))
                                $this->clients_gestion_notifications->immediatement = 1;
                            else
                                $this->clients_gestion_notifications->immediatement = 0;
                            // quotidienne
                            if (in_array($id_notif, array(1, 2, 4, 5)))
                                $this->clients_gestion_notifications->quotidienne = 1;
                            else
                                $this->clients_gestion_notifications->quotidienne = 0;
                            // hebdomadaire
                            if (in_array($id_notif, array(1, 4)))
                                $this->clients_gestion_notifications->hebdomadaire = 1;
                            else
                                $this->clients_gestion_notifications->hebdomadaire = 0;
                            // mensuelle
                            $this->clients_gestion_notifications->mensuelle = 0;
                            $this->clients_gestion_notifications->create();
                        }
                    }

                    /////////////////////////////////


                    // Mail au client particulier //
                    // Recuperation du modele de mail
                    if ($modif == true)
                        $this->mails_text->get('preteur-validation-modification-compte', 'lang = "' . $this->language . '" AND type');
                    else
                        $this->mails_text->get('preteur-confirmation-activation', 'lang = "' . $this->language . '" AND type');

                    $surl = $this->surl;
                    $url  = $this->furl;

                    // FB
                    $this->settings->get('Facebook', 'type');
                    $lien_fb = $this->settings->value;

                    // Twitter
                    $this->settings->get('Twitter', 'type');
                    $lien_tw = $this->settings->value;

                    if (in_array($this->clients->type, array(1, 3))) $lapage = 'particulier_doc';
                    else $lapage = 'societe_doc';

                    $month = $this->dates->tableauMois['fr'][date('n', $timeCreate)];

                    $varMail = array(
                        'surl'    => $surl,
                        'url'     => $url,
                        'prenom'  => $this->clients->prenom,
                        'projets' => $this->furl . '/projets-a-financer',
                        'lien_fb' => $lien_fb,
                        'lien_tw' => $lien_tw);
                    $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                    $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                    $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                    $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                    $this->email = $this->loadLib('email');
                    $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                    $this->email->setSubject(stripslashes($sujetMail));
                    $this->email->setHTMLBody(stripslashes($texteMail));

                    if ($this->Config['env'] === 'prod') {
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($this->clients->email));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }
                    ////////////////////


                    /////////////////// IMPOSITION ETRANGER ////////////////////

                    $this->echeanciers                = $this->loadData('echeanciers');
                    $this->lenders_imposition_history = $this->loadData('lenders_imposition_history');

                    // EQ-Acompte d'impôt sur le revenu
                    $this->settings->get("EQ-Acompte d'impôt sur le revenu", 'type');
                    $prelevements_obligatoires = $this->settings->value;

                    // EQ-Contribution additionnelle au Prélèvement Social
                    $this->settings->get('EQ-Contribution additionnelle au Prélèvement Social', 'type');
                    $contributions_additionnelles = $this->settings->value;

                    // EQ-CRDS
                    $this->settings->get('EQ-CRDS', 'type');
                    $crds = $this->settings->value;

                    // EQ-CSG
                    $this->settings->get('EQ-CSG', 'type');
                    $csg = $this->settings->value;

                    // EQ-Prélèvement de Solidarité
                    $this->settings->get('EQ-Prélèvement de Solidarité', 'type');
                    $prelevements_solidarite = $this->settings->value;

                    // EQ-Prélèvement social
                    $this->settings->get('EQ-Prélèvement social', 'type');
                    $prelevements_sociaux = $this->settings->value;

                    // EQ-Retenue à la source
                    $this->settings->get('EQ-Retenue à la source', 'type');
                    $retenues_source = $this->settings->value;

                    $this->etranger = 0;
                    // fr/resident etranger
                    if ($this->clients->id_nationalite <= 1 && $this->clients_adresses->id_pays_fiscal > 1) {
                        $this->etranger = 1;
                    } // no fr/resident etranger
                    elseif ($this->clients->id_nationalite > 1 && $this->clients_adresses->id_pays_fiscal > 1) {
                        $this->etranger = 2;
                    }

                    // On garde une trace de l'action
                    $this->lenders_imposition_history->id_lender         = $this->lenders_accounts->id_lender_account;
                    $this->lenders_imposition_history->exonere           = $this->lenders_accounts->exonere;
                    $this->lenders_imposition_history->resident_etranger = $this->etranger;
                    $this->lenders_imposition_history->id_pays           = $this->clients_adresses->id_pays_fiscal; // add 18/06/2015
                    $this->lenders_imposition_history->id_user           = $_SESSION['user']['id_user'];
                    $this->lenders_imposition_history->create();

                    $tabImpo = array(
                        'prelevements_obligatoires'    => $prelevements_obligatoires,
                        'contributions_additionnelles' => $contributions_additionnelles,
                        'crds'                         => $crds,
                        'csg'                          => $csg,
                        'prelevements_solidarite'      => $prelevements_solidarite,
                        'prelevements_sociaux'         => $prelevements_sociaux,
                        'retenues_source'              => $retenues_source);

                    $this->echeanciers->update_imposition_etranger($this->lenders_accounts->id_lender_account, $this->etranger, $tabImpo, $this->lenders_accounts->exonere, $this->lenders_accounts->debut_exoneration, $this->lenders_accounts->fin_exoneration);

                    $_SESSION['compte_valide'] = true;
                }

                $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);
                header('location:' . $this->lurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account);
                die;
            } // societe
            elseif (in_array($this->clients->type, array(2, 4))) {
                $this->companies->name    = $_POST['raison-sociale'];
                $this->companies->forme   = $_POST['form-juridique'];
                $this->companies->capital = str_replace(' ', '', $_POST['capital-sociale']);
                $this->companies->siren   = $_POST['siren'];
                $this->companies->siret   = $_POST['siret']; //(19/11/2014)
                $this->companies->phone   = str_replace(' ', '', $_POST['phone-societe']);

                $this->companies->rcs          = $_POST['rcs'];
                $this->companies->tribunal_com = $_POST['tribunal_com'];

                ////////////////////////////////////
                // On verifie meme adresse ou pas //
                ////////////////////////////////////
                if ($_POST['meme-adresse'] != false)
                    $this->companies->status_adresse_correspondance = '1'; // la meme
                else
                    $this->companies->status_adresse_correspondance = '0'; // pas la meme

                // adresse fiscal (siege de l'entreprise)
                $this->companies->adresse1 = $_POST['adresse'];
                $this->companies->city     = $_POST['ville'];
                $this->companies->zip      = $_POST['cp'];

                // adresse fiscal (dans client entreprise) on vide car c'est pour les particulier ca
                $this->clients_adresses->adresse_fiscal = '';
                $this->clients_adresses->ville_fiscal   = '';
                $this->clients_adresses->cp_fiscal      = '';

                // pas la meme
                if ($this->companies->status_adresse_correspondance == 0) {
                    // adresse client
                    $this->clients_adresses->adresse1 = $_POST['adresse2'];
                    $this->clients_adresses->ville    = $_POST['ville2'];
                    $this->clients_adresses->cp       = $_POST['cp2'];
                } // la meme
                else {
                    // adresse client
                    $this->clients_adresses->adresse1 = $_POST['adresse'];
                    $this->clients_adresses->ville    = $_POST['ville'];
                    $this->clients_adresses->cp       = $_POST['cp'];
                }
                ////////////////////////////////////////

                $this->companies->status_client = $_POST['enterprise']; // radio 1 dirigeant 2 pas dirigeant 3 externe

                $this->clients->civilite = $_POST['civilite_e'];
                $this->clients->nom      = $this->ficelle->majNom($_POST['nom_e']);
                $this->clients->prenom   = $this->ficelle->majNom($_POST['prenom_e']);
                $this->clients->fonction = $_POST['fonction_e'];

                //// check doublon mail ////
                $checkEmailExistant = $this->clients->selectPreteursByStatus('10,20,30,40,50,60', 'email = "' . $_POST['email_e'] . '" AND id_client != ' . $this->clients->id_client);
                if (count($checkEmailExistant) > 0) {
                    $les_id_client_email_exist = '';
                    foreach ($checkEmailExistant as $checkEmailEx) {
                        $les_id_client_email_exist .= ' ' . $checkEmailEx['id_client'];
                    }

                    $_SESSION['error_email_exist'] = 'Impossible de modifier l\'adresse email. Cette adresse est déjà utilisé par le compte id ' . $les_id_client_email_exist;
                } else $this->clients->email = $_POST['email_e'];

                //// fin check doublon mail ////

                $this->clients->telephone = str_replace(' ', '', $_POST['phone_e']);

                //extern ou non dirigeant
                if ($this->companies->status_client == 2 || $this->companies->status_client == 3) {
                    $this->companies->civilite_dirigeant = $_POST['civilite2_e'];
                    $this->companies->nom_dirigeant      = $this->ficelle->majNom($_POST['nom2_e']);
                    $this->companies->prenom_dirigeant   = $this->ficelle->majNom($_POST['prenom2_e']);
                    $this->companies->fonction_dirigeant = $_POST['fonction2_e'];
                    $this->companies->email_dirigeant    = $_POST['email2_e'];
                    $this->companies->phone_dirigeant    = str_replace(' ', '', $_POST['phone2_e']);

                    // externe
                    if ($this->companies->status_client == 3) {
                        $this->companies->status_conseil_externe_entreprise   = $_POST['status_conseil_externe_entreprise'];
                        $this->companies->preciser_conseil_externe_entreprise = $_POST['preciser_conseil_externe_entreprise'];
                    }
                } else {
                    $this->companies->civilite_dirigeant = '';
                    $this->companies->nom_dirigeant      = '';
                    $this->companies->prenom_dirigeant   = '';
                    $this->companies->fonction_dirigeant = '';
                    $this->companies->email_dirigeant    = '';
                    $this->companies->phone_dirigeant    = '';
                }

                // Si form societe ok

                $this->clients->id_langue       = 'fr';
                $this->clients->type            = 2;
                $this->clients->nom_usage       = '';
                $this->clients->naissance       = '0000-00-00';
                $this->clients->ville_naissance = '';

                if ($this->companies->exist($this->clients->id_client, 'id_client_owner')) {
                    $this->companies->update();
                } else {
                    $this->companies->id_client_owner = $this->clients->id_client;
                    $this->companies->create();
                }

                $this->lenders_accounts->bic = str_replace(' ', '', strtoupper($_POST['bic']));

                $iban = '';
                for ($i = 1; $i <= 7; $i++) {
                    $iban .= strtoupper($_POST['iban' . $i]);
                }
                $this->lenders_accounts->iban = str_replace(' ', '', $iban);

                $this->lenders_accounts->origine_des_fonds = $_POST['origine_des_fonds'];
                if ($this->lenders_accounts->origine_des_fonds == '1000000') $this->lenders_accounts->precision = $_POST['preciser'];
                else $this->lenders_accounts->precision = '';

                // debut fichiers //

                foreach ($_FILES as $field => $file) {
                    //We made the field name = attachment type id
                    $iAttachmentType = $field;
                    if ('' !== $file['name']) {
                        $this->uploadAttachment($this->lenders_accounts->id_lender_account, $field, $iAttachmentType);
                    }
                }

                // Mandat
                if (isset($_FILES['mandat']) && $_FILES['mandat']['name'] != '') {
                    if ($this->clients_mandats->get($this->clients->id_client, 'id_client')) $create = false;
                    else $create = true;

                    $this->upload->setUploadDir($this->path, 'protected/pdf/mandat/');
                    if ($this->upload->doUpload('mandat')) {
                        if ($this->clients_mandats->name != '') @unlink($this->path . 'protected/pdf/mandat/' . $this->clients_mandats->name);
                        $this->clients_mandats->name          = $this->upload->getName();
                        $this->clients_mandats->id_client     = $this->clients->id_client;
                        $this->clients_mandats->id_universign = 'no_universign';
                        $this->clients_mandats->url_pdf       = '/pdf/mandat/' . $this->clients->hash . '/';
                        $this->clients_mandats->status        = 1;

                        if ($create == true) $this->clients_mandats->create();
                        else $this->clients_mandats->update();

                    }
                }

                // fin fichier //

                // On met a jour le lender
                $this->lenders_accounts->id_company_owner = $this->companies->id_company;
                $this->lenders_accounts->update();
                $this->attachments = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);

                // On met a jour le client
                $this->clients->update();
                // On met a jour l'adresse client
                $this->clients_adresses->update();


                // Histo user //
                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST, 'files' => $_FILES));
                $this->users_history->histo(3, 'modif info preteur personne morale', $_SESSION['user']['id_user'], $serialize);

                if (isset($_POST['statut_valider_preteur']) && $_POST['statut_valider_preteur'] == 1) {
                    $this->clients_status_history->addStatus($_SESSION['user']['id_user'], '60', $this->clients->id_client);

                    // Mail au client  societe//

                    $this->clients_status_history->addStatus($_SESSION['user']['id_user'], '60', $this->clients->id_client);

                    // modif ou inscription
                    if ($this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = 5') > 0) $modif = true;
                    else $modif = false;

                    // Validation inscription
                    if ($modif == false) {
                        ///////////// OFFRE DE BIENVENUE /////////////
                        $this->create_offre_bienvenue($this->clients->id_client); //<---------------------
                        /////////// FIN OFFRE DE BIENVENUE ///////////
                    }

                    if ($modif == true) {
                        $this->mails_text->get('preteur-validation-modification-compte', 'lang = "' . $this->language . '" AND type');
                    } else {
                        $this->mails_text->get('preteur-confirmation-activation', 'lang = "' . $this->language . '" AND type');
                    }

                    $surl = $this->surl;
                    $url  = $this->furl;

                    $this->settings->get('Facebook', 'type');
                    $lien_fb = $this->settings->value;

                    $this->settings->get('Twitter', 'type');
                    $lien_tw = $this->settings->value;

                    $varMail = array(
                        'surl'    => $surl,
                        'url'     => $url,
                        'prenom'  => $this->clients->prenom,
                        'projets' => $this->furl . '/projets-a-financer',
                        'lien_fb' => $lien_fb,
                        'lien_tw' => $lien_tw
                    );
                    $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                    $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                    $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                    $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                    $this->email = $this->loadLib('email');
                    $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                    $this->email->setSubject(stripslashes($sujetMail));
                    $this->email->setHTMLBody(stripslashes($texteMail));

                    if ($this->Config['env'] === 'prod') {
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($this->clients->email));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }

                    $_SESSION['compte_valide'] = true;
                }

                header('location:' . $this->lurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account);
                die;
            }
        }

    }

    public function _liste_preteurs_non_inscrits()
    {
        $this->loadGestionData();

        // Partie delete
        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            // client a delete
            if ($this->clients->get($this->params[1], 'id_client') && $this->clients->status == 0) {
                if ($this->companies->get($this->clients->id_client, 'id_client_owner')) {
                    // on verif les autres table comapnie
                    $companies_actif_passif = $this->loadData('companies_actif_passif');
                    $companies_bilans       = $this->loadData('companies_bilans');
                    $companies_details      = $this->loadData('companies_details');

                    if ($companies_actif_passif->get($this->companies->id_company, 'id_company')) {
                        $companies_actif_passif->delete($this->companies->id_company, 'id_company');
                    }
                    if ($companies_bilans->get($this->companies->id_company, 'id_company')) {
                        $companies_bilans->delete($this->companies->id_company, 'id_company');
                    }
                    if ($companies_details->get($this->companies->id_company, 'id_company')) {
                        $companies_details->delete($this->companies->id_company, 'id_company');
                    }
                    $this->companies->delete($this->clients->id_client, 'id_client_owner');

                }

                $this->lenders_accounts->delete($this->clients->id_client, 'id_client_owner');

                if ($this->clients_adresses->get($this->clients->id_client, 'id_client')) {
                    $this->clients_adresses->delete($this->clients->id_client, 'id_client');
                }

                $serialize = serialize(array('id_client' => $this->clients->id_client));
                $this->users_history->histo(11, 'delete preteur inactif non inscrit', $_SESSION['user']['id_user'], $serialize);

                $this->clients->delete($this->clients->id_client, 'id_client');

                header('location:' . $this->lurl . '/preteurs/liste_preteurs_non_inscrits');
                die;
            }
        }

        // non inscrit = 2
        // offline = 1
        $nonValide = 2;

        if (isset($_POST['form_search_preteur'])) {
            // Recuperation de la liste des clients searchPreteurs
            $this->lPreteurs = $this->clients->searchPreteursV2($_POST['id'], $_POST['nom'], $_POST['email'], $_POST['prenom'], $_POST['raison_sociale'], $nonValide);

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Recherche d\'un prêteur non inscript';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        } else {
            // On recupera les 10 derniers clients
            $this->lPreteurs = $this->clients->searchPreteursV2('', '', '', '', '', $nonValide, '', '0', '300');
        }


        if (isset($this->params[0]) && $this->params[0] == 'status') {
            $this->clients->get($this->params[1], 'id_client');
            $this->clients->status = ($this->params[2] == 0 ? 1 : 0);
            $this->clients->update();

            $serialize = serialize(array('id_client' => $this->params[1], 'status' => $this->clients->status));
            $this->users_history->histo(12, 'status offline-online preteur non inscrit', $_SESSION['user']['id_user'], $serialize);

            $_SESSION['freeow']['title']   = 'Statut du preteur non inscrit';
            $_SESSION['freeow']['message'] = 'Le statut du preteur non inscrit a bien &eacute;t&eacute; modifi&eacute; !';

            header('location:' . $this->lurl . '/preteurs/gestion');
            die;
        }
    }

    // Activation des comptes prêteurs
    public function _activation()
    {
        $this->loadGestionData();

        // Partie delete
        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            if ($this->clients->get($this->params[1], 'id_client')) {
                $backup_delete = false;

                if ($this->lenders_accounts->get($this->clients->id_client, 'id_client_owner'))  {
                    // On verifie si on a deja une enchere d'effectué par ce compte
                    $nb = $this->bids->counter('id_lender_account = ' . $this->lenders_accounts->id_lender_account);

                    if ($nb > 0) {
                        $backup_delete = true;

                        $backup_clients      = $this->loadData('backup_delete_clients');
                        $backup_clients_addr = $this->loadData('backup_delete_clients_adresses');
                        $backup_compa_act_pa = $this->loadData('backup_delete_companies_actif_passif');
                        $backup_companies    = $this->loadData('backup_delete_companies');
                        $backup_comp_b       = $this->loadData('backup_delete_companies_bilans');
                        $backup_comp_det     = $this->loadData('backup_delete_companies_details');
                        $backup_lenders      = $this->loadData('backup_delete_lenders_accounts');
                    }
                }

                if ($this->companies->get($this->clients->id_client, 'id_client_owner')) {
                    $companies_actif_passif = $this->loadData('companies_actif_passif');
                    $companies_bilans       = $this->loadData('companies_bilans');
                    $companies_details      = $this->loadData('companies_details');

                    $actif_passif = $companies_actif_passif->select('id_company = ' . $this->companies->id_company);

                    if (count($actif_passif) > 0) {
                        foreach ($actif_passif as $a) {
                            if ($backup_delete == true) {
                                $backup_compa_act_pa->id_actif_passif                    = $a['id_actif_passif'];
                                $backup_compa_act_pa->id_company                         = $a['id_company'];
                                $backup_compa_act_pa->ordre                              = $a['ordre'];
                                $backup_compa_act_pa->annee                              = $a['annee'];
                                $backup_compa_act_pa->immobilisations_corporelles        = $a['immobilisations_corporelles'];
                                $backup_compa_act_pa->immobilisations_incorporelles      = $a['immobilisations_incorporelles'];
                                $backup_compa_act_pa->immobilisations_financieres        = $a['immobilisations_financieres'];
                                $backup_compa_act_pa->stocks                             = $a['stocks'];
                                $backup_compa_act_pa->creances_clients                   = $a['creances_clients'];
                                $backup_compa_act_pa->disponibilites                     = $a['disponibilites'];
                                $backup_compa_act_pa->valeurs_mobilieres_de_placement    = $a['valeurs_mobilieres_de_placement'];
                                $backup_compa_act_pa->capitaux_propres                   = $a['capitaux_propres'];
                                $backup_compa_act_pa->provisions_pour_risques_et_charges = $a['provisions_pour_risques_et_charges'];
                                $backup_compa_act_pa->amortissement_sur_immo             = $a['amortissement_sur_immo'];
                                $backup_compa_act_pa->dettes_financieres                 = $a['dettes_financieres'];
                                $backup_compa_act_pa->dettes_fournisseurs                = $a['dettes_fournisseurs'];
                                $backup_compa_act_pa->autres_dettes                      = $a['autres_dettes'];
                                $backup_compa_act_pa->added_backup                       = $a['added'];
                                $backup_compa_act_pa->updated_backup                     = $a['updated'];
                                $backup_compa_act_pa->create();
                            }
                            $companies_actif_passif->delete($backup_compa_act_pa->id_actif_passif, 'id_actif_passif');
                        }
                    }

                    $comp_b = $companies_bilans->select('id_company = ' . $this->companies->id_company);

                    if (count($comp_b) > 0) {
                        foreach ($comp_b as $a) {
                            if ($backup_delete == true) {
                                $backup_comp_b->id_actif_passif             = $a['id_actif_passif'];
                                $backup_comp_b->id_company                  = $a['id_company'];
                                $backup_comp_b->ca                          = $a['ca'];
                                $backup_comp_b->resultat_brute_exploitation = $a['resultat_brute_exploitation'];
                                $backup_comp_b->resultat_exploitation       = $a['resultat_exploitation'];
                                $backup_comp_b->investissements             = $a['investissements'];
                                $backup_comp_b->date                        = $a['date'];
                                $backup_comp_b->added_backup                = $a['added'];
                                $backup_comp_b->updated                     = $a['updated'];
                                $backup_comp_b->create();
                            }
                        }
                        $companies_bilans->delete($this->companies->id_company, 'id_company');
                    }

                    if ($companies_details->get($this->companies->id_company, 'id_company')) {
                        if ($backup_delete == true) {
                            $backup_comp_det->id_company_detail                                  = $companies_details->id_company_detail;
                            $backup_comp_det->id_company                                         = $companies_details->id_company;
                            $backup_comp_det->date_dernier_bilan                                 = $companies_details->date_dernier_bilan;
                            $backup_comp_det->date_dernier_bilan_mois                            = $companies_details->date_dernier_bilan_mois;
                            $backup_comp_det->date_dernier_bilan_annee                           = $companies_details->date_dernier_bilan_annee;
                            $backup_comp_det->encours_actuel_dette_fianciere                     = $companies_details->encours_actuel_dette_fianciere;
                            $backup_comp_det->remb_a_venir_cette_annee                           = $companies_details->remb_a_venir_cette_annee;
                            $backup_comp_det->remb_a_venir_annee_prochaine                       = $companies_details->remb_a_venir_annee_prochaine;
                            $backup_comp_det->tresorie_dispo_actuellement                        = $companies_details->tresorie_dispo_actuellement;
                            $backup_comp_det->autre_demandes_financements_prevues                = $companies_details->autre_demandes_financements_prevues;
                            $backup_comp_det->precisions                                         = $companies_details->precisions;
                            $backup_comp_det->decouverts_bancaires                               = $companies_details->decouverts_bancaires;
                            $backup_comp_det->lignes_de_tresorerie                               = $companies_details->lignes_de_tresorerie;
                            $backup_comp_det->affacturage                                        = $companies_details->affacturage;
                            $backup_comp_det->escompte                                           = $companies_details->escompte;
                            $backup_comp_det->financement_dailly                                 = $companies_details->financement_dailly;
                            $backup_comp_det->credit_de_tresorerie                               = $companies_details->credit_de_tresorerie;
                            $backup_comp_det->credit_bancaire_investissements_materiels          = $companies_details->credit_bancaire_investissements_materiels;
                            $backup_comp_det->credit_bancaire_investissements_immateriels        = $companies_details->credit_bancaire_investissements_immateriels;
                            $backup_comp_det->rachat_entreprise_ou_titres                        = $companies_details->rachat_entreprise_ou_titres;
                            $backup_comp_det->credit_immobilier                                  = $companies_details->credit_immobilier;
                            $backup_comp_det->credit_bail_immobilier                             = $companies_details->credit_bail_immobilier;
                            $backup_comp_det->credit_bail                                        = $companies_details->credit_bail;
                            $backup_comp_det->location_avec_option_achat                         = $companies_details->location_avec_option_achat;
                            $backup_comp_det->location_financiere                                = $companies_details->location_financiere;
                            $backup_comp_det->location_longue_duree                              = $companies_details->location_longue_duree;
                            $backup_comp_det->pret_oseo                                          = $companies_details->pret_oseo;
                            $backup_comp_det->pret_participatif                                  = $companies_details->pret_participatif;
                            $backup_comp_det->fichier_extrait_kbis                               = $companies_details->fichier_extrait_kbis;
                            $backup_comp_det->fichier_rib                                        = $companies_details->fichier_rib;
                            $backup_comp_det->fichier_delegation_pouvoir                         = $companies_details->fichier_delegation_pouvoir;
                            $backup_comp_det->fichier_logo_societe                               = $companies_details->fichier_logo_societe;
                            $backup_comp_det->fichier_photo_dirigeant                            = $companies_details->fichier_photo_dirigeant;
                            $backup_comp_det->fichier_dernier_bilan_certifie                     = $companies_details->fichier_dernier_bilan_certifie;
                            $backup_comp_det->fichier_cni_passeport                              = $companies_details->fichier_cni_passeport;
                            $backup_comp_det->fichier_derniere_liasse_fiscale                    = $companies_details->fichier_derniere_liasse_fiscale;
                            $backup_comp_det->fichier_derniers_comptes_approuves                 = $companies_details->fichier_derniers_comptes_approuves;
                            $backup_comp_det->fichier_derniers_comptes_consolides_groupe         = $companies_details->fichier_derniers_comptes_consolides_groupe;
                            $backup_comp_det->fichier_annexes_rapport_special_commissaire_compte = $companies_details->fichier_annexes_rapport_special_commissaire_compte;
                            $backup_comp_det->fichier_arret_comptable_recent                     = $companies_details->fichier_arret_comptable_recent;
                            $backup_comp_det->fichier_budget_exercice_en_cours_a_venir           = $companies_details->fichier_budget_exercice_en_cours_a_venir;
                            $backup_comp_det->fichier_notation_banque_france                     = $companies_details->fichier_notation_banque_france;
                            $backup_comp_det->fichier_autre_1                                    = $companies_details->fichier_autre_1;
                            $backup_comp_det->fichier_autre_2                                    = $companies_details->fichier_autre_2;
                            $backup_comp_det->fichier_autre_3                                    = $companies_details->fichier_autre_3;
                            $backup_comp_det->added_backup                                       = $companies_details->added;
                            $backup_comp_det->updated_backup                                     = $companies_details->updated;
                            $backup_comp_det->create();
                        }
                        $companies_details->delete($this->companies->id_company, 'id_company');
                    }

                    if ($backup_delete == true) {
                        $backup_companies->id_company                          = $this->companies->id_company;
                        $backup_companies->id_client_owner                     = $this->companies->id_client_owner;
                        $backup_companies->id_partenaire                       = $this->companies->id_partenaire;
                        $backup_companies->id_partenaire_subcode               = $this->companies->id_partenaire_subcode;
                        $backup_companies->email_facture                       = $this->companies->email_facture;
                        $backup_companies->name                                = $this->companies->name;
                        $backup_companies->forme                               = $this->companies->forme;
                        $backup_companies->siren                               = $this->companies->siren;
                        $backup_companies->siret                               = $this->companies->siret;
                        $backup_companies->iban                                = $this->companies->iban;
                        $backup_companies->bic                                 = $this->companies->bic;
                        $backup_companies->execices_comptables                 = $this->companies->execices_comptables;
                        $backup_companies->rcs                                 = $this->companies->rcs;
                        $backup_companies->tribunal_com                        = $this->companies->tribunal_com;
                        $backup_companies->activite                            = $this->companies->activite;
                        $backup_companies->lieu_exploi                         = $this->companies->lieu_exploi;
                        $backup_companies->tva                                 = $this->companies->tva;
                        $backup_companies->capital                             = $this->companies->capital;
                        $backup_companies->date_creation                       = $this->companies->date_creation;
                        $backup_companies->adresse1                            = $this->companies->adresse1;
                        $backup_companies->adresse2                            = $this->companies->adresse2;
                        $backup_companies->zip                                 = $this->companies->zip;
                        $backup_companies->city                                = $this->companies->city;
                        $backup_companies->id_pays                             = $this->companies->id_pays;
                        $backup_companies->phone                               = $this->companies->phone;
                        $backup_companies->status_adresse_correspondance       = $this->companies->status_adresse_correspondance;
                        $backup_companies->status_client                       = $this->companies->status_client;
                        $backup_companies->status_conseil_externe_entreprise   = $this->companies->status_conseil_externe_entreprise;
                        $backup_companies->preciser_conseil_externe_entreprise = $this->companies->preciser_conseil_externe_entreprise;
                        $backup_companies->civilite_dirigeant                  = $this->companies->civilite_dirigeant;
                        $backup_companies->nom_dirigeant                       = $this->companies->nom_dirigeant;
                        $backup_companies->prenom_dirigeant                    = $this->companies->prenom_dirigeant;
                        $backup_companies->fonction_dirigeant                  = $this->companies->fonction_dirigeant;
                        $backup_companies->email_dirigeant                     = $this->companies->email_dirigeant;
                        $backup_companies->phone_dirigeant                     = $this->companies->phone_dirigeant;
                        $backup_companies->sector                              = $this->companies->sector;
                        $backup_companies->risk                                = $this->companies->risk;
                        $backup_companies->altares_eligibility                 = $this->companies->altares_eligibility;
                        $backup_companies->altares_dateValeur                  = $this->companies->altares_dateValeur;
                        $backup_companies->altares_niveauRisque                = $this->companies->altares_niveauRisque;
                        $backup_companies->altares_scoreVingt                  = $this->companies->altares_scoreVingt;
                        $backup_companies->added_backup                        = $this->companies->added;
                        $backup_companies->updated_backup                      = $this->companies->updated;
                        $backup_companies->create();
                    }
                    $this->companies->delete($this->clients->id_client, 'id_client_owner');
                }

                if ($backup_delete == true) {
                    $attachment = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);

                    $backup_lenders->id_lender_account              = $this->lenders_accounts->id_lender_account;
                    $backup_lenders->id_client_owner                = $this->lenders_accounts->id_client_owner;
                    $backup_lenders->id_company_owner               = $this->lenders_accounts->id_company_owner;
                    $backup_lenders->exonere                        = $this->lenders_accounts->exonere;
                    $backup_lenders->iban                           = $this->lenders_accounts->iban;
                    $backup_lenders->bic                            = $this->lenders_accounts->bic;
                    $backup_lenders->origine_des_fonds              = $this->lenders_accounts->origine_des_fonds;
                    $backup_lenders->precision                      = $this->lenders_accounts->precision;
                    $backup_lenders->id_partenaire                  = $this->lenders_accounts->id_partenaire;
                    $backup_lenders->id_partenaire_subcode          = $this->lenders_accounts->id_partenaire_subcode;
                    $backup_lenders->status                         = $this->lenders_accounts->status;
                    $backup_lenders->type_transfert                 = $this->lenders_accounts->type_transfert;
                    $backup_lenders->motif                          = $this->lenders_accounts->motif;
                    $backup_lenders->fonds                          = $this->lenders_accounts->fonds;
                    $backup_lenders->cni_passeport                  = $this->lenders_accounts->cni_passeport;
                    $backup_lenders->fichier_cni_passeport          = isset($attachment[attachment_type::CNI_PASSPORTE]["path"]) ? $attachment[attachment_type::CNI_PASSPORTE]["path"] : '';
                    $backup_lenders->fichier_justificatif_domicile  = isset($attachment[attachment_type::JUSTIFICATIF_DOMICILE]["path"]) ? $attachment[attachment_type::JUSTIFICATIF_DOMICILE]["path"] : '';
                    $backup_lenders->fichier_rib                    = isset($attachment[attachment_type::RIB]["path"]) ? $attachment[attachment_type::RIB]["path"] : '';
                    $backup_lenders->fichier_cni_passeport_dirigent = isset($attachment[attachment_type::CNI_PASSPORTE_DIRIGEANT]["path"]) ? $attachment[attachment_type::CNI_PASSPORTE_DIRIGEANT]["path"] : '';
                    $backup_lenders->fichier_extrait_kbis           = isset($attachment[attachment_type::KBIS]["path"]) ? $attachment[attachment_type::KBIS]["path"] : '';
                    $backup_lenders->fichier_delegation_pouvoir     = isset($attachment[attachment_type::DELEGATION_POUVOIR]["path"]) ? $attachment[attachment_type::DELEGATION_POUVOIR]["path"] : '';
                    $backup_lenders->fichier_statuts                = isset($attachment[attachment_type::STATUTS]["path"]) ? $attachment[attachment_type::STATUTS]["path"] : '';
                    $backup_lenders->fichier_autre                  = isset($attachment[attachment_type::AUTRE1]["path"]) ? $attachment[attachment_type::AUTRE1]["path"] : '';
                    $backup_lenders->added_backup                   = $this->lenders_accounts->added;
                    $backup_lenders->updated_backup                 = $this->lenders_accounts->updated;
                    $backup_lenders->create();
                }
                $this->lenders_accounts->delete($this->clients->id_client, 'id_client_owner');

                // ON verif si il est dans adresses
                if ($this->clients_adresses->get($this->clients->id_client, 'id_client')) {
                    if ($backup_delete == true) {
                        $backup_clients_addr->id_adresse          = $this->clients_adresses->id_adresse;
                        $backup_clients_addr->id_client           = $this->clients_adresses->id_client;
                        $backup_clients_addr->defaut              = $this->clients_adresses->defaut;
                        $backup_clients_addr->type                = $this->clients_adresses->type;
                        $backup_clients_addr->nom_adresse         = $this->clients_adresses->nom_adresse;
                        $backup_clients_addr->civilite            = $this->clients_adresses->civilite;
                        $backup_clients_addr->nom                 = $this->clients_adresses->nom;
                        $backup_clients_addr->prenom              = $this->clients_adresses->prenom;
                        $backup_clients_addr->societe             = $this->clients_adresses->societe;
                        $backup_clients_addr->adresse1            = $this->clients_adresses->adresse1;
                        $backup_clients_addr->adresse2            = $this->clients_adresses->adresse2;
                        $backup_clients_addr->adresse3            = $this->clients_adresses->adresse3;
                        $backup_clients_addr->cp                  = $this->clients_adresses->cp;
                        $backup_clients_addr->ville               = $this->clients_adresses->ville;
                        $backup_clients_addr->id_pays             = $this->clients_adresses->id_pays;
                        $backup_clients_addr->telephone           = $this->clients_adresses->telephone;
                        $backup_clients_addr->mobile              = $this->clients_adresses->mobile;
                        $backup_clients_addr->commentaire         = $this->clients_adresses->commentaire;
                        $backup_clients_addr->meme_adresse_fiscal = $this->clients_adresses->meme_adresse_fiscal;
                        $backup_clients_addr->adresse_fiscal      = $this->clients_adresses->adresse_fiscal;
                        $backup_clients_addr->ville_fiscal        = $this->clients_adresses->ville_fiscal;
                        $backup_clients_addr->cp_fiscal           = $this->clients_adresses->cp_fiscal;
                        $backup_clients_addr->id_pays_fiscal      = $this->clients_adresses->id_pays_fiscal;
                        $backup_clients_addr->status              = $this->clients_adresses->status;
                        $backup_clients_addr->added_backup        = $this->clients_adresses->added;
                        $backup_clients_addr->updated_backup      = $this->clients_adresses->updated;
                        $backup_clients_addr->create();
                    }
                    $this->clients_adresses->delete($this->clients->id_client, 'id_client');
                }

                $serialize = serialize(array('id_client' => $this->clients->id_client));
                $this->users_history->histo(12, 'delete preteur activation', $_SESSION['user']['id_user'], $serialize);

                if ($backup_delete == true) {
                    $backup_clients->id_client                  = $this->clients->id_client;
                    $backup_clients->hash_client                = $this->clients->hash;
                    $backup_clients->id_langue                  = $this->clients->id_langue;
                    $backup_clients->id_partenaire              = $this->clients->id_partenaire;
                    $backup_clients->id_partenaire_subcode      = $this->clients->id_partenaire_subcode;
                    $backup_clients->id_facebook                = $this->clients->id_facebook;
                    $backup_clients->id_linkedin                = $this->clients->id_linkedin;
                    $backup_clients->id_viadeo                  = $this->clients->id_viadeo;
                    $backup_clients->id_twitter                 = $this->clients->id_twitter;
                    $backup_clients->civilite                   = $this->clients->civilite;
                    $backup_clients->nom                        = $this->clients->nom;
                    $backup_clients->nom_usage                  = $this->clients->nom_usage;
                    $backup_clients->prenom                     = $this->clients->prenom;
                    $backup_clients->slug                       = $this->clients->slug;
                    $backup_clients->fonction                   = $this->clients->fonction;
                    $backup_clients->naissance                  = $this->clients->naissance;
                    $backup_clients->ville_naissance            = $this->clients->ville_naissance;
                    $backup_clients->id_pays_naissance          = $this->clients->id_pays_naissance;
                    $backup_clients->id_nationalite             = $this->clients->id_nationalite;
                    $backup_clients->telephone                  = $this->clients->telephone;
                    $backup_clients->mobile                     = $this->clients->mobile;
                    $backup_clients->email                      = $this->clients->email;
                    $backup_clients->password                   = $this->clients->password;
                    $backup_clients->secrete_question           = $this->clients->secrete_question;
                    $backup_clients->secrete_reponse            = $this->clients->secrete_reponse;
                    $backup_clients->type                       = $this->clients->type;
                    $backup_clients->etape_inscription_preteur  = $this->clients->etape_inscription_preteur;
                    $backup_clients->status_inscription_preteur = $this->clients->status_inscription_preteur;
                    $backup_clients->status_pre_emp             = $this->clients->status_pre_emp;
                    $backup_clients->status_transition          = $this->clients->status_transition;
                    $backup_clients->cni_passeport              = $this->clients->cni_passeport;
                    $backup_clients->signature                  = $this->clients->signature;
                    $backup_clients->optin1                     = $this->clients->optin1;
                    $backup_clients->optin2                     = $this->clients->optin2;
                    $backup_clients->status                     = $this->clients->status;
                    $backup_clients->added_backup               = $this->clients->added;
                    $backup_clients->updated_backup             = $this->clients->updated;
                    $backup_clients->lastlogin                  = $this->clients->lastlogin;
                    $backup_clients->create();

                    mail('unilend@equinoa.fr', '[ALERTE] Compte supprime', 'Un compte (' . $this->clients->id_client . ') avec des encheres a ete supprime.');
                }

                $this->clients->delete($this->clients->id_client, 'id_client');

                header('location:' . $this->lurl . '/preteurs/activation');
                die;
            }
        }

        $this->lPreteurs = $this->clients->selectPreteursByStatus('10,20,30,40,50', '', 'added_status DESC');
    }

    public function _completude()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;
    }

    public function _completude_preview()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;

        $this->clients          = $this->loadData('clients');
        $this->lenders_accounts = $this->loadData('lenders_accounts');

        $this->mails_text->get('completude', 'lang = "' . $this->language . '" AND type');

        $this->clients->get($this->params[0], 'id_client');
        $this->lenders_accounts->get($this->params[0], 'id_client_owner');
    }

    public function _completude_preview_iframe()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;

        $this->clients                = $this->loadData('clients');
        $this->clients_status_history = $this->loadData('clients_status_history');

        $this->clients->get($this->params[0], 'id_client');

        $this->mails_text->get('completude', 'lang = "' . $this->language . '" AND type');

        $surl = $this->surl;
        $url  = $this->lurl;

        $this->settings->get('Facebook', 'type');
        $lien_fb = $this->settings->value;

        $this->settings->get('Twitter', 'type');
        $lien_tw = $this->settings->value;

        if (in_array($this->clients->type, array(1, 3))) {
            $lapage = 'particulier_doc';
        } else {
            $lapage = 'societe_doc';
        }

        $this->lActions = $this->clients_status_history->select('id_client = ' . $this->clients->id_client, 'added DESC');

        if ($this->lActions[0]['added'] != false) {
            $timeCreate = strtotime($this->lActions[0]['added']);
        } else {
            $timeCreate = strtotime($this->clients->added);
        }

        $month = $this->dates->tableauMois['fr'][date('n', $timeCreate)];

        $varMail = array(
            'furl'          => $this->furl,
            'surl'          => $surl,
            'url'           => $url,
            'prenom_p'      => $this->clients->prenom,
            'date_creation' => date('d', $timeCreate) . ' ' . $month . ' ' . date('Y', $timeCreate),
            'content'       => utf8_encode($_SESSION['content_email_completude'][$this->clients->id_client]),
            'lien_upload'   => $this->furl . '/profile/' . $lapage,
            'lien_fb'       => $lien_fb,
            'lien_tw'       => $lien_tw
        );

        $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

        echo $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
        die;
    }

    public function _offres_de_bienvenue()
    {
        $offres_bienvenues         = $this->loadData('offres_bienvenues');
        $offres_bienvenues_details = $this->loadData('offres_bienvenues_details');
        $transactions              = $this->loadData('transactions');
        $this->clients             = $this->loadData('clients');

        $this->settings->get("Offre de bienvenue motif", 'type');
        $this->motifOffreBienvenue = $this->settings->value;

        if ($offres_bienvenues->get(1, 'id_offre_bienvenue')) {
            $create = false;

            $debut       = explode('-', $offres_bienvenues->debut);
            $this->debut = $debut[2] . '/' . $debut[1] . '/' . $debut[0];

            $fin       = explode('-', $offres_bienvenues->fin);
            $this->fin = $fin[2] . '/' . $fin[1] . '/' . $fin[0];

            $this->montant       = str_replace('.', ',', ($offres_bienvenues->montant / 100));
            $this->montant_limit = str_replace('.', ',', ($offres_bienvenues->montant_limit / 100));
        } else $create = true;

        // form send offres de Bienvenues
        if (isset($_POST['form_send_offres'])) {

            $this->debut         = $_POST['debut'];
            $this->fin           = $_POST['fin'];
            $this->montant       = $_POST['montant'];
            $this->montant_limit = $_POST['montant_limit'];

            $form_ok = true;

            if (!isset($_POST['debut']) || strlen($_POST['debut']) == 0) {
                $form_ok = false;
            }
            if (!isset($_POST['fin']) || strlen($_POST['fin']) == 0) {
                $form_ok = false;
            }
            if (!isset($_POST['montant']) || strlen($_POST['montant']) == 0) {
                $form_ok = false;
            } elseif (is_numeric(str_replace(',', '.', $_POST['montant'])) == false) {
                $form_ok = false;
            }
            if (!isset($_POST['montant_limit']) || strlen($_POST['montant_limit']) == 0) {
                $form_ok = false;
            } elseif (is_numeric(str_replace(',', '.', $_POST['montant_limit'])) == false) {
                $form_ok = false;
            }

            if ($form_ok == true) {
                // debut
                $debut = explode('/', $_POST['debut']);
                $debut = $debut[2] . '-' . $debut[1] . '-' . $debut[0];
                // fin
                $fin = explode('/', $_POST['fin']);
                $fin = $fin[2] . '-' . $fin[1] . '-' . $fin[0];
                // montant
                $montant = str_replace(',', '.', $_POST['montant']);
                // montant limit
                $montant_limit = str_replace(',', '.', $_POST['montant_limit']);

                // Enregistrement
                $offres_bienvenues->debut         = $debut;
                $offres_bienvenues->fin           = $fin;
                $offres_bienvenues->montant       = ($montant * 100);
                $offres_bienvenues->montant_limit = ($montant_limit * 100);
                $offres_bienvenues->id_user       = $_SESSION['user']['id_user'];

                if ($create == false) $offres_bienvenues->update();
                else $offres_bienvenues->id_offre_bienvenue = $offres_bienvenues->create();

                $_SESSION['freeow']['title']   = 'Offre de bienvenue';
                $_SESSION['freeow']['message'] = 'Offre de bienvenue ajouté';
            } else {
                $_SESSION['freeow']['title']   = 'Offre de bienvenue';
                $_SESSION['freeow']['message'] = 'Erreur offre de bienvenue';
            }
        }

        $this->sumOffres = $offres_bienvenues_details->sum('type = 0 AND id_offre_bienvenue = ' . $offres_bienvenues->id_offre_bienvenue . ' AND status != 2', 'montant');
        $this->lOffres   = $offres_bienvenues_details->select('type = 0 AND id_offre_bienvenue = ' . $offres_bienvenues->id_offre_bienvenue . ' AND status != 2', 'added DESC');

        // Somme des virements unilend offre de bienvenue
        $sumVirementUnilendOffres = $transactions->sum('status = 1 AND etat = 1 AND type_transaction = 18', 'montant');
        // Somme des offres utilisé
        $sumOffresTransac = $transactions->sum('status = 1 AND etat = 1 AND type_transaction IN(16,17)', 'montant');
        // Somme reel dispo
        $this->sumDispoPourOffres = ($sumVirementUnilendOffres - $sumOffresTransac);
        $this->sumDispoPourOffresSelonMax = (($this->montant_limit * 100) - $sumOffresTransac);
    }

    // OFFRE DE BIENVENUE
    public function create_offre_bienvenue($id_client)
    {
        $this->clients = $this->loadData('clients');

        // si le client existe et qu'il vient de la page offre bienvenue
        if ($this->clients->get($id_client, 'id_client') && $this->clients->origine == 1) {
            $offres_bienvenues         = $this->loadData('offres_bienvenues');
            $offres_bienvenues_details = $this->loadData('offres_bienvenues_details');
            $transactions              = $this->loadData('transactions');
            $wallets_lines             = $this->loadData('wallets_lines');
            $lenders_accounts          = $this->loadData('lenders_accounts');
            $bank_unilend              = $this->loadData('bank_unilend');

            if ($offres_bienvenues->get(1, 'status = 0 AND id_offre_bienvenue')) {
                $sumOffres          = $offres_bienvenues_details->sum('type = 0 AND id_offre_bienvenue = ' . $offres_bienvenues->id_offre_bienvenue . ' AND status <> 2', 'montant');
                $sumOffresPlusOffre = ($sumOffres + $offres_bienvenues->montant);

                // Somme des virements unilend offre de bienvenue
                $sumVirementUnilendOffres = $transactions->sum('status = 1 AND etat = 1 AND type_transaction = 18', 'montant');
                // Somme des offres utilisé
                $sumOffresTransac = $transactions->sum('status = 1 AND etat = 1 AND type_transaction IN(16,17)', 'montant');
                // Somme reel dispo
                $sumDispoPourOffres = ($sumVirementUnilendOffres - $sumOffresTransac);

                // On regarde que l'offre soit pas terminé
                if (strtotime($offres_bienvenues->debut) <= time() && strtotime($offres_bienvenues->fin . ' 23:59:59') >= time() && $sumOffresPlusOffre <= $offres_bienvenues->montant_limit && $sumDispoPourOffres >= $offres_bienvenues->montant) {
                    $this->settings->get("Offre de bienvenue motif", 'type');
                    $this->motifOffreBienvenue = $this->settings->value;

                    $lenders_accounts->get($this->clients->id_client, 'id_client_owner');

                    $offres_bienvenues_details->id_offre_bienvenue        = $offres_bienvenues->id_offre_bienvenue;
                    $offres_bienvenues_details->motif                     = $this->motifOffreBienvenue;
                    $offres_bienvenues_details->id_client                 = $this->clients->id_client;
                    $offres_bienvenues_details->montant                   = $offres_bienvenues->montant;
                    $offres_bienvenues_details->status                    = 0;
                    $offres_bienvenues_details->create();

                    $transactions->id_client                 = $this->clients->id_client;
                    $transactions->montant                   = $offres_bienvenues->montant;
                    $transactions->id_offre_bienvenue_detail = $offres_bienvenues_details->id_offre_bienvenue_detail;
                    $transactions->id_langue                 = 'fr';
                    $transactions->date_transaction          = date('Y-m-d H:i:s');
                    $transactions->status                    = '1';
                    $transactions->etat                      = '1';
                    $transactions->ip_client                 = $_SERVER['REMOTE_ADDR'];
                    $transactions->type_transaction          = 16; // Offre de bienvenue
                    $transactions->transaction               = 2; // transaction virtuelle
                    $transactions->create();

                    $wallets_lines->id_lender                = $lenders_accounts->id_lender_account;
                    $wallets_lines->type_financial_operation = 30; // alimentation
                    $wallets_lines->id_transaction           = $transactions->id_transaction;
                    $wallets_lines->status                   = 1;
                    $wallets_lines->type                     = 1;
                    $wallets_lines->amount                   = $offres_bienvenues->montant;
                    $wallets_lines->create();

                    $bank_unilend->id_transaction = $transactions->id_transaction;
                    $bank_unilend->montant        = '-' . $offres_bienvenues->montant;  // on retire cette somme du total dispo
                    $bank_unilend->type           = 4; // Unilend offre de bienvenue
                    $bank_unilend->create();

                    $this->mails_text->get('offre-de-bienvenue', 'lang = "' . $this->language . '" AND type');

                    $this->settings->get('Facebook', 'type');
                    $lien_fb = $this->settings->value;

                    $this->settings->get('Twitter', 'type');
                    $lien_tw = $this->settings->value;

                    $varMail = array(
                        'surl'            => $this->surl,
                        'url'             => $this->furl,
                        'prenom_p'        => $this->clients->prenom,
                        'projets'         => $this->furl . '/projets-a-financer',
                        'offre_bienvenue' => $this->ficelle->formatNumber($offres_bienvenues->montant / 100),
                        'lien_fb'         => $lien_fb,
                        'lien_tw'         => $lien_tw
                    );

                    $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                    $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                    $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                    $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                    $this->email = $this->loadLib('email');
                    $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                    $this->email->setSubject(stripslashes($sujetMail));
                    $this->email->setHTMLBody(stripslashes($texteMail));

                    if ($this->Config['env'] === 'prod') {
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($this->clients->email));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }
                }
            }
        }
    }
    /**
     * @param integer $iOwnerId
     * @param integer $field
     * @param integer $iAttachmentType
     * @return bool
     */
    private function uploadAttachment($iOwnerId, $field, $iAttachmentType)
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
            $this->attachmentHelper = $this->loadLib('attachment_helper', array($this->attachment, $this->attachment_type, $this->path ));;
        }

        $sNewName = '';
        if (isset($_FILES[$field]['name']) && $aFileInfo = pathinfo($_FILES[$field]['name'])) {
            $sNewName = mb_substr($aFileInfo['filename'], 0, 30) . '_' . $iOwnerId;
        }

        $resultUpload = $this->attachmentHelper->upload($iOwnerId, attachment::LENDER, $iAttachmentType, $field, $this->upload, $sNewName);

        return $resultUpload;
    }

    public function _email_history()
    {
        $this->loadGestionData();

        $this->lenders_accounts->get($this->params[0], 'id_lender_account');
        $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');
        $this->clients_adresses->get($this->clients->id_client, 'id_client');

        if (in_array($this->clients->type, array(2, 4))) {
            $this->companies->get($this->lenders_accounts->id_company_owner, 'id_company');
        }

        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_type_notif    = $this->loadData('clients_gestion_type_notif');

        $this->lTypeNotifs = $this->clients_gestion_type_notif->select();
        $this->NotifC      = $this->clients_gestion_notifications->getNotifs($this->clients->id_client);
    }

    public function _portefeuille()
    {
        $this->loadGestionData();

        $this->projects_status         = $this->loadData('projects_status');
        $this->indexage_vos_operations = $this->loadData('indexage_vos_operations');

        $this->lenders_accounts->get($this->params[0], 'id_lender_account');
        $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');
        $this->clients_adresses->get($this->clients->id_client, 'id_client');

        if (in_array($this->clients->type, array(2, 4))) {
            $this->companies->get($this->lenders_accounts->id_company_owner, 'id_company');
        }

        $this->lSumLoans               = $this->loans->getSumLoansByProject($this->lenders_accounts->id_lender_account, '', 'next_echeance ASC');
        $this->arrayDeclarationCreance = $this->projects->getProjectsInDebt();

        $oLenderAccountStats = $this->loadData('lenders_account_stats');

        $this->IRR = $oLenderAccountStats->getLastIRRForLender($this->lenders_accounts->id_lender_account);

        if (empty($this->IRR)) {
            try {
                $this->IRR['tri_value'] = $this->lenders_accounts->calculateIRR($this->lenders_accounts->id_lender_account);
            } catch (Exception $e) {
                $oLoggerIRR    = new ULogger('Calculate IRR', $this->logPath, 'IRR.log');
                $oLoggerIRR->addRecord(ULogger::WARNING, 'Caught Exception: ' . $e->getMessage() .  ' ' . $e->getTraceAsString());
                $this->IRR = 'non calculable';
            }
        }

        $statusOk                = array(\projects_status::EN_FUNDING, \projects_status::FUNDE, \projects_status::FUNDING_KO, \projects_status::PRET_REFUSE, \projects_status::REMBOURSEMENT, \projects_status::REMBOURSE, \projects_status::REMBOURSEMENT_ANTICIPE);
        $statusKo                = array(\projects_status::PROBLEME, \projects_status::RECOUVREMENT, \projects_status::DEFAUT, \projects_status::PROBLEME_J_X, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE);
        $this->projectsPublished = $this->projects->countProjectsSinceLendersubscription($this->clients->id_client, array_merge($statusOk, $statusKo));
        $this->problProjects     = $this->projects->countProjectsByStatusAndLender($this->lenders_accounts->id_lender_account, $statusKo);
        $this->totalProjects     = $this->loans->getProjectsCount($this->lenders_accounts->id_lender_account);
    }

    public function _contratPdf()
    {
        $this->loadGestionData();
        $iloan = $this->params[1];

        $this->clients->get($this->params[0], 'hash');
        $this->clients_adresses->get($this->clients->id_client, 'id_client');
        $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');
        $this->loans->get($iloan, 'id_loan');
        $this->projects->get($this->loans->id_project, 'id_project');

        $oCommandPdf = new Command('pdf', 'contrat', $this->params, $this->language);
        $oPdf        = new pdfController($oCommandPdf, $this->Config, 'default');
        $oPdf->_contrat();
    }

    public function _creancesPdf()
    {
        $this->loadGestionData();
        $iloan = $this->params[1];

        $this->clients->get($this->params[0], 'hash');
        $this->clients_adresses->get($this->clients->id_client, 'id_client');
        $this->lenders_accounts->get($this->client->id_client, 'id_client_owner');
        $this->loans->get($iloan, 'id_loan');

        $oCommandPdf             = new Command('pdf', 'declaration_de_creances', array($this->clients->hash, $iloan), $this->language);
        $oPdf                    = new pdfController($oCommandPdf, $this->Config, 'default');
        $oPdf->clients           = $this->clients;
        $oPdf->projects          = $this->projects;
        $oPdf->oLenders_accounts = $this->lenders_accounts;
        $oPdf->clients_adresses  = $this->clients_adresses;
        $oPdf->params            = $this->params;
        $oPdf->companies         = $this->companies;
        $oPdf->_declaration_de_creances();
    }

    public function _control_fiscal_city()
    {
        $this->loadJs('default/jquery-ui-1.10.3.custom.min');

        /** @var lenders_accounts $oLenders */
        $oLenders = $this->loadData('lenders_accounts');
        $this->aLenders = $oLenders->getLendersToMatchCity(200);
    }

    public function _control_birth_city()
    {
        $this->loadJs('default/jquery-ui-1.10.3.custom.min');

        /** @var lenders_accounts $oLenders */
        $oLenders = $this->loadData('lenders_accounts');
        $this->aLenders = $oLenders->getLendersToMatchBirthCity(200);
    }
}
