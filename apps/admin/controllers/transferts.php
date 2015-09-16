
<?php

class transfertsController extends bootstrap {

    var $Command;

    function transfertsController($command, $config, $app) {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        // Controle d'acces à la rubrique
        $this->users->checkAccess('transferts');

        // Activation du menu
        $this->menu_admin = 'transferts';
        
        // types de remb
        $this->types_remb = array(0 => '',1 => 'Remb anticipé', 2 => 'Régularisation', 3 => 'Recouvrement');
    }

    function _default() {
        $this->receptions = $this->loadData('receptions');

        // virements
        $this->lvirements = $this->receptions->select('type = 2 AND status_virement = 1 AND id_project = 0', 'remb ASC,id_reception DESC');

        // Status Virement
        //$this->statusVirement = array(1 => 'Recu',2 => 'Emis', 3 => 'Rejeté');
        $this->statusVirement = array(0 => 'Reçu', 1 => 'Attribué manu', 2 => 'Attribué auto', 3 => 'Rejeté', 4 => 'Rejet');
    }

    function _prelevements() {
        $this->receptions = $this->loadData('receptions');

        // virements
        $this->lprelevements = $this->receptions->select('type = 1 AND status_prelevement = 2', 'id_reception DESC');

        // Status Prelevement
        //$this->statusPrelevement = array(1 => 'Recu',2 => 'Emis', 3 => 'Rejeté');
        $this->statusPrelevement = array(0 => 'Reçu', 1 => 'Attribué manu', 2 => 'Attribué auto', 3 => 'Rejeté', 4 => 'Rejet');
    }

