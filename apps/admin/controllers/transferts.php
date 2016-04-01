<?php

class transfertsController extends bootstrap
{
    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        $this->users->checkAccess('transferts');

        $this->menu_admin = 'transferts';

        $this->statusOperations = array(
            0 => 'Reçu',
            1 => 'Manu',
            2 => 'Auto',
            3 => 'Rejeté',
            4 => 'Rejet'
        );
    }

    public function _default()
    {
        header('Location: /transferts/preteurs');
        die;
    }

    public function _preteurs()
    {
        $oUsers            = $this->loadData('users');
        $oReceptions       = $this->loadData('receptions');
        $this->aOperations = $oReceptions->select('id_client != 0 AND id_project = 0 AND (type = 1 AND status_prelevement = 2 OR type = 2 AND status_virement = 1)', 'id_reception DESC');
        $this->aUsers      = array();

        foreach ($oUsers->select('id_user IN (' . implode(', ', array_unique(array_column($this->aOperations, 'id_user'))) . ')') as $aUser) {
            $this->aUsers[$aUser['id_user']] = $aUser;
        }

        if (isset($this->params[0]) && 'csv' === $this->params[0]) {
            $this->hideDecoration();
            $this->view = 'csv';
        }
    }

    public function _emprunteurs()
    {
        $oUsers            = $this->loadData('users');
        $oReceptions       = $this->loadData('receptions');
        $this->aOperations = $oReceptions->select('id_project != 0 AND (type = 1 AND status_prelevement = 2 OR type = 2 AND status_virement = 1)', 'id_reception DESC');
        $this->aUsers      = array();

        foreach ($oUsers->select('id_user IN (' . implode(', ', array_unique(array_column($this->aOperations, 'id_user'))) . ')') as $aUser) {
            $this->aUsers[$aUser['id_user']] = $aUser;
        }

        if (isset($this->params[0]) && 'csv' === $this->params[0]) {
            $this->hideDecoration();
            $this->view = 'csv';
        }
    }

    public function _non_attribues()
    {
        $this->projects    = $this->loadData('projects');
        $this->receptions  = $this->loadData('receptions');
        $this->aOperations = $this->receptions->select('id_client = 0 AND id_project = 0 AND type IN (1, 2) AND (type = 1 AND status_prelevement = 2 OR type = 2 AND status_virement = 1)', 'id_reception DESC');

        if (
            isset($_POST['id_project'], $_POST['id_reception'])
            && $this->projects->get($_POST['id_project'])
            && $this->receptions->get($_POST['id_reception'])
        ) {
            $bank_unilend = $this->loadData('bank_unilend');
            $companies    = $this->loadData('companies');
            $transactions = $this->loadData('transactions');
            $this->loadData('transactions_types'); // Variable is not used but we must call it in order to create CRUD if not existing :'(

            $companies->get($this->projects->id_company, 'id_company');

            if ($_POST['type_remb'] === 'remboursement_anticipe') {
                $this->receptions->id_project      = $this->projects->id_project;
                $this->receptions->status_bo       = 1;
                $this->receptions->type_remb       = \receptions::REPAYMENT_TYPE_EARLY;
                $this->receptions->remb            = 1;
                $this->receptions->id_user         = $_SESSION['user']['id_user'];
                $this->receptions->assignment_date = date('Y-m-d H:i:s');
                $this->receptions->update();

                $transactions->id_virement      = $this->receptions->id_reception;
                $transactions->id_project       = $this->projects->id_project;
                $transactions->montant          = $this->receptions->montant;
                $transactions->id_langue        = 'fr';
                $transactions->date_transaction = date('Y-m-d H:i:s');
                $transactions->status           = 1;
                $transactions->etat             = 1;
                $transactions->transaction      = 1;
                $transactions->type_transaction = \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT;
                $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                $transactions->create();

                $bank_unilend->id_transaction = $transactions->id_transaction;
                $bank_unilend->id_project     = $this->projects->id_project;
                $bank_unilend->montant        = $this->receptions->montant;
                $bank_unilend->type           = 1; // remb emprunteur
                $bank_unilend->status         = 0; // chez unilend
                $bank_unilend->create();
            } elseif ($_POST['type_remb'] === 'regularisation') {
                $this->receptions->id_project      = $this->projects->id_project;
                $this->receptions->id_client       = $companies->id_client_owner;
                $this->receptions->status_bo       = 1;
                $this->receptions->type_remb       = \receptions::REPAYMENT_TYPE_REGULARISATION;
                $this->receptions->remb            = 1;
                $this->receptions->id_user         = $_SESSION['user']['id_user'];
                $this->receptions->assignment_date = date('Y-m-d H:i:s');
                $this->receptions->update();

                $transactions->id_virement      = $this->receptions->id_reception;
                $transactions->montant          = $this->receptions->montant;
                $transactions->id_langue        = 'fr';
                $transactions->date_transaction = date('Y-m-d H:i:s');
                $transactions->status           = 1;
                $transactions->etat             = 1;
                $transactions->transaction      = 1;
                $transactions->type_transaction = \transactions_types::TYPE_REGULATION_BANK_TRANSFER;
                $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                $transactions->create();

                $bank_unilend->id_transaction = $transactions->id_transaction;
                $bank_unilend->id_project     = $this->projects->id_project;
                $bank_unilend->montant        = $this->receptions->montant;
                $bank_unilend->type           = 1;
                $bank_unilend->create();

                $this->updateEcheances($this->projects->id_project, $this->receptions->montant, $this->projects->remb_auto);
            }

            header('Location: ' . $this->lurl . '/transferts/emprunteurs');
            die;
        }
    }

    // @todo duplicate function in cron.php
    private function updateEcheances($id_project, $montant, $remb_auto)
    {
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        $echeanciers            = $this->loadData('echeanciers');
        $projects_remb          = $this->loadData('projects_remb');

        $eche   = $echeanciers_emprunteur->select('status_emprunteur = 0 AND id_project = ' . $id_project, 'ordre ASC');
        $newsum = $montant / 100;

        foreach ($eche as $e) {
            $ordre         = $e['ordre'];
            $montantDuMois = $echeanciers->getMontantRembEmprunteur($e['montant'] / 100, $e['commission'] / 100, $e['tva'] / 100);

            if ($montantDuMois <= $newsum) {
                $echeanciers->updateStatusEmprunteur($id_project, $ordre);

                $echeanciers_emprunteur->get($id_project, 'ordre = ' . $ordre . ' AND id_project');
                $echeanciers_emprunteur->status_emprunteur             = 1;
                $echeanciers_emprunteur->date_echeance_emprunteur_reel = date('Y-m-d H:i:s');
                $echeanciers_emprunteur->update();

                $newsum = $newsum - $montantDuMois;

                if ($projects_remb->counter('id_project = "' . $id_project . '" AND ordre = "' . $ordre . '" AND status IN(0, 1)') <= 0) {
                    $date_echeance_preteur = $echeanciers->select('id_project = "' . $id_project . '" AND ordre = "' . $ordre . '"', '', 0, 1);

                    if ($remb_auto == 0) {
                        $projects_remb->id_project                = $id_project;
                        $projects_remb->ordre                     = $ordre;
                        $projects_remb->date_remb_emprunteur_reel = date('Y-m-d H:i:s');
                        $projects_remb->date_remb_preteurs        = $date_echeance_preteur[0]['date_echeance'];
                        $projects_remb->date_remb_preteurs_reel   = '0000-00-00 00:00:00';
                        $projects_remb->status                    = \projects_remb::STATUS_PENDING;
                        $projects_remb->create();
                    }
                }
            } else {
                break;
            }
        }
    }

    public function _attribution()
    {
        $this->hideDecoration();

        $this->receptions = $this->loadData('receptions');
        $this->receptions->get($this->params[0], 'id_reception');
    }

    public function _attribution_preteur()
    {
        $this->hideDecoration();

        $this->clients   = $this->loadData('clients');
        $this->companies = $this->loadData('companies');

        if (isset($_POST['id'], $_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['raison_sociale'], $_POST['id_reception'])) {
            $_SESSION['controlDoubleAttr'] = md5($_SESSION['user']['id_user']);

            $this->lPreteurs    = $this->clients->searchPreteurs($_POST['id'], $_POST['nom'], $_POST['email'], $_POST['prenom'], $_POST['raison_sociale']);
            $this->id_reception = $_POST['id_reception'];
        }
    }

    public function _attribuer_preteur()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $preteurs                            = $this->loadData('clients');
        $receptions                          = $this->loadData('receptions');
        $lenders                             = $this->loadData('lenders_accounts');
        $transactions                        = $this->loadData('transactions');
        $wallets                             = $this->loadData('wallets_lines');
        $bank                                = $this->loadData('bank_lines');
        $this->notifications                 = $this->loadData('notifications');
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
        $this->loadData('clients_gestion_type_notif'); // Variable is not used but we must call it in order to create CRUD if not existing :'(
        $this->loadData('transactions_types'); // Variable is not used but we must call it in order to create CRUD if not existing :'(

        if (
            isset($_POST['id_client'], $_POST['id_reception'], $_SESSION['controlDoubleAttr'])
            && $preteurs->get($_POST['id_client'], 'id_client')
            && $receptions->get($_POST['id_reception'], 'id_reception')
            && false === $transactions->get($_POST['id_reception'], 'status = 1 AND etat = 1 AND id_virement')
            && $_SESSION['controlDoubleAttr'] == md5($_SESSION['user']['id_user'])
        ) {
            unset($_SESSION['controlDoubleAttr']);

            $lenders->get($_POST['id_client'], 'id_client_owner');
            $lenders->status = 1;
            $lenders->update();

            $transactions->id_virement      = $receptions->id_reception;
            $transactions->id_client        = $lenders->id_client_owner;
            $transactions->montant          = $receptions->montant;
            $transactions->id_langue        = 'fr';
            $transactions->date_transaction = date('Y-m-d H:i:s');
            $transactions->status           = 1;
            $transactions->etat             = 1;
            $transactions->transaction      = 1;
            $transactions->type_transaction = \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT;
            $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
            $transactions->create();

            $wallets->id_lender                = $lenders->id_lender_account;
            $wallets->type_financial_operation = 30; // alimenation
            $wallets->id_transaction           = $transactions->id_transaction;
            $wallets->type                     = 1; // physique
            $wallets->amount                   = $receptions->montant;
            $wallets->status                   = 1;
            $wallets->create();

            $bank->id_wallet_line    = $wallets->id_wallet_line;
            $bank->id_lender_account = $lenders->id_lender_account;
            $bank->status            = 1;
            $bank->amount            = $receptions->montant;
            $bank->create();

            $receptions->id_client       = $lenders->id_client_owner;
            $receptions->status_bo       = 1;
            $receptions->remb            = 1;
            $receptions->id_user         = $_SESSION['user']['id_user'];
            $receptions->assignment_date = date('Y-m-d H:i:s');
            $receptions->update();

            $this->notifications->type      = \notifications::TYPE_BANK_TRANSFER_CREDIT;
            $this->notifications->id_lender = $lenders->id_lender_account;
            $this->notifications->amount    = $receptions->montant;
            $this->notifications->create();

            $this->clients_gestion_mails_notif->id_client       = $lenders->id_client_owner;
            $this->clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT;
            $this->clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
            $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
            $this->clients_gestion_mails_notif->id_transaction  = $transactions->id_transaction;
            $this->clients_gestion_mails_notif->create();

            if ($preteurs->etape_inscription_preteur < 3) {
                $preteurs->etape_inscription_preteur = 3;
                $preteurs->update();
            }

            if ($this->clients_gestion_notifications->getNotif($lenders->id_client_owner, \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT, 'immediatement') == true) {
                $this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                $this->clients_gestion_mails_notif->immediatement = 1;
                $this->clients_gestion_mails_notif->update();

                $this->mails_text->get('preteur-alimentation-manu', 'lang = "' . $this->language . '" AND type');

                $this->settings->get('Facebook', 'type');
                $lien_fb = $this->settings->value;

                $this->settings->get('Twitter', 'type');
                $lien_tw = $this->settings->value;

                $varMail = array(
                    'surl'            => $this->surl,
                    'url'             => $this->furl,
                    'prenom_p'        => html_entity_decode($preteurs->prenom, null, 'UTF-8'),
                    'fonds_depot'     => $this->ficelle->formatNumber($receptions->montant / 100),
                    'solde_p'         => $this->ficelle->formatNumber($transactions->getSolde($receptions->id_client)),
                    'motif_virement'  => $preteurs->getLenderPattern($preteurs->id_client),
                    'projets'         => $this->furl . '/projets-a-financer',
                    'gestion_alertes' => $this->furl . '/profile',
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
                    Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $preteurs->email, $tabFiler);
                    $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                } else {
                    $this->email->addRecipient(trim($preteurs->email));
                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                }
            }

            echo $receptions->id_client;
        }
    }

    public function _annuler_attribution_preteur()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $preteurs     = $this->loadData('clients');
        $receptions   = $this->loadData('receptions');
        $transactions = $this->loadData('transactions');
        $wallets      = $this->loadData('wallets_lines');
        $bank         = $this->loadData('bank_lines');

        if (
            isset($_POST['id_client'], $_POST['id_reception'])
            && $preteurs->get($_POST['id_client'], 'id_client')
            && $receptions->get($_POST['id_reception'], 'id_reception')
            && $transactions->get($_POST['id_reception'], 'status = 1 AND etat = 1 AND id_virement')
        ) {
            $wallets->get($transactions->id_transaction, 'id_transaction');

            $bank->delete($wallets->id_wallet_line, 'id_wallet_line');
            $wallets->delete($transactions->id_transaction, 'id_transaction');

            $transactions->etat   = 3;
            $transactions->status = 0;
            $transactions->update();

            $receptions->id_client = 0;
            $receptions->status_bo = 0;
            $receptions->remb      = 0;
            $receptions->update();
        }
    }

    public function _annuler_attribution_projet()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $projects               = $this->loadData('projects');
        $receptions             = $this->loadData('receptions');
        $transactions           = $this->loadData('transactions');
        $bank_unilend           = $this->loadData('bank_unilend');
        $echeanciers            = $this->loadData('echeanciers');
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        $projects_remb          = $this->loadData('projects_remb');

        if (
            isset($_POST['id_project'], $_POST['id_reception'])
            && $projects->get($_POST['id_project'], 'id_project')
            && $receptions->get($_POST['id_reception'], 'id_reception')
            && $transactions->get($_POST['id_reception'], 'status = 1 AND etat = 1 AND type_transaction = 6 AND id_prelevement')
        ) {
            $bank_unilend->delete($transactions->id_transaction, 'id_transaction');

            $transactions->etat    = 3;
            $transactions->status  = 0;
            $transactions->id_user = $_SESSION['user']['id_user'];
            $transactions->update();

            $receptions->id_client  = 0;
            $receptions->id_project = 0;
            $receptions->status_bo  = 0;
            $receptions->remb       = 0;
            $receptions->update();

            $eche   = $echeanciers_emprunteur->select('status_emprunteur = 1 AND id_project = ' . $_POST['id_project'], 'ordre DESC');
            $newsum = $receptions->montant / 100;

            foreach ($eche as $e) {
                $montantDuMois = $echeanciers->getMontantRembEmprunteur($e['montant'] / 100, $e['commission'] / 100, $e['tva'] / 100);

                if ($montantDuMois <= $newsum) {
                    $echeanciers->updateStatusEmprunteur($_POST['id_project'], $e['ordre'], 'annuler');
                    $echeanciers_emprunteur->get($_POST['id_project'], 'ordre = ' . $e['ordre'] . ' AND id_project');
                    $echeanciers_emprunteur->status_emprunteur             = 0;
                    $echeanciers_emprunteur->date_echeance_emprunteur_reel = '0000-00-00 00:00:00';
                    $echeanciers_emprunteur->update();

                    // et on retire du wallet unilend
                    $newsum = $newsum - $montantDuMois;

                    if ($projects_remb->counter('id_project = "' . $projects->id_project . '" AND ordre = "' . $e['ordre'] . '" AND status = 0') > 0) {
                        $projects_remb->delete($e['ordre'], 'status = 0 AND id_project = "' . $projects->id_project . '" AND ordre');
                    }
                } else {
                    break;
                }
            }

            echo 'supp';
        } else {
            echo 'nok';
        }
    }

    public function _rejeter_prelevement_projet()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $projects                = $this->loadData('projects');
        $companies               = $this->loadData('companies');
        $clients                 = $this->loadData('clients');
        $receptions              = $this->loadData('receptions');
        $transactions            = $this->loadData('transactions');
        $new_transactions        = $this->loadData('transactions');
        $bank_unilend            = $this->loadData('bank_unilend');
        $echeanciers             = $this->loadData('echeanciers');
        $echeanciers_emprunteur  = $this->loadData('echeanciers_emprunteur');
        $projects_remb           = $this->loadData('projects_remb');

        if (
            isset($_POST['id_project'], $_POST['id_reception'])
            && $projects->get($_POST['id_project'], 'id_project')
            && $receptions->get($_POST['id_reception'], 'id_reception')
            && $transactions->get($_POST['id_reception'], 'status = 1 AND etat = 1 AND type_transaction = 6 AND id_prelevement')
            && false === $new_transactions->get($_POST['id_reception'], 'status = 1 AND etat = 1 AND type_transaction = 15 AND id_prelevement')
        ) {
            $companies->get($projects->id_company, 'id_company');
            $clients->get($companies->id_client_owner, 'id_client');

            $new_transactions->id_prelevement   = $receptions->id_reception;
            $new_transactions->id_client        = $clients->id_client;
            $new_transactions->montant          = '-' . $receptions->montant;
            $new_transactions->id_langue        = 'fr';
            $new_transactions->date_transaction = date('Y-m-d H:i:s');
            $new_transactions->status           = 1;
            $new_transactions->etat             = 1;
            $new_transactions->transaction      = 1;
            $new_transactions->type_transaction = \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION;
            $new_transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
            $new_transactions->id_user          = $_SESSION['user']['id_user'];
            $new_transactions->create();

            $bank_unilend->id_transaction = $new_transactions->id_transaction;
            $bank_unilend->id_project     = $projects->id_project;
            $bank_unilend->montant        = '-' . $receptions->montant;
            $bank_unilend->type           = 1;
            $bank_unilend->create();

            $receptions->status_bo = 3; // rejeté
            $receptions->remb      = 0;
            $receptions->update();

            $eche   = $echeanciers_emprunteur->select('status_emprunteur = 1 AND id_project = ' . $projects->id_project, 'ordre DESC');
            $newsum = $receptions->montant / 100;

            foreach ($eche as $e) {
                $montantDuMois = $echeanciers->getMontantRembEmprunteur($e['montant'] / 100, $e['commission'] / 100, $e['tva'] / 100);

                if ($montantDuMois <= $newsum) {
                    $echeanciers->updateStatusEmprunteur($projects->id_project, $e['ordre'], 'annuler');

                    $echeanciers_emprunteur->get($projects->id_project, 'ordre = ' . $e['ordre'] . ' AND id_project');
                    $echeanciers_emprunteur->status_emprunteur             = 0;
                    $echeanciers_emprunteur->date_echeance_emprunteur_reel = '0000-00-00 00:00:00';
                    $echeanciers_emprunteur->update();

                    // et on retire du wallet unilend
                    $newsum = $newsum - $montantDuMois;

                    // On met a jour le remb emprunteur rejete
                    if ($projects_remb->counter('id_project = "' . $projects->id_project . '" AND ordre = "' . $e['ordre'] . '" AND status = 0') > 0) {
                        $projects_remb->get($e['ordre'], 'status = 0 AND id_project = "' . $projects->id_project . '" AND ordre');
                        $projects_remb->status = \projects_remb::STATUS_REJECTED;
                        $projects_remb->update();
                    }

                } else {
                    break;
                }
            }

            echo 'ok';
        }
    }

    public function _rattrapage_offre_bienvenue()
    {
        $this->dates             = $this->loadLib('dates');
        $this->offres_bienvenues = $this->loadData('offres_bienvenues');
        $this->clients           = $this->loadData('clients');
        $this->companies         = $this->loadData('companies');
        $oWelcomeOfferDetails    = $this->loadData('offres_bienvenues_details');
        $oTransactions           = $this->loadData('transactions');
        $oWalletsLines           = $this->loadData('wallets_lines');
        $oBankUnilend            = $this->loadData('bank_unilend');
        $oLendersAccounts        = $this->loadData('lenders_accounts');
        //load for use of constants
        $this->loadData('transactions_types');
        $this->loadData('clients_status');

        if (isset($this->params[0])) {
            $this->clients->get($this->params[0]);
            $this->companies->get('id_client_owner', $this->clients->id_client);
        }
        $this->offres_bienvenues->get(1, 'status = 0 AND id_offre_bienvenue');

        $this->settings->get('Offre de bienvenue motif', 'type');
        $sWelcomeOfferMotive = $this->settings->value;

        unset($_SESSION['forms']['rattrapage_offre_bienvenue']);

        if (isset($_POST['spy_search'])) {
            if (false === empty($_POST['dateStart']) && false === empty($_POST['dateEnd'])) {
                $oDateTimeStart                                                   = \DateTime::createFromFormat('d/m/Y', $_POST['dateStart']);
                $oDateTimeEnd                                                     = \DateTime::createFromFormat('d/m/Y', $_POST['dateEnd']);
                $sStartDateSQL                                                    = $oDateTimeStart->format('Y-m-d');
                $sEndDateSQL                                                      = $oDateTimeEnd->format('Y-m-d');
                $_SESSION['forms']['rattrapage_offre_bienvenue']['sStartDateSQL'] = $sStartDateSQL;
                $_SESSION['forms']['rattrapage_offre_bienvenue']['sEndDateSQL']   = $sEndDateSQL;

                $this->aClientsWithoutWelcomeOffer = $this->clients->getClientsWithNoWelcomeOffer(null, $sStartDateSQL, $sEndDateSQL);
            } elseif (false === empty($_POST['id'])) {
                $this->aClientsWithoutWelcomeOffer = $this->clients->getClientsWithNoWelcomeOffer($_POST['id']);
                $_SESSION['forms']['rattrapage_offre_bienvenue']['id'] = $_POST['id'];
            } else {
                $_SESSION['freeow']['title']   = 'Recherche non abouti';
                $_SESSION['freeow']['message'] = 'Il faut une date de d&eacutebut et de fin ou ID(s)!';
            }
        }

        if (isset($_POST['affect_welcome_offer']) && isset($this->params[0]) && isset($this->params[1])) {
            $this->clients->get($this->params[0]);
            $this->offres_bienvenues->get($this->params[1]);
            $oLendersAccounts->get($this->clients->id_client, 'id_client_owner');

            $bOfferValid      = false;
            $bEnoughMoneyLeft = false;
            $aVirtualWelcomeOfferTransactions = array(
                \transactions_types::TYPE_WELCOME_OFFER,
                \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION
            );

            $iSumOfAllWelcomeOffersDistributed      = $oWelcomeOfferDetails->sum('type = 0 AND id_offre_bienvenue = ' . $this->offres_bienvenues->id_offre_bienvenue . ' AND status <> 2', 'montant');
            $iSumOfPhysicalWelcomeOfferTransactions = $oTransactions->sum('status = 1 AND etat = 1 AND type_transaction = ' . \transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER, 'montant');
            $iSumOfVirtualWelcomeOfferTransactions  = $oTransactions->sum('status = 1 AND etat = 1 AND type_transaction IN(' . implode(',', $aVirtualWelcomeOfferTransactions) . ')', 'montant');
            $iAvailableAmountForWelcomeOffers       = $iSumOfPhysicalWelcomeOfferTransactions - $iSumOfVirtualWelcomeOfferTransactions;

            $oStartWelcomeOffer = \DateTime::createFromFormat('Y-m-d', $this->offres_bienvenues->debut);
            $oEndWelcomeOffer   = \DateTime::createFromFormat('Y-m-d', $this->offres_bienvenues->fin);
            $oToday             = new \DateTime();

            if ($oStartWelcomeOffer <= $oToday && $oEndWelcomeOffer >= $oToday) {
                $bOfferValid = true;
            } else {
                $_SESSION['freeow']['title']   = 'Offre de bienvenue non cr&eacute;dit&eacute;';
                $_SESSION['freeow']['message'] = 'Il n\'y a plus d\'offre valide en cours !';
            }

            if ($iSumOfAllWelcomeOffersDistributed <= $this->offres_bienvenues->montant_limit && $iAvailableAmountForWelcomeOffers >= $this->offres_bienvenues->montant) {
                $bEnoughMoneyLeft = true;
            } else {
                $_SESSION['freeow']['title']   = 'Offre de bienvenue non cr&eacute;dit&eacute;';
                $_SESSION['freeow']['message'] = 'Il n\'y a plus assez d\'argent disponible !';
            }

            if ($bOfferValid && $bEnoughMoneyLeft) {
                $oWelcomeOfferDetails->id_offre_bienvenue        = $this->offres_bienvenues->id_offre_bienvenue;
                $oWelcomeOfferDetails->motif                     = $sWelcomeOfferMotive;
                $oWelcomeOfferDetails->id_client                 = $this->clients->id_client;
                $oWelcomeOfferDetails->montant                   = $this->offres_bienvenues->montant;
                $oWelcomeOfferDetails->status                    = 0;
                $oWelcomeOfferDetails->create();

                $oTransactions->id_client                        = $this->clients->id_client;
                $oTransactions->montant                          = $oWelcomeOfferDetails->montant;
                $oTransactions->id_offre_bienvenue_detail        = $oWelcomeOfferDetails->id_offre_bienvenue_detail;
                $oTransactions->id_langue                        = 'fr';
                $oTransactions->date_transaction                 = date('Y-m-d H:i:s');
                $oTransactions->status                           = '1';
                $oTransactions->etat                             = '1';
                $oTransactions->ip_client                        = $_SERVER['REMOTE_ADDR'];
                $oTransactions->type_transaction                 = \transactions_types::TYPE_WELCOME_OFFER;
                $oTransactions->transaction                      = 2;
                $oTransactions->create();

                $oWalletsLines->id_lender                        = $oLendersAccounts->id_lender_account;
                $oWalletsLines->type_financial_operation         = \wallets_lines::TYPE_MONEY_SUPPLY;
                $oWalletsLines->id_transaction                   = $oTransactions->id_transaction;
                $oWalletsLines->status                           = 1;
                $oWalletsLines->type                             = 1;
                $oWalletsLines->amount                           = $oWelcomeOfferDetails->montant;
                $oWalletsLines->create();

                $oBankUnilend->id_transaction                    = $oTransactions->id_transaction;
                $oBankUnilend->montant                           = '-' . $oWelcomeOfferDetails->montant;
                $oBankUnilend->type                              = \bank_unilend::TYPE_UNILEND_WELCOME_OFFER_PATRONAGE;
                $oBankUnilend->create();

                $oMailsText = $this->loadData('mails_text');
                $oMailsText->get('offre-de-bienvenue', 'lang = "' . $this->language . '" AND type');

                $this->settings->get('Facebook', 'type');
                $sFacebook = $this->settings->value;

                $this->settings->get('Twitter', 'type');
                $sTwitter = $this->settings->value;

                $aVariables = array(
                    'surl'            => $this->surl,
                    'url'             => $this->furl,
                    'prenom_p'        => $this->clients->prenom,
                    'projets'         => $this->furl . '/projets-a-financer',
                    'offre_bienvenue' => $this->ficelle->formatNumber($oWelcomeOfferDetails->montant / 100),
                    'lien_fb'         => $sFacebook,
                    'lien_tw'         => $sTwitter
                );

                $this->email = $this->loadLib('email');
                $this->email->setFrom($oMailsText->exp_email, utf8_decode($oMailsText->exp_name));
                $this->email->setSubject(stripslashes(utf8_decode($oMailsText->subject)));
                $this->email->setHTMLBody(stripslashes(strtr(utf8_decode($oMailsText->content), $this->tnmp->constructionVariablesServeur($aVariables))));

                if ($this->Config['env'] === 'prod') {
                    Mailer::sendNMP($this->email, $this->mails_filer, $oMailsText->id_textemail, $this->clients->email, $tabFiler);
                    $this->tnmp->sendMailNMP($tabFiler, $aVariables, $oMailsText->nmp_secure, $oMailsText->id_nmp, $oMailsText->nmp_unique, $oMailsText->mode);
                } else {
                    $this->email->addRecipient(trim($this->clients->email));
                    Mailer::send($this->email, $this->mails_filer, $oMailsText->id_textemail);
                }
            }
        }
    }

    public function _csv_rattrapage_offre_bienvenue()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        $oClients = $this->loadData('clients');
        $aClientsWithoutWelcomeOffer = array();

        if (isset($_SESSION['forms']['rattrapage_offre_bienvenue']['sStartDateSQL']) && isset($_SESSION['forms']['rattrapage_offre_bienvenue']['sEndDateSQL'])) {
            $aClientsWithoutWelcomeOffer = $oClients->getClientsWithNoWelcomeOffer(
                null,
                $_SESSION['forms']['rattrapage_offre_bienvenue']['sStartDateSQL'],
                $_SESSION['forms']['rattrapage_offre_bienvenue']['sEndDateSQL']
            );
        }

        if (isset($_SESSION['forms']['rattrapage_offre_bienvenue']['id'])) {
            $aClientsWithoutWelcomeOffer = $oClients->getClientsWithNoWelcomeOffer($_SESSION['forms']['rattrapage_offre_bienvenue']['id']);
        }

        $sFileName      = 'ratrappage_offre_bienvenue';
        $aColumnHeaders = array('ID Client', 'Nom ou Raison Sociale', 'Prénom', 'Email', 'Date de création', 'Date de validation');
        $aData          = array();

        foreach ($aClientsWithoutWelcomeOffer as $key =>$aClient) {
            $aData[] = array(
                $aClient['id_client'],
                empty($aClient['company']) ? $aClient['nom'] : $aClient['company'],
                empty($aClient['company']) ? $aClient['prenom'] : '',
                $aClient['email'],
                $this->dates->formatDateMysqltoShortFR($aClient['date_creation']),
                (false === empty($aClient['date_validation'])) ? $this->dates->formatDateMysqltoShortFR($aClient['date_validation']) : ''
            );
        }
        $this->exportCSV($aColumnHeaders, $aData, $sFileName);
    }

    private function exportCSV($aColumnHeaders, $aData, $sFileName)
    {
        PHPExcel_Settings::setCacheStorageMethod(
            PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            array('memoryCacheSize' => '2048MB', 'cacheTime' => 1200)
        );

        $oDocument    = new PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);

        if (count($aColumnHeaders) > 0) {
            foreach ($aColumnHeaders as $iIndex => $sColumnName) {
                $oActiveSheet->setCellValueByColumnAndRow($iIndex, 1, $sColumnName);
            }
        }

        foreach ($aData as $iRowIndex => $aRow) {
            $iColIndex = 0;
            foreach ($aRow as $sCellValue) {
                $oActiveSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, $sCellValue);
            }
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $sFileName . '.csv');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        $oWriter = PHPExcel_IOFactory::createWriter($oDocument, 'CSV');
        $oWriter->setUseBOM(true);
        $oWriter->setDelimiter(';');
        $oWriter->save('php://output');
    }

    public function _affect_welcome_offer()
    {
        $this->hideDecoration();

        $this->oWelcomeOffer = $this->loadData('offres_bienvenues');
        $this->oClient       = $this->loadData('clients');
        $this->oCompany      = $this->loadData('companies');

        $this->oClient->get($this->params[0]);
        $this->oCompany->get('id_client_owner', $this->oClient->id_client);
        $this->oWelcomeOffer->get(1, 'status = 0 AND id_offre_bienvenue');
    }
}
