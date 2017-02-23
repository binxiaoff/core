<?php

use Psr\Log\LoggerInterface;

class transfertsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

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
                $transactions->status           = \transactions::STATUS_VALID;
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
                $transactions->status           = \transactions::STATUS_VALID;
                $transactions->type_transaction = \transactions_types::TYPE_REGULATION_BANK_TRANSFER;
                $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                $transactions->create();

                $bank_unilend->id_transaction = $transactions->id_transaction;
                $bank_unilend->id_project     = $this->projects->id_project;
                $bank_unilend->montant        = $this->receptions->montant;
                $bank_unilend->type           = 1;
                $bank_unilend->create();

                $this->updateEcheances($this->projects->id_project, $this->receptions->montant);
            }

            header('Location: ' . $this->lurl . '/transferts/emprunteurs');
            die;
        }
    }

    private function updateEcheances($id_project, $montant)
    {
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        $echeanciers            = $this->loadData('echeanciers');
        $projects_remb          = $this->loadData('projects_remb');

        $eche   = $echeanciers_emprunteur->select('id_project = ' . $id_project . ' AND status_emprunteur = 0', 'ordre ASC');
        $newsum = $montant / 100;

        foreach ($eche as $e) {
            $ordre         = $e['ordre'];
            $montantDuMois = round($e['montant'] / 100 + $e['commission'] / 100 + $e['tva'] / 100, 2);

            if ($montantDuMois <= $newsum) {
                $echeanciers->updateStatusEmprunteur($id_project, $ordre);

                $echeanciers_emprunteur->get($id_project, 'ordre = ' . $ordre . ' AND id_project');
                $echeanciers_emprunteur->status_emprunteur             = 1;
                $echeanciers_emprunteur->date_echeance_emprunteur_reel = date('Y-m-d H:i:s');
                $echeanciers_emprunteur->update();

                $newsum = $newsum - $montantDuMois;

                if ($projects_remb->counter('id_project = "' . $id_project . '" AND ordre = "' . $ordre . '" AND status IN(0, 1)') <= 0) {
                    $date_echeance_preteur = $echeanciers->select('id_project = "' . $id_project . '" AND ordre = "' . $ordre . '"', '', 0, 1);

                    $projects_remb->id_project                = $id_project;
                    $projects_remb->ordre                     = $ordre;
                    $projects_remb->date_remb_emprunteur_reel = date('Y-m-d H:i:s');
                    $projects_remb->date_remb_preteurs        = $date_echeance_preteur[0]['date_echeance'];
                    $projects_remb->date_remb_preteurs_reel   = '0000-00-00 00:00:00';
                    $projects_remb->status                    = \projects_remb::STATUS_PENDING;
                    $projects_remb->create();
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

        /** @var \clients $preteurs */
        $preteurs = $this->loadData('clients');
        /** @var \receptions $receptions */
        $receptions = $this->loadData('receptions');
        /** @var \lenders_accounts $lenders */
        $lenders = $this->loadData('lenders_accounts');
        /** @var \transactions $transactions */
        $transactions = $this->loadData('transactions');
        /** @var \wallets_lines $wallets */
        $wallets = $this->loadData('wallets_lines');
        /** @var \bank_lines $bank */
        $bank = $this->loadData('bank_lines');
        /** @var \notifications notifications */
        $this->notifications = $this->loadData('notifications');
        /** @var \clients_gestion_notifications clients_gestion_notifications */
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif clients_gestion_mails_notif */
        $this->clients_gestion_mails_notif = $this->loadData('clients_gestion_mails_notif');
        $this->loadData('clients_gestion_type_notif'); // Variable is not used but we must call it in order to create CRUD if not existing :'(
        $this->loadData('transactions_types'); // Variable is not used but we must call it in order to create CRUD if not existing :'(
        /** @var \settings setting */
        $this->setting = $this->loadData('settings');

        if (
            isset($_POST['id_client'], $_POST['id_reception'], $_SESSION['controlDoubleAttr'])
            && $preteurs->get($_POST['id_client'], 'id_client')
            && $receptions->get($_POST['id_reception'], 'id_reception')
            && false === $transactions->get($_POST['id_reception'], 'status = ' . \transactions::STATUS_VALID . ' AND id_virement')
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
            $transactions->status           = \transactions::STATUS_VALID;
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

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-alimentation-manu', $varMail);
                $message->setTo($preteurs->email);
                $mailer = $this->get('mailer');
                $mailer->send($message);
            }

            echo $receptions->id_client;
        }
    }

    public function _annuler_attribution_preteur()
    {
        $this->hideDecoration();
        $this->autoFireView = false;
        /**@var \clients $preteurs */
        $preteurs = $this->loadData('clients');
        /** @var \receptions $receptions */
        $receptions = $this->loadData('receptions');
        /** @var \transactions $transactions */
        $transactions = $this->loadData('transactions');
        /** @var \wallets_lines $wallets */
        $wallets = $this->loadData('wallets_lines');
        /** @var \bank_lines $bank */
        $bank = $this->loadData('bank_lines');

        if (
            isset($_POST['id_client'], $_POST['id_reception'])
            && $preteurs->get($_POST['id_client'], 'id_client')
            && $receptions->get($_POST['id_reception'], 'id_reception')
            && $transactions->get($_POST['id_reception'], 'status = ' . \transactions::STATUS_VALID . ' AND id_virement')
        ) {
            $wallets->get($transactions->id_transaction, 'id_transaction');

            $bank->delete($wallets->id_wallet_line, 'id_wallet_line');
            $wallets->delete($transactions->id_transaction, 'id_transaction');

            $transactions->status = \transactions::STATUS_CANCELED;
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

        /** @var \projects $projects */
        $projects = $this->loadData('projects');
        /** @var \receptions $receptions */
        $receptions = $this->loadData('receptions');
        /** @var \transactions $transactions */
        $transactions = $this->loadData('transactions');
        /** @var \bank_unilend $bank_unilend */
        $bank_unilend = $this->loadData('bank_unilend');
        /** @var \echeanciers $echeanciers */
        $echeanciers = $this->loadData('echeanciers');
        /** @var \echeanciers_emprunteur $echeanciers_emprunteur */
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        /** @var \projects_remb $projects_remb */
        $projects_remb = $this->loadData('projects_remb');

        if (
            isset($_POST['id_project'], $_POST['id_reception'])
            && $projects->get($_POST['id_project'], 'id_project')
            && $receptions->get($_POST['id_reception'], 'id_reception')
            && $transactions->get($_POST['id_reception'], 'status = ' . \transactions::STATUS_VALID . ' AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT . ' AND id_prelevement')
        ) {
            $bank_unilend->delete($transactions->id_transaction, 'id_transaction');

            $transactions->status  = \transactions::STATUS_CANCELED;
            $transactions->id_user = $_SESSION['user']['id_user'];
            $transactions->update();

            $receptions->id_client  = 0;
            $receptions->id_project = 0;
            $receptions->status_bo  = 0;
            $receptions->remb       = 0;
            $receptions->update();

            $eche   = $echeanciers_emprunteur->select('id_project = ' . $_POST['id_project'] . ' AND status_emprunteur = 1', 'ordre DESC');
            $newsum = $receptions->montant / 100;

            foreach ($eche as $e) {
                $montantDuMois = round($e['montant'] / 100 + $e['commission'] / 100 + $e['tva'] / 100, 2);

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

        /** @var \projects $projects */
        $projects = $this->loadData('projects');
        /** @var \companies $companies */
        $companies = $this->loadData('companies');
        /** @var \clients $clients */
        $clients = $this->loadData('clients');
        /** @var \receptions $receptions */
        $receptions = $this->loadData('receptions');
        /** @var \transactions $transactions */
        $transactions = $this->loadData('transactions');
        /** @var \transactions $new_transactions */
        $new_transactions = $this->loadData('transactions');
        /** @var \bank_lines $bank_unilend */
        $bank_unilend = $this->loadData('bank_unilend');
        /** @var \echeanciers $echeanciers */
        $echeanciers = $this->loadData('echeanciers');
        /** @var \echeanciers_emprunteur $echeanciers_emprunteur */
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        /** @var \projects_remb $projects_remb */
        $projects_remb = $this->loadData('projects_remb');

        if (
            isset($_POST['id_project'], $_POST['id_reception'])
            && $projects->get($_POST['id_project'], 'id_project')
            && $receptions->get($_POST['id_reception'], 'id_reception')
            && $transactions->get($_POST['id_reception'], 'status = ' . \transactions::STATUS_VALID . ' AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT . ' AND id_prelevement')
            && false === $new_transactions->get($_POST['id_reception'], 'status = ' . \transactions::STATUS_VALID . ' AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION . ' AND id_prelevement')
        ) {
            $companies->get($projects->id_company, 'id_company');
            $clients->get($companies->id_client_owner, 'id_client');

            $new_transactions->id_prelevement   = $receptions->id_reception;
            $new_transactions->id_client        = $clients->id_client;
            $new_transactions->montant          = - $receptions->montant;
            $new_transactions->id_langue        = 'fr';
            $new_transactions->date_transaction = date('Y-m-d H:i:s');
            $new_transactions->status           = \transactions::STATUS_VALID;
            $new_transactions->type_transaction = \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION;
            $new_transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
            $new_transactions->id_user          = $_SESSION['user']['id_user'];
            $new_transactions->create();

            $bank_unilend->id_transaction = $new_transactions->id_transaction;
            $bank_unilend->id_project     = $projects->id_project;
            $bank_unilend->montant        = - $receptions->montant;
            $bank_unilend->type           = 1;
            $bank_unilend->create();

            $receptions->status_bo = 3; // rejeté
            $receptions->remb      = 0;
            $receptions->update();

            $eche   = $echeanciers_emprunteur->select('id_project = ' . $projects->id_project . ' AND status_emprunteur = 1', 'ordre DESC');
            $newsum = $receptions->montant / 100;

            foreach ($eche as $e) {
                $montantDuMois = round($e['montant'] / 100 + $e['commission'] / 100 + $e['tva'] / 100, 2);

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
        /** @var \clients clients */
        $this->clients = $this->loadData('clients');
        /** @var \lenders_accounts $oLendersAccounts */
        $oLendersAccounts = $this->loadData('lenders_accounts');

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
                $_SESSION['freeow']['title']   = 'Recherche non aboutie. Indiquez soit la liste des ID clients soit un interval de date';
                $_SESSION['freeow']['message'] = 'Il faut une date de d&eacutebut et de fin ou ID(s)!';
            }
        }

        if (isset($_POST['affect_welcome_offer']) && isset($this->params[0])) {
            if($this->clients->get($this->params[0])&& $oLendersAccounts->get($this->clients->id_client, 'id_client_owner')) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager $welcomeOfferManager */
                $welcomeOfferManager = $this->get('unilend.service.welcome_offer_manager');
                $response = $welcomeOfferManager->createWelcomeOffer($this->clients);

                switch ($response['code']) {
                    case 0:
                        $_SESSION['freeow']['title']   = 'Offre de bienvenue cr&eacute;dit&eacute;';
                        break;
                    default:
                        $_SESSION['freeow']['title']   = 'Offre de bienvenue non cr&eacute;dit&eacute;';
                        break;
                }
                $_SESSION['freeow']['message'] = $response['message'];
            }
        }
    }

    public function _csv_rattrapage_offre_bienvenue()
    {
        $this->autoFireView = false;
        $this->hideDecoration();
        /** @var \clients $oClients */
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

        /** @var \PHPExcel_Writer_CSV $oWriter */
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

    public function _deblocage()
    {
        /** @var \projects $project */
        $project = $this->loadData('projects');
        /** @var \clients_mandats $mandate */
        $mandate = $this->loadData('clients_mandats');
        /** @var \projects_pouvoir $proxy */
        $proxy = $this->loadData('projects_pouvoir');

        if (
            isset($_POST['validateProxy'], $_POST['id_project'])
            && $project->get($_POST['id_project'])
            && $mandate->get($_POST['id_project'] . '" AND status = "' . \clients_mandats::STATUS_SIGNED, 'id_project')
            && $proxy->get($_POST['id_project'] . '" AND status = "' . \projects_pouvoir::STATUS_SIGNED, 'id_project')
        ) {
            /** @var \companies $companies */
            $companies = $this->loadData('companies');
            $companies->get($project->id_company, 'id_company');

            /** @var \clients clients */
            $clients = $this->loadData('clients');
            $clients->get($companies->id_client_owner, 'id_client');

            /** @var LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->info('Checking refund status (project ' . $project->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));

            /** @var \settings $paymentInspectionStopped */
            $paymentInspectionStopped = $this->loadData('settings');
            $paymentInspectionStopped->get('Controle statut remboursement', 'type');

            if ($project->status != \projects_status::FUNDE) {
                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'Le projet n\'est pas fundé';
            } elseif ($paymentInspectionStopped->value == 1) {
                ini_set('memory_limit', '512M');

                $proxy->status_remb = \projects_pouvoir::STATUS_VALIDATED;
                $proxy->update();

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
                $oProjectManager = $this->get('unilend.service.project_manager');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $oMailerManager */
                $oMailerManager = $this->get('unilend.service.email_manager');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\NotificationManager $oNotificationManager */
                $oNotificationManager = $this->get('unilend.service.notification_manager');
                /** @var \lenders_accounts $lender */
                $lender = $this->loadData('lenders_accounts');
                /** @var \transactions $transactions */
                $transactions = $this->loadData('transactions');
                /** @var \virements $virements */
                $virements = $this->loadData('virements');
                /** @var \bank_unilend $bank_unilend */
                $bank_unilend = $this->loadData('bank_unilend');
                /** @var \loans $loans */
                $loans = $this->loadData('loans');
                /** @var \echeanciers_emprunteur $paymentSchedule */
                $paymentSchedule = $this->loadData('echeanciers_emprunteur');
                /** @var \projects_status_history $projectsStatusHistory */
                $projectsStatusHistory = $this->loadData('projects_status_history');
                /** @var \accepted_bids $acceptedBids */
                $acceptedBids = $this->loadData('accepted_bids');

                $paymentInspectionStopped->value = 0;
                $paymentInspectionStopped->update();

                $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::REMBOURSEMENT, $project);

                /** @var \tax_type $taxType */
                $taxType = $this->loadData('tax_type');
                $taxType->get(\tax_type::TYPE_VAT);

                $commissionFundsRateVATIncluded = bcmul(
                    bcadd(1, round(bcdiv($taxType, 100, 4), 2), 2),
                    round(bcdiv($project->commission_rate_funds, 100, 4), 2),
                    2
                );
                $projectLoanAmount              = $loans->sumPretsProjet($project->id_project);
                $unilendShareAmount             = round($projectLoanAmount * $commissionFundsRateVATIncluded, 2);
                $projectLoanAmount              -= $unilendShareAmount;

                if (false === $transactions->get($project->id_project, 'type_transaction = ' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT . ' AND id_project')) {
                    $aMandate = $mandate->select('id_project = ' . $project->id_project . ' AND id_client = ' . $clients->id_client . ' AND status = ' . \clients_mandats::STATUS_SIGNED, 'id_mandat DESC', 0, 1);
                    $aMandate = array_shift($aMandate);

                    $transactions->id_client        = $clients->id_client;
                    $transactions->montant          = bcmul($projectLoanAmount, -100);
                    $transactions->montant_unilend  = bcmul($unilendShareAmount, 100);
                    $transactions->id_langue        = 'fr';
                    $transactions->id_project       = $project->id_project;
                    $transactions->date_transaction = date('Y-m-d H:i:s');
                    $transactions->status           = \transactions::STATUS_VALID;
                    $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                    $transactions->type_transaction = \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT;
                    $transactions->create();

                    $bank_unilend->id_transaction = $transactions->id_transaction;
                    $bank_unilend->id_project     = $project->id_project;
                    $bank_unilend->montant        = bcmul($unilendShareAmount, 100);
                    $bank_unilend->create();

                    /** @var \platform_account_unilend $oAccountUnilend */
                    $oAccountUnilend                 = $this->loadData('platform_account_unilend');

                    $oAccountUnilend->id_transaction = $transactions->id_transaction;
                    $oAccountUnilend->id_project     = $project->id_project;
                    $oAccountUnilend->amount         = bcmul($unilendShareAmount, 100);
                    $oAccountUnilend->type           = \platform_account_unilend::TYPE_COMMISSION_PROJECT;
                    $oAccountUnilend->create();

                    $virements->id_client      = $clients->id_client;
                    $virements->id_project     = $project->id_project;
                    $virements->id_transaction = $transactions->id_transaction;
                    $virements->montant        = bcmul($projectLoanAmount, 100);
                    $virements->motif          = $oProjectManager->getBorrowerBankTransferLabel($project);
                    $virements->type           = 2;
                    $virements->create();

                    /** @var \prelevements $prelevements */
                    $prelevements = $this->loadData('prelevements');

                    $echea = $paymentSchedule->select('id_project = ' . $project->id_project);

                    foreach ($echea as $key => $e) {
                        $dateEcheEmp = strtotime($e['date_echeance_emprunteur']);
                        $result      = mktime(0, 0, 0, date('m', $dateEcheEmp), date('d', $dateEcheEmp) - 15, date('Y', $dateEcheEmp));

                        $prelevements->id_client                          = $clients->id_client;
                        $prelevements->id_project                         = $project->id_project;
                        $prelevements->motif                              = $virements->motif;
                        $prelevements->montant                            = bcadd(bcadd($e['montant'], $e['commission'], 2), $e['tva'], 2);
                        $prelevements->bic                                = str_replace(' ', '', $aMandate['bic']);
                        $prelevements->iban                               = str_replace(' ', '', $aMandate['iban']);
                        $prelevements->type_prelevement                   = 1; // recurrent
                        $prelevements->type                               = 2; //emprunteur
                        $prelevements->num_prelevement                    = $e['ordre'];
                        $prelevements->date_execution_demande_prelevement = date('Y-m-d', $result);
                        $prelevements->date_echeance_emprunteur           = $e['date_echeance_emprunteur'];
                        $prelevements->create();
                    }

                    $aAcceptedBids = $acceptedBids->getDistinctBids($project->id_project);
                    $aLastLoans    = array();

                    foreach ($aAcceptedBids as $aBid) {
                        $lender->get($aBid['id_lender']);

                        $oNotification = $oNotificationManager->createNotification(\notifications::TYPE_LOAN_ACCEPTED, $lender->id_client_owner, $project->id_project, $aBid['amount'], $aBid['id_bid']);

                        $aLoansForBid = $acceptedBids->select('id_bid = ' . $aBid['id_bid']);

                        foreach ($aLoansForBid as $aLoan) {
                            if (in_array($aLoan['id_loan'], $aLastLoans) === false) {
                                $oNotificationManager->createEmailNotification($oNotification->id_notification, \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED, $lender->id_client_owner, null, null, $aLoan['id_loan']);
                                $aLastLoans[] = $aLoan['id_loan'];
                            }
                        }
                    }

                    $oMailerManager->sendLoanAccepted($project);
                }

                $oMailerManager->sendBorrowerBill($project);

                $aRepaymentHistory = $projectsStatusHistory->select('id_project = ' . $project->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ')', 'added DESC, id_project_status_history DESC', 0, 1);

                if (false === empty($aRepaymentHistory)) {
                    /** @var \compteur_factures $invoiceCounter */
                    $invoiceCounter = $this->loadData('compteur_factures');
                    /** @var \factures $invoice */
                    $invoice = $this->loadData('factures');
                    /** @var \tax_type $taxType */
                    $taxType = $this->loadData('tax_type');

                    $taxRate            = $taxType->getTaxRateByCountry('fr');
                    $sDateFirstPayment  = $aRepaymentHistory[0]['added'];
                    $fCommission        = bcmul($unilendShareAmount, 100);
                    $fVATFreeCommission = round($fCommission / (1 + $taxRate[\tax_type::TYPE_VAT] / 100));

                    $invoice->num_facture     = 'FR-E' . date('Ymd', strtotime($sDateFirstPayment)) . str_pad($invoiceCounter->compteurJournalier($project->id_project, $sDateFirstPayment), 5, '0', STR_PAD_LEFT);
                    $invoice->date            = $sDateFirstPayment;
                    $invoice->id_company      = $companies->id_company;
                    $invoice->id_project      = $project->id_project;
                    $invoice->ordre           = 0;
                    $invoice->type_commission = \factures::TYPE_COMMISSION_FINANCEMENT;
                    $invoice->commission      = $project->commission_rate_funds;
                    $invoice->montant_ttc     = $fCommission;
                    $invoice->montant_ht      = $fVATFreeCommission;
                    $invoice->tva             = $fCommission - $fVATFreeCommission;
                    $invoice->create();
                }

                $paymentInspectionStopped->value = 1;
                $paymentInspectionStopped->update();

                $logger->info('Check refund status done (project ' . $project->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));

                $ekomi = $this->get('unilend.service.ekomi');
                $ekomi->sendProjectEmail($project);

                $slackManager = $this->container->get('unilend.service.slack_manager');
                $message      = $slackManager->getProjectName($project) . ' - Fonds débloqués par ' . $_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['name'];
                $slackManager->sendMessage($message);
            } else {
                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'Un remboursement est déjà en cours';
            }

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->id_project);
            die;
        }

        $aProjects = $project->selectProjectsByStatus([\projects_status::FUNDE], '', [], '', '', false);

        $this->aProjects = array();
        foreach ($aProjects as $iProject => $aProject) {
            $this->aProjects[$iProject] = $aProject;

            $aMandate = $mandate->select('id_project = ' . $this->aProjects[$iProject]['id_project'] . ' AND status = ' . \clients_mandats::STATUS_SIGNED, 'added DESC', 0, 1);
            if ($aMandate = array_shift($aMandate)) {
                $this->aProjects[$iProject]['bic']           = $aMandate['bic'];
                $this->aProjects[$iProject]['iban']          = $aMandate['iban'];
                $this->aProjects[$iProject]['mandat']        = $aMandate['name'];
                $this->aProjects[$iProject]['status_mandat'] = $aMandate['status'];
            }

            $aProxy = $proxy->select('id_project = ' . $this->aProjects[$iProject]['id_project'] . ' AND status = ' . \projects_pouvoir::STATUS_SIGNED, 'added DESC', 0, 1);
            if ($aProxy = array_shift($aProxy)) {
                $this->aProjects[$iProject]['url_pdf']          = $aProxy['name'];
                $this->aProjects[$iProject]['status_remb']      = $aProxy['status_remb'];
                $this->aProjects[$iProject]['authority_status'] = $aProxy['status'];
            }

            if ($aAttachments = $project->getAttachments($this->aProjects[$iProject]['id_project'])) {
                $this->aProjects[$iProject]['kbis']    = isset($aAttachments[\attachment_type::KBIS]) ? $aAttachments[\attachment_type::KBIS]['path'] : '';
                $this->aProjects[$iProject]['id_kbis'] = isset($aAttachments[\attachment_type::KBIS]) ? $aAttachments[\attachment_type::KBIS]['id'] : '';
                $this->aProjects[$iProject]['rib']     = isset($aAttachments[\attachment_type::RIB]) ? $aAttachments[\attachment_type::RIB]['path'] : '';
                $this->aProjects[$iProject]['id_rib']  = isset($aAttachments[\attachment_type::RIB]) ? $aAttachments[\attachment_type::RIB]['id'] : '';
            }
        }
    }

    public function _succession()
    {
        if (isset($_POST['succession_check']) || isset($_POST['succession_validate'])) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientManager $clientManager */
            $clientManager = $this->get('unilend.service.client_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager $clientStatusManager */
            $clientStatusManager = $this->get('unilend.service.client_status_manager');
            /** @var \clients $originalClient */
            $originalClient = $this->loadData('clients');
            /** @var \clients $newOwner */
            $newOwner = $this->loadData('clients');
            /** @var \attachment $transferDocument */
            $transferDocument = $this->loadData('attachment');

            if (
                false === empty($_POST['id_client_to_transfer'])
                && (false === is_numeric($_POST['id_client_to_transfer'])
                    || false === $originalClient->get($_POST['id_client_to_transfer'])
                    || false === $clientManager->isLender($originalClient))) {
                $this->addErrorMessageAndRedirect('Le défunt n\'est pas un prêteur');
            }

            if (
                false === empty($_POST['id_client_receiver'])
                && (false === is_numeric($_POST['id_client_receiver'])
                    || false === $newOwner->get($_POST['id_client_receiver'])
                    || false === $clientManager->isLender($newOwner))
            ) {
                $this->addErrorMessageAndRedirect('L\'héritier n\'est pas un prêteur');
            }

            /** @var \lenders_accounts $originalLender */
            $originalLender = $this->loadData('lenders_accounts');
            $originalLender->get($originalClient->id_client, 'id_client_owner');

            if ($clientStatusManager->getLastClientStatus($newOwner) != \clients_status::VALIDATED) {
                $this->addErrorMessageAndRedirect('Le compte de l\'héritier n\'est pas validé');
            }

            /** @var \bids $bids */
            $bids = $this->loadData('bids');
            if ($bids->exist($originalLender->id_lender_account, 'status = ' . \bids::STATUS_BID_PENDING . ' AND id_lender_account ')) {
                $this->addErrorMessageAndRedirect('Le défunt a des bids en cours.');
            }

            /** @var \loans $loans */
            $loans                 = $this->loadData('loans');
            $loansInRepayment      = $loans->getLoansForProjectsWithStatus($originalLender->id_lender_account, array_merge(\projects_status::$runningRepayment, [\projects_status::FUNDE]));
            $originalClientBalance = $clientManager->getClientBalance($originalClient);

            if (isset($_POST['succession_check'])) {
                $_SESSION['succession']['check'] = [
                    'accountBalance' => $originalClientBalance,
                    'numberLoans'    => count($loansInRepayment),
                    'formerClient'   => [
                        'nom'       => $originalClient->nom,
                        'prenom'    => $originalClient->prenom,
                        'id_client' => $originalClient->id_client
                    ],
                    'newOwner'       => [
                        'nom'       => $newOwner->nom,
                        'prenom'    => $newOwner->prenom,
                        'id_client' => $newOwner->id_client
                    ]
                ];
            }

            if (isset($_POST['succession_validate'])) {
                if (empty($_FILES['transfer_document']['name'])) {
                    $this->addErrorMessageAndRedirect('Il manque le justificatif de transfer');
                }

                /** @var \transfer $transfer */
                $transfer                     = $this->loadData('transfer');
                $transfer->id_client_origin   = $originalClient->id_client;
                $transfer->id_client_receiver = $newOwner->id_client;
                $transfer->id_transfer_type  = \transfer_type::TYPE_INHERITANCE;
                $transfer->create();

                $this->uploadTransferDocument($transferDocument, $transfer, 'transfer_document');

                /** @var \transactions $transactions */
                $transactions          = $this->loadData('transactions');
                $originalClientBalance = $clientManager->getClientBalance($originalClient);
                $this->transferAccountBalance($transfer, $transactions, $originalClientBalance);

                /** @var \loan_transfer $loanTransfer */
                $loanTransfer = $this->loadData('loan_transfer');
                /** @var \lenders_accounts $originalLender */
                $originalLender = $this->loadData('lenders_accounts');
                $originalLender->get($transfer->id_client_origin, 'id_client_owner');
                /** @var \lenders_accounts $newLender */
                $newLender = $this->loadData('lenders_accounts');
                $newLender->get($transfer->id_client_receiver, 'id_client_owner');

                $numberLoans  = 0;
                foreach ($loansInRepayment as $loan) {
                    $loans->get($loan['id_loan']);
                    $this->transferLoan($transfer, $loanTransfer, $loans, $newLender, $originalClient, $newOwner);
                    $loans->unsetData();
                    $numberLoans += 1;
                }
                /** @var \lenders_accounts_stats_queue $lenderStatQueue */
                $lenderStatQueue = $this->loadData('lenders_accounts_stats_queue');
                $lenderStatQueue->addLenderToQueue($newLender);
                $lenderStatQueue->addLenderToQueue($originalLender);

                $comment = 'Compte soldé . ' . $this->ficelle->formatNumber($originalClientBalance) . ' EUR et ' . $numberLoans . ' prêts transferés sur le compte client ' . $newOwner->id_client;
                try {
                    $clientStatusManager->closeAccount($originalClient, $_SESSION['user']['id_user'], $comment);
                } catch (\Exception $exception){
                    $this->addErrorMessageAndRedirect('Le status client n\'a pas pu être changé ' . $exception->getMessage());
                }

                $clientStatusManager->addClientStatus($newOwner, $_SESSION['user']['id_user'], $clientStatusManager->getLastClientStatus($newOwner), 'Reçu solde ('. $this->ficelle->formatNumber($originalClientBalance) .') et prêts (' . $numberLoans . ') du compte ' . $originalClient->id_client);

                $_SESSION['succession']['success'] = [
                    'accountBalance' => $originalClientBalance,
                    'numberLoans'    => $numberLoans,
                    'formerClient'   => [
                        'nom'    => $originalClient->nom,
                        'prenom' => $originalClient->prenom
                    ],
                    'newOwner'       => [
                        'nom'    => $newOwner->nom,
                        'prenom' => $newOwner->prenom
                    ]
                ];
            }

            header('Location: ' . $this->lurl . '/transferts/succession');
            die;
        }
    }

    /**
     * @param \transfer $transfer
     * @param \transactions $transactions
     * @param $accountBalance
     */
    private function transferAccountBalance(\transfer $transfer, \transactions $transactions, $accountBalance)
    {
        $transactions->id_client        = $transfer->id_client_origin;
        $transactions->montant          = -$accountBalance * 100;
        $transactions->status           = \transactions::STATUS_VALID;
        $transactions->type_transaction = \transactions_types::TYPE_LENDER_BALANCE_TRANSFER;
        $transactions->date_transaction = date('Y-m-d h:i:s');
        $transactions->id_langue        = 'fr';
        $transactions->id_transfer      = $transfer->id_transfer;
        $transactions->create();

        $transactions->unsetData();

        $transactions->id_client        =$transfer->id_client_receiver;
        $transactions->montant          = $accountBalance * 100;
        $transactions->status           = \transactions::STATUS_VALID;
        $transactions->type_transaction = \transactions_types::TYPE_LENDER_BALANCE_TRANSFER;
        $transactions->date_transaction = date('Y-m-d h:i:s');
        $transactions->id_langue        = 'fr';
        $transactions->id_transfer      = $transfer->id_transfer;
        $transactions->create();
    }

    private function transferLoan(\transfer $transfer, \loan_transfer $loanTransfer, \loans $loans, \lenders_accounts $newLender, \clients $originalClient, \clients $newOwner)
    {
        $loanTransfer->id_transfer = $transfer->id_transfer;
        $loanTransfer->id_loan     = $loans->id_loan;
        $loanTransfer->create();

        $loans->id_transfer = $loanTransfer->id_loan_transfer;
        $loans->id_lender   = $newLender->id_lender_account;
        $loans->update();

        $loanTransfer->unsetData();
        $this->transferRepaymentSchedule($loans, $newLender);
        $this->transferLoanPdf($loans, $originalClient, $newOwner);
        $this->deleteClaimsPdf($loans, $originalClient);
    }

    /**
     * @param \loans $loans
     * @param \lenders_accounts $newLender
     */
    private function transferRepaymentSchedule(\loans $loans, \lenders_accounts $newLender)
    {
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->loadData('echeanciers');

        foreach ($repaymentSchedule->select('id_loan = ' . $loans->id_loan) as $repayment){
            $repaymentSchedule->get($repayment['id_echeancier']);
            $repaymentSchedule->id_lender = $newLender->id_lender_account;
            $repaymentSchedule->update();
            $repaymentSchedule->unsetData();
        }
    }

    /**
     * @param string $errorMessage
     */
    private function addErrorMessageAndRedirect($errorMessage)
    {
        $_SESSION['succession']['error'] = $errorMessage;
        header('Location: ' . $this->lurl . '/transferts/succession');
        die;
    }

    /**
     * @param \attachment $attachment
     * @param \transfer $transfer
     * @param string $field
     * @return \attachment
     */
    private function uploadTransferDocument(\attachment $attachment, \transfer $transfer, $field)
    {
        if (false === isset($this->attachment_type) || false === $this->attachment_type instanceof attachment_type) {
            $this->attachment_type = $this->loadData('attachment_type');
        }

        if (false === isset($this->upload) || false === $this->upload instanceof upload) {
            $this->upload = $this->loadLib('upload');
        }

        if (false === isset($this->attachmentHelper) || false === $this->attachmentHelper instanceof attachment_helper) {
            $this->attachmentHelper = $this->loadLib('attachment_helper', array($attachment, $this->attachment_type, $this->path));
        }

        $newName = '';
        if (isset($_FILES[$field]['name']) && $fileInfo = pathinfo($_FILES[$field]['name'])) {
            $newName = mb_substr($fileInfo['filename'], 0, 20) . '_' . $transfer->id_client_origin . '_' . $transfer->id_client_receiver . '_' . $transfer->id_transfer;
        }

        $idAttachment = $this->attachmentHelper->upload($transfer->id_transfer, \attachment::TRANSFER, \attachment_type::TRANSFER_CERTIFICATE, $field, $this->upload, $newName);
        $attachment->get($idAttachment);

        return $attachment;
    }

    private function transferLoanPdf(\loans $loan, \clients $originalClient, \clients $newOwner)
    {
        $oldFilePath = $this->path . 'protected/pdf/contrat/contrat-' . $originalClient->hash . '-' . $loan->id_loan . '.pdf';
        $newFilePath = $this->path . 'protected/pdf/contrat/contrat-' . $newOwner->hash . '-' . $loan->id_loan . '.pdf';

        if (file_exists($oldFilePath)) {
            rename($oldFilePath, $newFilePath);
        }
    }

    private function deleteClaimsPdf(\loans $loan, \clients $originalClient)
    {
        $filePath      = $this->path . 'protected/pdf/declaration_de_creances/' . $loan->id_project . '/';
        $filePath      = ($loan->id_project == '1456') ? $filePath : $filePath . $originalClient->id_client . '/';
        $filePath      = $filePath . 'declaration-de-creances' . '-' . $originalClient->hash . '-' . $loan->id_loan . '.pdf';
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
