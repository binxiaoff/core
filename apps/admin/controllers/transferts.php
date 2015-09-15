
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

        // post d'attribution de projet
        if (isset($_POST['id']) && isset($_POST['id_reception'])) {
            if ($this->projects->get($_POST['id']) && $this->receptions->get($_POST['id_reception'])) {
                // on check ici si le montant edité est rempli
                if (isset($_POST['montant_edite']) &&
                        $_POST['montant_edite'] != "" &&
                        $_POST['montant_edite'] > 0
                ) {
                    $this->receptions->montant = ($_POST['montant_edite'] * 100);
                }

                // RA
                if ($_POST['type_remb'] == 1) {
                    
                    // on met a jour le virement RA
                    $this->receptions->motif = $_POST['motif'];
                    $this->receptions->id_project = $_POST['id'];
                    $this->receptions->remb_anticipe = 1;
                    $this->receptions->status_bo = 1;
                    $this->receptions->update();

                    // transact
                    $transactions = $this->loadData('transactions');
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
                    $bank_unilend = $this->loadData('bank_unilend');
                    $bank_unilend->id_transaction = $transactions->id_transaction;
                    $bank_unilend->id_project = $this->projects->id_project;
                    $bank_unilend->montant = $this->receptions->montant;
                    $bank_unilend->type = 1; // remb emprunteur
                    $bank_unilend->status = 0; // chez unilend
                    $bank_unilend->create();
                }
                // Regularisation
                elseif ($_POST['type_remb'] == 2) {
                    
                }
                // Recouvrement
                elseif ($_POST['type_remb'] == 3) {
                    
                }
            }
        }

        // virements
        $this->lvirements = $this->receptions->select('type = 2 AND status_virement = 1 AND (remb_anticipe = 1 OR (remb_anticipe = 0 AND id_client = 0))', 'id_reception DESC');

        // Status Virement
        //$this->statusVirement = array(1 => 'Recu',2 => 'Emis', 3 => 'Rejeté');
        $this->statusVirement = array(0 => 'Reçu', 1 => 'Attribué manu', 2 => 'Attribué auto', 3 => 'Rejeté', 4 => 'Rejet');
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
