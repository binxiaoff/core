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
                $transactions->status           = 1;
                $transactions->etat             = 1;
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

        $eche   = $echeanciers_emprunteur->select('status_emprunteur = 0 AND id_project = ' . $id_project, 'ordre ASC');
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
        $this->setting = $this->loadData('settings');

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
            && $transactions->get($_POST['id_reception'], 'status = 1 AND etat = 1 AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT . ' AND id_prelevement')
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

        $projects               = $this->loadData('projects');
        $companies              = $this->loadData('companies');
        $clients                = $this->loadData('clients');
        $receptions             = $this->loadData('receptions');
        $transactions           = $this->loadData('transactions');
        $new_transactions       = $this->loadData('transactions');
        $bank_unilend           = $this->loadData('bank_unilend');
        $echeanciers            = $this->loadData('echeanciers');
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        $projects_remb          = $this->loadData('projects_remb');

        if (
            isset($_POST['id_project'], $_POST['id_reception'])
            && $projects->get($_POST['id_project'], 'id_project')
            && $receptions->get($_POST['id_reception'], 'id_reception')
            && $transactions->get($_POST['id_reception'], 'status = 1 AND etat = 1 AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT . ' AND id_prelevement')
            && false === $new_transactions->get($_POST['id_reception'], 'status = 1 AND etat = 1 AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION . ' AND id_prelevement')
        ) {
            $companies->get($projects->id_company, 'id_company');
            $clients->get($companies->id_client_owner, 'id_client');

            $new_transactions->id_prelevement   = $receptions->id_reception;
            $new_transactions->id_client        = $clients->id_client;
            $new_transactions->montant          = - $receptions->montant;
            $new_transactions->id_langue        = 'fr';
            $new_transactions->date_transaction = date('Y-m-d H:i:s');
            $new_transactions->status           = 1;
            $new_transactions->etat             = 1;
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

            $eche   = $echeanciers_emprunteur->select('status_emprunteur = 1 AND id_project = ' . $projects->id_project, 'ordre DESC');
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
        $this->dates             = $this->loadLib('dates');
        $this->offres_bienvenues = $this->loadData('offres_bienvenues');
        $this->clients           = $this->loadData('clients');
        $this->companies         = $this->loadData('companies');
        /** @var \offres_bienvenues_details $oWelcomeOfferDetails */
        $oWelcomeOfferDetails = $this->loadData('offres_bienvenues_details');
        /** @var \transactions $oTransactions */
        $oTransactions = $this->loadData('transactions');
        /** @var \wallets_lines $oWalletsLines */
        $oWalletsLines = $this->loadData('wallets_lines');
        /** @var \bank_unilend $oBankUnilend */
        $oBankUnilend = $this->loadData('bank_unilend');
        /** @var \lenders_accounts $oLendersAccounts */
        $oLendersAccounts = $this->loadData('lenders_accounts');
        /** @var \settings $oSettings */
        $oSettings = $this->loadData('settings');

        if (isset($this->params[0])) {
            $this->clients->get($this->params[0]);
            $this->companies->get('id_client_owner', $this->clients->id_client);
        }
        $this->offres_bienvenues->get(1, 'status = 0 AND id_offre_bienvenue');

        $oSettings->get('Offre de bienvenue motif', 'type');
        $sWelcomeOfferMotive = $oSettings->value;

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

            $bOfferValid                      = false;
            $bEnoughMoneyLeft                 = false;
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
                $oWelcomeOfferDetails->id_offre_bienvenue = $this->offres_bienvenues->id_offre_bienvenue;
                $oWelcomeOfferDetails->motif              = $sWelcomeOfferMotive;
                $oWelcomeOfferDetails->id_client          = $this->clients->id_client;
                $oWelcomeOfferDetails->montant            = $this->offres_bienvenues->montant;
                $oWelcomeOfferDetails->status             = 0;
                $oWelcomeOfferDetails->create();

                $oTransactions->id_client                 = $this->clients->id_client;
                $oTransactions->montant                   = $oWelcomeOfferDetails->montant;
                $oTransactions->id_offre_bienvenue_detail = $oWelcomeOfferDetails->id_offre_bienvenue_detail;
                $oTransactions->id_langue                 = 'fr';
                $oTransactions->date_transaction          = date('Y-m-d H:i:s');
                $oTransactions->status                    = 1;
                $oTransactions->etat                      = 1;
                $oTransactions->ip_client                 = $_SERVER['REMOTE_ADDR'];
                $oTransactions->type_transaction          = \transactions_types::TYPE_WELCOME_OFFER;
                $oTransactions->create();

                $oWalletsLines->id_lender                = $oLendersAccounts->id_lender_account;
                $oWalletsLines->type_financial_operation = \wallets_lines::TYPE_MONEY_SUPPLY;
                $oWalletsLines->id_transaction           = $oTransactions->id_transaction;
                $oWalletsLines->status                   = 1;
                $oWalletsLines->type                     = 1;
                $oWalletsLines->amount                   = $oWelcomeOfferDetails->montant;
                $oWalletsLines->create();

                $oBankUnilend->id_transaction = $oTransactions->id_transaction;
                $oBankUnilend->montant        = - $oWelcomeOfferDetails->montant;
                $oBankUnilend->type           = \bank_unilend::TYPE_UNILEND_WELCOME_OFFER_PATRONAGE;
                $oBankUnilend->create();

                $oMailTemplate = $this->loadData('mail_templates');
                $oMailTemplate->get('offre-de-bienvenue', 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');

                $oSettings->get('Facebook', 'type');
                $sFacebook = $oSettings->value;

                $oSettings->get('Twitter', 'type');
                $sTwitter = $oSettings->value;

                $aVariables = array(
                    'surl'            => $this->surl,
                    'url'             => $this->furl,
                    'prenom_p'        => $this->clients->prenom,
                    'projets'         => $this->furl . '/projets-a-financer',
                    'offre_bienvenue' => $this->ficelle->formatNumber($oWelcomeOfferDetails->montant / 100),
                    'lien_fb'         => $sFacebook,
                    'lien_tw'         => $sTwitter
                );

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('offre-de-bienvenue', $aVariables);
                $message->setTo($this->clients->email);
                $mailer = $this->get('mailer');
                $mailer->send($message);
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

            /** @var \projects_status $projectStatus */
            $projectStatus = $this->loadData('projects_status');
            $projectStatus->getLastStatut($project->id_project);

            /** @var LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->info('Checking refund status (project ' . $project->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));

            /** @var \settings $paymentInspectionStopped */
            $paymentInspectionStopped = $this->loadData('settings');
            $paymentInspectionStopped->get('Controle statut remboursement', 'type');

            if ($projectStatus->status != \projects_status::FUNDE) {
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
                /** @var \echeanciers $repaymentSchedule */
                $repaymentSchedule = $this->loadData('echeanciers');
                /** @var \echeanciers_emprunteur $paymentSchedule */
                $paymentSchedule = $this->loadData('echeanciers_emprunteur');
                /** @var \projects_status_history $projectsStatusHistory */
                $projectsStatusHistory = $this->loadData('projects_status_history');
                /** @var \accepted_bids $acceptedBids */
                $acceptedBids = $this->loadData('accepted_bids');

                $paymentInspectionStopped->value = 0;
                $paymentInspectionStopped->update();

                $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::REMBOURSEMENT, $project);

                /** @var \clients_adresses $clientsAddresses */
                $clientsAddresses = $this->loadData('clients_adresses');
                $clientsAddresses->get($companies->id_client_owner, 'id_client');

                $this->settings->get('Part unilend', 'type');
                $PourcentageUnilend = $this->settings->value;

                $montant = $loans->sumPretsProjet($project->id_project);

                $partUnilend = $montant * $PourcentageUnilend;

                $montant -= $partUnilend;

                if (false === $transactions->get($project->id_project, 'type_transaction = ' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT . ' AND id_project')) {
                    $aMandate = $mandate->select('id_project = ' . $project->id_project . ' AND id_client = ' . $clients->id_client . ' AND status = ' . \clients_mandats::STATUS_SIGNED, 'id_mandat DESC', 0, 1);
                    $aMandate = array_shift($aMandate);

                    $transactions->id_client        = $clients->id_client;
                    $transactions->montant          = bcmul($montant, -100);
                    $transactions->montant_unilend  = bcmul($partUnilend, 100);
                    $transactions->id_langue        = 'fr';
                    $transactions->id_project       = $project->id_project;
                    $transactions->date_transaction = date('Y-m-d H:i:s');
                    $transactions->status           = '1';
                    $transactions->etat             = '1';
                    $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                    $transactions->civilite_fac     = $clients->civilite;
                    $transactions->nom_fac          = $clients->nom;
                    $transactions->prenom_fac       = $clients->prenom;
                    if ($clients->type == 2) {
                        $transactions->societe_fac = $companies->name;
                    }
                    $transactions->adresse1_fac     = $clientsAddresses->adresse1;
                    $transactions->cp_fac           = $clientsAddresses->cp;
                    $transactions->ville_fac        = $clientsAddresses->ville;
                    $transactions->id_pays_fac      = $clientsAddresses->id_pays;
                    $transactions->type_transaction = \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT;
                    $transactions->transaction      = 1;
                    $transactions->id_transaction   = $transactions->create();

                    $bank_unilend->id_transaction = $transactions->id_transaction;
                    $bank_unilend->id_project     = $project->id_project;
                    $bank_unilend->montant        = bcmul($partUnilend, 100);
                    $bank_unilend->create();

                    $oAccountUnilend                 = $this->loadData('platform_account_unilend');
                    $oAccountUnilend->id_transaction = $transactions->id_transaction;
                    $oAccountUnilend->id_project     = $project->id_project;
                    $oAccountUnilend->amount         = bcmul($partUnilend, 100);
                    $oAccountUnilend->type           = \platform_account_unilend::TYPE_COMMISSION_PROJECT;
                    $oAccountUnilend->create();

                    $virements->id_client      = $clients->id_client;
                    $virements->id_project     = $project->id_project;
                    $virements->id_transaction = $transactions->id_transaction;
                    $virements->montant        = bcmul($montant, 100);
                    $virements->motif          = $oProjectManager->getBorrowerBankTransferLabel($project);
                    $virements->type           = 2;
                    $virements->create();

                    $prelevements = $this->loadData('prelevements');

                    $echea = $paymentSchedule->select('id_project = ' . $project->id_project);

                    foreach ($echea as $key => $e) {
                        $dateEcheEmp = strtotime($e['date_echeance_emprunteur']);
                        $result      = mktime(0, 0, 0, date("m", $dateEcheEmp), date("d", $dateEcheEmp) - 15, date("Y", $dateEcheEmp));
                        $dateExec    = date('Y-m-d', $result);

                        $montant = $repaymentSchedule->getMontantRembEmprunteur($e['montant'], $e['commission'], $e['tva']);

                        $prelevements->id_client                          = $clients->id_client;
                        $prelevements->id_project                         = $project->id_project;
                        $prelevements->motif                              = $virements->motif;
                        $prelevements->montant                            = $montant;
                        $prelevements->bic                                = str_replace(' ', '', $aMandate['bic']);
                        $prelevements->iban                               = str_replace(' ', '', $aMandate['iban']);
                        $prelevements->type_prelevement                   = 1; // recurrent
                        $prelevements->type                               = 2; //emprunteur
                        $prelevements->num_prelevement                    = $e['ordre'];
                        $prelevements->date_execution_demande_prelevement = $dateExec;
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
                                $oNotificationManager->createEmailNotification($oNotification->id_notification, \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED, $clients->id_client, $aLoan['id_loan']);
                                $aLastLoans[] = $aLoan['id_loan'];
                            }
                        }
                    }

                    $oMailerManager->sendLoanAccepted($project);
                }

                $oMailerManager->sendBorrowerBill($project);

                $aRepaymentHistory = $projectsStatusHistory->select('id_project = ' . $project->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ')', 'id_project_status_history DESC', 0, 1);

                if (false === empty($aRepaymentHistory)) {
                    $oInvoiceCounter = $this->loadData('compteur_factures');
                    $oInvoice        = $this->loadData('factures');

                    $transactions->get($project->id_project, 'type_transaction = ' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT . ' AND status = 1 AND etat = 1 AND id_project');

                    $this->settings->get('TVA', 'type');
                    $fVATRate = (float) $this->settings->value;

                    $sDateFirstPayment  = $aRepaymentHistory[0]['added'];
                    $fCommission        = $transactions->montant_unilend;
                    $fVATFreeCommission = $fCommission / ($fVATRate + 1);

                    $oInvoice->num_facture     = 'FR-E' . date('Ymd', strtotime($sDateFirstPayment)) . str_pad($oInvoiceCounter->compteurJournalier($project->id_project, $sDateFirstPayment), 5, '0', STR_PAD_LEFT);
                    $oInvoice->date            = $sDateFirstPayment;
                    $oInvoice->id_company      = $companies->id_company;
                    $oInvoice->id_project      = $project->id_project;
                    $oInvoice->ordre           = 0;
                    $oInvoice->type_commission = \factures::TYPE_COMMISSION_FINANCEMENT;
                    $oInvoice->commission      = round($fVATFreeCommission / (abs($transactions->montant) + $fCommission) * 100, 0);
                    $oInvoice->montant_ttc     = $fCommission;
                    $oInvoice->montant_ht      = $fVATFreeCommission;
                    $oInvoice->tva             = ($fCommission - $fVATFreeCommission);
                    $oInvoice->create();
                }

                $paymentInspectionStopped->value = 1;
                $paymentInspectionStopped->update();

                $logger->info('Check refund status done (project ' . $project->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));
            } else {
                $_SESSION['freeow']['title']   = 'Déblocage des fonds impossible';
                $_SESSION['freeow']['message'] = 'Un remboursement est déjà en cours';
            }

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->id_project);
            die;
        }

        $aProjects = $project->selectProjectsByStatus(\projects_status::FUNDE, '', '', array(), '', '', false);

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
}
