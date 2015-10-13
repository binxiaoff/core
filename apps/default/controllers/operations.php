<?

class operationsController extends bootstrap
{
    var $Command;

    function operationsController($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        // On prend le header account
        $this->setHeader('header_account');

        // On check si y a un compte
        if (!$this->clients->checkAccess()) {
            header('Location:' . $this->lurl);
            die;
        } else {
            // check preteur ou emprunteur (ou les deux)
            $this->clients->checkStatusPreEmp($this->clients->status_pre_emp, 'preteur', $this->clients->id_client);
        }

        // page
        $this->page = 'operations';

        $this->lng['preteur-operations'] = $this->ln->selectFront('preteur-operations', $this->language, $this->App);

    }

    function _default()
    {
        $this->transactions            = $this->loadData('transactions');
        $this->wallets_lines           = $this->loadData('wallets_lines');
        $this->bids                    = $this->loadData('bids');
        $this->loans                   = $this->loadData('loans');
        $this->echeanciers             = $this->loadData('echeanciers');
        $this->projects                = $this->loadData('projects');
        $this->companies               = $this->loadData('companies');
        $this->projects_status         = $this->loadData('projects_status');
        $this->indexage_vos_operations = $this->loadData('indexage_vos_operations');

        $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
        $this->lng['preteur-operations-pdf']            = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);
        $this->lng['preteur-operations-detail']         = $this->ln->selectFront('preteur-operations-detail', $this->language, $this->App);
        $this->lng['profile']                           = $this->ln->selectFront('preteur-profile', $this->language, $this->App);

        // conf par defaut pour la date (1M)
        $date_debut_time = mktime(0, 0, 0, date("m") - 1, date("d"), date('Y')); // date debut
        $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin

        // dates pour la requete
        $this->date_debut = date('Y-m-d', $date_debut_time);
        $this->date_fin   = date('Y-m-d', $date_fin_time);

        // affichage dans le filtre
        $this->date_debut_display = date('d/m/Y', $date_debut_time);
        $this->date_fin_display   = date('d/m/Y', $date_fin_time);

        $array_type_transactions = array(
            1  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            2  => array(1 => $this->lng['preteur-operations-vos-operations']['offre-en-cours'], 2 => $this->lng['preteur-operations-vos-operations']['offre-rejetee'], 3 => $this->lng['preteur-operations-vos-operations']['offre-acceptee']),
            3  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            4  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            5  => $this->lng['preteur-operations-vos-operations']['remboursement'],
            7  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            8  => $this->lng['preteur-operations-vos-operations']['retrait-dargents'],
            16 => $this->lng['preteur-operations-vos-operations']['offre-de-bienvenue'],
            17 => $this->lng['preteur-operations-vos-operations']['retrait-offre'],
            19 => $this->lng['preteur-operations-vos-operations']['gain-filleul'],
            20 => $this->lng['preteur-operations-vos-operations']['gain-parrain'],
            22 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe'],
            23 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe-preteur']);

        $array_type_transactions_liste_deroulante = array(
            1 => '1,2,3,4,5,7,8,16,17,19,20,23',
            2 => '3,4,7,8',
            3 => '3,4,7',
            4 => '8',
            5 => '2',
            6 => '5,23');


        // on va chercker si il n'y a pas de nouvelle indexation à faire
        $this->indexation_client($this->clients->id_client);


        // On va chercher ce qu'on a dans la table d'indexage
        $this->lTrans = $this->indexage_vos_operations->select('id_client= ' . $this->clients->id_client . ' AND LEFT(date_operation,10) >= "' . $this->date_debut . '" AND LEFT(date_operation,10) <= "' . $this->date_fin . '"', 'date_operation DESC, id_projet DESC');

        // filtre secondaire
        $this->lProjectsLoans = $this->indexage_vos_operations->get_liste_libelle_projet('id_client = ' . $this->clients->id_client . ' AND LEFT(date_operation,10) >= "' . $this->date_debut . '" AND LEFT(date_operation,10) <= "' . $this->date_fin . '"');


        /*echo '<pre>';
        print_r($this->lTrans);
        echo '</pre>';*/
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


        // DETAIL DE VOS OPERATIONS //

        $year         = date('Y');
        $this->lLoans = $this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND YEAR(added) = ' . $year . ' AND status = 0', 'added DESC');

        //////////////////////////////


        // VOS PRETS //
        $this->lSumLoans = $this->loans->getSumLoansByProject($this->lenders_accounts->id_lender_account, $year, 'next_echeance ASC');

        $this->arrayDeclarationCreance = array(1456, 1009, 1614, 3089, 10971, 970);


        /*echo '<pre>';
        print_r($this->lSumLoans);
        echo '</pre>';*/
        ///////////////


        // ONGLET VOS DOCS FISCAUX

        $this->ifu        = $this->loadData('ifu');
        $this->liste_docs = $this->ifu->select('id_client =' . $this->clients->id_client . ' AND statut = 1', 'annee ASC');


        // END DOCS FISCAUX


    }

