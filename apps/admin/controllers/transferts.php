<?php

class transfertsController extends bootstrap
{
    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        $this->users->checkAccess('transferts');

        $this->menu_admin = 'transferts';

        $this->types_remb = array(0 => '', 1 => 'Remboursement anticipé', 2 => 'Régularisation', 3 => 'Recouvrement');
    }

    public function _default()
    {
        $this->receptions = $this->loadData('receptions');

        $this->lvirements = $this->receptions->select('type = 2 AND status_virement = 1 AND id_project = 0', 'remb ASC,id_reception DESC');
        $this->statusVirement = array(0 => 'Reçu', 1 => 'Attribué manu', 2 => 'Attribué auto', 3 => 'Rejeté', 4 => 'Rejet');
    }

    public function _prelevements()
    {
        $this->receptions = $this->loadData('receptions');

        $this->lprelevements = $this->receptions->select('type = 1 AND status_prelevement = 2', 'id_reception DESC');
        $this->statusPrelevement = array(0 => 'Reçu', 1 => 'Attribué manu', 2 => 'Attribué auto', 3 => 'Rejeté', 4 => 'Rejet');
    }

    public function _virements_emprunteurs()
    {
        $this->receptions   = $this->loadData('receptions');
        $this->projects     = $this->loadData('projects');
        $transactions       = $this->loadData('transactions');
        $bank_unilend       = $this->loadData('bank_unilend');
        $companies          = $this->loadData('companies');

        $this->settings->get('Recouvrement - commission ht', 'type');
        $commission_ht = $this->settings->value;

        $this->settings->get('TVA', 'type');
        $tva = $this->settings->value;

        if (isset($_POST['id']) && isset($_POST['id_reception'])) {
            if ($this->projects->get($_POST['id']) && $this->receptions->get($_POST['id_reception'])) {
                $companies->get($this->projects->id_company, 'id_company');

                if (isset($_POST['montant_edite']) && $_POST['montant_edite'] != "" && $_POST['montant_edite'] > 0) {
                    $this->receptions->montant = ($_POST['montant_edite'] * 100);
                }

                // RA
                if ($_POST['type_remb'] == 1) {
                    $this->receptions->motif         = $_POST['motif'];
                    $this->receptions->id_client     = $companies->id_client_owner;
                    $this->receptions->id_project    = $_POST['id'];
                    $this->receptions->remb_anticipe = 1;
                    $this->receptions->status_bo     = 1;
                    $this->receptions->type_remb     = 1;
                    $this->receptions->remb          = 1;
                    $this->receptions->update();

                    $transactions->id_virement      = $this->receptions->id_reception;
                    $transactions->id_project       = $this->projects->id_project;
                    $transactions->montant          = $this->receptions->montant;
                    $transactions->id_langue        = 'fr';
                    $transactions->date_transaction = date('Y-m-d H:i:s');
                    $transactions->status           = 1;
                    $transactions->etat             = 1;
                    $transactions->transaction      = 1;
                    $transactions->type_transaction = 22; // remboursement anticipe
                    $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                    $transactions->create();

                    $bank_unilend->id_transaction = $transactions->id_transaction;
                    $bank_unilend->id_project     = $this->projects->id_project;
                    $bank_unilend->montant        = $this->receptions->montant;
                    $bank_unilend->type           = 1; // remb emprunteur
                    $bank_unilend->status         = 0; // chez unilend
                    $bank_unilend->create();
                } elseif (in_array($_POST['type_remb'], array(2, 3))) { // 2 Regularisation /  3 recouvrement
                    $motif      = $_POST['motif'];
                    $id_project = $_POST['id'];

                    // Regularisation
                    if ($_POST['type_remb'] == 2) {
                        $type_remb        = 2;
                        $type_transaction = 24;
                        $montant          = $this->receptions->montant;
                    } else { // Recouvrement
                        $type_remb        = 3;
                        $type_transaction = 25;

                        // Montant avec la com + tva (on enregistre le prelevement avec la com et pareille chez unilend)
                        $montant = ($this->receptions->montant / (1 - ($commission_ht * (1 + $tva))));
                    }

                    // on met a jour le virement RA
                    $this->receptions->motif      = $motif;
                    $this->receptions->id_client  = $companies->id_client_owner;
                    $this->receptions->id_project = $id_project;
                    $this->receptions->status_bo  = 1;
                    $this->receptions->type_remb  = $type_remb;
                    $this->receptions->remb       = 1;
                    $this->receptions->update();

                    $receptions                     = $this->loadData('receptions');
                    $receptions->id_parent          = $this->receptions->id_reception; // fils d'une reception virement
                    $receptions->motif              = $motif;
                    $receptions->montant            = $montant;
                    $receptions->type               = 1; // prelevement
                    $receptions->type_remb          = $type_remb; // regularisation / recouvrement
                    $receptions->status_prelevement = 2; // émis
                    $receptions->status_bo          = 1; // attr manu
                    $receptions->remb               = 1; // remboursé oui
                    $receptions->id_client          = $companies->id_client_owner;
                    $receptions->id_project         = $id_project;
                    $receptions->ligne              = $this->receptions->ligne;
                    $receptions->create();

                    $transactions->id_prelevement   = $receptions->id_reception;
                    $transactions->id_client        = $companies->id_client_owner;
                    $transactions->montant          = $receptions->montant;
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

                    // on met a jour les echeances que pour les regularisations
                    if ($_POST['type_remb'] == 2) {
                        $this->updateEcheances($this->projects->id_project, $this->receptions->montant, $this->projects->remb_auto);
                    }
                }

                header('Location: ' . $this->lurl . '/transferts/virements_emprunteurs');
                die;
            }
        }

        $this->lvirements = $this->receptions->select('type = 2 AND status_virement = 1 AND (type_remb != 0 OR id_client = 0)', 'id_reception DESC');
        $this->statusVirement = array(0 => 'Reçu', 1 => 'Attribué manu', 2 => 'Attribué auto', 3 => 'Rejeté', 4 => 'Rejet');
    }

    private function updateEcheances($id_project, $montant, $remb_auto)
    {
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        $echeanciers            = $this->loadData('echeanciers');
        $projects_remb          = $this->loadData('projects_remb');

        $eche    = $echeanciers_emprunteur->select('status_emprunteur = 0 AND id_project = ' . $id_project, 'ordre ASC');
        $sumRemb = ($montant / 100);
        $newsum  = $sumRemb;

        foreach ($eche as $e) {
            $ordre = $e['ordre'];

            // on récup le montant que l'emprunteur doit rembourser
            $montantDuMois = $echeanciers->getMontantRembEmprunteur($e['montant'] / 100, $e['commission'] / 100, $e['tva'] / 100);
            // On verifie si le montant a remb est inferieur ou égale a la somme récupéré
            if ($montantDuMois <= $newsum) {
                // On met a jour les echeances du mois
                $echeanciers->updateStatusEmprunteur($id_project, $ordre);

                $echeanciers_emprunteur->get($id_project, 'ordre = ' . $ordre . ' AND id_project');
                $echeanciers_emprunteur->status_emprunteur             = 1;
                $echeanciers_emprunteur->date_echeance_emprunteur_reel = date('Y-m-d H:i:s');
                $echeanciers_emprunteur->update();

                // et on retire du wallet unilend
                $newsum = $newsum - $montantDuMois;

                if ($projects_remb->counter('id_project = "' . $id_project . '" AND ordre = "' . $ordre . '" AND status IN(0,1)') <= 0) {
                    $date_echeance_preteur = $echeanciers->select('id_project = "' . $id_project . '" AND ordre = "' . $ordre . '"', '', 0, 1);
                    // On regarde si le remb preteur auto est autorisé (eclatement preteur auto)
                    if ($remb_auto == 0) {
                        // file d'attente pour les remb auto preteurs
                        $projects_remb->id_project                = $id_project;
                        $projects_remb->ordre                     = $ordre;
                        $projects_remb->date_remb_emprunteur_reel = date('Y-m-d H:i:s');
                        $projects_remb->date_remb_preteurs        = $date_echeance_preteur[0]['date_echeance'];
                        $projects_remb->date_remb_preteurs_reel   = '0000-00-00 00:00:00';
                        $projects_remb->status                    = 0; // nom remb aux preteurs
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
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $this->receptions = $this->loadData('receptions');
        $this->receptions->get($this->params[0], 'id_reception');

        if ($this->receptions->id_client != 0) {
            header('Location: ' . $this->lurl . '/transferts');
            die;
        }
    }

    public function _attribution_emprunteur()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $this->receptions     = $this->loadData('receptions');
        $this->receptions->get($this->params[0], 'id_reception');

        if ($this->receptions->id_project != 0) {
            header('Location: ' . $this->lurl . '/transferts');
            die;
        }
    }

    public function _attribution_project()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $this->receptions = $this->loadData('receptions');
        $this->receptions->get($this->params[0], 'id_reception');

        if ($this->receptions->id_client != 0) {
            header('Location: ' . $this->lurl . '/transferts/prelevements');
            die;
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

                                    // recup lender
                                    $this->lenders_accounts->get($id_client, 'id_client_owner');

                                    // recup des echeances non remb
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

                $retour = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = 10', 'added DESC', 0, 1);

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

            // Projet
            $this->projects->get($this->receptions->id_project, 'id_project');

            // last recouvrement
            if (isset($_SESSION['DER']) && $_SESSION['DER'] != '') {
                $this->lastDateRecouvrement = date('d/m/Y', strtotime($_SESSION['DER']));
                $this->lastFormatSql        = date('Y-m-d', strtotime($_SESSION['DER']));
            } else {
                $retour = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = 10', 'added DESC', 0, 1);
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
}