    function _virements_emprunteurs() {
        $this->receptions = $this->loadData('receptions');
        $this->projects = $this->loadData('projects');

        $transactions = $this->loadData('transactions');
        $transactions_types = $this->loadData('transactions_types');
        $bank_unilend = $this->loadData('bank_unilend');
        $soldes_emprunteurs = $this->loadData('soldes_emprunteurs');
        $companies = $this->loadData('companies');

        // post d'attribution de projet
        if (isset($_POST['id']) && isset($_POST['id_reception'])) {
            if ($this->projects->get($_POST['id']) && $this->receptions->get($_POST['id_reception'])) {

                // On recupere l'entreprise
                $companies->get($this->projects->id_company, 'id_company');

                // RA
                if ($_POST['type_remb'] == 1) {

                    // on check ici si le montant edité est rempli
                    if (isset($_POST['montant_edite']) && $_POST['montant_edite'] != "" && $_POST['montant_edite'] > 0) {
                        $this->receptions->montant = ($_POST['montant_edite'] * 100);
                    }

                    // on met a jour le virement RA
                    $this->receptions->motif = $_POST['motif'];
                    $this->receptions->id_client = $companies->id_client_owner;
                    $this->receptions->id_project = $_POST['id'];
                    $this->receptions->remb_anticipe = 1;
                    $this->receptions->status_bo = 1;
                    $this->receptions->type_remb = 1;
                    $this->receptions->remb = 1;
                    $this->receptions->update();

                    // transact
                    $transactions->id_virement = $this->receptions->id_reception;
                    $transactions->id_project = $this->projects->id_project;
                    $transactions->montant = $this->receptions->montant;
                    $transactions->id_langue = 'fr';
                    $transactions->date_transaction = date('Y-m-d H:i:s');
                    $transactions->status = 1;
                    $transactions->etat = 1;
                    $transactions->transaction = 1;
                    $transactions->type_transaction = 22; // remboursement anticipe
                    $transactions->ip_client = $_SERVER['REMOTE_ADDR'];
                    $transactions->id_transaction = $transactions->create();

                    // bank unilend
                    $bank_unilend->id_transaction = $transactions->id_transaction;
                    $bank_unilend->id_project = $this->projects->id_project;
                    $bank_unilend->montant = $this->receptions->montant;
                    $bank_unilend->type = 1; // remb emprunteur
                    $bank_unilend->status = 0; // chez unilend
                    $bank_unilend->create();

                    ///// DEBUT SOLDE EMPRUNTEUR /////

                    $lastSolde = $soldes_emprunteurs->lastSoldeEmprunteur($companies->id_client_owner);
                    $newSolde = $lastSolde + $this->receptions->montant;

                    $transactions_types->get(22, 'id_transaction_type');

                    $soldes_emprunteurs->id_client = $companies->id_client_owner;
                    $soldes_emprunteurs->id_company = $companies->id_client;
                    $soldes_emprunteurs->id_transaction = $transactions->id_transaction;
                    $soldes_emprunteurs->type = $transactions_types->nom;
                    $soldes_emprunteurs->montant = $this->receptions->montant;
                    $soldes_emprunteurs->solde = $newSolde;
                    $soldes_emprunteurs->date_transaction = date('Y-m-d H:i:s');
                    $soldes_emprunteurs->create();

                    ///// FIN SOLDE EMPRUNTEUR /////
                }
                // Regularisation
                elseif ($_POST['type_remb'] == 2) {

                    // on check ici si le montant edité est rempli
                    if (isset($_POST['montant_edite']) && $_POST['montant_edite'] != "" && $_POST['montant_edite'] > 0) {
                        $this->receptions->montant = ($_POST['montant_edite'] * 100);
                    }



                    // on met a jour le virement RA
                    $this->receptions->motif = $_POST['motif'];
                    $this->receptions->id_client = $companies->id_client_owner;
                    $this->receptions->id_project = $_POST['id'];
                    $this->receptions->status_bo = 1;
                    $this->receptions->type_remb = 2;
                    $this->receptions->remb = 1;
                    $this->receptions->update();

                    // on créé le prelevement
                    $receptions = $this->loadData('receptions');
                    $receptions->id_parent = $this->receptions->id_reception; // fils d'une reception virement
                    $receptions->motif = $_POST['motif'];
                    $receptions->montant = $this->receptions->montant;
                    $receptions->type = 1; // prelevement
                    $receptions->type_remb = 2; // regularisation
                    $receptions->status_prelevement = 2; // émis
                    $receptions->status_bo = 1; // attr manu
                    $receptions->remb = 1; // remboursé oui
                    $receptions->id_client = $companies->id_client_owner;
                    $receptions->id_project = $_POST['id'];
                    $receptions->ligne = $this->receptions->ligne;
                    $receptions->id_reception = $receptions->create();

                    // transact
                    $transactions->id_prelevement = $receptions->id_reception;
                    $transactions->id_client = $companies->id_client_owner;
                    $transactions->montant = $receptions->montant;
                    $transactions->id_langue = 'fr';
                    $transactions->date_transaction = date('Y-m-d H:i:s');
                    $transactions->status = 1;
                    $transactions->etat = 1;
                    $transactions->transaction = 1;
                    $transactions->type_transaction = 24; // Virement de régularisation (remb emprunteur)
                    $transactions->ip_client = $_SERVER['REMOTE_ADDR'];
                    $transactions->id_transaction = $transactions->create();

                    // bank unilend
                    $bank_unilend->id_transaction = $transactions->id_transaction;
                    $bank_unilend->id_project = $this->projects->id_project;
                    $bank_unilend->montant = $this->receptions->montant;
                    $bank_unilend->type = 1;
                    $bank_unilend->create();

                    $this->updateEcheances($this->projects->id_project, $this->receptions->montant, $this->projects->remb_auto);

                    ///// DEBUT SOLDE EMPRUNTEUR /////

                    $lastSolde = $soldes_emprunteurs->lastSoldeEmprunteur($companies->id_client_owner);
                    $newSolde = $lastSolde + $this->receptions->montant;

                    $transactions_types->get(24, 'id_transaction_type');

                    $soldes_emprunteurs->id_client = $companies->id_client_owner;
                    $soldes_emprunteurs->id_company = $companies->id_company;
                    $soldes_emprunteurs->id_transaction = $transactions->id_transaction;
                    $soldes_emprunteurs->type = $transactions_types->nom;
                    $soldes_emprunteurs->montant = $this->receptions->montant;
                    $soldes_emprunteurs->solde = $newSolde;
                    $soldes_emprunteurs->date_transaction = date('Y-m-d H:i:s');
                    $soldes_emprunteurs->create();

                    ///// FIN SOLDE EMPRUNTEUR /////
                }
                // Recouvrement
                elseif ($_POST['type_remb'] == 3) {
                    
                }

                header('location:' . $this->lurl . '/transferts/virements_emprunteurs');
                die;
            }
        }

        // virements
        $this->lvirements = $this->receptions->select('type = 2 AND status_virement = 1 AND (type_remb != 0 OR id_client = 0)', 'id_reception DESC');

        // Status Virement
        //$this->statusVirement = array(1 => 'Recu',2 => 'Emis', 3 => 'Rejeté');
        $this->statusVirement = array(0 => 'Reçu', 1 => 'Attribué manu', 2 => 'Attribué auto', 3 => 'Rejeté', 4 => 'Rejet');
    }

