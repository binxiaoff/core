<?php

class mouvementController extends bootstrap {

    var $Command;

    function mouvementController($command, $config, $app) {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        // On prend le header account
//        $this->setHeader('header_account');
//
//        // On check si y a un compte
//        if (!$this->clients->checkAccess()) {
//            header('Location:' . $this->lurl);
//            die;
//        } else {
//            // check preteur ou emprunteur (ou les deux)
//            $this->clients->checkStatusPreEmp($this->clients->status_pre_emp, 'preteur', $this->clients->id_client);
//        }
//
//        //Recuperation des element de traductions
//        $this->lng['preteur-mouvement'] = $this->ln->selectFront('preteur-mouvement', $this->language, $this->App);
//
//        $this->page = 'mouvement';
        
        

    }

    function _default_old() {
        
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
        $this->setView('../root/404');

        // Chargement des datas
//        $this->transactions = $this->loadData('transactions');
//        $this->lenders_accounts = $this->loadData('lenders_accounts');
//        $this->loans = $this->loadData('loans');
//        $this->echeanciers = $this->loadData('echeanciers');
//
//        $firstYear = date('Y', strtotime($this->clients->added));
//        $a = 0;
//        for ($i = $firstYear; $i <= date('Y'); $i++) {
//            $this->dateN[$a] = $firstYear + $a;
//            $a++;
//        }
//
//        //$this->dateN[0] = date('Y')-3;
//        /* $this->dateN[1] = date('Y')-2;
//          $this->dateN[2] = date('Y')-1;
//          $this->dateN[3] = date('Y');
//          $this->dateN[4] = date('Y')+1;
//          $this->dateN[5]  = date('Y')+2;
//          $this->dateN[6]= date('Y')+3; */
//
//        $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');
//
//        $sumVersParMois = $this->transactions->getSumDepotByMonths($this->clients->id_client, date('Y'));
//
//        $sumPretsParMois = $this->loans->getSumPretsByMonths($this->lenders_accounts->id_lender_account, date('Y'));
//
//        $sumRembParMois = $this->echeanciers->getSumRembByMonths($this->lenders_accounts->id_lender_account, date('Y'));
//
//        $sumIntbParMois = $this->echeanciers->getSumIntByMonths($this->lenders_accounts->id_lender_account, date('Y'));
//
//
//        for ($i = 1; $i <= 12; $i++) {
//            $i = ($i < 10 ? '0' . $i : $i);
//            $this->sumVersParMois[$i] = number_format(($sumVersParMois[$i] != '' ? $sumVersParMois[$i] : 0), 2, '.', '');
//            $this->sumPretsParMois[$i] = number_format(($sumPretsParMois[$i] != '' ? $sumPretsParMois[$i] : 0), 2, '.', '');
//            $this->sumRembParMois[$i] = number_format(($sumRembParMois[$i] != '' ? $sumRembParMois[$i] : 0), 2, '.', '');
//            $this->sumIntbParMois[$i] = number_format(($sumIntbParMois[$i] != '' ? $sumIntbParMois[$i] : 0), 2, '.', '');
//        }
    }

    function _default() {
        
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
        $this->setView('../root/404');
        
        // Chargement des datas
//        $this->transactions = $this->loadData('transactions');
//        $this->lenders_accounts = $this->loadData('lenders_accounts');
//        $this->loans = $this->loadData('loans');
//        $this->echeanciers = $this->loadData('echeanciers');
//        $this->transactions = $this->loadData('transactions');
//        $this->projects = $this->loadData('projects');
//        $this->companies = $this->loadData('companies');
//        $this->projects_status = $this->loadData('projects_status');
//
//        // Offre de bienvenue motif
//        $this->settings->get('Offre de bienvenue motif', 'type');
//        $this->motif_offre_bienvenue = $this->settings->value;
//
//        $firstYear = date('Y', strtotime($this->clients->added));
//        $a = 0;
//        for ($i = $firstYear; $i <= date('Y'); $i++) {
//            $this->dateN[$a] = $firstYear + $a;
//            $a++;
//        }
//
//        $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');
//
//        $sumVersParMois = $this->transactions->getSumDepotByMonths($this->clients->id_client, date('Y'));
//
//        $sumPretsParMois = $this->loans->getSumPretsByMonths($this->lenders_accounts->id_lender_account, date('Y'));
//
//        $sumRembParMois = $this->echeanciers->getSumRembByMonths($this->lenders_accounts->id_lender_account, date('Y'));
//
//        $sumIntbParMois = $this->echeanciers->getSumIntByMonths($this->lenders_accounts->id_lender_account, date('Y'));
//
//        $sumRevenuesfiscalesParMois = $this->echeanciers->getSumRevenuesFiscalesByMonths($this->lenders_accounts->id_lender_account, date('Y'));
//
//
//        for ($i = 1; $i <= 12; $i++) {
//            $i = ($i < 10 ? '0' . $i : $i);
//            $this->sumVersParMois[$i] = number_format(($sumVersParMois[$i] != '' ? $sumVersParMois[$i] : 0), 2, '.', '');
//            $this->sumPretsParMois[$i] = number_format(($sumPretsParMois[$i] != '' ? $sumPretsParMois[$i] : 0), 2, '.', '');
//            $this->sumRembParMois[$i] = number_format(($sumRembParMois[$i] != '' ? $sumRembParMois[$i] - $sumRevenuesfiscalesParMois[$i] : 0), 2, '.', '');
//            $this->sumIntbParMois[$i] = number_format(($sumIntbParMois[$i] != '' ? $sumIntbParMois[$i] - $sumRevenuesfiscalesParMois[$i] : 0), 2, '.', '');
//        }
//
//
//
//        ///////////////////
//        // HISTO TRANSAC //
//        ///////////////////
//
//        $this->lng['profile'] = $this->ln->selectFront('preteur-profile', $this->language, $this->App);
//
//        $year = date('Y');
//
//
//        //// transaction ///
//        //$this->lLoans = $this->loans->sumPretsByProject($this->lenders_accounts->id_lender_account,$year,'added DESC');
//
//        $this->lLoans = $this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND YEAR(added) = ' . $year . ' AND status = 0', 'added DESC');
//
//
//
//
//        $this->lTrans = $this->transactions->select('type_transaction IN (1,3,4,5,7,8,16,17) AND status = 1 AND etat = 1 AND display = 0 AND id_client = ' . $this->clients->id_client . ' AND YEAR(date_transaction) = ' . $year, 'added DESC');
//
//
//        $this->lesStatuts = array(1 => $this->lng['profile']['versement-initial'], 3 => $this->lng['profile']['alimentation-cb'], 4 => $this->lng['profile']['alimentation-virement'], 5 => 'Remboursement', 7 => $this->lng['profile']['alimentation-prelevement'], 8 => $this->lng['profile']['retrait'], 16 => $this->motif_offre_bienvenue, 17 => 'Retrait offre de bienvenue');
    }

    function _detail_transac() {
        
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
        $this->setView('../root/404');

        // On masque les Head, header et footer originaux plus le debug
//        $this->autoFireHeader = false;
//        $this->autoFireHead = false;
//        $this->autoFireFooter = false;
//        $this->autoFireDebug = false;
    }

    function _histo_transac() {
        
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
        $this->setView('../root/404');

        // On masque les Head, header et footer originaux plus le debug
//        $this->autoFireHeader = false;
//        $this->autoFireHead = false;
//        $this->autoFireFooter = false;
//        $this->autoFireDebug = false;
    }

}
