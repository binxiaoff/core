<?php

class operationsController extends bootstrap
{
    const LAST_OPERATION_DATE = '2013-01-01';

    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;
        // On prend le header account
        $this->setHeader('header_account');

        // On check si y a un compte
        if (false === $this->clients->checkAccess()) {
            header('Location:' . $this->lurl);
            die;
        }
        $this->clients->checkAccessLender();
        // page
        $this->page                      = 'operations';
        $this->lng['preteur-operations'] = $this->ln->selectFront('preteur-operations', $this->language, $this->App);
    }

    public function _default()
    {
        $this->transactions            = $this->loadData('transactions');
        $this->wallets_lines           = $this->loadData('wallets_lines');
        $this->bids                    = $this->loadData('bids');
        $this->loans                   = $this->loadData('loans');
        $this->projects                = $this->loadData('projects');
        $this->companies               = $this->loadData('companies');
        $this->projects_status         = $this->loadData('projects_status');
        $this->indexage_vos_operations = $this->loadData('indexage_vos_operations');
        $this->ifu                     = $this->loadData('ifu');
        $this->loadData('transactions_types'); // @todo included for class constants

        $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
        $this->lng['preteur-operations-pdf']            = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);
        $this->lng['preteur-operations-detail']         = $this->ln->selectFront('preteur-operations-detail', $this->language, $this->App);
        $this->lng['profile']                           = $this->ln->selectFront('preteur-profile', $this->language, $this->App);

        // conf par defaut pour la date (1M)
        $date_debut_time = mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'));
        $date_fin_time   = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

        // dates pour la requete
        $this->date_debut = date('Y-m-d', $date_debut_time);
        $this->date_fin   = date('Y-m-d', $date_fin_time);

        // affichage dans le filtre
        $this->date_debut_display = date('d/m/Y', $date_debut_time);
        $this->date_fin_display   = date('d/m/Y', $date_fin_time);

        $this->indexation_client($this->clients);

        $this->lTrans         = $this->indexage_vos_operations->select('id_client= ' . $this->clients->id_client . ' AND DATE(date_operation) >= "' . $this->date_debut . '" AND DATE(date_operation) <= "' . $this->date_fin . '"', 'date_operation DESC, id_projet DESC');
        $this->lProjectsLoans = $this->indexage_vos_operations->get_liste_libelle_projet('id_client = ' . $this->clients->id_client . ' AND DATE(date_operation) >= "' . $this->date_debut . '" AND DATE(date_operation) <= "' . $this->date_fin . '"');
        $this->lLoans         = $this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND YEAR(added) = ' . date('Y') . ' AND status = 0', 'added DESC');
        $this->liste_docs     = $this->ifu->select('id_client =' . $this->clients->id_client . ' AND statut = 1', 'annee ASC');

        unset($_SESSION['filtre_vos_operations']);
        unset($_SESSION['id_last_action']);
        $_SESSION['filtre_vos_operations']['debut']            = $this->date_debut_display;
        $_SESSION['filtre_vos_operations']['fin']              = $this->date_fin_display;
        $_SESSION['filtre_vos_operations']['nbMois']           = '1';
        $_SESSION['filtre_vos_operations']['annee']            = date('Y');
        $_SESSION['filtre_vos_operations']['tri_type_transac'] = 1;
        $_SESSION['filtre_vos_operations']['tri_projects']     = 1;
        $_SESSION['filtre_vos_operations']['id_last_action']   = 'order_operations';
        $_SESSION['filtre_vos_operations']['order']            = '';
        $_SESSION['filtre_vos_operations']['type']             = '';
        $_SESSION['filtre_vos_operations']['id_client']        = $this->clients->id_client;

        $this->commonLoans();

        $this->aFilterStatuses = $this->projects_status->select('status >= ' . \projects_status::REMBOURSEMENT, 'status ASC');
        $this->aLoansYears     = array_count_values(array_column($this->lSumLoans, 'loan_year'));
        krsort($this->aLoansYears);
    }

    public function _loans()
    {
        $this->hideDecoration();

        $this->lng['preteur-operations-detail'] = $this->ln->selectFront('preteur-operations-detail', $this->language, $this->App);

        $this->commonLoans();
    }

    public function _loans_csv()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $this->lng['preteur-operations-detail'] = $this->ln->selectFront('preteur-operations-detail', $this->language, $this->App);

        $this->commonLoans();

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment;filename=prets_' . date('Y-m-d_H:i:s') . '.xls');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        PHPExcel_Settings::setCacheStorageMethod(
            PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            array('memoryCacheSize' => '2048MB', 'cacheTime' => 1200)
        );

        $oDocument    = new PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);
        // @todo Intl
        $oActiveSheet->setCellValue('A1', 'Projet');
        $oActiveSheet->setCellValue('B1', 'Numéro de projet');
        $oActiveSheet->setCellValue('C1', 'Montant');
        $oActiveSheet->setCellValue('D1', 'Statut');
        $oActiveSheet->setCellValue('E1', 'Taux d\'intérêts');
        $oActiveSheet->setCellValue('F1', 'Premier remboursement');
        $oActiveSheet->setCellValue('G1', 'Prochain remboursement prévu');
        $oActiveSheet->setCellValue('H1', 'Date dernier remboursement');
        $oActiveSheet->setCellValue('I1', 'Capital perçu');
        $oActiveSheet->setCellValue('J1', 'Intérêts perçus');
        $oActiveSheet->setCellValue('K1', 'Capital restant dû');
        $oActiveSheet->setCellValue('L1', $this->lng['preteur-operations-detail']['titre-note']);

        foreach ($this->lSumLoans as $iRowIndex => $aProjectLoans) {
            $oActiveSheet->setCellValue('A' . ($iRowIndex + 2), $aProjectLoans['title']);
            $oActiveSheet->setCellValue('B' . ($iRowIndex + 2), $aProjectLoans['id_project']);
            $oActiveSheet->setCellValue('C' . ($iRowIndex + 2), $aProjectLoans['amount']);
            $oActiveSheet->setCellValue('D' . ($iRowIndex + 2), $this->lng['preteur-operations-detail']['info-status-' . $aProjectLoans['project_status']]);
            $oActiveSheet->setCellValue('E' . ($iRowIndex + 2), round($aProjectLoans['rate'], 1));
            $oActiveSheet->setCellValue('F' . ($iRowIndex + 2), $this->dates->formatDate($aProjectLoans['debut'], 'd/m/Y'));
            $oActiveSheet->setCellValue('G' . ($iRowIndex + 2), $this->dates->formatDate($aProjectLoans['next_echeance'], 'd/m/Y'));
            $oActiveSheet->setCellValue('H' . ($iRowIndex + 2), $this->dates->formatDate($aProjectLoans['fin'], 'd/m/Y'));
            $oActiveSheet->setCellValue('I' . ($iRowIndex + 2), (string) round($this->echeanciers->sum('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND id_project = ' . $aProjectLoans['id_project'] . ' AND status = 1', 'capital'), 2));
            $oActiveSheet->setCellValue('J' . ($iRowIndex + 2), round($this->echeanciers->sum('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND id_project = ' . $aProjectLoans['id_project'] . ' AND status = 1', 'interets'), 2));
            $oActiveSheet->setCellValue('K' . ($iRowIndex + 2), round($this->echeanciers->sum('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND id_project = ' . $aProjectLoans['id_project'] . ' AND status = 0', 'capital'), 2));

            $sRisk = isset($aProjectLoans['risk']) ? $aProjectLoans['risk'] : '';
            $sNote = $this->getProjectNote($sRisk);
            $oActiveSheet->setCellValue('L' . ($iRowIndex + 2), $sNote);
        }

        /** @var \PHPExcel_Writer_Excel5 $oWriter */
        $oWriter = PHPExcel_IOFactory::createWriter($oDocument, 'Excel5');
        $oWriter->save('php://output');
    }

    /**
     * @param string $sRisk a letter that gives the risk value [A-H]
     * @return string
     */
    private function getProjectNote($sRisk)
    {
        $sNote = '';
        switch ($sRisk) {
            case 'A':
                $sNote = '5';
                break;
            case 'B':
                $sNote = '4,5';
                break;
            case 'C':
                $sNote = '4';
                break;
            case 'D':
                $sNote = '3,5';
                break;
            case 'E':
                $sNote = '3';
                break;
            case 'F':
                $sNote = '2,5';
                break;
            case 'G':
                $sNote = '2';
                break;
            case 'H':
                $sNote = '1,5';
                break;
            default:
                $sNote = '';
        }
        return $sNote;
    }

    private function commonLoans()
    {
        $this->echeanciers = $this->loadData('echeanciers');

        $this->sOrderField     = isset($_POST['type']) ? $_POST['type'] : 'start';
        $this->sOrderDirection = isset($_POST['order']) && 'asc' === $_POST['order'] ? 'ASC' : 'DESC';

        switch ($this->sOrderField) {
            case 'status':
                $this->sOrderField = 'status';
                $sOrderBy          = 'project_status ' . $this->sOrderDirection . ', debut DESC, p.title ASC';
                break;
            case 'title':
                $this->sOrderField = 'title';
                $sOrderBy          = 'p.title ' . $this->sOrderDirection . ', debut DESC';
                break;
            case 'note':
                $this->sOrderField = 'note';
                $sOrderBy          = 'p.risk ' . $this->sOrderDirection . ', debut DESC, p.title ASC';
                break;
            case 'amount':
                $this->sOrderField = 'amount';
                $sOrderBy          = 'amount ' . $this->sOrderDirection . ', debut DESC, p.title ASC';
                break;
            case 'interest':
                $this->sOrderField = 'interest';
                $sOrderBy          = 'rate ' . $this->sOrderDirection . ', debut DESC, p.title ASC';
                break;
            case 'next':
                $this->sOrderField = 'next';
                $sOrderBy          = 'next_echeance ' . $this->sOrderDirection . ', debut DESC, p.title ASC';
                break;
            case 'end':
                $this->sOrderField = 'end';
                $sOrderBy          = 'fin ' . $this->sOrderDirection . ', debut DESC, p.title ASC';
                break;
            case 'repayment':
                $this->sOrderField = 'repayment';
                $sOrderBy          = 'mensuel ' . $this->sOrderDirection . ', debut DESC, p.title ASC';
                break;
            case 'start':
            default:
                $this->sOrderField = 'start';
                $sOrderBy          = 'debut ' . $this->sOrderDirection . ', p.title ASC';
                break;
        }

        $this->aProjectsInDebt = $this->projects->getProjectsInDebt();
        $this->lSumLoans       = $this->loans->getSumLoansByProject($this->lenders_accounts->id_lender_account, $sOrderBy, isset($_POST['year']) && is_numeric($_POST['year']) ? (int) $_POST['year'] : null, isset($_POST['status']) && is_numeric($_POST['status']) ? (int) $_POST['status'] : null);

        $this->aLoansStatuses = array(
            'no-problem'            => 0,
            'late-repayment'        => 0,
            'recovery'              => 0,
            'collective-proceeding' => 0,
            'default'               => 0,
            'refund-finished'       => 0,
        );

        foreach ($this->lSumLoans as $iLoandIndex => $aProjectLoans) {
            switch ($aProjectLoans['project_status']) {
                case \projects_status::PROBLEME:
                case \projects_status::PROBLEME_J_X:
                    $this->lSumLoans[$iLoandIndex]['status-color'] = 'warning';
                    ++$this->aLoansStatuses['late-repayment'];
                    break;
                case \projects_status::RECOUVREMENT:
                    $this->lSumLoans[$iLoandIndex]['status-color'] = 'problem';
                    ++$this->aLoansStatuses['recovery'];
                    break;
                case \projects_status::PROCEDURE_SAUVEGARDE:
                case \projects_status::REDRESSEMENT_JUDICIAIRE:
                case \projects_status::LIQUIDATION_JUDICIAIRE:
                    $this->lSumLoans[$iLoandIndex]['status-color'] = 'problem';
                    ++$this->aLoansStatuses['collective-proceeding'];
                    break;
                case \projects_status::DEFAUT:
                    $this->lSumLoans[$iLoandIndex]['status-color'] = 'default';
                    ++$this->aLoansStatuses['default'];
                    break;
                case \projects_status::REMBOURSE:
                case \projects_status::REMBOURSEMENT_ANTICIPE:
                    $this->lSumLoans[$iLoandIndex]['status-color'] = '';
                    ++$this->aLoansStatuses['refund-finished'];
                    break;
                case \projects_status::REMBOURSEMENT:
                default:
                    $this->lSumLoans[$iLoandIndex]['status-color'] = '';
                    ++$this->aLoansStatuses['no-problem'];
                    break;
            }
        }
    }

    public function _vos_operations()
    {
        $this->hideDecoration();
    }

    public function _vos_prets()
    {
        $this->hideDecoration();
    }

    public function _histo_transac()
    {
        $this->hideDecoration();
    }

    public function _doc_fiscaux()
    {
        $this->hideDecoration();
    }

    public function _vos_operation_csv()
    {
        $this->hideDecoration();

        $this->transactions                      = $this->loadData('transactions');
        $this->wallets_lines                     = $this->loadData('wallets_lines');
        $this->bids                              = $this->loadData('bids');
        $this->loans                             = $this->loadData('loans');
        $this->echeanciers                       = $this->loadData('echeanciers');
        $this->projects                          = $this->loadData('projects');
        $this->companies                         = $this->loadData('companies');
        $this->clients                           = $this->loadData('clients');
        $this->echeanciers_recouvrements_prorata = $this->loadData('echeanciers_recouvrements_prorata');

        $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
        $this->lng['preteur-operations-pdf']            = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);

        $post_debut            = $_SESSION['filtre_vos_operations']['debut'];
        $post_fin              = $_SESSION['filtre_vos_operations']['fin'];
        $post_nbMois           = $_SESSION['filtre_vos_operations']['nbMois'];
        $post_annee            = $_SESSION['filtre_vos_operations']['annee'];
        $post_tri_type_transac = $_SESSION['filtre_vos_operations']['tri_type_transac'];
        $post_tri_projects     = $_SESSION['filtre_vos_operations']['tri_projects'];
        $post_id_last_action   = $_SESSION['filtre_vos_operations']['id_last_action'];
        $post_order            = $_SESSION['filtre_vos_operations']['order'];
        $post_type             = $_SESSION['filtre_vos_operations']['type'];
        $post_id_client        = $_SESSION['filtre_vos_operations']['id_client'];

        $this->clients->get($post_id_client, 'id_client');

        // tri debut/fin
        if (isset($post_id_last_action) && in_array($post_id_last_action, array('debut', 'fin'))) {
            $debutTemp = explode('/', $post_debut);
            $finTemp   = explode('/', $post_fin);

            $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date debut
            $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date fin

            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;

        } elseif (isset($post_id_last_action) && $post_id_last_action == 'nbMois') {// NB mois
            $nbMois          = $post_nbMois;
            $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, 1, date('Y')); // date debut
            $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin
            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;
        } elseif (isset($post_id_last_action) && $post_id_last_action == 'annee') {// Annee
            $year            = $post_annee;
            $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date debut
            $date_fin_time   = mktime(0, 0, 0, 12, 31, $year); // date fin
            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;
        } elseif (isset($_SESSION['id_last_action'])) {// si on a une session
            if (in_array($_SESSION['id_last_action'], array('debut', 'fin'))) {
                $debutTemp       = explode('/', $post_debut);
                $finTemp         = explode('/', $post_fin);
                $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date debut
                $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date fin
            } elseif ($_SESSION['id_last_action'] == 'nbMois') {
                $nbMois          = $post_nbMois;
                $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, 1, date('Y')); // date debut
                $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin
            } elseif ($_SESSION['id_last_action'] == 'annee') {
                $year            = $post_annee;
                $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date debut
                $date_fin_time   = mktime(0, 0, 0, 12, 31, $year); // date fin
            }
        } else {// Par defaut (on se base sur le 1M)
            if (isset($post_debut) && isset($post_fin)) {
                $debutTemp       = explode('/', $post_debut);
                $finTemp         = explode('/', $post_fin);
                $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date debut
                $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date fin
            } else {
                $date_debut_time = mktime(0, 0, 0, date("m") - 1, 1, date('Y')); // date debut
                $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin
            }
        }

        $this->date_debut = date('Y-m-d', $date_debut_time);
        $this->date_fin   = date('Y-m-d', $date_fin_time);

        $array_type_transactions = array(
            1  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            2  => array(
                1 => $this->lng['preteur-operations-vos-operations']['offre-en-cours'],
                2 => $this->lng['preteur-operations-vos-operations']['offre-rejetee'],
                3 => $this->lng['preteur-operations-vos-operations']['offre-acceptee']
            ),
            3  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            4  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            5  => array(
                1 => $this->lng['preteur-operations-vos-operations']['remboursement'],
                2 => $this->lng['preteur-operations-vos-operations']['recouvrement']
            ),
            7  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            8  => $this->lng['preteur-operations-vos-operations']['retrait-dargents'],
            16 => $this->lng['preteur-operations-vos-operations']['offre-de-bienvenue'],
            17 => $this->lng['preteur-operations-vos-operations']['retrait-offre'],
            19 => $this->lng['preteur-operations-vos-operations']['gain-filleul'],
            20 => $this->lng['preteur-operations-vos-operations']['gain-parrain'],
            22 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe'],
            23 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe-preteur']
        );

        $array_type_transactions_liste_deroulante = array(
            1 => '1,2,3,4,5,7,8,16,17,19,20,23',
            2 => '3,4,7,8',
            3 => '3,4,7',
            4 => '8',
            5 => '2',
            6 => '5,23'
        );

        if (isset($post_tri_type_transac)) {
            $tri_type_transac = $array_type_transactions_liste_deroulante[$post_tri_type_transac];
        } else {
            $tri_type_transac = $array_type_transactions_liste_deroulante[1];
        }

        if (isset($post_tri_projects)) {
            if (in_array($post_tri_projects, array(0, 1))) {
                $tri_project = '';
            } else {
                $tri_project = ' AND le_id_project = ' . $post_tri_projects;
            }
        }

        $order = 'date_operation DESC, id_transaction DESC';
        if (isset($_POST['type']) && isset($_POST['order'])) {
            $this->type  = $_POST['type'];
            $this->order = $_POST['order'];

            if ($this->type == 'order_operations') {
                if ($this->order == 'asc') {
                    $order = ' type_transaction ASC, id_transaction ASC';
                } else {
                    $order = ' type_transaction DESC, id_transaction DESC';
                }
            } elseif ($this->type == 'order_projects') {
                if ($this->order == 'asc') {
                    $order = ' libelle_projet ASC , id_transaction ASC';
                } else {
                    $order = ' libelle_projet DESC , id_transaction DESC';
                }
            } elseif ($this->type == 'order_date') {
                if ($this->order == 'asc') {
                    $order = ' date_operation ASC, id_transaction ASC';
                } else {
                    $order = ' date_operation DESC, id_transaction DESC';
                }
            } elseif ($this->type == 'order_montant') {
                if ($this->order == 'asc') {
                    $order = ' montant_operation ASC, id_transaction ASC';
                } else {
                    $order = ' montant_operation DESC, id_transaction DESC';
                }
            } elseif ($this->type == 'order_bdc') {
                if ($this->order == 'asc') {
                    $order = ' ABS(bdc) ASC, id_transaction ASC';
                } else {
                    $order = ' ABS(bdc) DESC, id_transaction DESC';
                }
            } else {
                $order = 'date_operation DESC, id_transaction DESC';
            }
        }
        $this->indexage_vos_operations = $this->loadData('indexage_vos_operations');

        $this->lTrans = $this->indexage_vos_operations->select('type_transaction IN (' . $tri_type_transac . ') AND id_client = ' . $this->clients->id_client . ' AND DATE(date_operation) >= "' . $this->date_debut . '" AND DATE(date_operation) <= "' . $this->date_fin . '"' . $tri_project, $order);

        header("Content-type: application/vnd.ms-excel; charset=utf-8");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("content-disposition: attachment;filename=" . $this->bdd->generateSlug('operations_' . date('Y-m-d')) . ".xls");
        // si exoneré à la date de la transact on change le libelle
        $libelle_prelevements = $this->lng['preteur-operations-vos-operations']['prelevements-fiscaux-et-sociaux'];
        // on check si il s'agit d'une PM ou PP
        if ($this->clients->type != 1 && $this->clients->type != 3) {
            $libelle_prelevements = $this->lng['preteur-operations-vos-operations']['retenues-a-la-source'];
        }
        ?>
        <meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8"/>
        <table border=1>
            <tr>
                <th><?= $this->lng['preteur-operations-pdf']['operations'] ?></th>
                <th><?= $this->lng['preteur-operations-pdf']['info-titre-loan-id'] ?></th>
                <th><?= $this->lng['preteur-operations-pdf']['info-titre-project-id'] ?></th>
                <th><?= $this->lng['preteur-operations-pdf']['projets'] ?></th>
                <th><?= $this->lng['preteur-operations-pdf']['date-de-loperation'] ?></th>
                <th><?= $this->lng['preteur-operations-pdf']['montant-de-loperation'] ?></th>
                <th><?= $this->lng['preteur-operations-vos-operations']['capital-rembourse'] ?></th>
                <th><?= $this->lng['preteur-operations-vos-operations']['interets-recus'] ?></th>
                <th>Pr&eacute;l&egrave;vements obligatoires</th>
                <th>Retenue &agrave; la source</th>
                <th>CSG</th>
                <th>Pr&eacute;l&egrave;vements sociaux</th>
                <th>Contributions additionnelles</th>
                <th>Pr&eacute;l&egrave;vements solidarit&eacute;</th>
                <th>CRDS</th>
                <?php /* Recouvrement
                <th>Commission HT</th>
                <th>Commission TVA</th>
                <th>Commission TTC</th> */ ?>
                <th><?= $this->lng['preteur-operations-pdf']['solde-de-votre-compte'] ?></th>
                <td></td>
            </tr>
            <?php
            $asterix_on = false;
            foreach ($this->lTrans as $t) {
                $t['libelle_operation'] = $t['libelle_operation'];
                $t['libelle_projet']    = $t['libelle_projet'];
                if ($t['montant_operation'] > 0) {
                    $plus    = '+';
                    $moins   = '&nbsp;';
                    $couleur = ' style="color:#40b34f;"';
                } else {
                    $plus    = '&nbsp;';
                    $moins   = '-';
                    $couleur = ' style="color:red;"';
                }

                $sProjectId = $t['id_projet'] == 0 ? '' : $t['id_projet'];

                $solde = $t['solde'];
                // remb
                if ($t['type_transaction'] == 5 || $t['type_transaction'] == 23) {
                    $this->echeanciers->get($t['id_echeancier'], 'id_echeancier');

                    $retenuesfiscals = $this->echeanciers->prelevements_obligatoires + $this->echeanciers->retenues_source + $this->echeanciers->csg + $this->echeanciers->prelevements_sociaux + $this->echeanciers->contributions_additionnelles + $this->echeanciers->prelevements_solidarite + $this->echeanciers->crds;

                    if ($t['type_transaction'] == 23) {
                        $this->echeanciers->prelevements_obligatoires    = 0;
                        $this->echeanciers->retenues_source              = 0;
                        $this->echeanciers->interets                     = 0;
                        $this->echeanciers->retenues_source              = 0;
                        $this->echeanciers->csg                          = 0;
                        $this->echeanciers->prelevements_sociaux         = 0;
                        $this->echeanciers->contributions_additionnelles = 0;
                        $this->echeanciers->prelevements_solidarite      = 0;
                        $this->echeanciers->crds                         = 0;
                        $this->echeanciers->capital                      = $t['montant_operation'];
                    } elseif ($t['type_transaction'] == 5 && $t['recouvrement'] == 1 && $this->echeanciers_recouvrements_prorata->get($t['id_transaction'], 'id_transaction')) {
                        $retenuesfiscals = $this->echeanciers_recouvrements_prorata->prelevements_obligatoires + $this->echeanciers_recouvrements_prorata->retenues_source + $this->echeanciers_recouvrements_prorata->csg + $this->echeanciers_recouvrements_prorata->prelevements_sociaux + $this->echeanciers_recouvrements_prorata->contributions_additionnelles + $this->echeanciers_recouvrements_prorata->prelevements_solidarite + $this->echeanciers_recouvrements_prorata->crds;

                        $this->echeanciers->prelevements_obligatoires    = $this->echeanciers_recouvrements_prorata->prelevements_obligatoires;
                        $this->echeanciers->retenues_source              = $this->echeanciers_recouvrements_prorata->retenues_source;
                        $this->echeanciers->interets                     = $this->echeanciers_recouvrements_prorata->interets;
                        $this->echeanciers->retenues_source              = $this->echeanciers_recouvrements_prorata->retenues_source;
                        $this->echeanciers->csg                          = $this->echeanciers_recouvrements_prorata->csg;
                        $this->echeanciers->prelevements_sociaux         = $this->echeanciers_recouvrements_prorata->prelevements_sociaux;
                        $this->echeanciers->contributions_additionnelles = $this->echeanciers_recouvrements_prorata->contributions_additionnelles;
                        $this->echeanciers->prelevements_solidarite      = $this->echeanciers_recouvrements_prorata->prelevements_solidarite;
                        $this->echeanciers->crds                         = $this->echeanciers_recouvrements_prorata->crds;
                        $this->echeanciers->capital                      = $this->echeanciers_recouvrements_prorata->capital;
                    }
                    ?>
                    <tr>
                        <td><?= $t['libelle_operation'] ?></td>
                        <td><?= $t['bdc'] ?></td>
                        <td><?= $sProjectId ?></td>
                        <td><?= $t['libelle_projet'] ?></td>
                        <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                        <td<?= $couleur ?>><?= $this->ficelle->formatNumber($t['montant_operation'] / 100) ?></td>
                        <td><?= $this->ficelle->formatNumber(($this->echeanciers->capital / 100)) ?></td>
                        <td><?= $this->ficelle->formatNumber(($this->echeanciers->interets / 100)) ?></td>
                        <td><?= $this->ficelle->formatNumber($this->echeanciers->prelevements_obligatoires) ?></td>
                        <td><?= $this->ficelle->formatNumber($this->echeanciers->retenues_source) ?></td>
                        <td><?= $this->ficelle->formatNumber($this->echeanciers->csg) ?></td>
                        <td><?= $this->ficelle->formatNumber($this->echeanciers->prelevements_sociaux) ?></td>
                        <td><?= $this->ficelle->formatNumber($this->echeanciers->contributions_additionnelles) ?></td>
                        <td><?= $this->ficelle->formatNumber($this->echeanciers->prelevements_solidarite) ?></td>
                        <td><?= $this->ficelle->formatNumber($this->echeanciers->crds) ?></td>
                        <?php /* Recouvrement
                        <td><?= $this->ficelle->formatNumber($t['commission_ht'] / 100) ?></td>
                        <td><?= $this->ficelle->formatNumber($t['commission_tva'] / 100) ?></td>
                        <td><?= $this->ficelle->formatNumber($t['commission_ttc'] / 100) ?></td> */ ?>
                        <td><?= $this->ficelle->formatNumber($solde / 100) ?></td>
                        <td></td>
                    </tr>
                    <?
                } elseif (in_array($t['type_transaction'], array(8, 1, 3, 4, 16, 17, 19, 20))) {
                    // Récupération de la traduction et non plus du libelle dans l'indexation (si changement on est ko)
                    switch ($t['type_transaction']) {
                        case 8:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['retrait-dargents'];
                            break;
                        case 1:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['depot-de-fonds'];
                            break;
                        case 3:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['depot-de-fonds'];
                            break;
                        case 4:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['depot-de-fonds'];
                            break;
                        case 16:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['offre-de-bienvenue'];
                            break;
                        case 17:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['retrait-offre'];
                            break;
                        case 19:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['gain-filleul'];
                            break;
                        case 20:
                            $t['libelle_operation'] = $this->lng['preteur-operations-vos-operations']['gain-parrain'];
                            break;
                    }
                    $type = "";
                    if ($t['type_transaction'] == 8 && $t['montant'] > 0) {
                        $type = "Annulation retrait des fonds - compte bancaire clos";
                    } else {
                        $type = $t['libelle_operation'];
                    }
                    ?>
                    <tr>
                        <td><?= $type ?></td>
                        <td></td>
                        <td><?= $sProjectId ?></td>
                        <td></td>
                        <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                        <td<?= $couleur ?>><?= $this->ficelle->formatNumber($t['montant_operation'] / 100) ?></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <?php /* Recouvrement
                        <td></td>
                        <td></td>
                        <td></td> */ ?>
                        <td><?= $this->ficelle->formatNumber($solde / 100) ?></td>
                        <td></td>
                    </tr>
                    <?

                } elseif (in_array($t['type_transaction'], array(2))) {//offres en cours
                    $bdc = $t['bdc'];
                    if ($t['bdc'] == 0) {
                        $bdc = "";
                    }
                    //asterix pour les offres acceptees
                    $asterix       = "";
                    $offre_accepte = false;
                    if ($t['libelle_operation'] == $this->lng['preteur-operations-vos-operations']['offre-acceptee']) {
                        $asterix       = " *";
                        $offre_accepte = true;
                        $asterix_on    = true;
                    }
                    ?>
                    <tr>
                        <td><?= $t['libelle_operation'] ?></td>
                        <td><?= $bdc ?></td>
                        <td><?= $sProjectId ?></td>
                        <td><?= $t['libelle_projet'] ?></td>
                        <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                        <td<?= (! $offre_accepte ? $couleur : '') ?>><?= $this->ficelle->formatNumber($t['montant_operation'] / 100) ?></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <?php /* Recouvrement
                        <td></td>
                        <td></td>
                        <td></td> */ ?>
                        <td><?= $this->ficelle->formatNumber($t['solde'] / 100) ?></td>
                        <td><?= $asterix ?></td>
                    </tr>
                    <?
                }
            }
            ?>
        </table>
        <?php
        if ($asterix_on) {
            ?>
            <div>* <?= $this->lng['preteur-operations-vos-operations']['offre-acceptee-asterix-csv'] ?></div>
            <?
        }
        die;
    }

    public function _get_ifu()
    {
        // recup du fichier
        $hash_client = $this->params[0];
        $annee       = $this->params[1];
        $this->ifu   = $this->loadData('ifu');
        if ($this->clients->hash == $hash_client) {
            if ($this->ifu->get($this->clients->id_client, 'annee = ' . $annee . ' AND statut = 1 AND id_client')) {
                if (file_exists($this->ifu->chemin)) {
                    $url = ($this->ifu->chemin);
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . basename($url) . '";');
                    @readfile($url);
                    die();
                }
            }
        }
    }

    private function indexation_client(\clients $clients)
    {
        $this->echeanciers                       = $this->loadData('echeanciers');
        $this->echeanciers_recouvrements_prorata = $this->loadData('echeanciers_recouvrements_prorata');
        $this->indexage_vos_operations           = $this->loadData('indexage_vos_operations');
        $this->transactions                      = $this->loadData('transactions');

        $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
        $this->lng['preteur-operations-pdf']            = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);
        $this->lng['preteur-operations']                = $this->ln->selectFront('preteur-operations', $this->language, $this->App);

        $this->settings->get('Recouvrement - commission ht', 'type');
        $commission_ht = $this->settings->value;

        $this->settings->get('TVA', 'type');
        $tva = $this->settings->value;

        $array_type_transactions = array(
            1  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            2  => array(
                1 => $this->lng['preteur-operations-vos-operations']['offre-en-cours'],
                2 => $this->lng['preteur-operations-vos-operations']['offre-rejetee'],
                3 => $this->lng['preteur-operations-vos-operations']['offre-acceptee']
            ),
            3  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            4  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            5  => array(
                1 => $this->lng['preteur-operations-vos-operations']['remboursement'],
                2 => $this->lng['preteur-operations-vos-operations']['recouvrement']
            ),
            7  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            8  => $this->lng['preteur-operations-vos-operations']['retrait-dargents'],
            16 => $this->lng['preteur-operations-vos-operations']['offre-de-bienvenue'],
            17 => $this->lng['preteur-operations-vos-operations']['retrait-offre'],
            19 => $this->lng['preteur-operations-vos-operations']['gain-filleul'],
            20 => $this->lng['preteur-operations-vos-operations']['gain-parrain'],
            22 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe'],
            23 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe-preteur'],
            26 => $this->lng['preteur-operations-vos-operations']['remboursement-recouvrement-preteur']
        );
        $sLastOperation = $this->indexage_vos_operations->getLastOperationDate($clients->id_client);

        if (empty($sLastOperation)) {
            $date_debut_a_indexer = self::LAST_OPERATION_DATE;
        } else {
            $date_debut_a_indexer = substr($sLastOperation, 0, 10);
        }

        $this->lTrans = $this->transactions->selectTransactionsOp($array_type_transactions, 't.type_transaction IN (1,2,3,4,5,7,8,16,17,19,20,23,26)
            AND t.status = 1
            AND t.etat = 1
            AND t.display = 0
            AND t.id_client = ' . $clients->id_client . '
            AND DATE(t.date_transaction) >= "' . $date_debut_a_indexer . '"', 'id_transaction DESC');

        foreach ($this->lTrans as $t) {
            if (0 == $this->indexage_vos_operations->counter('id_transaction = ' . $t['id_transaction'] . ' AND libelle_operation = "' . $t['type_transaction_alpha'] . '"')) {
                $retenuesfiscals = 0.0;
                $capital         = 0.0;
                $interets        = 0.0;

                if ($this->echeanciers->get($t['id_echeancier'], 'id_echeancier')) {
                    $retenuesfiscals = $this->echeanciers->prelevements_obligatoires + $this->echeanciers->retenues_source + $this->echeanciers->csg + $this->echeanciers->prelevements_sociaux + $this->echeanciers->contributions_additionnelles + $this->echeanciers->prelevements_solidarite + $this->echeanciers->crds;
                    $capital         = $this->echeanciers->capital;
                    $interets        = $this->echeanciers->interets;
                }

                // si c'est un recouvrement on remplace les données
                if ($t['type_transaction'] == 5 && $t['recouvrement'] == 1 && $this->echeanciers_recouvrements_prorata->get($t['id_transaction'], 'id_transaction')) {
                    $retenuesfiscals = $this->echeanciers_recouvrements_prorata->prelevements_obligatoires + $this->echeanciers_recouvrements_prorata->retenues_source + $this->echeanciers_recouvrements_prorata->csg + $this->echeanciers_recouvrements_prorata->prelevements_sociaux + $this->echeanciers_recouvrements_prorata->contributions_additionnelles + $this->echeanciers_recouvrements_prorata->prelevements_solidarite + $this->echeanciers_recouvrements_prorata->crds;
                    $capital         = $this->echeanciers_recouvrements_prorata->capital;
                    $interets        = $this->echeanciers_recouvrements_prorata->interets;
                }

                // si exoneré à la date de la transact on change le libelle
                $libelle_prelevements = $this->lng['preteur-operations-vos-operations']['prelevements-fiscaux-et-sociaux'];
                // on check si il s'agit d'une PM ou PP
                if ($clients->type == 1 or $clients->type == 3) {
                    // Si le client est exoneré on doit modifier le libelle de prelevement
                    // on doit checker si le client est exonéré
                    $this->lenders_imposition_history = $this->loadData('lenders_imposition_history');
                    $exoneration                      = $this->lenders_imposition_history->is_exonere_at_date($this->lenders_accounts->id_lender_account, $t['date_transaction']);
                    if ($exoneration) {
                        $libelle_prelevements = $this->lng['preteur-operations-vos-operations']['cotisations-sociales'];
                    }
                } else {// PM
                    $libelle_prelevements = $this->lng['preteur-operations-vos-operations']['retenues-a-la-source'];
                }

                $this->indexage_vos_operations->id_client           = $t['id_client'];
                $this->indexage_vos_operations->id_transaction      = $t['id_transaction'];
                $this->indexage_vos_operations->id_echeancier       = $t['id_echeancier'];
                $this->indexage_vos_operations->id_projet           = $t['le_id_project'];
                $this->indexage_vos_operations->type_transaction    = $t['type_transaction'];
                $this->indexage_vos_operations->recouvrement        = $t['recouvrement'];
                $this->indexage_vos_operations->libelle_operation   = $t['type_transaction_alpha'];
                $this->indexage_vos_operations->bdc                 = $t['bdc'];
                $this->indexage_vos_operations->libelle_projet      = $t['title'];
                $this->indexage_vos_operations->date_operation      = $t['date_tri'];
                $this->indexage_vos_operations->solde               = $t['solde'] * 100;
                $this->indexage_vos_operations->montant_operation   = $t['amount_operation'];
                $this->indexage_vos_operations->libelle_prelevement = $libelle_prelevements;
                $this->indexage_vos_operations->montant_prelevement = $retenuesfiscals * 100;

                if ($t['type_transaction'] == 23) {
                    $this->indexage_vos_operations->montant_capital = $t['montant'];
                    $this->indexage_vos_operations->montant_interet = 0;
                } else {
                    $this->indexage_vos_operations->montant_capital = $capital;
                    $this->indexage_vos_operations->montant_interet = $interets;
                }


                if ($t['type_transaction'] == 5 && $t['recouvrement'] == 1) {
                    $taux_com         = $commission_ht;
                    $taux_tva         = $tva;
                    $montant          = $capital / 100 + $interets / 100;
                    $montant_avec_com = round($montant / (1 - $taux_com * (1 + $taux_tva)), 2);
                    $com_ht           = round($montant_avec_com * $taux_com, 2);
                    $com_tva          = round($com_ht * $taux_tva, 2);
                    $com_ttc          = round($com_ht + $com_tva, 2);

                    $this->indexage_vos_operations->commission_ht  = $com_ht * 100;
                    $this->indexage_vos_operations->commission_tva = $com_tva * 100;
                    $this->indexage_vos_operations->commission_ttc = $com_ttc * 100;
                }
                $this->indexage_vos_operations->create();
            }
        }
    }
}
