<?php

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

            $this->settings->get('Recouvrement - commission ht', 'type');
            $commission_ht = $this->settings->value;

            $this->settings->get('TVA', 'type');
            $tva = $this->settings->value;
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
            } elseif (in_array($_POST['type_remb'], array('regularisation', 'recouvrement'))) {
                if ($_POST['type_remb'] === 'regularisation') {
                    $type_remb        = \receptions::REPAYMENT_TYPE_REGULARISATION;
                    $type_transaction = \transactions_types::TYPE_REGULATION_BANK_TRANSFER;
                    $montant          = $this->receptions->montant;
                } else {
                    $type_remb        = \receptions::REPAYMENT_TYPE_RECOVERY;
                    $type_transaction = \transactions_types::TYPE_RECOVERY_BANK_TRANSFER;
                    $montant          = $this->receptions->montant / (1 - ($commission_ht * (1 + $tva)));// Montant avec la com + tva (on enregistre le prelevement avec la com et pareille chez unilend)
                }

                $this->receptions->id_project      = $this->projects->id_project;
                $this->receptions->id_client       = $companies->id_client_owner;
                $this->receptions->status_bo       = 1;
                $this->receptions->type_remb       = $type_remb;
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
                $transactions->type_transaction = $type_transaction; // Virement de régularisation (remb emprunteur)
                $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                $transactions->create();

                $bank_unilend->id_transaction = $transactions->id_transaction;
                $bank_unilend->id_project     = $this->projects->id_project;
                $bank_unilend->montant        = $montant;
                $bank_unilend->type           = 1;
                $bank_unilend->create();

                if ($_POST['type_remb'] === 'regularisation') {
                    $this->updateEcheances($this->projects->id_project, $this->receptions->montant);
                }
            }

            header('Location: ' . $this->lurl . '/transferts/emprunteurs');
            die;
        }
    }

    // @todo duplicate function in cron.php
    private function updateEcheances($id_project, $montant)
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

    public function _recouvrement()
    {
        $this->receptions                        = $this->loadData('receptions');
        $this->projects                          = $this->loadData('projects');
        $this->projects_status_history           = $this->loadData('projects_status_history');
        $this->echeanciers                       = $this->loadData('echeanciers');
        $this->receptions                        = $this->loadData('receptions');
        $this->lenders_accounts                  = $this->loadData('lenders_accounts');
        $this->transactions                      = $this->loadData('transactions');
        $this->echeanciers_recouvrements_prorata = $this->loadData('echeanciers_recouvrements_prorata');
        $this->bank_unilend                      = $this->loadData('bank_unilend');
        $this->settings                          = $this->loadData('settings');

        $this->settings->get('Recouvrement - commission ht', 'type');
        $commission_ht = $this->settings->value;

        $this->settings->get('TVA', 'type');
        $tva = $this->settings->value;

        if (isset($this->params[0]) && $this->receptions->get($this->params[0], 'type = 1 AND type_remb = 3 AND id_reception')) {
            $this->projects->get($this->receptions->id_project, 'id_project');

            if (isset($_POST['send_form_remb_preteurs'])) {
                if (isset($_SESSION['anti_double_action']) && $_SESSION['anti_double_action'] != '') {
                    header('Location: ' . $this->lurl . '/transferts/recouvrement/' . $this->receptions->id_reception);
                    die;
                }

                if ($this->bank_unilend->counter('id_project = ' . $this->projects->id_project . ' AND type = 1 AND status = 0') <= 0) {
                    $_SESSION['freeow']['title']   = 'Rembourser les preteurs';
                    $_SESSION['freeow']['message'] = 'Ce remboursement preteur a deja ete fait !';

                    header('Location: ' . $this->lurl . '/transferts/recouvrement/' . $this->receptions->id_reception);
                    die;
                }

                $_SESSION['anti_double_action'] = 'en cours';

                if (isset($_FILES['csv']) && $_FILES['csv']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/recouvrements/');

                    // nom du csv avec : date, id_projet, id_reception (prelevement)
                    $name = 'recouvrement_' . date('Ymd') . '_' . $this->projects->id_project . '_' . $this->receptions->id_reception;

                    if ($this->upload->doUpload('csv', $name, true)) {
                        if (($handle = fopen($this->path . 'protected/recouvrements/' . $name . '.csv', "r")) !== FALSE) {
                            $i           = 0;
                            $montant_csv = 0;
                            $results     = array();
                            while (($data = fgetcsv($handle, 2000, ";")) !== FALSE) {
                                if ($i > 0) {
                                    $montant_csv += $data[1];
                                    $results[$i] = $data;
                                }
                                $i++;
                            }
                            fclose($handle);

                            // montant du recouvrement
                            $montant_reception = ($this->receptions->montant / 100);

                            // Calcule montant sans la commission
                            $com_ht                     = $montant_reception * $commission_ht;
                            $com                        = round(($com_ht * $tva) + $com_ht, 2);
                            $montant_reception_hors_com = ($montant_reception - $com);
                            $montant_reception_hors_com = (int) $montant_reception_hors_com;

                            // Si le montant du recouvrement est egale au montant total du csv
                            if ($montant_csv == $montant_reception_hors_com && $results != false) {
                                $total_remb_brut = 0;
                                $total_etat      = 0;
                                $total_remb_net  = 0;

                                foreach ($results as $data) {
                                    $id_client    = $data[0];
                                    $recouvrement = $data[1];

                                    $this->lenders_accounts->get($id_client, 'id_client_owner');

                                    $lEcheances = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND status = 0', 'date_echeance ASC');

                                    $recouvrement_restant = $recouvrement;

                                    if ($lEcheances != false) {
                                        foreach ($lEcheances as $e) {
                                            $montant_echeance = ($e['montant'] / 100);
                                            $capital          = ($e['capital'] / 100);
                                            $interets         = ($e['interets'] / 100);

                                            echo '<br>----------------<br>';
                                            echo 'montant echeance (id lender : ' . $e['id_lender'] . ') ' . $e['ordre'] . ' : ';

                                            // si le recouvrement est superieur au montant de l'echeance
                                            if ($recouvrement_restant >= $montant_echeance) {
                                                // On verifie que la transaction n'existe pas
                                                if ($this->transactions->counter('id_echeancier = ' . $e['id_echeancier']) <= 0) {
                                                    $Array_fiscals = array(
                                                        'prelevements_obligatoires'    => $e['prelevements_obligatoires'],
                                                        'retenues_source'              => $e['retenues_source'],
                                                        'csg'                          => $e['csg'],
                                                        'prelevements_sociaux'         => $e['prelevements_sociaux'],
                                                        'contributions_additionnelles' => $e['contributions_additionnelles'],
                                                        'prelevements_solidarite'      => $e['prelevements_solidarite'],
                                                        'crds'                         => $e['crds']
                                                    );

                                                    // Remboursement echeance preteur (on remb la totalité de l'echeance)
                                                    $retour = $this->rembEcheance($this->receptions->id_reception, $e['id_echeancier'], $id_client, $this->lenders_accounts->id_lender_account, $capital, $interets, $Array_fiscals);

                                                    $total_remb_brut += $retour['remb_brut'];
                                                    $total_etat += $retour['etat'];
                                                    $total_remb_net += $retour['remb_net'];

                                                    // On retire ce qu'on a pris pour remb l'echeance
                                                    $recouvrement_restant -= $montant_echeance;

                                                    echo '<b>' . $montant_echeance . '</b> - Reste : ' . $recouvrement_restant . ' (remb normal)';
                                                } // Si on a deja une transaction on verifie si il ne s'agit pas d'un recouvrement au prorata
                                                elseif ($this->echeanciers_recouvrements_prorata->counter('id_echeancier = ' . $e['id_echeancier']) > 0) {

                                                    // on à trouvé au moins un resultat du coup on va regarder si le montant est le meme ou pas
                                                    $sum = $this->echeanciers_recouvrements_prorata->sumCapitalInterets('id_echeancier = ' . $e['id_echeancier']);

                                                    // montant prorata trouvé
                                                    $montant_echeance_recouvrement = $sum['capital'] + $sum['interets'];

                                                    // si il manque de l'argent on recupere l'argent qu'il faut
                                                    if ($montant_echeance_recouvrement < $montant_echeance) {
                                                        $montant_manquant  = $montant_echeance - $montant_echeance_recouvrement;
                                                        $capital_manquant  = $capital - $sum['capital'];
                                                        $interets_manquant = $interets - $sum['interets'];

                                                        $prelevements_obligatoires_manquant    = $e['prelevements_obligatoires'] - $sum['prelevements_obligatoires'];
                                                        $retenues_source_manquant              = $e['retenues_source'] - $sum['retenues_source'];
                                                        $csg_manquant                          = $e['csg'] - $sum['csg'];
                                                        $prelevements_sociaux_manquant         = $e['prelevements_sociaux'] - $sum['prelevements_sociaux'];
                                                        $contributions_additionnelles_manquant = $e['contributions_additionnelles'] - $sum['contributions_additionnelles'];
                                                        $prelevements_solidarite_manquant      = $e['prelevements_solidarite'] - $sum['prelevements_solidarite'];
                                                        $crds_manquant                         = $e['crds'] - $sum['crds'];

                                                        // array fiscal manquant <---- a verifier
                                                        $Array_fiscals = array(
                                                            'prelevements_obligatoires'    => $prelevements_obligatoires_manquant,
                                                            'retenues_source'              => $retenues_source_manquant,
                                                            'csg'                          => $csg_manquant,
                                                            'prelevements_sociaux'         => $prelevements_sociaux_manquant,
                                                            'contributions_additionnelles' => $contributions_additionnelles_manquant,
                                                            'prelevements_solidarite'      => $prelevements_solidarite_manquant,
                                                            'crds'                         => $crds_manquant
                                                        );

                                                        // Remboursement echeance preteur (on remb la totalité de l'echeance)
                                                        $retour = $this->rembEcheance($this->receptions->id_reception, $e['id_echeancier'], $id_client, $this->lenders_accounts->id_lender_account, $capital_manquant, $interets_manquant, $Array_fiscals, true, true);

                                                        $total_remb_brut += $retour['remb_brut'];
                                                        $total_etat += $retour['etat'];
                                                        $total_remb_net += $retour['remb_net'];

                                                        $recouvrement_restant -= $montant_manquant;

                                                        echo '<b>' . $montant_echeance . '</b> montant manquant : ' . $montant_manquant . ' (capital : <b>' . $capital_manquant . '</b> au lieu de ' . ($e['capital'] / 100) . ', interets : <b>' . $interets_manquant . '</b> au lieu de ' . ($e['interets'] / 100) . ') - Reste : ' . $recouvrement_restant . ' (remb manquant) <----- ';
                                                    }
                                                }
                                            } // si il reste de l'argent mais pas suffisament pour une echeance entiere
                                            elseif ($recouvrement_restant < $montant_echeance && $recouvrement_restant > 0) {
                                                if ($this->transactions->counter('id_echeancier = ' . $e['id_echeancier']) <= 0) {
                                                    $capital_prorata  = round(($recouvrement_restant * $capital) / $montant_echeance, 2);
                                                    $interets_prorata = round(($recouvrement_restant * $interets) / $montant_echeance, 2);

                                                    $Array_fiscals = array(
                                                        'prelevements_obligatoires'    => round(($recouvrement_restant * $e['prelevements_obligatoires']) / $montant_echeance, 2),
                                                        'retenues_source'              => round(($recouvrement_restant * $e['retenues_source']) / $montant_echeance, 2),
                                                        'csg'                          => round(($recouvrement_restant * $e['csg']) / $montant_echeance, 2),
                                                        'prelevements_sociaux'         => round(($recouvrement_restant * $e['prelevements_sociaux']) / $montant_echeance, 2),
                                                        'contributions_additionnelles' => round(($recouvrement_restant * $e['contributions_additionnelles']) / $montant_echeance, 2),
                                                        'prelevements_solidarite'      => round(($recouvrement_restant * $e['prelevements_solidarite']) / $montant_echeance, 2),
                                                        'crds'                         => round(($recouvrement_restant * $e['crds']) / $montant_echeance, 2)
                                                    );

                                                    // Remboursement echeance preteur (on remb qu'une partie de l'echeance)
                                                    $retour = $this->rembEcheance($this->receptions->id_reception, $e['id_echeancier'], $id_client, $this->lenders_accounts->id_lender_account, $capital_prorata, $interets_prorata, $Array_fiscals, true);

                                                    $total_remb_brut += $retour['remb_brut'];
                                                    $total_etat += $retour['etat'];
                                                    $total_remb_net += $retour['remb_net'];

                                                    // On retire ce qu'on a pris pour remb l'echeance
                                                    $recouvrement_restant = 0;

                                                    echo '<b>' . $montant_echeance . '</b> (capital : <b>' . $capital_prorata . '</b> au lieu de ' . ($e['capital'] / 100) . ', interets : <b>' . $interets_prorata . '</b> au lieu de ' . ($e['interets'] / 100) . ') - Reste : ' . $recouvrement_restant . ' (remb du reste) <----- ';
                                                } // Si on a deja une transaction on verifie si il ne s'agit pas d'un recouvrement au prorata
                                                elseif ($this->echeanciers_recouvrements_prorata->counter('id_echeancier = ' . $e['id_echeancier']) > 0) {
                                                    // on à trouvé au moins un resultat du coup on va regarder si le montant est le meme ou pas
                                                    $sum = $this->echeanciers_recouvrements_prorata->sumCapitalInterets('id_echeancier = ' . $e['id_echeancier']);

                                                    // montant prorata trouvé
                                                    $montant_echeance_recouvrement = $sum['capital'] + $sum['interets'];

                                                    // si il manque de l'argent on recupere l'argent qu'il faut
                                                    if ($montant_echeance_recouvrement < $montant_echeance) {

                                                        // on recup la diff
                                                        $montant_echeance_manquant             = $montant_echeance - $montant_echeance_recouvrement;
                                                        $capital_manquant                      = $capital - $sum['capital'];
                                                        $interets_manquant                     = $interets - $sum['interets'];
                                                        $prelevements_obligatoires_manquant    = $e['prelevements_obligatoires'] - $sum['prelevements_obligatoires'];
                                                        $retenues_source_manquant              = $e['retenues_source'] - $sum['retenues_source'];
                                                        $csg_manquant                          = $e['csg'] - $sum['csg'];
                                                        $prelevements_sociaux_manquant         = $e['prelevements_sociaux'] - $sum['prelevements_sociaux'];
                                                        $contributions_additionnelles_manquant = $e['contributions_additionnelles'] - $sum['contributions_additionnelles'];
                                                        $prelevements_solidarite_manquant      = $e['prelevements_solidarite'] - $sum['prelevements_solidarite'];
                                                        $crds_manquant                         = $e['crds'] - $sum['crds'];

                                                        // array fiscal manquant <---- a verifier
                                                        // prorata
                                                        $capital_prorata  = round(($recouvrement_restant * $capital_manquant) / $montant_echeance_manquant, 2);
                                                        $interets_prorata = round(($recouvrement_restant * $interets_manquant) / $montant_echeance_manquant, 2);

                                                        $Array_fiscals = array(
                                                            'prelevements_obligatoires'    => round(($recouvrement_restant * $prelevements_obligatoires_manquant) / $montant_echeance_manquant, 2),
                                                            'retenues_source'              => round(($recouvrement_restant * $retenues_source_manquant) / $montant_echeance_manquant, 2),
                                                            'csg'                          => round(($recouvrement_restant * $csg_manquant) / $montant_echeance_manquant, 2),
                                                            'prelevements_sociaux'         => round(($recouvrement_restant * $prelevements_sociaux_manquant) / $montant_echeance_manquant, 2),
                                                            'contributions_additionnelles' => round(($recouvrement_restant * $contributions_additionnelles_manquant) / $montant_echeance_manquant, 2),
                                                            'prelevements_solidarite'      => round(($recouvrement_restant * $prelevements_solidarite_manquant) / $montant_echeance_manquant, 2),
                                                            'crds'                         => round(($recouvrement_restant * $crds_manquant) / $montant_echeance_manquant, 2)
                                                        );

                                                        // Remboursement echeance preteur (on remb qu'une partie de l'echeance qui avait deja eu un prorata)
                                                        $retour = $this->rembEcheance($this->receptions->id_reception, $e['id_echeancier'], $id_client, $this->lenders_accounts->id_lender_account, $capital_prorata, $interets_prorata, $Array_fiscals, true);

                                                        $total_remb_brut += $retour['remb_brut'];
                                                        $total_etat += $retour['etat'];
                                                        $total_remb_net += $retour['remb_net'];

                                                        // On retire ce qu'on a pris pour remb l'echeance
                                                        $recouvrement_restant = 0;

                                                        echo '<b>' . $montant_echeance . '</b> montant manquant : ' . $montant_manquant . ' (capital : <b>' . $capital_manquant . '</b> au lieu de ' . ($e['capital'] / 100) . ', interets : <b>' . $interets_manquant . '</b> au lieu de ' . ($e['interets'] / 100) . ') - Reste : ' . $recouvrement_restant . ' (remb manquant au prorata) <----- ';
                                                    } // end if montant recouvrement prorata < montant de l'echeance
                                                } // end if recouvrement prorata
                                            }
                                        }
                                    }
                                } // end if boucle preteurs csv
                                echo '<br>------------------------------------------------------<br>';
                                echo 'total_remb_brut : ' . $total_remb_brut . '<br>';
                                echo 'total_etat : ' . $total_etat . '<br>';
                                echo 'total_remb_net : ' . $total_remb_net . '<br>';

                                // On enregistre la transaction du total remb
                                $this->transactions->montant                  = 0;
                                $this->transactions->id_echeancier            = 0; // on reinitialise
                                $this->transactions->id_client                = 0; // on reinitialise
                                $this->transactions->montant_unilend          = '-' . $total_remb_net * 100;
                                $this->transactions->montant_etat             = $total_etat * 100;
                                $this->transactions->id_echeancier_emprunteur = ''; // id de l'echeance emprunteur
                                $this->transactions->id_langue                = 'fr';
                                $this->transactions->date_transaction         = date('Y-m-d H:i:s');
                                $this->transactions->status                   = 1;
                                $this->transactions->etat                     = 1;
                                $this->transactions->recouvrement             = 1;
                                $this->transactions->ip_client                = $_SERVER['REMOTE_ADDR'];
                                $this->transactions->type_transaction         = 10; // remb unilend pour les preteurs
                                $this->transactions->transaction              = 2; // transaction virtuelle
                                $this->transactions->create();

                                // bank_unilend (on retire l'argent redistribué)
                                $this->bank_unilend->id_transaction         = $this->transactions->id_transaction;
                                $this->bank_unilend->id_project             = $this->projects->id_project;
                                $this->bank_unilend->montant                = '-' . $total_remb_net * 100;
                                $this->bank_unilend->etat                   = $total_etat * 100;
                                $this->bank_unilend->type                   = 2; // remb unilend
                                $this->bank_unilend->id_echeance_emprunteur = '';
                                $this->bank_unilend->status                 = 1;
                                $this->bank_unilend->create();

                                $lesRembEmprun = $this->bank_unilend->select('type = 1 AND status = 0 AND id_project = ' . $this->projects->id_project, 'id_unilend ASC', 0, 1); // on ajoute la restriction pour BT 17882

                                // On parcourt les remb non reversé aux preteurs dans bank unilend et on met a jour le satut pour dire que c'est remb
                                foreach ($lesRembEmprun as $r) {
                                    $this->bank_unilend->get($r['id_unilend'], 'id_unilend');
                                    $this->bank_unilend->status = 1;
                                    $this->bank_unilend->update();
                                }
                            }
                        }
                    }
                }

                unset($_SESSION['anti_double_action']);

                $_SESSION['freeow']['title']   = 'Rembourser les preteurs';
                $_SESSION['freeow']['message'] = 'le remboursement a bien ete fait !';

                header('Location: ' . $this->lurl . '/transferts/recouvrement/' . $this->receptions->id_reception);
                die;
            }

            if ($this->params[1] == 'memory' && isset($_SESSION['DER']) && $_SESSION['DER'] != '') {
                $this->lastDateRecouvrement = date('d/m/Y', strtotime($_SESSION['DER']));
                $this->lastFormatSql        = date('Y-m-d', strtotime($_SESSION['DER']));
            } else {
                $_SESSION['DER'] = ''; // DER : date d'entree en recouvrement

                $retour = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::RECOUVREMENT . ')', 'id_project_status_history DESC', 0, 1);

                if ($retour != false) {
                    $this->lastDateRecouvrement = date('d/m/Y', strtotime($retour[0]['added']));
                    $this->lastFormatSql        = date('Y-m-d', strtotime($retour[0]['added']));
                    $_SESSION['DER']            = $this->lastFormatSql;
                } else {
                    $this->lastDateRecouvrement = date('d/m/Y');
                    $this->lastFormatSql        = date('Y-m-d');
                    $_SESSION['DER']            = $this->lastFormatSql;
                }
            }

            $this->CapitalEchu      = $this->echeanciers->sum('id_project = ' . $this->projects->id_project . ' AND status = 0 AND LEFT(date_echeance,10) <= "' . $this->lastFormatSql . '"', 'capital');
            $this->InteretsEchu     = $this->echeanciers->sum('id_project = ' . $this->projects->id_project . ' AND status = 0 AND LEFT(date_echeance,10) <= "' . $this->lastFormatSql . '"', 'interets');
            $this->CapitalRestantDu = $this->echeanciers->sum('id_project = ' . $this->projects->id_project . ' AND status = 0 AND LEFT(date_echeance,10) > "' . $this->lastFormatSql . '"', 'capital');
            $lastEcheanceImpaye     = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND status = 0 AND LEFT(date_echeance,10) <=  "' . $this->lastFormatSql . '"', 'date_echeance DESC', 0, 1);
            $dateLastEcheanceImpaye = date('Y-m-d', strtotime($lastEcheanceImpaye[0]['date_echeance']));
            $echeanceMoisDER        = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND ordre = ' . ($lastEcheanceImpaye[0]['ordre'] + 1), 'date_echeance DESC', 0, 1);
            $interetsMoisDER        = $this->echeanciers->sum('id_project = ' . $this->projects->id_project . ' AND ordre = ' . ($lastEcheanceImpaye[0]['ordre'] + 1), 'interets');
            $nbJourMoisDER          = date('t', strtotime($echeanceMoisDER[0]['date_echeance']));
            $diff                   = $this->dates->nbJours($dateLastEcheanceImpaye, $this->lastFormatSql);
            $this->interetsCourus   = round($diff / $nbJourMoisDER * $interetsMoisDER, 2);
            $this->montantRecouvre  = $this->receptions->sum('type_remb = 3 AND type = 1 AND remb = 1 AND id_project = ' . $this->projects->id_project);
            $this->montantRecouvre  = $this->montantRecouvre / 100;
        }
    }

    private function udpdate_statut_remb_echeance($id_echeancier)
    {
        $echeanciers = $this->loadData('echeanciers');

        if ($echeanciers->get($id_echeancier, 'id_echeancier')) {
            $echeanciers->status                        = 1; // remboursé
            $echeanciers->status_emprunteur             = 1; // remboursé emprunteur
            $echeanciers->date_echeance_reel            = date('Y-m-d H:i:s');
            $echeanciers->date_echeance_emprunteur_reel = date('Y-m-d H:i:s');
            $echeanciers->update();
        }
    }

    private function rembEcheance($id_reception, $id_echeancier, $id_client, $id_lender, $capital, $interet, $array_fiscal, $prorata = false, $manquant = false)
    {
        $echeanciers_recouvrements_prorata = $this->loadData('echeanciers_recouvrements_prorata');
        $transactions                      = $this->loadData('transactions');
        $wallets_lines                     = $this->loadData('wallets_lines');

        $remb_brut = round(($capital + $interet), 2);
        $etat      = round(array_sum($array_fiscal), 2);
        $remb_net  = $remb_brut - $etat;

        $transactions->id_client        = $id_client;
        $transactions->montant          = ($remb_net * 100);
        $transactions->id_echeancier    = $id_echeancier; // id de l'echeance remb
        $transactions->id_langue        = 'fr';
        $transactions->date_transaction = date('Y-m-d H:i:s');
        $transactions->status           = '1';
        $transactions->etat             = '1';
        $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
        $transactions->type_transaction = 5; // remb enchere
        $transactions->recouvrement     = 1; //on signale que c'est un recouvrement
        $transactions->transaction      = 2; // transaction virtuelle
        $transactions->id_transaction   = $transactions->create();

        $wallets_lines->id_lender                = $id_lender;
        $wallets_lines->type_financial_operation = 40;
        $wallets_lines->id_transaction           = $transactions->id_transaction;
        $wallets_lines->status                   = 1; // non utilisé
        $wallets_lines->type                     = 2; // transaction virtuelle
        $wallets_lines->amount                   = ($remb_net * 100);
        $wallets_lines->id_wallet_line           = $wallets_lines->create();

        if ($prorata) {
            $echeanciers_recouvrements_prorata->id_reception                 = $id_reception;
            $echeanciers_recouvrements_prorata->id_transaction               = $transactions->id_transaction;
            $echeanciers_recouvrements_prorata->id_echeancier                = $id_echeancier;
            $echeanciers_recouvrements_prorata->capital                      = ($capital * 100);
            $echeanciers_recouvrements_prorata->interets                     = ($interet * 100);
            $echeanciers_recouvrements_prorata->prelevements_obligatoires    = $array_fiscal['prelevements_obligatoires'];
            $echeanciers_recouvrements_prorata->retenues_source              = $array_fiscal['retenues_source'];
            $echeanciers_recouvrements_prorata->csg                          = $array_fiscal['csg'];
            $echeanciers_recouvrements_prorata->prelevements_sociaux         = $array_fiscal['prelevements_sociaux'];
            $echeanciers_recouvrements_prorata->contributions_additionnelles = $array_fiscal['contributions_additionnelles'];
            $echeanciers_recouvrements_prorata->prelevements_solidarite      = $array_fiscal['prelevements_solidarite'];
            $echeanciers_recouvrements_prorata->crds                         = $array_fiscal['crds'];
            $echeanciers_recouvrements_prorata->create();
            // Si on a un prorata pour combler la somme manquante de l'echeance
            if ($manquant) {
                $this->udpdate_statut_remb_echeance($id_echeancier);
            }
        } // echeance preteur  (si on fait un remb au prorata on met pas a jour le statut de l'echeance, on le fera quand le l'echeance sera entierement remb)
        else {
            $this->udpdate_statut_remb_echeance($id_echeancier);
        }

        return array('remb_brut' => $remb_brut, 'etat' => $etat, 'remb_net' => $remb_net);
    }

    public function _recouvrement_preteurs()
    {
        $this->receptions              = $this->loadData('receptions');
        $this->projects                = $this->loadData('projects');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->echeanciers             = $this->loadData('echeanciers');
        $this->receptions              = $this->loadData('receptions');
        $this->loans                   = $this->loadData('loans');

        if (isset($this->params[0]) && $this->receptions->get($this->params[0], 'type = 1 AND type_remb = 3 AND id_reception')) {
            $this->projects->get($this->receptions->id_project, 'id_project');

            if (isset($_SESSION['DER']) && $_SESSION['DER'] != '') {
                $this->lastDateRecouvrement = date('d/m/Y', strtotime($_SESSION['DER']));
                $this->lastFormatSql        = date('Y-m-d', strtotime($_SESSION['DER']));
            } else {
                $retour = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::RECOUVREMENT . ')', 'id_project_status_history DESC', 0, 1);
                if ($retour != false) {
                    $this->lastDateRecouvrement = date('d/m/Y', strtotime($retour[0]['added']));
                    $this->lastFormatSql        = date('Y-m-d', strtotime($retour[0]['added']));
                } else {
                    $this->lastDateRecouvrement = date('d/m/Y');
                    $this->lastFormatSql        = date('Y-m-d');
                }
            }

            $this->preteurs         = $this->loans->getPreteursDetail($this->projects->id_project, $this->lastFormatSql);
            $lastEcheanceImpaye     = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND status = 0 AND LEFT(date_echeance,10) <=  "' . $this->lastFormatSql . '"', 'date_echeance DESC', 0, 1);
            $dateLastEcheanceImpaye = date('Y-m-d', strtotime($lastEcheanceImpaye[0]['date_echeance']));
            $echeanceMoisDER        = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND ordre = ' . ($lastEcheanceImpaye[0]['ordre'] + 1), 'date_echeance DESC', 0, 1);
            $this->nbJourMoisDER    = date('t', strtotime($echeanceMoisDER[0]['date_echeance']));
            $this->diff             = $this->dates->nbJours($dateLastEcheanceImpaye, $this->lastFormatSql);
            $this->montantRecouvre  = $this->receptions->sum('type_remb = 3 AND type = 1 AND remb = 1 AND id_project = ' . $this->projects->id_project);
            $this->montantRecouvre  = $this->montantRecouvre / 100;
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
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-alimentation-manu', $this->language, $varMail);
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
        $oSettings               = $this->loadData('settings');
        //load for use of constants
        $this->loadData('transactions_types');
        $this->loadData('clients_status');

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
                $oMailsText->get('offre-de-bienvenue', 'status = ' . \mails_text::STATUS_ACTIVE . ' AND lang = "' . $this->language . '" AND type');

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
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('offre-de-bienvenue', $this->language, $aVariables);
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
}