    function _vos_operations()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;
    }

    function _vos_prets()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;
    }

    function _histo_transac()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;
    }

    function _doc_fiscaux()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;
    }


    function _vos_operation_csv()
    {

        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $this->transactions  = $this->loadData('transactions');
        $this->wallets_lines = $this->loadData('wallets_lines');
        $this->bids          = $this->loadData('bids');
        $this->loans         = $this->loadData('loans');
        $this->echeanciers   = $this->loadData('echeanciers');
        $this->projects      = $this->loadData('projects');
        $this->companies     = $this->loadData('companies');
        $this->clients       = $this->loadData('clients');

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

        /*echo '<pre>';
        print_r($filtre_vos_operations);
        echo '</pre>';*/

        $this->clients->get($post_id_client, 'id_client');

        //////////// DEBUT PARTIE DATES //////////////
        //echo $_SESSION['id_last_action'];
        // tri debut/fin


        if (isset($post_id_last_action) && in_array($post_id_last_action, array('debut', 'fin'))) {

            $debutTemp = explode('/', $post_debut);
            $finTemp   = explode('/', $post_fin);

            $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date debut
            $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date fin

            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;

        } // NB mois
        elseif (isset($post_id_last_action) && $post_id_last_action == 'nbMois') {

            $nbMois = $post_nbMois;

            $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, 1, date('Y')); // date debut
            $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin

            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;
        } // Annee
        elseif (isset($post_id_last_action) && $post_id_last_action == 'annee') {

            $year = $post_annee;

            $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date debut
            $date_fin_time   = mktime(0, 0, 0, 12, 31, $year); // date fin

            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;

        } // si on a une session
        elseif (isset($_SESSION['id_last_action'])) {
            if (in_array($_SESSION['id_last_action'], array('debut', 'fin'))) {
                //echo 'toto';
                $debutTemp = explode('/', $post_debut);
                $finTemp   = explode('/', $post_fin);

                $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date debut
                $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date fin
            } elseif ($_SESSION['id_last_action'] == 'nbMois') {
                //echo 'titi';
                $nbMois = $post_nbMois;

                $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, 1, date('Y')); // date debut
                $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin
            } elseif ($_SESSION['id_last_action'] == 'annee') {
                //echo 'tata';

                $year = $post_annee;

                $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date debut
                $date_fin_time   = mktime(0, 0, 0, 12, 31, $year); // date fin
            }
        } // Par defaut (on se base sur le 1M)
        else {
            //echo 'cc';
            if (isset($post_debut) && isset($post_fin)) {
                $debutTemp = explode('/', $post_debut);
                $finTemp   = explode('/', $post_fin);

                $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date debut
                $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date fin
            } else {
                $date_debut_time = mktime(0, 0, 0, date("m") - 1, 1, date('Y')); // date debut
                $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin
            }
        }

        // on recup au format sql
        $this->date_debut = date('Y-m-d', $date_debut_time);
        $this->date_fin   = date('Y-m-d', $date_fin_time);
        //////////// FIN PARTIE DATES //////////////


        $array_type_transactions = array(
            1  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            2  => array(1 => $this->lng['preteur-operations-vos-operations']['offre-en-cours'], 2 => $this->lng['preteur-operations-vos-operations']['offre-rejetee'], 3 => $this->lng['preteur-operations-vos-operations']['offre-acceptee']),
            3  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            4  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            5  => $this->lng['preteur-operations-vos-operations']['remboursement'],
            7  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            8  => $this->lng['preteur-operations-vos-operations']['retrait-dargents'],
            16 => $this->lng['preteur-operations-vos-operations']['offre-de-bienvenue'],
            17 => $this->lng['preteur-operations-vos-operations']['retrait-offre'],
            19 => $this->lng['preteur-operations-vos-operations']['gain-filleul'],
            20 => $this->lng['preteur-operations-vos-operations']['gain-parrain'],
            22 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe'],
            23 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe-preteur']);

        ////////// DEBUT PARTIE TRI TYPE TRANSAC /////////////

        $array_type_transactions_liste_deroulante = array(
            1 => '1,2,3,4,5,7,8,16,17,19,20,23',
            2 => '3,4,7,8',
            3 => '3,4,7',
            4 => '8',
            5 => '2',
            6 => '5,23');

        if (isset($post_tri_type_transac)) {

            $tri_type_transac = $array_type_transactions_liste_deroulante[$post_tri_type_transac];
        } else {
            $tri_type_transac = $array_type_transactions_liste_deroulante[1];
        }

        ////////// FIN PARTIE TRI TYPE TRANSAC /////////////


        ////////// DEBUT TRI PAR PROJET /////////////
        if (isset($post_tri_projects)) {
            if (in_array($post_tri_projects, array(0, 1))) {
                $tri_project = '';
            } else {
                //$tri_project = ' HAVING le_id_project = '.$post_tri_projects;
                $tri_project = ' AND le_id_project = ' . $post_tri_projects;
            }
        }
        ////////// FIN TRI PAR PROJET /////////////


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

        $this->lTrans = $this->indexage_vos_operations->select('type_transaction IN (' . $tri_type_transac . ') AND id_client = ' . $this->clients->id_client . ' AND LEFT(date_operation,10) >= "' . $this->date_debut . '" AND LEFT(date_operation,10) <= "' . $this->date_fin . '"' . $tri_project, $order);


        /*echo '<pre>';
        print_r($this->lTrans);
        echo '</pre>';
        die;*/


        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        // MAC
        if (strpos($user_agent, "Mac") !== FALSE || 5 == 5) {
            header("Content-type: application/vnd.ms-excel; charset=utf-8");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("content-disposition: attachment;filename=" . $this->bdd->generateSlug('operations_' . date('Y-m-d')) . ".xls");


            // si exoneré à la date de la transact on change le libelle
            $libelle_prelevements = $this->lng['preteur-operations-vos-operations']['prelevements-fiscaux-et-sociaux'];

            // on check si il s'agit d'une PM ou PP
            if ($this->clients->type == 1 or $this->clients->type == 3) {
                // Pour les personnes physiques tjs le meme intitule
            } else // PM
            {
                $libelle_prelevements = $this->lng['preteur-operations-vos-operations']['retenues-a-la-source'];
            }


            ?>
            <meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8"/>
            <table border=1>
                <tr>
                    <th><?= $this->lng['preteur-operations-pdf']['operations'] ?></th>
                    <th><?= $this->lng['preteur-operations-pdf']['info-titre-bon-caisse'] ?></th>
                    <th><?= $this->lng['preteur-operations-pdf']['projets'] ?></th>
                    <th><?= $this->lng['preteur-operations-pdf']['date-de-loperation'] ?></th>
                    <?php /*?><th>+</th>
                    <th>-</th><?php */
                    ?>
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

                    <th><?= $this->lng['preteur-operations-pdf']['solde-de-votre-compte'] ?></th>
                    <td></td>
                </tr>

                <?
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

                            $this->echeanciers->capital = $t['montant_operation'];
                        }
                        ?>
                        <tr>
                            <td><?= $t['libelle_operation'] ?></td>
                            <td><?= $t['bdc'] ?></td>
                            <td><?= $t['libelle_projet'] ?></td>
                            <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                            <?php /*?><td><?=$plus?></td>
                            <td><?=$moins?></td><?php */
                            ?>
                            <td<?= $couleur ?>><?= number_format($t['montant_operation'] / 100, 2, ',', '') ?></td>

                            <td><?= number_format(($this->echeanciers->capital / 100), 2, ',', ' ') ?></td>
                            <td><?= number_format(($this->echeanciers->interets / 100), 2, ',', ' ') ?></td>
                            <?php /*?><td>-<?=number_format($retenuesfiscals, 2, ',', ' ')?> €</td><?php */
                            ?>

                            <td><?= number_format($this->echeanciers->prelevements_obligatoires, 2, ',', ' ') ?></td>
                            <td><?= number_format($this->echeanciers->retenues_source, 2, ',', ' ') ?></td>
                            <td><?= number_format($this->echeanciers->csg, 2, ',', ' ') ?></td>
                            <td><?= number_format($this->echeanciers->prelevements_sociaux, 2, ',', ' ') ?></td>
                            <td><?= number_format($this->echeanciers->contributions_additionnelles, 2, ',', ' ') ?></td>
                            <td><?= number_format($this->echeanciers->prelevements_solidarite, 2, ',', ' ') ?></td>
                            <td><?= number_format($this->echeanciers->crds, 2, ',', ' ') ?></td>


                            <td><?= number_format($solde / 100, 2, ',', '') ?></td>
                            <td></td>

                        </tr>

                        <?php /*?><tr>
                            <td colspan="5">
                                <table>
                                    <tr>
                                        <td width="13.6%" class="detail_remb"><?=$this->lng['preteur-operations-vos-operations']['voici-le-detail-de-votre-remboursement']?></td>
                                        <td width="28%" class="detail_left"><?=$this->lng['preteur-operations-vos-operations']['capital-rembourse']?></td>
                                        <td></td>
                                        <td width="8%" class="chiffres" style="padding-bottom:8px;"><?=number_format(($this->echeanciers->capital/100), 2, ',', ' ')?> €</td>
                                        <td width="14%">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="detail_left"><?=$this->lng['preteur-operations-vos-operations']['interets-recus']?></td>
                                        <td></td>
                                        <td class="chiffres"><?=number_format(($this->echeanciers->interets/100), 2, ',', ' ')?> €</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="detail_left"><?=$libelle_prelevements?></td>
                                        <td></td>
                                        <td class="chiffres">-<?=number_format($retenuesfiscals, 2, ',', ' ')?> €</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                </table>
                            </td>
                        </tr><?php */
                        ?>


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
                            <td>&nbsp;</td>
                            <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                            <?php /*?><td><?=$plus?></td>
                            <td><?=$moins?></td><?php */
                            ?>
                            <td<?= $couleur ?>><?= number_format($t['montant_operation'] / 100, 2, ',', '') ?></td>

                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><?= number_format($solde / 100, 2, ',', '') ?></td>
                            <td></td>
                        </tr>
                        <?

                    } //offres en cours
                    elseif (in_array($t['type_transaction'], array(2))) {

                        /*if($t['id_bid_remb'] != 0){
                            $this->bids->get($t['id_bid_remb'],'id_bid');
                            $id_loan = '';
                        }
                        else{
                            $this->wallets_lines->get($t['id_transaction'],'id_transaction');
                            $this->bids->get($this->wallets_lines->id_wallet_line,'id_lender_wallet_line');

                            if($this->loans->get($this->bids->id_bid,'status = 0 AND id_bid')){
                                $id_loan = ' - '.$this->loans->id_loan;
                            }
                            else $id_loan = '';
                        }*/


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
                            <td><?= $t['libelle_projet'] ?></td>
                            <td><?= $this->dates->formatDate($t['date_operation'], 'd-m-Y') ?></td>
                            <?php /*?><td><?=$plus?></td>
                            <td><?=$moins?></td><?php */
                            ?>
                            <td<?= (!$offre_accepte ? $couleur : '') ?>><?= number_format($t['montant_operation'] / 100, 2, ',', ' ') ?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>

                            <td><?= number_format($t['solde'] / 100, 2, ',', '') ?></td>
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

        } // PC (old on utilise pas)
        else {


            $header = $this->lng['preteur-operations-pdf']['operations'] . ";" . $this->lng['preteur-operations-pdf']['projets'] . ";" . $this->lng['preteur-operations-pdf']['date-de-loperation'] . ";+;-;" . $this->lng['preteur-operations-pdf']['montant-de-loperation'] . ";" . $this->lng['preteur-operations-pdf']['solde-de-votre-compte'] . ";";
            $header = $header;

            $csv = "";
            $csv .= $header . " \n";

            foreach ($this->lTrans as $t) {

                if ($t['montant_operation'] > 0) {
                    $plus  = '+';
                    $moins = '';
                } else {
                    $plus  = '';
                    $moins = '-';
                }

                $solde = $t['solde'];
                // remb
                if ($t['type_transaction'] == 5 || $t['type_transaction'] == 23) {
                    $this->echeanciers->get($t['id_echeancier'], 'id_echeancier');


                    $csv .= $t['libelle_operation'] . " - " . $this->echeanciers->id_loan . ";" . $t['title'] . ";" . $this->dates->formatDate($t['date_operation'], 'd-m-Y') . ";" . $plus . ";" . $moins . ";" . number_format($t['montant_operation'] / 100, 2, ',', '') . " €;" . number_format($solde, 2, ',', '') . " €;";
                    $csv .= " \n";
                } elseif (in_array($t['type_transaction'], array(8, 1, 3, 4, 16, 17, 19, 20))) {
                    $csv .= $t['libelle_operation'] . ";" . $t['title'] . ";" . $this->dates->formatDate($t['date_operation'], 'd-m-Y') . ";" . $plus . ";" . $moins . ";" . number_format($t['montant_operation'] / 100, 2, ',', '') . " €;" . number_format($solde, 2, ',', '') . " €;";
                    $csv .= " \n";
                } //offres en cours
                elseif (in_array($t['type_transaction'], array(2))) {

                    if ($t['id_bid_remb'] != 0) {
                        $this->bids->get($t['id_bid_remb'], 'id_bid');
                        $id_loan = '';
                    } else {
                        $this->wallets_lines->get($t['id_transaction'], 'id_transaction');
                        $this->bids->get($this->wallets_lines->id_wallet_line, 'id_lender_wallet_line');

                        if ($this->loans->get($this->bids->id_bid, 'status = 0 AND id_bid')) {
                            $id_loan = ' - ' . $this->loans->id_loan;
                        } else $id_loan = '';
                    }


                    $csv .= $t['libelle_operation'] . $id_loan . ";" . $t['libelle_projet'] . ";" . $this->dates->formatDate($t['date_operation'], 'd-m-Y') . ";" . $plus . ";" . $moins . ";" . number_format($t['montant_operation'] / 100, 2, ',', '') . " €;" . number_format($solde, 2, ',', '') . " €;";
                    $csv .= " \n";
                }
            }


            //echo 'pas mac';
            //die;
            header("Content-type: application/vnd.ms-excel");
            //header("content-type:application/csv;charset=UTF-8");
            header("Content-disposition: attachment; filename=\"" . $titre . ".csv\"");

            echo($csv);

        }


        die;


    }

    function conversion_vers_csv($chemin_fichier, array $donnees)
    {

        // On cherche des infos sur le fichier à ouvrir
        $infos_fichier = stat($chemin_fichier);

        // Si le fichier est inexistant ou vide, on va le créer et y ajouter les
        // libellés de colonne.
        if (!file_exists($chemin_fichier) || $infos_fichier['size'] == 0) {

            // On ouvre le fichier en écriture seule et on le vide de son contenu
            $fp = @fopen($chemin_fichier, 'w');
            if ($fp === false)
                throw new Exception("Le fichier ${chemin_fichier} n'a pas pu être créé.");

            // Les entêtes sont les clés du tableau associatif
            $entetes = array_keys($donnees[0]);

            // Décodage des entêtes qui sont en UTF8 à la base
            foreach ($entetes as &$entete) {
                // Notez l'utilisation de iconv pour changer l'encodage.
                $entete = (is_string($entete)) ?
                    iconv("UTF-8", "Windows-1252//TRANSLIT", $entete) : $entete;
            }

            // On utilise le troisième paramètre de fputcsv pour changer le séparateur
            // par défaut de php.
            fputcsv($fp, $entetes, ';');
        }

        // On ouvre le handler en écriture pour écrire le fichier
        // s'il ne l'est pas déjà.
        $fp = ($fp) ? $fp : fopen($chemin_fichier, 'a');

        // Écriture des données
        foreach ($donnees as $donnee) {
            foreach ($donnee as &$champ) {
                $champ = (is_string($champ)) ?
                    iconv("UTF-8", "Windows-1252//TRANSLIT", $champ) : $champ;
            }
            fputcsv($fp, $donnee, ';');
        }

        fclose($fp);
    }


    function _get_ifu()
    {
        // recup du fichier
        $hash_client = $this->params[0];
        $annee       = $this->params[1];

        $this->ifu = $this->loadData('ifu');

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


    function indexation_client($id_client)
    {

        //params
        $liste_id_a_forcer             = $id_client;
        $limit_client                  = 50;
        $uniquement_ceux_jamais_indexe = false; // on veut aussi ceux deja indexé


        $this->indexage_vos_operations = $this->loadData('indexage_vos_operations');
        $this->transactions            = $this->loadData('transactions');
        $this->clients_indexation      = $this->loadData('clients');
        $this->echeanciers             = $this->loadData('echeanciers');
        $this->indexage_suivi          = $this->loadData('indexage_suivi');


        $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
        $this->lng['preteur-operations-pdf']            = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);
        $this->lng['preteur-operations']                = $this->ln->selectFront('preteur-operations', $this->language, $this->App);

        $array_type_transactions = array(
            1  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            2  => array(1 => $this->lng['preteur-operations-vos-operations']['offre-en-cours'], 2 => $this->lng['preteur-operations-vos-operations']['offre-rejetee'], 3 => $this->lng['preteur-operations-vos-operations']['offre-acceptee']),
            3  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            4  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            5  => $this->lng['preteur-operations-vos-operations']['remboursement'],
            7  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            8  => $this->lng['preteur-operations-vos-operations']['retrait-dargents'],
            16 => $this->lng['preteur-operations-vos-operations']['offre-de-bienvenue'],
            17 => $this->lng['preteur-operations-vos-operations']['retrait-offre'],
            19 => $this->lng['preteur-operations-vos-operations']['gain-filleul'],
            20 => $this->lng['preteur-operations-vos-operations']['gain-parrain'],
            22 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe'],
            23 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe-preteur']);


        $sql_forcage_id_client = "";
        if ($liste_id_a_forcer != 0) {
            $sql_forcage_id_client = " AND id_client IN(" . $liste_id_a_forcer . ")";
        }

        if ($uniquement_ceux_jamais_indexe) {
            $this->L_clients = $this->clients_indexation->select(' etape_inscription_preteur = 3 ' . $sql_forcage_id_client . ' AND id_client NOT IN (SELECT id_client FROM indexage_suivi WHERE deja_indexe = 1)', '', '', $limit_client);
        } else {
            $this->L_clients = $this->clients_indexation->select(' etape_inscription_preteur = 3 ' . $sql_forcage_id_client, '', '', $limit_client);
        }

        $nb_client_concernes = count($this->L_clients);

        foreach ($this->L_clients as $clt) {

            if ($this->clients_indexation->get($clt['id_client'], 'id_client')) {
                // Récupération de la date de la derniere indexation
                if ($this->indexage_suivi->get($clt['id_client'], 'id_client')) {
                    $date_debut_a_indexer = $this->indexage_suivi->date_derniere_indexation;
                    $tab_date             = explode(' ', $date_debut_a_indexer);
                    $date_debut_a_indexer = $tab_date[0];
                } else {
                    $time_ya_xh_stamp = mktime(date('H'), date('i'), date('s'), date("m"), date('d') - 2, date("Y"));
                    $time_ya_xh       = date('Y-m-d', $time_ya_xh_stamp);

                    $date_debut_a_indexer = "2013-01-01";
                }


                $this->lTrans = $this->transactions->selectTransactionsOp($array_type_transactions, 't.type_transaction IN (1,2,3,4,5,7,8,16,17,19,20,23)
						AND t.status = 1
						AND t.etat = 1
						AND t.display = 0
						AND t.id_client = ' . $this->clients_indexation->id_client . '
						AND LEFT(t.date_transaction,10) >= "' . $date_debut_a_indexer . '"', 'id_transaction DESC');


                $nb_entrees = count($this->lTrans);
                foreach ($this->lTrans as $t) {
                    $this->indexage_vos_operations = $this->loadData('indexage_vos_operations');

                    $indexage_client_existe = false;

                    if (!$this->indexage_vos_operations->get($t['id_transaction'], ' id_client = ' . $t['id_client'] . ' AND type_transaction = "' . $t['type_transaction'] . '" AND libelle_operation ="' . $t['type_transaction_alpha'] . '" AND id_transaction')) {


                        $indexage_client_existe = true;

                        $this->echeanciers->get($t['id_echeancier'], 'id_echeancier');


                        $retenuesfiscals = $this->echeanciers->prelevements_obligatoires + $this->echeanciers->retenues_source + $this->echeanciers->csg + $this->echeanciers->prelevements_sociaux + $this->echeanciers->contributions_additionnelles + $this->echeanciers->prelevements_solidarite + $this->echeanciers->crds;

                        // si exoneré à la date de la transact on change le libelle
                        $libelle_prelevements = $this->lng['preteur-operations-vos-operations']['prelevements-fiscaux-et-sociaux'];

                        // on check si il s'agit d'une PM ou PP
                        if ($this->clients_indexation->type == 1 or $this->clients_indexation->type == 3) {
                            // Si le client est exoneré on doit modifier le libelle de prelevement
                            // on doit checker si le client est exonéré
                            $this->lenders_imposition_history = $this->loadData('lenders_imposition_history');
                            $exoneration                      = $this->lenders_imposition_history->is_exonere_at_date($this->lenders_accounts->id_lender_account, $t['date_transaction']);


                            if ($exoneration) {
                                $libelle_prelevements = $this->lng['preteur-operations-vos-operations']['cotisations-sociales'];
                            }
                        } else // PM
                        {
                            $libelle_prelevements = $this->lng['preteur-operations-vos-operations']['retenues-a-la-source'];
                        }


                        $this->indexage_vos_operations->id_client         = $t['id_client'];
                        $this->indexage_vos_operations->id_transaction    = $t['id_transaction'];
                        $this->indexage_vos_operations->id_echeancier     = $t['id_echeancier'];
                        $this->indexage_vos_operations->id_projet         = $t['le_id_project'];
                        $this->indexage_vos_operations->type_transaction  = $t['type_transaction'];
                        $this->indexage_vos_operations->libelle_operation = $t['type_transaction_alpha'];
                        $this->indexage_vos_operations->bdc               = $t['bdc'];
                        $this->indexage_vos_operations->libelle_projet    = $t['title'];
                        $this->indexage_vos_operations->date_operation    = $t['date_tri'];
                        $this->indexage_vos_operations->solde             = $t['solde'] * 100;
                        $this->indexage_vos_operations->montant_operation = $t['montant'];

                        if ($t['type_transaction'] == 23) {
                            $this->indexage_vos_operations->montant_capital = $t['montant'];
                            $this->indexage_vos_operations->montant_interet = 0;
                        } else {
                            $this->indexage_vos_operations->montant_capital = $this->echeanciers->capital;
                            $this->indexage_vos_operations->montant_interet = $this->echeanciers->interets;
                        }

                        $this->indexage_vos_operations->libelle_prelevement = $libelle_prelevements;
                        $this->indexage_vos_operations->montant_prelevement = $retenuesfiscals * 100;
                        $this->indexage_vos_operations->create();

                    }
                }


                $this->indexage_suivi = $this->loadData('indexage_suivi');
                if ($this->indexage_suivi->get($clt['id_client'], 'id_client')) {
                    $this->indexage_suivi->date_derniere_indexation = date("Y-m-d H:i:s");
                    $this->indexage_suivi->deja_indexe              = 1;
                    $this->indexage_suivi->nb_entrees               = $nb_entrees;
                    $this->indexage_suivi->update();
                    $nb_maj++;
                } else {
                    $this->indexage_suivi->id_client                = $clt['id_client'];
                    $this->indexage_suivi->date_derniere_indexation = date("Y-m-d H:i:s");
                    $this->indexage_suivi->deja_indexe              = 1;
                    $this->indexage_suivi->nb_entrees               = $nb_entrees;
                    $this->indexage_suivi->create();
                    $nb_creation++;
                }

            } else {
                // on get pas le client donc erreur
                mail('k1@david.equinoa.net', 'UNILEND - Erreur cron indexage', 'Erreur de get sur le client :' . $clt['id_client']);
            }

        }
    }


}