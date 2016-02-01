<?php

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
        $this->clients = $this->loadData('clients');

        if (isset($_POST['form_search_preteur'])) {
            $nonValide       = (isset($_POST['nonValide']) && $_POST['nonValide'] != false) ? 1 : '';
            $this->lPreteurs = $this->clients->searchPreteurs($_POST['id'], $_POST['nom'], $_POST['email'], $_POST['prenom'], $_POST['raison_sociale'], $nonValide);
            $_SESSION['freeow']['title']   = 'Recherche d\'un prêteur';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        } else {
            $this->lPreteurs = $this->clients->searchPreteurs('', '', '', '', '', null, '', '0', '300');
        }

        $iOriginForUserHistory = 1;

        if (isset($this->params[0]) && $this->params[0] == 'status') {
            $this->changeClientStatus($this->params[1], $this->params[2], $iOriginForUserHistory);
            $oClientsStatusHistory = $this->loadData('clients_status_history');
            $oClientsStatusHistory->addStatus($_SESSION['user']['id_user'], ($this->params[2] == \clients::STATUS_OFFLINE) ? \clients_status::CLOSED_BY_UNILEND : \clients_status::VALIDATED, $this->clients->id_client);
            header('Location: ' . $this->lurl . '/preteurs/gestion');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'deactivate') {
            $this->changeClientStatus($this->params[1], $this->params[2], $iOriginForUserHistory);
            $this->sendEmailClosedAccount();
            $oClientsStatusHistory = $this->loadData('clients_status_history');
            $oClientsStatusHistory->addStatus($_SESSION['user']['id_user'], \clients_status::CLOSED_LENDER_REQUEST, $this->clients->id_client);
            header('Location: ' . $this->lurl . '/preteurs/gestion');
            die;
        }

        //preteur sans mouvement
        $aTransactionTypes = array(\transactions_types::TYPE_LENDER_SUBSCRIPTION,
            \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
            \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
            \transactions_types::TYPE_LENDER_REPAYMENT,
            \transactions_types::TYPE_LENDER_WITHDRAWAL,
            \transactions_types::TYPE_DIRECT_DEBIT,
            \transactions_types::TYPE_LENDER_REGULATION
        );
        $this->z = count($this->clients->selectPreteursByStatus(\clients_status::VALIDATED, 'c.status = 1 AND status_inscription_preteur = 1 AND (SELECT COUNT(t.id_transaction) FROM transactions t WHERE t.type_transaction IN (' . implode(',', $aTransactionTypes) . ') AND t.status = 1 AND t.etat = 1 AND t.id_client = c.id_client) < 1'));
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
        $this->projects = $this->loadData('projects');

        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->lenders_accounts->get($this->params[0], 'id_lender_account');

        $this->clients = $this->loadData('clients');
        $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');

        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->clients_adresses->get($this->clients->id_client, 'id_client');

        $this->companies = $this->loadData('companies');
        if (in_array($this->clients->type, array(clients::TYPE_LEGAL_ENTITY, clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
            $this->companies->get($this->lenders_accounts->id_company_owner, 'id_company');
        }

        // le nombre de prets effectué
        $this->loans    = $this->loadData('loans');
        $this->nb_pret  = $this->loans->counter('id_lender = "' . $this->lenders_accounts->id_lender_account . '" AND status = 0');
        $this->txMoyen  = $this->loans->getAvgPrets('id_lender = "' . $this->lenders_accounts->id_lender_account . '" AND status = 0');
        $this->sumPrets = $this->loans->sumPrets($this->lenders_accounts->id_lender_account);

        if (isset($this->params[1])) {
            $this->lEncheres = $this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND YEAR(added) = ' . $this->params[1] . ' AND status = 0');
        } else {
            $this->lEncheres = $this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND YEAR(added) = YEAR(CURDATE()) AND status = 0');
        }

        $this->wallets_lines  = $this->loadData('wallets_lines');
        $this->SumDepot       = $this->wallets_lines->getSumDepot($this->lenders_accounts->id_lender_account, '10,30');
        $this->SumInscription = $this->wallets_lines->getSumDepot($this->lenders_accounts->id_lender_account, '10');

        $this->echeanciers    = $this->loadData('echeanciers');
        $this->sumRembInte    = $this->echeanciers->getSumRemb($this->lenders_accounts->id_lender_account, 'interets');
        $this->nextRemb       = $this->echeanciers->getNextRemb($this->lenders_accounts->id_lender_account);
        $sumRembMontant       = $this->echeanciers->getSumRembV2($this->lenders_accounts->id_lender_account);
        $this->sumRembMontant = $sumRembMontant['montant'];

        $this->bids           = $this->loadData('bids');
        $this->avgPreteur     = $this->bids->getAvgPreteur($this->lenders_accounts->id_lender_account, 'amount', '1,2');
        $this->sumBidsEncours = $this->bids->sumBidsEncours($this->lenders_accounts->id_lender_account);
        $this->lBids          = $this->bids->select('id_lender_account = ' . $this->lenders_accounts->id_lender_account . ' AND status = 0', 'added DESC');
        $this->NbBids         = count($this->lBids);

        $this->clients_mandats = $this->loadData('clients_mandats');
        $this->clients_mandats->get($this->clients->id_client, 'id_client');

        $this->attachment       = $this->loadData('attachment');
        $this->attachment_type  = $this->loadData('attachment_type');
        $this->attachments      = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);
        $this->aAttachmentTypes = $this->attachment_type->getAllTypesForLender();

        //// transactions mouvements ////
        $this->lng['profile']                           = $this->ln->selectFront('preteur-profile', $this->language, $this->App);
        $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);

        $year = date('Y');

        $this->transactions = $this->loadData('transactions');
        $this->solde        = $this->transactions->getSolde($this->clients->id_client);
        $this->soldeRetrait = $this->transactions->sum('status = 1 AND etat = 1 AND transaction = 1 AND type_transaction = '. \transactions_types::TYPE_LENDER_WITHDRAWAL .' AND id_client = ' . $this->clients->id_client, 'montant');
        $this->soldeRetrait = str_replace('-', '', $this->soldeRetrait / 100);
        $this->lTrans       = $this->transactions->select('type_transaction IN (1,3,4,5,7,8,14,16,17,19,20,22,23) AND status = 1 AND etat = 1 AND id_client = ' . $this->clients->id_client . ' AND YEAR(date_transaction) = ' . $year, 'added DESC');

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

        $this->getMessageAboutClientStatus();
    }

    public function _edit_preteur()
    {
        $this->loadJs('default/jquery-ui-1.10.3.custom.min');

        $this->clients_mandats         = $this->loadData('clients_mandats');
        $this->nationalites            = $this->loadData('nationalites_v2');
        $this->lNatio                  = $this->nationalites->select();
        $this->pays                    = $this->loadData('pays_v2');
        $this->lPays                   = $this->pays->select('', 'ordre ASC');
        $this->nationalites            = $this->loadData('nationalites_v2');
        $this->pays                    = $this->loadData('pays_v2');
        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
        $this->lNatio                  = $this->nationalites->select();
        $this->lPays                   = $this->pays->select('', 'ordre ASC');

        $lElements = $this->blocs_elements->select('id_bloc = 9 AND id_langue = "' . $this->language . '"');
        foreach ($lElements as $b_elt) {
            $this->elements->get($b_elt['id_element']);
            $this->completude_wording[$this->elements->slug] = $b_elt['value'];
        }
        $this->nbWordingCompletude = count($this->completude_wording);

        $this->settings->get("Liste deroulante conseil externe de l'entreprise", 'type');
        $this->conseil_externe = $this->ficelle->explodeStr2array($this->settings->value);

        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->lenders_accounts->get($this->params[0], 'id_lender_account');

        $this->clients = $this->loadData('clients');
        $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');

        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->clients_adresses->get($this->clients->id_client, 'id_client');

        $this->companies = $this->loadData('companies');

        if (in_array($this->clients->type, array(clients::TYPE_LEGAL_ENTITY, clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
            $this->companies->get($this->lenders_accounts->id_company_owner, 'id_company');

            $this->meme_adresse_fiscal = $this->companies->status_adresse_correspondance;
            $this->adresse_fiscal      = $this->companies->adresse1;
            $this->city_fiscal         = $this->companies->city;
            $this->zip_fiscal          = $this->companies->zip;

            $this->settings->get("Liste deroulante origine des fonds societe", 'status = 1 AND type');
            $this->origine_fonds = $this->settings->value;
            $this->origine_fonds = explode(';', $this->origine_fonds);

        } else {
            // Adresse fiscal
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

        if ($this->clients->telephone != '') {
            trim(chunk_split($this->clients->telephone, 2, ' '));
        }
        if ($this->companies->phone != '') {
            $this->companies->phone = trim(chunk_split($this->companies->phone, 2, ' '));
        }
        if ($this->companies->phone_dirigeant != '') {
            $this->companies->phone_dirigeant = trim(chunk_split($this->companies->phone_dirigeant, 2, ' '));
        }

        $this->clients_status = $this->loadData('clients_status');
        $this->clients_status->getLastStatut($this->clients->id_client);

        $this->clients_status_history   = $this->loadData('clients_status_history');
        $this->oClientsStatusForHistory = $this->loadData('clients_status');
        $aLenderStatusForQuery          = array(
            \clients_status::TO_BE_CHECKED,
            \clients_status::COMPLETENESS,
            \clients_status::COMPLETENESS_REPLY,
            \clients_status::MODIFICATION,
            \clients_status::VALIDATED,
            \clients_status::CLOSED_LENDER_REQUEST,
            \clients_status::CLOSED_BY_UNILEND
        );
        $this->lActions                 = $this->clients_status_history->select('id_client = ' . $this->clients->id_client . ' AND id_client_status IN ( SELECT cs.id_client_status FROM  clients_status cs WHERE cs.status IN (' . implode(',', $aLenderStatusForQuery) . '))', 'added DESC');

        $this->getMessageAboutClientStatus();

        $this->attachment       = $this->loadData('attachment');
        $this->attachment_type  = $this->loadData('attachment_type');
        $this->attachments      = $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);
        $this->aAttachmentTypes = $this->attachment_type->getAllTypesForLender();

        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
        $this->lAcceptCGV              = $this->acceptations_legal_docs->select('id_client = ' . $this->clients->id_client);

        $this->lAcceptCGV = $this->acceptations_legal_docs->select('id_client = ' . $this->clients->id_client);

        if (isset($_POST['send_completude'])) {
            $this->sendCompletenessRequest();
            $this->clients_status_history->addStatus($_SESSION['user']['id_user'], \clients_status::COMPLETENESS, $this->clients->id_client, utf8_encode($_SESSION['content_email_completude'][ $this->clients->id_client ]));

            unset($_SESSION['content_email_completude'][$this->clients->id_client]);

            $_SESSION['email_completude_confirm'] = true;

            header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account);
            die;
        } elseif (isset($_POST['send_edit_preteur'])) {
            if (in_array($this->clients->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER))) {
                ////////////////////////////////////
                // On verifie meme adresse ou pas //
                ////////////////////////////////////
                if ($_POST['meme-adresse'] != false) {
                    $this->clients_adresses->meme_adresse_fiscal = 1;
                } else {
                    $this->clients_adresses->meme_adresse_fiscal = 0;
                }
                // adresse fiscal
                $this->clients_adresses->adresse_fiscal = $_POST['adresse'];
                $this->clients_adresses->ville_fiscal   = $_POST['ville'];
                $this->clients_adresses->cp_fiscal      = $_POST['cp'];
                $this->clients_adresses->id_pays_fiscal = $_POST['id_pays_fiscal'];

                // pas la meme
                if ($this->clients_adresses->meme_adresse_fiscal == 0) {
                    $this->clients_adresses->adresse1 = $_POST['adresse2'];
                    $this->clients_adresses->ville    = $_POST['ville2'];
                    $this->clients_adresses->cp       = $_POST['cp2'];
                    $this->clients_adresses->id_pays  = $_POST['id_pays'];
                } else {
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
                $aLenderStatusForQuery = array(
                    \clients_status::TO_BE_CHECKED,
                    \clients_status::COMPLETENESS,
                    \clients_status::COMPLETENESS_REMINDER,
                    \clients_status::COMPLETENESS_REPLY,
                    \clients_status::MODIFICATION,
                    \clients_status::VALIDATED,
                    \clients_status::CLOSED_LENDER_REQUEST,
                    \clients_status::CLOSED_BY_UNILEND
                );
                $checkEmailExistant    = $this->clients->selectPreteursByStatus(implode(',', $aLenderStatusForQuery), 'email = "' . $_POST['email'] . '" AND id_client != ' . $this->clients->id_client);
                if (count($checkEmailExistant) > 0) {
                    $les_id_client_email_exist = '';
                    foreach ($checkEmailExistant as $checkEmailEx) {
                        $les_id_client_email_exist .= ' ' . $checkEmailEx['id_client'];
                    }

                    $_SESSION['error_email_exist'] = 'Impossible de modifier l\'adresse email. Cette adresse est déjà utilisé par le compte id ' . $les_id_client_email_exist;
                } else {
                    $this->clients->email = $_POST['email'];
                }

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
                if ($this->lenders_accounts->origine_des_fonds == '1000000') {
                    $this->lenders_accounts->precision = $_POST['preciser'];
                } else {
                    $this->lenders_accounts->precision = '';
                }

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
                    if ($this->clients_mandats->get($this->clients->id_client, 'id_client')) {
                        $create = false;
                    } else {
                        $create = true;
                    }

                    $this->upload->setUploadDir($this->path, 'protected/pdf/mandat/');
                    if ($this->upload->doUpload('mandat')) {
                        if ($this->clients_mandats->name != '') {
                            @unlink($this->path . 'protected/pdf/mandat/' . $this->clients_mandats->name);
                        }
                        $this->clients_mandats->name          = $this->upload->getName();
                        $this->clients_mandats->id_client     = $this->clients->id_client;
                        $this->clients_mandats->id_universign = 'no_universign';
                        $this->clients_mandats->url_pdf       = '/pdf/mandat/' . $this->clients->hash . '/';
                        $this->clients_mandats->status        = \clients_mandats::STATUS_SIGNED;

                        if ($create == true) {
                            $this->clients_mandats->create();
                        } else {
                            $this->clients_mandats->update();
                        }
                    }
                }

                $this->lenders_accounts->exonere = $_POST['exonere'];

                /////////////////////////// EXONERATION MISE A JOUR SUR LES ECHEANCES ////////////////////////////////////////

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
                $this->lenders_imposition_history->exonere           = $this->lenders_accounts->exonere;
                $this->lenders_imposition_history->resident_etranger = $this->etranger;
                $this->lenders_imposition_history->id_pays           = $this->clients_adresses->id_pays;
                $this->lenders_imposition_history->id_user           = $_SESSION['user']['id_user'];
                // KLE,BT 17712 on ne veut plus ajouter de ligne sur la sauvegarde, seulement sur la validation du preteur
                // $this->lenders_imposition_history->create();

                if ($this->etranger == 0) {
                    // on retire les prelevements sur les futures echeances
                    if ($this->lenders_accounts->exonere == 1) {
                        if (isset($_POST['debut']) && $_POST['debut'] != '') {
                            $debut     = explode('/', $_POST['debut']);
                            $debut_exo = $debut[2] . '-' . $debut[1] . '-' . $debut[0];
                        } else {
                            $debut_exo = '';
                        }

                        if (isset($_POST['fin']) && $_POST['fin'] != '') {
                            $fin     = explode('/', $_POST['fin']);
                            $fin_exo = $fin[2] . '-' . $fin[1] . '-' . $fin[0];
                        } else {
                            $fin_exo = '';
                        }

                        $this->lenders_accounts->debut_exoneration = $debut_exo;
                        $this->lenders_accounts->fin_exoneration   = $fin_exo;

                        $this->echeanciers->update_prelevements_obligatoires($this->lenders_accounts->id_lender_account, 1, '', $debut_exo, $fin_exo);
                    } elseif (0 == $this->lenders_accounts->exonere) { // on ajoute les prelevements sur les futures echeances
                        $this->lenders_accounts->debut_exoneration = '0000-00-00';
                        $this->lenders_accounts->fin_exoneration   = '0000-00-00';

                        $this->echeanciers->update_prelevements_obligatoires($this->lenders_accounts->id_lender_account, 0, $prelevements_obligatoires);
                    }
                }
                ///////////////////////////////////////////////////////////////////

                // mise a jour
                $this->clients->update();
                $this->clients_adresses->update();
                $this->lenders_accounts->update();
                $this->lenders_accounts->getAttachments($this->lenders_accounts->id_lender_account);

                // Si on a une entreprise reliée, on la supprime car elle n'a plus rien a faire ici. on est un particulier.
                $this->companies = $this->loadData('companies');
                if ($this->companies->get($this->clients->id_client, 'id_client_owner')) {
                    $this->companies->delete($this->companies->id_company, 'id_company');
                }

                $this->clients->get($this->clients->id_client, 'id_client');

                // Histo user //
                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST, 'files' => $_FILES));
                $this->users_history->histo(3, 'modif info preteur', $_SESSION['user']['id_user'], $serialize);
                ////////////////

                if (isset($_POST['statut_valider_preteur']) && 1 == $_POST['statut_valider_preteur']) {

                    $aExistingClient       = $this->clients->getDuplicates($this->clients->nom, $this->clients->prenom, $this->clients->naissance);
                    $iOriginForUserHistory = 3;

                    if (false === empty($aExistingClient)) {
                        $this->changeClientStatus($this->clients->id_client, \clients::STATUS_OFFLINE, $iOriginForUserHistory);
                        $this->clients_status_history->addStatus($_SESSION['user']['id_user'], \clients_status::CLOSED_BY_UNILEND, $this->clients->id_client, 'Doublon avec client ID : ' . $aExistingClient[0]['id_client']);
                        header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account);
                        die;
                    } else {
                        // On check si on a deja eu le compte validé au moins une fois. si c'est pas le cas on check l'offre
                        if (0 == $this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = (SELECT cs.id_client_status FROM clients_status cs WHERE cs.status = ' . \clients_status::VALIDATED . ')')) {
                            $this->create_offre_bienvenue($this->clients->id_client);
                        }

                    $this->clients_status_history->addStatus($_SESSION['user']['id_user'], \clients_status::VALIDATED, $this->clients->id_client);

                    // gestion alert notification //
                    $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
                    $this->clients_gestion_type_notif    = $this->loadData('clients_gestion_type_notif');

                        ////// Liste des notifs //////
                        $this->lTypeNotifs = $this->clients_gestion_type_notif->select();
                        $this->lNotifs     = $this->clients_gestion_notifications->select('id_client = ' . $this->clients->id_client);

                    if (false == $this->lNotifs ) {
                        foreach ($this->lTypeNotifs as $n) {
                            $this->clients_gestion_notifications->id_client = $this->clients->id_client;
                            $this->clients_gestion_notifications->id_notif  = $n['id_client_gestion_type_notif'];
                            $id_notif                                       = $n['id_client_gestion_type_notif'];
                            // immediatement
                            if (in_array($id_notif, array(3, 6, 7, 8))) {
                                $this->clients_gestion_notifications->immediatement = 1;
                            } else {
                                $this->clients_gestion_notifications->immediatement = 0;
                            }
                            // quotidienne
                            if (in_array($id_notif, array(1, 2, 4, 5))) {
                                $this->clients_gestion_notifications->quotidienne = 1;
                            } else {
                                $this->clients_gestion_notifications->quotidienne = 0;
                            }
                            // hebdomadaire
                            if (in_array($id_notif, array(1, 4))) {
                                $this->clients_gestion_notifications->hebdomadaire = 1;
                            } else {
                                $this->clients_gestion_notifications->hebdomadaire = 0;
                            }
                            // mensuelle
                            $this->clients_gestion_notifications->mensuelle = 0;
                            $this->clients_gestion_notifications->create();
                        }
                    }

                    /////////////////////////////////


                    // Mail au client particulier //
                    // Recuperation du modele de mail
                    // modif ou inscription
                    if ($this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = 5') > 0) {
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
                            'surl'    => $this->surl,
                            'url'     => $this->furl,
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

                    $this->foreignerTax($this->clients, $this->lenders_accounts, $this->clients_adresses);

                    $_SESSION['compte_valide'] = true;
                }

                if (isset($_POST['valider_pays']) && '' !== $_POST['valider_pays']) {
                    $this->foreignerTax($this->clients, $this->lenders_accounts, $this->clients_adresses);
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

                $this->companies->tribunal_com = $_POST['tribunal_com'];

                    ////////////////////////////////////
                    // On verifie meme adresse ou pas //
                    ////////////////////////////////////
                    if ($_POST['meme-adresse'] != false) {
                        $this->companies->status_adresse_correspondance = '1';
                    } else {
                        $this->companies->status_adresse_correspondance = '0';
                    }
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
                    } else {
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

                    $aLenderStatusForQuery = array(
                        \clients_status::TO_BE_CHECKED,
                        \clients_status::COMPLETENESS,
                        \clients_status::COMPLETENESS_REMINDER,
                        \clients_status::COMPLETENESS_REPLY,
                        \clients_status::MODIFICATION,
                        \clients_status::VALIDATED,
                    );
                    $checkEmailExistant = $this->clients->selectPreteursByStatus(implode(',', $aLenderStatusForQuery), 'email = "' . $_POST['email_e'] . '" AND id_client != ' . $this->clients->id_client);
                    if (count($checkEmailExistant) > 0) {
                        $les_id_client_email_exist = '';
                        foreach ($checkEmailExistant as $checkEmailEx) {
                            $les_id_client_email_exist .= ' ' . $checkEmailEx['id_client'];
                        }

                        $_SESSION['error_email_exist'] = 'Impossible de modifier l\'adresse email. Cette adresse est déjà utilisé par le compte id ' . $les_id_client_email_exist;
                    } else {
                        $this->clients->email = $_POST['email_e'];
                    }

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
                        $iban .= strtoupper($_POST[ 'iban' . $i ]);
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
                    if ($this->clients_mandats->get($this->clients->id_client, 'id_client')) {
                        $create = false;
                    } else {
                        $create = true;
                    }

                    $this->upload->setUploadDir($this->path, 'protected/pdf/mandat/');
                    if ($this->upload->doUpload('mandat')) {
                        if ($this->clients_mandats->name != '') @unlink($this->path . 'protected/pdf/mandat/' . $this->clients_mandats->name);
                        $this->clients_mandats->name          = $this->upload->getName();
                        $this->clients_mandats->id_client     = $this->clients->id_client;
                        $this->clients_mandats->id_universign = 'no_universign';
                        $this->clients_mandats->url_pdf       = '/pdf/mandat/' . $this->clients->hash . '/';
                        $this->clients_mandats->status        = \clients_mandats::STATUS_SIGNED;

                        if ($create == true) {
                            $this->clients_mandats->create();
                        } else {
                            $this->clients_mandats->update();
                        }
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
                    $this->clients_status_history->addStatus($_SESSION['user']['id_user'], \clients_status::VALIDATED, $this->clients->id_client);

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
                        if ($this->clients_status_history->counter('id_client = ' . $this->clients->id_client . ' AND id_client_status = (SELECT cs.id_client_status FROM clients_status cs WHERE cs.status = '. \clients_status::MODIFICATION .')') > 0) {
                            $this->mails_text->get('preteur-validation-modification-compte', 'lang = "' . $this->language . '" AND type');
                        } else {
                            $this->create_offre_bienvenue($this->clients->id_client);
                            $this->mails_text->get('preteur-confirmation-activation', 'lang = "' . $this->language . '" AND type');
                        }

                        $this->settings->get('Facebook', 'type');
                        $lien_fb = $this->settings->value;

                        $this->settings->get('Twitter', 'type');
                        $lien_tw = $this->settings->value;

                        $varMail = array(
                            'surl'    => $this->surl,
                            'url'     => $this->furl,
                            'prenom'  => $this->clients->prenom,
                            'projets' => $this->furl . '/projets-a-financer',
                            'lien_fb' => $lien_fb,
                            'lien_tw' => $lien_tw
                        );
                        $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                        $this->email = $this->loadLib('email');
                        $this->email->setFrom($this->mails_text->exp_email, strtr(utf8_decode($this->mails_text->exp_name), $tabVars));
                        $this->email->setSubject(stripslashes(strtr(utf8_decode($this->mails_text->subject), $tabVars)));
                        $this->email->setHTMLBody(stripslashes(strtr(utf8_decode($this->mails_text->content), $tabVars)));

                    if ($this->Config['env'] === 'prod') {
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($this->clients->email));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }

                    $_SESSION['compte_valide'] = true;

                        if ($this->Config['env'] === 'prod') {
                            Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                            $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                        } else {
                            $this->email->addRecipient(trim($this->clients->email));
                            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        }
                        $_SESSION['compte_valide'] = true;
                    }
                    header('Location: ' . $this->lurl . '/preteurs/edit_preteur/' . $this->lenders_accounts->id_lender_account);
                    die;
                }
            }
        }
    }

    public function _liste_preteurs_non_inscrits()
    {
        $this->loadGestionData();

        $nonValide = 2;
        $this->clients = $this->loadData('clients');

        if (isset($_POST['form_search_preteur'])) {
            // Recuperation de la liste des clients searchPreteurs
            $this->lPreteurs = $this->clients->searchPreteurs($_POST['id'], $_POST['nom'], $_POST['email'], $_POST['prenom'], $_POST['raison_sociale'], $nonValide);

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Recherche d\'un prêteur non inscript';
            $_SESSION['freeow']['message'] = 'La recherche est termin&eacute;e !';
        } else {
            // On recupera les 10 derniers clients
            $this->lPreteurs = $this->clients->searchPreteurs('', '', '', '', '', $nonValide, '', '0', '300');
        }

        if (isset($this->params[0]) && $this->params[0] == 'status') {
            $iOriginForUserHistory = 12;

            $this->changeClientStatus(
                $this->params[1],
                $this->params[2],
                (($this->params[2] == \clients::STATUS_OFFLINE) ? \clients_status::CLOSED_BY_UNILEND : \clients_status::TO_BE_CHECKED),
                $iOriginForUserHistory
            );
            header('Location: ' . $this->lurl . '/preteurs/gestion');
            die;
        }
    }

    public function _activation()
    {
        $this->clients      = $this->loadData('clients');
        $this->transactions = $this->loadData('transactions');
        $this->companies    = $this->loadData('companies');

        $aStatusNotValidated = array(
            \clients_status::TO_BE_CHECKED,
            \clients_status::COMPLETENESS,
            \clients_status::COMPLETENESS_REMINDER,
            \clients_status::COMPLETENESS_REPLY,
            \clients_status::MODIFICATION
        );

        $this->lPreteurs     = $this->clients->selectPreteursByStatus(implode(',', $aStatusNotValidated), '', 'added_status DESC');
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

        $this->settings->get('Facebook', 'type');
        $lien_fb = $this->settings->value;

        $this->settings->get('Twitter', 'type');
        $lien_tw = $this->settings->value;

        $lapage         = (in_array($this->clients->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER))) ? 'particulier_doc' : 'societe_doc';
        $this->lActions = $this->clients_status_history->select('id_client = ' . $this->clients->id_client, 'added DESC');
        $timeCreate     = (false === empty($this->lActions[0]['added'])) ? strtotime($this->lActions[0]['added']) : strtotime($this->clients->added);
        $month          = $this->dates->tableauMois['fr'][ date('n', $timeCreate) ];

        $varMail = array(
            'furl'          => $this->furl,
            'surl'          => $this->surl,
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
        } else {
            $create = true;
        }

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

                if ($create == false) {
                    $offres_bienvenues->update();
                } else {
                    $offres_bienvenues->id_offre_bienvenue = $offres_bienvenues->create();
                }

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
                    // Motif
                    $this->settings->get("Offre de bienvenue motif", 'type');
                    $this->motifOffreBienvenue = $this->settings->value;

                    // Lender
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

                    // EMAIL //
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
            $this->attachmentHelper = $this->loadLib('attachment_helper', array($this->attachment, $this->attachment_type, $this->path));
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
        if (isset($_POST['send_dates'])) {
            $_SESSION['FilterMails']['StartDate'] = $_POST['debut'];
            $_SESSION['FilterMails']['EndDate']   = $_POST['fin'];

            header('Location: ' . $this->lurl . '/preteurs/email_history/' . $this->params[0]);
            die;
        }

        $this->clients                = $this->loadData('clients');
        $this->lenders_accounts       = $this->loadData('lenders_accounts');

        $this->lenders_accounts->get($this->params[0], 'id_lender_account');
        $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');

        $oClientsNotifications       = $this->loadData('clients_gestion_notifications');
        $oTypesOfClientNotifications = $this->loadData('clients_gestion_type_notif');
        $this->aTypesOfNotifications = $oTypesOfClientNotifications->select();
        $this->aClientsNotifications = $oClientsNotifications->getNotifs($this->clients->id_client);

        if (isset($_SESSION['FilterMails'])) {
            $oDateTimeStart = \DateTime::createFromFormat('d/m/Y', $_SESSION['FilterMails']['StartDate']);
            $oDateTimeEnd   = \DateTime::createFromFormat('d/m/Y', $_SESSION['FilterMails']['EndDate']);

            unset($_SESSION['FilterMails']);
        } else {
            $oDateTimeStart = new \DateTime('NOW - 1 year');
            $oDateTimeEnd   = new \DateTime('NOW');
        }

        $this->sDisplayDateTimeStart = $oDateTimeStart->format('d/m/Y');
        $this->sDisplayDateTimeEnd   = $oDateTimeEnd->format('d/m/Y');
        $sStartDate                  = $oDateTimeStart->format('Y-m-d');
        $sEndDate                    = $oDateTimeEnd->format('Y-m-d');

        $oMailsFiler               = $this->loadData('mails_filer');
        $this->aEmailsSentToClient = $oMailsFiler->getListOfEmails($this->clients->email, $sStartDate, $sEndDate);
        $this->getMessageAboutClientStatus();
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

        $this->lSumLoans               = $this->loans->getSumLoansByProject($this->lenders_accounts->id_lender_account, 'next_echeance ASC');
        $this->arrayDeclarationCreance = $this->projects->getProjectsInDebt();

        $oLenderAccountStats = $this->loadData('lenders_account_stats');
        $aIRR                = $oLenderAccountStats->getLastIRRForLender($this->lenders_accounts->id_lender_account);

        if ($aIRR) {
            $this->IRRValue = $aIRR['tri_value'];
            $this->IRRDate = $aIRR['tri_date'];
        }

        $statusOk                = array(\projects_status::EN_FUNDING, \projects_status::FUNDE, \projects_status::FUNDING_KO, \projects_status::PRET_REFUSE, \projects_status::REMBOURSEMENT, \projects_status::REMBOURSE, \projects_status::REMBOURSEMENT_ANTICIPE);
        $statusKo                = array(\projects_status::PROBLEME, \projects_status::RECOUVREMENT, \projects_status::DEFAUT, \projects_status::PROBLEME_J_X, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE);
        $this->projectsPublished = $this->projects->countProjectsSinceLendersubscription($this->clients->id_client, array_merge($statusOk, $statusKo));
        $this->problProjects     = $this->projects->countProjectsByStatusAndLender($this->lenders_accounts->id_lender_account, $statusKo);
        $this->totalProjects     = $this->loans->getProjectsCount($this->lenders_accounts->id_lender_account);

        $this->getMessageAboutClientStatus();
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
        $oLenders       = $this->loadData('lenders_accounts');
        $this->aLenders = $oLenders->getLendersToMatchCity(200);
    }

    public function _control_birth_city()
    {
        $this->loadJs('default/jquery-ui-1.10.3.custom.min');

        /** @var lenders_accounts $oLenders */
        $oLenders       = $this->loadData('lenders_accounts');
        $this->aLenders = $oLenders->getLendersToMatchBirthCity(200);
    }

    public function _email_history_preview()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->oMail = $this->loadData('mails_filer');
        $this->oMail->get($this->params[0]);
    }

    public function _email_history_preview_iframe()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;

        $this->oMail = $this->loadData('mails_filer');
        $this->oMail->get($this->params[0]);

        echo stripslashes($this->oMail->content);
        $this->autoFireView = false;
    }

    private function foreignerTax($oClients, $oLendersAccounts, $oClientsAdresses)
    {
        // EQ-Acompte d'impôt sur le revenu
        $this->settings->get("EQ-Acompte d'impôt sur le revenu", 'type');
        $sPrelevementsObligatoires = $this->settings->value;

        // EQ-Contribution additionnelle au Prélèvement Social
        $this->settings->get('EQ-Contribution additionnelle au Prélèvement Social', 'type');
        $sContributionsAdditionnelles = $this->settings->value;

        // EQ-CRDS
        $this->settings->get('EQ-CRDS', 'type');
        $sCrds = $this->settings->value;

        // EQ-CSG
        $this->settings->get('EQ-CSG', 'type');
        $sCsg = $this->settings->value;

        // EQ-Prélèvement de Solidarité
        $this->settings->get('EQ-Prélèvement de Solidarité', 'type');
        $sPrelevementsSolidarite = $this->settings->value;

        // EQ-Prélèvement social
        $this->settings->get('EQ-Prélèvement social', 'type');
        $sPrelevementsSociaux = $this->settings->value;

        // EQ-Retenue à la source
        $this->settings->get('EQ-Retenue à la source', 'type');
        $sRetenuesSource = $this->settings->value;

        $iForeigner = 0;
        // fr/resident etranger
        if ($oClients->id_nationalite <= 1 && $oClientsAdresses->id_pays_fiscal > 1) {
            $iForeigner = 1;
        } // no fr/resident etranger
        elseif ($oClients->id_nationalite > 1 && $oClientsAdresses->id_pays_fiscal > 1) {
            $iForeigner = 2;
        }

        // On garde une trace de l'action
        $oLendersImpositionHistory = $this->loadData('lenders_imposition_history');

        $oLendersImpositionHistory->id_lender         = $oLendersAccounts->id_lender_account;
        $oLendersImpositionHistory->exonere           = $oLendersAccounts->exonere;
        $oLendersImpositionHistory->resident_etranger = $iForeigner;
        $oLendersImpositionHistory->id_pays           = $oClientsAdresses->id_pays_fiscal;
        $oLendersImpositionHistory->id_user           = $_SESSION['user']['id_user'];
        $oLendersImpositionHistory->create();

        $tabImpo = array(
            'prelevements_obligatoires'    => $sPrelevementsObligatoires,
            'contributions_additionnelles' => $sContributionsAdditionnelles,
            'crds'                         => $sCrds,
            'csg'                          => $sCsg,
            'prelevements_solidarite'      => $sPrelevementsSolidarite,
            'prelevements_sociaux'         => $sPrelevementsSociaux,
            'retenues_source'              => $sRetenuesSource
        );

        $this->loadData('echeanciers')->update_imposition_etranger($oLendersAccounts->id_lender_account, $iForeigner, $tabImpo, $oLendersAccounts->exonere, $oLendersAccounts->debut_exoneration, $oLendersAccounts->fin_exoneration);
    }

    public function _modify_passed_repaymet_schedule()
    {
        $aMatch                  = array();
        $aNonMatch               = array();
        $this->aChangesRepayment = array();
        $aFieldCanBeModified     = array(
            'montant',
            'capital',
            'interets',
            'commission',
            'tva',
            'prelevements_obligatoires',
            'retenues_source',
            'csg',
            'prelevements_sociaux',
            'contributions_additionnelles',
            'prelevements_solidarite',
            'crds'
        );

        if (isset($_FILES['echeances_csv']) && 'text/csv' === $_FILES['echeances_csv']['type']) {
            $rFile = fopen($_FILES['echeances_csv']['tmp_name'], 'r');
            if ($aKeys = fgetcsv($rFile, 0, ';')) {
                /** @var echeanciers $oEcheanciers */
                $oEcheanciers = $this->loadData('echeanciers');
                /** @var transactions $oTransactionsLender */
                $oTransactionsLender = $this->loadData('transactions');
                /** @var wallets_lines $oWalletLine */
                $oWalletLine = $this->loadData('wallets_lines');
                /** @var echeanciers_emprunteur $oEcheanciersEmprunteur */
                $oEcheanciersEmprunteur = $this->loadData('echeanciers_emprunteur');
                /** @var transactions $oTransactionsUnilend */
                $oTransactionsUnilend = $this->loadData('transactions');
                /** @var bank_unilend $oBankUnilend */
                $oBankUnilend = $this->loadData('bank_unilend');
                while (($aRow = fgetcsv($rFile, 0, ';')) !== false) {
                    $oEcheanciers->unsetData();
                    $oTransactionsLender->unsetData();
                    $oWalletLine->unsetData();
                    $oEcheanciersEmprunteur->unsetData();
                    $oTransactionsUnilend->unsetData();
                    $oBankUnilend->unsetData();

                    $aRepayment = array_combine($aKeys, $aRow);
                    if ('' === $aRepayment['id_echeancier']
                        || false === $oEcheanciers->get($aRepayment['id_echeancier'])
                        || $oEcheanciers->id_lender != $aRepayment['id_lender']
                        || $oEcheanciers->id_project != $aRepayment['id_project']
                        || $oEcheanciers->id_loan != $aRepayment['id_loan']
                    ) {
                        $aNonMatch[] = $aRepayment;
                        continue;
                    }
                    $aRepaymentOld = array_shift($oEcheanciers->selectEcheances_a_remb('id_echeancier = ' . $aRepayment['id_echeancier']));

                    foreach ($aRepayment as $sKey => &$sValue) {
                        if (false === in_array($sKey, $aFieldCanBeModified)) {
                            continue;
                        }
                        $sValue                     = str_replace(',', '.', $sValue);
                        $aRepayment[$sKey . '_old'] = $oEcheanciers->$sKey;
                        $oEcheanciers->$sKey        = $sValue;
                        $aMatch[$aRepayment['id_echeancier']] = $aRepayment;
                    }
                    $oEcheanciers->update();

                    $aRepaymentNew = array_shift($oEcheanciers->selectEcheances_a_remb('id_echeancier = ' . $aRepayment['id_echeancier']));

                    $iRepaymentNetDiff = ($aRepaymentNew['rembNet'] - $aRepaymentOld['rembNet']) * 100;
                    $iRepaymentTaxDiff = ($aRepaymentNew['etat'] - $aRepaymentOld['etat']) * 100;

                    if (0 != $iRepaymentNetDiff) {
                        // Update transaction type "Remboursement prêteur"
                        if (false === $oTransactionsLender->get($aRepayment['id_echeancier'], 'type_transaction = 5 AND id_echeancier')) {
                            $aTransaction = array_shift($oTransactionsLender->select('type_transaction = 5 AND id_echeancier = ' . $aRepayment['id_echeancier']));
                            if (null === $aTransaction || false === $oTransactionsLender->get($aTransaction['id_transaction'])) {
                                //Rollback repayment
                                $this->rollbackRepayment($aRepayment, $oEcheanciers, $aFieldCanBeModified);
                                $aNonMatch[] = $aRepayment;
                                unset($aMatch[$aRepayment['id_echeancier']]);
                                continue;
                            }
                        }
                        $this->addLogChangesSchedule($aRepayment['id_echeancier'], 'transactions', $oTransactionsLender->id_transaction, 'montant', $oTransactionsLender->montant . ' + (' . $iRepaymentNetDiff . ')');

                        $oTransactionsLender->montant += $iRepaymentNetDiff;
                        $oTransactionsLender->update();


                        // Update lender's wallet line
                        if (false === $oWalletLine->get($oTransactionsLender->id_transaction, 'id_transaction')) {
                            $this->rollbackRepayment($aRepayment, $oEcheanciers, $aFieldCanBeModified);
                            $this->rollbackTransactionLender($oTransactionsLender, $iRepaymentNetDiff);
                            $this->removeLogChangesSchedule($aRepayment['id_echeancier']);
                            $aNonMatch[] = $aRepayment;
                            unset($aMatch[$aRepayment['id_echeancier']]);
                            continue;
                        }
                        $this->addLogChangesSchedule($aRepayment['id_echeancier'], 'wallets_lines', $oWalletLine->id_wallet_line, 'amount', $oWalletLine->amount . ' + (' . $iRepaymentNetDiff . ')');

                        $oWalletLine->amount += $iRepaymentNetDiff;
                        $oWalletLine->update();
                    }

                    if (0 != $iRepaymentNetDiff || 0 != $iRepaymentTaxDiff) {
                        // Update transaction type "Remboursement Unilend"
                        $aPaymentSchedule = array_shift($oEcheanciersEmprunteur->select('id_project = ' . $aRepayment['id_project'] . ' AND ordre = ' . $aRepayment['ordre']));
                        if (null === $aPaymentSchedule) {
                            $this->rollbackRepayment($aRepayment, $oEcheanciers, $aFieldCanBeModified);
                            $this->rollbackTransactionLender($oTransactionsLender, $iRepaymentNetDiff);
                            $this->rollbackWalletLine($oWalletLine, $iRepaymentNetDiff);
                            $this->removeLogChangesSchedule($aRepayment['id_echeancier']);
                            $aNonMatch[] = $aRepayment;
                            unset($aMatch[$aRepayment['id_echeancier']]);
                            continue;
                        }
                        if (false === $oTransactionsUnilend->get($aPaymentSchedule['id_echeancier_emprunteur'], 'type_transaction = 10 AND id_echeancier_emprunteur')) {
                            $aTransactionUnilend = array_shift($oTransactionsUnilend->select('type_transaction = 10 AND id_echeancier_emprunteur = ' . $aPaymentSchedule['id_echeancier_emprunteur']));
                            if (null === $aTransactionUnilend || false === $oTransactionsUnilend->get($aTransactionUnilend['id_transaction'])) {
                                $this->rollbackRepayment($aRepayment, $oEcheanciers, $aFieldCanBeModified);
                                $this->rollbackTransactionLender($oTransactionsLender, $iRepaymentNetDiff);
                                $this->rollbackWalletLine($oWalletLine, $iRepaymentNetDiff);
                                $this->removeLogChangesSchedule($aRepayment['id_echeancier']);
                                $aNonMatch[] = $aRepayment;
                                unset($aMatch[$aRepayment['id_echeancier']]);
                                continue;
                            }
                        }
                        $this->addLogChangesSchedule($aRepayment['id_echeancier'], 'transactions', $oTransactionsUnilend->id_transaction, 'montant_unilend', $oTransactionsUnilend->montant_unilend . ' + (' . -1 * $iRepaymentNetDiff . ')');
                        $this->addLogChangesSchedule($aRepayment['id_echeancier'], 'transactions', $oTransactionsUnilend->id_transaction, 'montant_etat', $oTransactionsUnilend->montant_etat . ' + (' . $iRepaymentTaxDiff . ')');

                        $oTransactionsUnilend->montant_unilend += -1 * $iRepaymentNetDiff;
                        $oTransactionsUnilend->montant_etat    += $iRepaymentTaxDiff;
                        $oTransactionsUnilend->update();

                        // Update bank unilend type "Remboursement prêteur"
                        if (false === $oBankUnilend->get($oTransactionsUnilend->id_transaction, 'type = 2 AND id_transaction')) {
                            $aBankUnilend = array_shift($oBankUnilend->select('type = 2 and id_transaction = ' . $oTransactionsUnilend->id_transaction));
                            if (null === $aBankUnilend || false === $oBankUnilend->get($aBankUnilend['id_unilend'])) {
                                $this->rollbackRepayment($aRepayment, $oEcheanciers, $aFieldCanBeModified);
                                $this->rollbackTransactionLender($oTransactionsLender, $iRepaymentNetDiff);
                                $this->rollbackWalletLine($oWalletLine, $iRepaymentNetDiff);
                                $this->rollbackTransactionUnilend($oTransactionsUnilend, $iRepaymentNetDiff, $iRepaymentTaxDiff);
                                $this->removeLogChangesSchedule($aRepayment['id_echeancier']);
                                $aNonMatch[] = $aRepayment;
                                unset($aMatch[$aRepayment['id_echeancier']]);
                                continue;
                            }
                        }
                        $this->addLogChangesSchedule($aRepayment['id_echeancier'], 'bank_unilend', $oBankUnilend->id_unilend, 'montant', $oBankUnilend->montant . ' + (' . -1 * $iRepaymentNetDiff . ')');
                        $this->addLogChangesSchedule($aRepayment['id_echeancier'], 'bank_unilend', $oBankUnilend->id_unilend, 'etat', $oBankUnilend->etat . ' + (' . $iRepaymentTaxDiff . ')');

                        $oBankUnilend->montant += -1 * $iRepaymentNetDiff;
                        $oBankUnilend->etat += $iRepaymentTaxDiff;
                        $oBankUnilend->update();
                    }
                }
            }
            $this->aTemplateVariables = array(
                'aMatch'    => $aMatch,
                'aNonMatch' => $aNonMatch,
                'aChanges'  => $this->aChangesRepayment
            );
        }
    }

    private function rollbackRepayment($aRepayment, $oEcheanciers, $aFieldCanBeModified)
    {
        foreach ($aRepayment as $sKey => $sValue) {
            if (false === in_array($sKey, $aFieldCanBeModified)) {
                continue;
            }
            $oEcheanciers->$sKey = $aRepayment[$sKey . '_old'];
        }
        $oEcheanciers->update();
    }

    private function rollbackTransactionLender($oTransactionsLender, $iRepaymentNetDiff)
    {
        $oTransactionsLender->montant -= $iRepaymentNetDiff;
        $oTransactionsLender->update();
    }

    private function rollbackWalletLine($oWalletLine, $iRepaymentNetDiff)
    {
        $oWalletLine->amount -= $iRepaymentNetDiff;
        $oWalletLine->update();
    }

    private function rollbackTransactionUnilend($oTransactionsUnilend, $iRepaymentNetDiff, $iRepaymentTaxDiff)
    {
        $oTransactionsUnilend->montant      -= (-1 * $iRepaymentNetDiff);
        $oTransactionsUnilend->montant_etat -= $iRepaymentTaxDiff;
        $oTransactionsUnilend->update();
    }

    private function addLogChangesSchedule($iIdSchedule, $sTable, $iId, $sColumn, $sMovement)
    {
        $this->aChangesRepayment[$iIdSchedule][] = array(
            'table' => $sTable,
            'id'    => $iId,
            'column' => $sColumn,
            'movement' => $sMovement
        );
    }

    private  function removeLogChangesSchedule($iIdSchedule)
    {
        unset($this->aChangesRepayment[$iIdSchedule]);
    }

    private function changeClientStatus($iClientId, $iStatus, $iOrigin)
    {
        if (false === $this->clients->isBorrower($this->loadData('projects'), $this->loadData('companies'), $iClientId)) {
            $this->clients->get($iClientId, 'id_client');
            $this->clients->status = $iStatus;
            $this->clients->update();

            $serialize = serialize(array('id_client' => $iClientId, 'status' => $this->clients->status));
            switch ($iOrigin) {
                case 1:
                    $this->users_history->histo($iOrigin, 'status preteur', $_SESSION['user']['id_user'], $serialize);
                    $_SESSION['freeow']['title']   = 'Statut du preteur';
                    $_SESSION['freeow']['message'] = 'Le statut du preteur a bien &eacute;t&eacute; modifi&eacute; !';
                    break;
                case 3:
                    $this->users_history->histo($iOrigin, 'status offline d\'un preteur doublon', $_SESSION['user']['id_user'], $serialize);
                    $_SESSION['freeow']['title']   = 'Doublon client';
                    $_SESSION['freeow']['message'] = 'Attention, homonyme d\'un autre client. Client mis hors ligne !';
                    break;
                case 12:
                    $this->users_history->histo($iOrigin, 'status offline-online preteur non inscrit', $_SESSION['user']['id_user'], $serialize);
                    $_SESSION['freeow']['title']   = 'Statut du preteur non inscrit';
                    $_SESSION['freeow']['message'] = 'Le statut du preteur non inscrit a bien &eacute;t&eacute; modifi&eacute; !';
                    break;
            }
        } else {
            $_SESSION['freeow']['title']   = 'Statut du preteur non modifiable';
            $_SESSION['freeow']['message'] = 'Le client est &eacute;galement un emprunteur et ne peux &ecirc;tre mis hors ligne !';

            $oLendersAccounts = $this->loadData('lenders_accounts');
            $oLendersAccounts->get($iClientId, 'id_client_owner');

            header('Location: ' . $this->lurl . '/preteurs/edit/' . $oLendersAccounts->id_lender_account);
            die;
        }
    }

    private function sendEmailClosedAccount()
    {
        $this->mails_text->get('confirmation-fermeture-compte-preteur', 'lang = "' . $this->language . '" AND type');

        $this->settings->get('Facebook', 'type');
        $sFB = $this->settings->value;

        $this->settings->get('Twitter', 'type');
        $sTW = $this->settings->value;

        $aVariablesMail = array(
            'surl'    => $this->surl,
            'url'     => $this->furl,
            'prenom'  => $this->clients->prenom,
            'lien_fb' => $sFB,
            'lien_tw' => $sTW
        );
        $tabVars        = $this->tnmp->constructionVariablesServeur($aVariablesMail);

        $exp_name = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

        $this->email = $this->loadLib('email');
        $this->email->setFrom($this->mails_text->exp_email, $exp_name);
        $this->email->setSubject(stripslashes(strtr(utf8_decode($this->mails_text->subject), $tabVars)));
        $this->email->setHTMLBody(stripslashes(strtr(utf8_decode($this->mails_text->content), $tabVars)));

        if ($this->Config['env'] === 'prod') {
            Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
            $this->tnmp->sendMailNMP($tabFiler, $aVariablesMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
        } else {
            $this->email->addRecipient(trim($this->clients->email));
            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
        }
    }

    private function sendCompletenessRequest()
    {
        $this->mails_text->get('completude', 'lang = "' . $this->language . '" AND type');

        $this->settings->get('Facebook', 'type');
        $lien_fb = $this->settings->value;

        $this->settings->get('Twitter', 'type');
        $lien_tw = $this->settings->value;

        $lapage = (in_array($this->clients->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER))) ? 'particulier_doc' : 'societe_doc';

        $timeCreate = (false === empty($this->lActions[0]['added'])) ? strtotime($this->lActions[0]['added']) : strtotime($this->clients->added);
        $month      = $this->dates->tableauMois['fr'][ date('n', $timeCreate) ];

        $varMail = array(
            'furl'          => $this->furl,
            'surl'          => $this->surl,
            'prenom_p'      => $this->clients->prenom,
            'date_creation' => date('d', $timeCreate) . ' ' . $month . ' ' . date('Y', $timeCreate),
            'content'       => utf8_encode($_SESSION['content_email_completude'][$this->clients->id_client]),
            'lien_upload'   => $this->furl . '/profile/' . $lapage,
            'lien_fb'       => $lien_fb,
            'lien_tw'       => $lien_tw
        );
        $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

        $this->email = $this->loadLib('email');
        $this->email->setFrom($this->mails_text->exp_email, strtr(utf8_decode($this->mails_text->exp_name), $tabVars));
        $this->email->setSubject(stripslashes(strtr(utf8_decode($this->mails_text->subject), $tabVars)));
        $this->email->setHTMLBody(stripslashes(strtr(utf8_decode($this->mails_text->content), $tabVars)));

        if ($this->Config['env'] === 'prod') {
            Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
            $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
        } else {
            $this->email->addRecipient(trim($this->clients->email));
            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
        }
    }

    private function getMessageAboutClientStatus()
    {
        $oClientsStatus        = $this->loadData('clients_status');
        $oClientsStatusHistory = $this->loadData('clients_status_history');
        $aLastClientHistory    = $oClientsStatusHistory->select('id_client = ' . $this->clients->id_client, 'added DESC', 'LIMIT 1');
        $sTimeCreate           = (false === is_null($aLastClientHistory[0]['added'])) ? strtotime($aLastClientHistory[0]['added']) : $timeCreate = strtotime($this->clients->added);
        $oClientsStatus->getLastStatut($this->clients->id_client);

        $this->sClientStatusMessage = '';

        switch ($oClientsStatus->status) {
            case \clients_status::TO_BE_CHECKED :
                $this->sClientStatusMessage = '<div class="attention">Attention : compte non validé - créé le '. date('d/m/Y', $sTimeCreate) . '</div>';
                break;
            case \clients_status::COMPLETENESS :
            case \clients_status::COMPLETENESS_REMINDER:
            case \clients_status::COMPLETENESS_REPLY:
                $this->sClientStatusMessage = '<div class="attention" style="background-color:#F9B137">Attention : compte en complétude - créé le ' . date('d/m/Y', $sTimeCreate) . ' </div>';
                break;
            case \clients_status::MODIFICATION:
                $this->sClientStatusMessage = '<div class="attention" style="background-color:#F2F258">Attention : compte en modification - créé le ' . date('d/m/Y', $sTimeCreate) . '</div>';
                break;
            case \clients_status::CLOSED_LENDER_REQUEST:
                $this->sClientStatusMessage = '<div class="attention">Attention : compte clôturé à la demande du prêteur</div>';
                break;
            case \clients_status::CLOSED_BY_UNILEND:
                $this->sClientStatusMessage = '<div class="attention">Attention : compte clôturé par Unilend</div>';
                break;
            case \clients_status::VALIDATED:
                $this->sClientStatusMessage = '';
                break;
            default;
                trigger_error('Unknown Client Status', E_USER_NOTICE);
                break;
        }
    }
}