    function updateEcheances($id_project, $montant, $remb_auto) {

        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        $echeanciers = $this->loadData('echeanciers');
        $projects_remb = $this->loadData('projects_remb');

        // on parcourt les echeances
        //$eche = $echeanciers->getSumRembEmpruntByMonths($projects->id_project,'','0');
        $eche = $echeanciers_emprunteur->select('status_emprunteur = 0 AND id_project = ' . $id_project, 'ordre ASC');
        $sumRemb = ($montant / 100);

        $newsum = $sumRemb;
        foreach ($eche as $e) {
            $ordre = $e['ordre'];

            // on récup le montant que l'emprunteur doit rembourser
            $montantDuMois = $echeanciers->getMontantRembEmprunteur($e['montant'] / 100, $e['commission'] / 100, $e['tva'] / 100);
            // On verifie si le montant a remb est inferieur ou égale a la somme récupéré
            if ($montantDuMois <= $newsum) {
                // On met a jour les echeances du mois
                $echeanciers->updateStatusEmprunteur($id_project, $ordre);

                $echeanciers_emprunteur->get($id_project, 'ordre = ' . $ordre . ' AND id_project');
                $echeanciers_emprunteur->status_emprunteur = 1;
                $echeanciers_emprunteur->date_echeance_emprunteur_reel = date('Y-m-d H:i:s');
                $echeanciers_emprunteur->update();

                // et on retire du wallet unilend 
                $newsum = $newsum - $montantDuMois;

                if ($projects_remb->counter('id_project = "' . $id_project . '" AND ordre = "' . $ordre . '" AND status IN(0,1)') <= 0) {

                    $date_echeance_preteur = $echeanciers->select('id_project = "' . $id_project . '" AND ordre = "' . $ordre . '"', '', 0, 1);
                    // On regarde si le remb preteur auto est autorisé (eclatement preteur auto)
                    if ($remb_auto == 0) {
                        // file d'attente pour les remb auto preteurs
                        $projects_remb->id_project = $id_project;
                        $projects_remb->ordre = $ordre;
                        $projects_remb->date_remb_emprunteur_reel = date('Y-m-d H:i:s');
                        $projects_remb->date_remb_preteurs = $date_echeance_preteur[0]['date_echeance'];
                        $projects_remb->date_remb_preteurs_reel = '0000-00-00 00:00:00';
                        $projects_remb->status = 0; // nom remb aux preteurs
                        $projects_remb->create();
                    }
                }
            } else
                break;
        }
    }

    function _attribution() {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug = false;


        $this->receptions = $this->loadData('receptions');

        $this->receptions->get($this->params[0], 'id_reception');

        if ($this->receptions->id_client != 0) {
            header('location:' . $this->lurl . '/transferts');
            die;
        }
    }

    function _attribution_emprunteur() {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug = false;
        $this->receptions = $this->loadData('receptions');

        $this->receptions->get($this->params[0], 'id_reception');

        if ($this->receptions->id_project != 0) {
            header('location:' . $this->lurl . '/transferts');
            die;
        }
    }

    function _attribution_project() {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug = false;

        $this->receptions = $this->loadData('receptions');

        $this->receptions->get($this->params[0], 'id_reception');

        if ($this->receptions->id_client != 0) {
            header('location:' . $this->lurl . '/transferts/prelevements');
            die;
        }
    }

}
