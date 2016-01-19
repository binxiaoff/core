<?php

class ajaxController extends bootstrap
{
    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $_SESSION['request_url'] = $this->lurl;

        $this->autoFireHeader = false;
        $this->autoFireDebug  = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;

    }

    /* Modification de la modifcation des traductions à la volée */
    public function _activeModificationsTraduction()
    {
        // On desactive la vue qui sert à rien
        $this->autoFireView = false;

        // On renseigne la session avec l'etat demandé
        $_SESSION['modification'] = $this->params[0];
    }

    public function _session_etape2_lender()
    {
        $this->autoFireView = false;

        unset($_SESSION['inscription_etape2']);

        $_SESSION['inscription_etape2']['bic'] = str_replace(' ', '', $_POST['bic']);
        for ($i = 1; $i <= 7; $i++) {
            $_SESSION['inscription_etape2']['iban'] .= str_replace(' ', '', $_POST['iban' . $i]);
        }
        $_SESSION['inscription_etape2']['origine_des_fonds'] = $_POST['origine_des_fonds'];
        $_SESSION['inscription_etape2']['cni_passeport']     = $_POST['cni_passeport'];
        $_SESSION['inscription_etape2']['preciser']          = $_POST['preciser'];
    }

    public function _checkPostCode()
    {
        $this->autoFireView = false;
        $response = 'nok';

        if (isset($this->params[1]) && 1 != $this->params[1]) {
            echo 'ok';
            return;
        }
        if (isset($this->params[0]) && '' != $this->params[0]) {
            /** @var villes $oVille */
            $oVille = $this->loadData('villes');

            if ($oVille->exist($this->params[0], 'cp')) {
                $response = 'ok';
            }
            unset($oVille);
        }
        echo $response;
    }

    public function _checkCity()
    {
        $this->autoFireView = false;
        $response = 'nok';

        if (isset($this->params[1]) && 1 != $this->params[1]) {
            echo 'ok';
            return;
        }
        if (isset($this->params[0]) && '' != $this->params[0]) {
            /** @var villes $oVille */
            $oVille = $this->loadData('villes');

            if ($oVille->exist(urldecode($this->params[0]), 'ville')) {
                $response = 'ok';
            }
            unset($oVille);
        }
        echo $response;
    }

    public function _checkPostCodeCity()
    {
        $this->autoFireView = false;
        $response = 'nok';

        if (isset($this->params[2]) && 1 != $this->params[2]) {
            echo 'ok';
            return;
        }
        $this->params[1] = urldecode($this->params[1]);
        if (isset($this->params[0]) && '' != $this->params[0] && isset($this->params[1]) && '' != $this->params[1]) {
            /** @var villes $oVille */
            $oVille = $this->loadData('villes');

            if ($oVille->get($this->params[0] . '" AND ville = "' . $this->params[1], 'cp')) {
                $response = 'ok';
            }
            unset($oVille);
        }
        echo $response;
    }

    public function _load_project()
    {
        $this->autoFireView = false;

        $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

        $this->settings->get('Heure fin periode funding', 'type');
        $this->heureFinFunding = $this->settings->value;

        $this->settings->get('Tri par taux', 'type');
        $this->triPartx = $this->settings->value;
        $this->triPartx = explode(';', $this->triPartx);

        $this->settings->get('Tri par taux intervalles', 'type');
        $this->triPartxInt = $this->settings->value;
        $this->triPartxInt = explode(';', $this->triPartxInt);

        $this->projects          = $this->loadData('projects');
        $this->projects_status   = $this->loadData('projects_status');
        $this->companies         = $this->loadData('companies');
        $this->companies_details = $this->loadData('companies_details');
        $this->favoris           = $this->loadData('favoris');
        $this->bids              = $this->loadData('bids');
        $this->loans             = $this->loadData('loans');
        $this->lenders_accounts  = $this->loadData('lenders_accounts');

        $where       = '';
        $restriction = '';
        $ordre       = $this->tabOrdreProject[$_POST['ordreProject']];

        $_SESSION['ordreProject'] = $_POST['ordreProject'];

        if (false === empty($_POST['where']) && false === empty($_SESSION['tri']['taux'])) {
            $key = $_SESSION['tri']['taux'];
            $val = explode('-', $this->triPartxInt[$key - 1]);

            // where pour la requete
            $where .= ' AND p.target_rate BETWEEN "' . $val[0] . '" AND "' . $val[1] . '" ';
            $this->where = $key;
        } else {
            $this->where = '';
        }

        // filter completed projects
        if (isset($_POST['type']) && $_POST['type'] == 4) {
            $restriction = ' AND p.date_fin < "'. date('Y-m-d') .'"';
        }

        // statut
        // where
        // order
        // start
        // nb
        $this->lProjetsFunding = $this->projects->selectProjectsByStatus($this->tabProjectDisplay, $where . $restriction . ' AND p.status = 0 AND p.display = 0', $ordre, $_POST['positionStart'], 10);
        $affichage             = '';

        foreach ($this->lProjetsFunding as $k => $pf) {

            $this->projects_status->getLastStatut($pf['id_project']);

            $this->companies->get($pf['id_company'], 'id_company');
            $this->companies_details->get($pf['id_company'], 'id_company');

            $inter = $this->dates->intervalDates(date('Y-m-d h:i:s'), $pf['date_retrait_full']); // date fin 21h a chaque fois
            if ($inter['mois'] > 0) {
                $dateRest = $inter['mois'] . ' ' . $this->lng['preteur-projets']['mois'];
            } else {
                $dateRest = '';
            }

            // dates pour le js
            $mois_jour = $this->dates->formatDate($pf['date_retrait'], 'F d');
            $annee     = $this->dates->formatDate($pf['date_retrait'], 'Y');

            $CountEnchere = $this->bids->counter('id_project = ' . $pf['id_project']);

            $montantHaut = 0;
            $montantBas  = 0;
            // si fundé ou remboursement
            if ($this->projects_status->status == 60 || $this->projects_status->status >= 80) {
                foreach ($this->loans->select('id_project = ' . $pf['id_project']) as $b) {
                    $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                    $montantBas += ($b['amount'] / 100);
                }
            } // funding ko
            elseif ($this->projects_status->status == 70) {
                foreach ($this->bids->select('id_project = ' . $pf['id_project']) as $b) {
                    $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                    $montantBas += ($b['amount'] / 100);
                }
            } // emprun refusé
            elseif ($this->projects_status->status == 75) {
                foreach ($this->bids->select('id_project = ' . $pf['id_project'] . ' AND status = 1') as $b) {
                    $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                    $montantBas += ($b['amount'] / 100);
                }
            } else {
                foreach ($this->bids->select('id_project = ' . $pf['id_project'] . ' AND status = 0') as $b) {
                    $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                    $montantBas += ($b['amount'] / 100);
                }
            }
            if ($montantHaut > 0 && $montantBas > 0) {
                $avgRate = ($montantHaut / $montantBas);
            } else {
                $avgRate = 0;
            }

            $affichage .= "
            <tr class='unProjet' id='project" . $pf['id_project'] . "'>
                <td>";
            if ($this->projects_status->status >= 60) {
                $dateRest = 'Terminé';
            } else {
                $tab_date_retrait = explode(' ', $pf['date_retrait_full']);
                $tab_date_retrait = explode(':', $tab_date_retrait[1]);
                $heure_retrait    = $tab_date_retrait[0] . ':' . $tab_date_retrait[1];

                $affichage .= "
                        <script>
                            var cible" . $pf['id_project'] . " = new Date('" . $mois_jour . ", " . $annee . " " . $heure_retrait . ":00');
                            var letime" . $pf['id_project'] . " = parseInt(cible" . $pf['id_project'] . ".getTime() / 1000, 10);
                            setTimeout('decompte(letime" . $pf['id_project'] . ",\"val" . $pf['id_project'] . "\")', 500);
                        </script>";
            }

            if ($pf['photo_projet'] != '') {
                $affichage .= "<a class='lien' href='" . $this->lurl . "/projects/detail/" . $pf['slug'] . "'><img src='" . $this->surl . '/images/dyn/projets/72/' . $pf['photo_projet'] . "' alt='" . $pf['photo_projet'] . "' class='thumb'></a>";
            }

            $affichage .= "
                    <div class='description'>";
            if ($_SESSION['page_projet'] == 'projets_fo') {
                $affichage .= "<h5><a href='" . $this->lurl . '/projects/detail/' . $pf['slug'] . "'>" . $pf['title'] . "</a></h5>";
            } else {
                $affichage .= "<h5><a href='" . $this->lurl . "/projects/detail/" . $pf['slug'] . "'>" . $pf['title'] . "</a></h5>";
            }
            $affichage .= "<h6>" . $this->companies->city . ($this->companies->zip != '' ? ', ' : '') . $this->companies->zip . "</h6>
                        <p>" . $pf['nature_project'] . "</p>
                    </div><!-- /.description -->
                </td>
                <td>
                    <a class='lien' href='" . $this->lurl . "/projects/detail/" . $pf['slug'] . "'>
                        <div class='cadreEtoiles'><div class='etoile " . $this->lNotes[$pf['risk']] . "'></div></div>
                    </a>
                </td>
                <td style='white-space:nowrap;'>
                    <a class='lien' href='" . $this->lurl . "/projects/detail/" . $pf['slug'] . "'>
                        " . $this->ficelle->formatNumber($pf['amount'], 0) . "€
                    </a>
                </td>
                <td style='white-space:nowrap;'>
                <a class='lien' href='" . $this->lurl . "/projects/detail/" . $pf['slug'] . "'>
                    " . ($pf['period'] == 1000000 ? $this->lng['preteur-projets']['je-ne-sais-pas'] : $pf['period'] . ' ' . $this->lng['preteur-projets']['mois']) . "
                    </a>
                </td>";

            $affichage .= "<td><a class='lien' href='" . $this->lurl . "/projects/detail/" . $pf['slug'] . "'>";
            if ($CountEnchere > 0) {
                $affichage .= $this->ficelle->formatNumber($avgRate, 1) . "%";
            } else {
                $affichage .= ($pf['target_rate'] == '-' ? $pf['target_rate'] : $this->ficelle->formatNumber($pf['target_rate'], 1)) . "%";
            }
            $affichage .= "</a></td>";


            $affichage .= "<td><a class='lien' href='" . $this->lurl . "/projects/detail/" . $pf['slug'] . "'><strong id='val" . $pf['id_project'] . "'>" . $dateRest . "</strong></a></td>
                <td>";

            if ($this->projects_status->status >= 60) {
                $affichage .= "<a href='" . $this->lurl . "/projects/detail/" . $pf['slug'] . "' class='btn btn-info btn-small multi grise1 btn-grise'>" . $this->lng['preteur-projets']['voir-le-projet'] . "</a>";
            } else {
                $affichage .= "<a href='" . $this->lurl . "/projects/detail/" . $pf['slug'] . "' class='btn btn-info btn-small'>" . $this->lng['preteur-projets']['pretez'] . "</a>";
            }

            if (isset($_SESSION['client'])) {
                $affichage .= "<a class='fav-btn " . $favori . "' id='fav" . $pf['id_project'] . "' onclick=\"favori(" . $pf['id_project'] . ",'fav" . $pf['id_project'] . "'," . $this->clients->id_client . ",0);\">" . $this->lng['preteur-projets']['favori'] . " <i></i></a>";
            }
            $affichage .= "</td>
            </tr>
            ";
        }
        $table = array('affichage' => $affichage, 'positionStart' => $this->lProjetsFunding[0]['positionStart']);
        echo json_encode($table);

    }

    public function _triProject()
    {
        $this->autoFireView = true;

        $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

        $this->settings->get('Heure fin periode funding', 'type');
        $this->heureFinFunding = $this->settings->value;

        $this->settings->get('Tri par taux', 'type');
        $this->triPartx = $this->settings->value;
        $this->triPartx = explode(';', $this->triPartx);

        $this->settings->get('Tri par taux intervalles', 'type');
        $this->triPartxInt = $this->settings->value;
        $this->triPartxInt = explode(';', $this->triPartxInt);

        $this->projects          = $this->loadData('projects');
        $this->projects_status   = $this->loadData('projects_status');
        $this->companies         = $this->loadData('companies');
        $this->companies_details = $this->loadData('companies_details');
        $this->favoris           = $this->loadData('favoris');
        $this->bids              = $this->loadData('bids');
        $this->loans             = $this->loadData('loans');
        $this->lenders_accounts  = $this->loadData('lenders_accounts');

        if (isset($_POST['val']) && isset($_POST['id'])) {
            $val = $_POST['val'];
            $id  = $_POST['id'];

            $_SESSION['tri'][$id] = $val;
        }

        // Reset du tri
        if (isset($_POST['rest_val']) && $_POST['rest_val'] == 1) {
            unset($_SESSION['tri']);
        }

        // Si session on execute
        if (isset($_SESSION['tri'])) {
            $where       = '';
            $this->where = '';

            // tri temps
            if (isset($_SESSION['tri']['temps'])) {
                $this->ordreProject = $_SESSION['tri']['temps'];
            } else {
                $this->ordreProject = 1;
            }

            // tri taux
            if (isset($_SESSION['tri']['taux'])) {
                $key = $_SESSION['tri']['taux'];
                $val = explode('-', $this->triPartxInt[$key - 1]);

                // where pour la requete
                $where .= ' AND p.target_rate BETWEEN "' . $val[0] . '" AND "' . $val[1] . '" ';

                // where pour le js
                $this->where = $key;
            }

            // filter completed projects
            $restriction = '';
            if (isset($_SESSION['tri']['type']) && $_SESSION['tri']['type'] == 4) {
                    $restriction = ' AND p.date_fin < "'. date('Y-m-d') .'"';
            }

            $_SESSION['ordreProject'] = $this->ordreProject;

            $this->lProjetsFunding = $this->projects->selectProjectsByStatus($this->tabProjectDisplay, $where . $restriction . ' AND p.status = 0 AND p.display = 0', $this->tabOrdreProject[$this->ordreProject], 0, 10);
            $this->nbProjects      = $this->projects->countSelectProjectsByStatus($this->tabProjectDisplay . ', 75', $where . $restriction . ' AND p.status = 0 AND p.display = 0');
        } else {
            $this->ordreProject = 1;
            $this->type         = 0;

            $_SESSION['ordreProject'] = $this->ordreProject;

            $this->where           = '';
            $this->lProjetsFunding = $this->projects->selectProjectsByStatus($this->tabProjectDisplay, ' AND p.status = 0', $this->tabOrdreProject[$this->ordreProject], 0, 10);
            $this->nbProjects      = $this->projects->countSelectProjectsByStatus($this->tabProjectDisplay . ', 75' . ' AND p.status = 0');
        }
    }

    public function _favori()
    {
        $this->autoFireView = false;

        $this->projects          = $this->loadData('projects');
        $this->companies         = $this->loadData('companies');
        $this->companies_details = $this->loadData('companies_details');
        $this->favoris           = $this->loadData('favoris');

        if (isset($_POST['id_project']) && isset($_POST['id_client']) && $this->clients->get($_POST['id_client'], 'id_client') && $this->projects->get($_POST['id_project'], 'id_project')) {
            // si deja dans favori
            if ($this->favoris->get($this->clients->id_client, 'id_project = ' . $this->projects->id_project . ' AND id_client')) {
                // on supprime
                $this->favoris->delete($this->clients->id_client, 'id_project = ' . $this->projects->id_project . ' AND id_client');
                echo 'delete';
            } // Sinon on ajoute aux favoris
            else {
                $this->favoris->id_client  = $this->clients->id_client;
                $this->favoris->id_project = $this->projects->id_project;
                $this->favoris->create();
                echo 'create';
            }

            // Histo client //
            $serialize = serialize(array('id_client' => $_POST['id_client'], 'post' => $_POST, 'action' => $val));
            $this->clients_history_actions->histo(8, 'favoris', $_POST['id_client'], $serialize);
            ////////////////
        } else {
            echo 'nok';
        }
    }

    public function _get_cities()
    {
        $this->autoFireView = false;
        $aCities = array();
        if (isset($_GET['term']) && '' !== trim($_GET['term'])) {
            $_GET  = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
            $oVilles = $this->loadData('villes');

            $bBirthPlace = false;
            if (isset($this->params[0]) && 'birthplace' === $this->params[0]) {
                $bBirthPlace = true;
            }

            $sTerm = htmlspecialchars_decode($_GET['term'], ENT_QUOTES);
            if ($bBirthPlace) {
                $aResults = $oVilles->lookupCities($sTerm, array('ville', 'cp'), true);
            } else {
                $aResults = $oVilles->lookupCities($sTerm);
            }
            if (false === empty($aResults)) {
                foreach ($aResults as $aItem) {
                    if ($bBirthPlace) {
                        // unique insee code
                        $aCities[$aItem['insee'].'-'.$aItem['ville']] = array(
                            'label' => $aItem['ville'] . ' (' . $aItem['num_departement'] . ')',
                            'value' => $aItem['insee']
                        );
                    } else {
                        $aCities[] = array(
                            'label' => $aItem['ville'] . ' (' . $aItem['cp'] . ')',
                            'value' => $aItem['insee']
                        );
                    }
                }
            }
        }

        echo json_encode($aCities);
    }

    public function _displayAll()
    {
        $this->autoFireView = true;

        $this->bids             = $this->loadData('bids');
        $this->projects         = $this->loadData('projects');
        $this->lenders_accounts = $this->loadData('lenders_accounts');

        $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

        $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

        $this->projects->get($this->bdd->escape_string($_POST['id']), 'id_project');

        if (isset($_POST['tri'])) {
            $order = $_POST['tri'];
        } else {
            $order = 'ordre';
        }

        if (isset($_POST['direction'])) {
            if ($_POST['direction'] == 1) {
                $direction       = 'ASC';
                $this->direction = 2;
            } else {
                $direction       = 'DESC';
                $this->direction = 1;
            }
        }

        if ($order == 'rate') {
            $order = 'rate ' . $direction . ', ordre ' . $direction;
        } elseif ($order == 'amount') {
            $order = 'amount ' . $direction . ', rate ' . $direction . ', ordre ' . $direction;
        } elseif ($order == 'status') {
            $order = 'status ' . $direction . ', rate ' . $direction . ', ordre ' . $direction;
        } else {
            $order = 'ordre ' . $direction;
        }

        $this->lEnchere     = $this->bids->select('id_project = ' . $this->projects->id_project, $order);
        $this->CountEnchere = $this->bids->counter('id_project = ' . $this->projects->id_project);
        $this->avgAmount    = $this->bids->getAVG($this->projects->id_project, 'amount', '0');
        $this->avgRate      = $this->bids->getAVG($this->projects->id_project, 'rate');

        $montantHaut = 0;
        $tauxBas     = 0;
        $montantBas  = 0;
        foreach ($this->bids->select('id_project = ' . $this->projects->id_project . ' AND status = 0') as $b) {
            $montantHaut += $b['rate'] * $b['amount'] / 100;
            $tauxBas     += $b['rate'];
            $montantBas  += $b['amount'] / 100;
        }

        $this->avgRate = ($montantHaut > 0 && $montantBas > 0) ? $montantHaut / $montantBas : 0;
        $this->status  = array($this->lng['preteur-projets']['enchere-en-cours'], $this->lng['preteur-projets']['enchere-ok'], $this->lng['preteur-projets']['enchere-ko']);
    }

    // Affichage du tableau d'offres en cours mobile
    public function _displayAll_mobile()
    {
        $this->autoFireView = true;

        $this->bids             = $this->loadData('bids');
        $this->projects         = $this->loadData('projects');
        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

        $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

        $this->projects->get($this->bdd->escape_string($_POST['id']), 'id_project');

        $order = isset($_POST['tri']) ? $_POST['tri'] : 'ordre';

        if (isset($_POST['direction'])) {
            if ($_POST['direction'] == 1) {
                $direction       = 'ASC';
                $this->direction = 2;
            } else {
                $direction       = 'DESC';
                $this->direction = 1;
            }
        }

        if ($order == 'rate') {
            $order = 'rate ' . $direction . ', ordre ' . $direction;
        } elseif ($order == 'amount') {
            $order = 'amount ' . $direction . ', rate ' . $direction . ', ordre ' . $direction;
        } elseif ($order == 'status') {
            $order = 'status ' . $direction . ', rate ' . $direction . ', ordre ' . $direction;
        } else {
            $order = 'ordre ' . $direction;
        }

        $this->lEnchere     = $this->bids->select('id_project = ' . $this->projects->id_project, $order);
        $this->CountEnchere = $this->bids->counter('id_project = ' . $this->projects->id_project);
        $this->avgAmount    = $this->bids->getAVG($this->projects->id_project, 'amount', '0');
        $this->avgRate      = $this->bids->getAVG($this->projects->id_project, 'rate');

        $montantHaut = 0;
        $tauxBas     = 0;
        $montantBas  = 0;
        foreach ($this->bids->select('id_project = ' . $this->projects->id_project . ' AND status = 0') as $b) {
            $montantHaut += ($b['rate'] * ($b['amount'] / 100));
            $tauxBas += $b['rate'];
            $montantBas += ($b['amount'] / 100);
        }

        if ($montantHaut > 0 && $montantBas > 0) {
            $this->avgRate = ($montantHaut / $montantBas);
        } else {
            $this->avgRate = 0;
        }

        $this->status = array($this->lng['preteur-projets']['enchere-en-cours'], $this->lng['preteur-projets']['enchere-ok'], $this->lng['preteur-projets']['enchere-ko']);
    }

    public function _loadGraph()
    {
        $this->autoFireView = true;

        $this->transactions     = $this->loadData('transactions');
        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->loans            = $this->loadData('loans');
        $this->echeanciers      = $this->loadData('echeanciers');

        //Recuperation des element de traductions
        $this->lng['preteur-mouvement'] = $this->ln->selectFront('preteur-mouvement', $this->language, $this->App);

        if (isset($_POST['year'])) {
            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

            $sumVersParMois             = $this->transactions->getSumDepotByMonths($this->clients->id_client, $_POST['year']);
            $sumPretsParMois            = $this->loans->getSumPretsByMonths($this->lenders_accounts->id_lender_account, $_POST['year']);
            $sumRembParMois             = $this->echeanciers->getSumRembByMonths($this->lenders_accounts->id_lender_account, $_POST['year']);
            $sumIntbParMois             = $this->echeanciers->getSumIntByMonths($this->lenders_accounts->id_lender_account, $_POST['year']);
            $sumRevenuesfiscalesParMois = $this->echeanciers->getSumRevenuesFiscalesByMonths($this->lenders_accounts->id_lender_account, $_POST['year']);

            for ($i = 1; $i <= 12; $i++) {
                $i                         = ($i < 10 ? '0' . $i : $i);
                $this->sumVersParMois[$i]  = number_format(($sumVersParMois[$i] != '' ? $sumVersParMois[$i] : 0), 2, '.', '');
                $this->sumPretsParMois[$i] = number_format(($sumPretsParMois[$i] != '' ? $sumPretsParMois[$i] : 0), 2, '.', '');
                $this->sumRembParMois[$i]  = number_format(($sumRembParMois[$i] != '' ? $sumRembParMois[$i] - $sumRevenuesfiscalesParMois[$i] : 0), 2, '.', '');
                $this->sumIntbParMois[$i]  = number_format(($sumIntbParMois[$i] != '' ? $sumIntbParMois[$i] - $sumRevenuesfiscalesParMois[$i] : 0), 2, '.', '');
            }
        }
    }

    public function _changeMdp()
    {
        $this->autoFireView = false;

        $this->clients = $this->loadData('clients');

        if (isset($_POST['newMdp']) && isset($_POST['oldMdp']) && isset($_POST['id']) && $this->clients->get($_POST['id'], 'id_client')) {
            $serialize = serialize(array('id_client' => $_POST['id'], 'newmdp' => md5($_POST['newMdp']), 'question' => $_POST['question'], 'reponse' => md5($_POST['reponse'])));
            $this->clients_history_actions->histo(7, 'change mdp', $_POST['id'], $serialize);

            if (md5($_POST['oldMdp']) != $this->clients->password) {
                echo 'nok';
            } else {
                $this->clients->password        = md5($_POST['newMdp']);
                $_SESSION['client']['password'] = $this->clients->password;
                // question / reponse
                if (isset($_POST['question']) && isset($_POST['reponse']) && $_POST['question'] != '' && $_POST['reponse'] != '') {
                    $this->clients->secrete_question = $_POST['question'];
                    $this->clients->secrete_reponse  = md5($_POST['reponse']);
                }
                $this->clients->update();

                //************************************//
                //*** ENVOI DU MAIL GENERATION MDP ***//
                //************************************//

                $this->mails_text->get('generation-mot-de-passe', 'lang = "' . $this->language . '" AND type');

                $surl  = $this->surl;
                $url   = $this->lurl;
                $login = $this->clients->email;

                $this->settings->get('Facebook', 'type');
                $lien_fb = $this->settings->value;

                $this->settings->get('Twitter', 'type');
                $lien_tw = $this->settings->value;

                $varMail = array(
                    'surl'     => $surl,
                    'url'      => $url,
                    'login'    => $login,
                    'prenom_p' => $this->clients->prenom,
                    'mdp'      => '',
                    'lien_fb'  => $lien_fb,
                    'lien_tw'  => $lien_tw
                );

                $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                $this->email = $this->loadLib('email');
                $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                $this->email->setSubject(stripslashes($sujetMail));
                $this->email->setHTMLBody(stripslashes($texteMail));

                if ($this->Config['env'] == 'prod') {
                    Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                    $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                } else {
                    $this->email->addRecipient(trim($this->clients->email));
                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                }

                echo 'ok';
            }
        }
    }

    public function _mdp_lost()
    {
        $this->autoFireView = false;

        $clients = $this->loadData('clients');

        if (isset($_POST['email']) && $this->ficelle->isEmail($_POST['email']) && $clients->get($_POST['email'], 'email')) {
            //*************************//
            //*** ENVOI DU MAIL MDP ***//
            //*************************//

            $this->mails_text->get('mot-de-passe-oublie', 'lang = "' . $this->language . '" AND type');

            $surl          = $this->surl;
            $url           = $this->lurl;
            $prenom        = $clients->prenom;
            $login         = $clients->email;
            $link_password = $this->lurl . '/' . $this->tree->getSlug(119, $this->language) . '/' . $clients->hash;

            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            $varMail = array(
                'surl'          => $surl,
                'url'           => $url,
                'prenom'        => $prenom,
                'login'         => $login,
                'link_password' => $link_password,
                'lien_fb'       => $lien_fb,
                'lien_tw'       => $lien_tw
            );

            $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

            $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
            $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
            $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

            $this->email = $this->loadLib('email');
            $this->email->setFrom($this->mails_text->exp_email, $exp_name);
            $this->email->setSubject(stripslashes($sujetMail));
            $this->email->setHTMLBody(stripslashes($texteMail));

            if ($this->Config['env'] == 'prod') {
                Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $clients->email, $tabFiler);
                $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
            } else {
                $this->email->addRecipient(trim($clients->email));
                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
            }
            echo 'ok';
        } else {
            echo 'nok';
        }
    }

    public function _load_finances()
    {
        $this->autoFireView = true;

        if (isset($_POST['year']) && isset($_POST['id_lender'])) {
            $this->lenders_accounts = $this->loadData('lenders_accounts');
            $this->companies        = $this->loadData('companies');
            $this->loans            = $this->loadData('loans');
            $this->projects         = $this->loadData('projects');
            $this->echeanciers      = $this->loadData('echeanciers');
            $this->projects_status  = $this->loadData('projects_status');

            $this->lng['profile'] = $this->ln->selectFront('preteur-profile', $this->language, $this->App);

            $year = $_POST['year'];

            $this->lLoans = $this->loans->select('id_lender = ' . $_POST['id_lender'] . ' AND YEAR(added) = ' . $year . ' AND status = 0', 'added DESC');
        }
    }

    public function _load_transac()
    {
        $this->autoFireView = true;

        $this->lng['profile'] = $this->ln->selectFront('preteur-profile', $this->language, $this->App);

        if (isset($_POST['year']) && isset($_POST['id_client'])) {
            $this->clients      = $this->loadData('clients');
            $this->transactions = $this->loadData('transactions');
            $this->echeanciers  = $this->loadData('echeanciers');
            $this->projects     = $this->loadData('projects');
            $this->companies    = $this->loadData('companies');

            $this->lng['profile'] = $this->ln->selectFront('preteur-profile', $this->language, $this->App);

            // Offre de bienvenue motif
            $this->settings->get('Offre de bienvenue motif', 'type');
            $this->motif_offre_bienvenue = $this->settings->value;

            $year = $_POST['year'];

            $this->lTrans     = $this->transactions->select('type_transaction IN (1,3,4,5,7,8,16,17) AND status = 1 AND etat = 1 AND display = 0 AND id_client = ' . $_POST['id_client'] . ' AND YEAR(date_transaction) = ' . $year, 'added DESC');
            $this->lesStatuts = array(1 => $this->lng['profile']['versement-initial'], 3 => $this->lng['profile']['alimentation-cb'], 4 => $this->lng['profile']['alimentation-virement'], 5 => $this->lng['profile']['remboursement'], 7 => $this->lng['profile']['alimentation-prelevement'], 8 => $this->lng['profile']['retrait'], 16 => $this->motif_offre_bienvenue, 17 => 'Retrait offre de bienvenue');
        }
    }

    // page alimentation / transferer des fonds (retrait d'argent)
    public function _transfert()
    {
        $this->autoFireView = false;

        $this->clients                       = $this->loadData('clients');
        $this->transactions                  = $this->loadData('transactions');
        $this->wallets_lines                 = $this->loadData('wallets_lines');
        $this->bank_lines                    = $this->loadData('bank_lines');
        $this->virements                     = $this->loadData('virements');
        $this->lenders_accounts              = $this->loadData('lenders_accounts');
        $this->clients_status                = $this->loadData('clients_status');
        $this->offres_bienvenues_details     = $this->loadData('offres_bienvenues_details');
        $this->parrains_filleuls_mouvements  = $this->loadData('parrains_filleuls_mouvements');
        $this->notifications                 = $this->loadData('notifications');
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications'); // add gestion alertes
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif'); // add gestion alertes

        // On verfie la presence de l'id_client, mdp et montant
        if (isset($_POST['id_client']) && $this->clients->get($_POST['id_client'], 'id_client') && isset($_POST['mdp']) && isset($_POST['montant'])) {
            $serialize = serialize(array('id_client' => $_POST['id_client'], 'montant' => $_POST['montant'], 'mdp' => md5($_POST['mdp'])));
            $this->clients_history_actions->histo(3, 'retrait argent', $_POST['id_client'], $serialize);

            $this->clients_status->getLastStatut($this->clients->id_client);

            if ($this->clients_status->status < 60) {
                echo 'nok';
                die;
            }

            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

            $verif   = 'ok';
            $montant = str_replace(',', '.', $_POST['montant']);

            // on verifie le mdp
            if (md5($_POST['mdp']) != $this->clients->password) {
                $verif = 'noMdp';
            } else {
                // on verifie si le montant est bien un chiffre
                if (! is_numeric($montant)) {
                    $verif = 'noMontant';
                } elseif ($this->lenders_accounts->bic == '') {
                    $verif = 'noBic';
                } elseif ($this->lenders_accounts->iban == '') {
                    $verif = 'noIban';
                } // Si c'est un chiffre on verifie que le montant est inferieur ou egale au solde du client
                else {
                    $offre_presente = false;
                    $sumOffres      = $this->offres_bienvenues_details->sum('id_client = ' . $this->clients->id_client . ' AND status = 0', 'montant');

                    if ($sumOffres > 0) {
                        $sumOffres      = ($sumOffres / 100);
                        $offre_presente = true;
                    } else {
                        $sumOffres = 0;
                    }

                    if (($montant + $sumOffres) > $this->transactions->getSolde($this->clients->id_client) || $montant <= 0) {
                        if ($offre_presente == true) {
                            $verif = 'noMontant3';
                        } else {
                            $verif = 'noMontant2';
                        }
                    }
                }
            }

            if ($verif == 'ok') {
                $p           = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))), 0, 1);
                $nom         = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
                $id_client   = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
                $this->motif = mb_strtoupper($id_client . $p . $nom, 'UTF-8');

                // on effectue une demande de virement
                // on retire la somme dur les transactions, bank_line et wallet
                $this->transactions->id_client        = $this->clients->id_client;
                $this->transactions->montant          = '-' . ($montant * 100);
                $this->transactions->id_langue        = 'fr';
                $this->transactions->date_transaction = date('Y-m-d H:i:s');
                $this->transactions->status           = '1'; // on met en mode reglé pour ne plus avoir la somme sur le compte
                $this->transactions->etat             = '1';
                $this->transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                $this->transactions->civilite_fac     = $this->clients->civilite;
                $this->transactions->nom_fac          = $this->clients->nom;
                $this->transactions->prenom_fac       = $this->clients->prenom;
                if ($this->clients->type == 2) {
                    $this->transactions->societe_fac = $this->companies->name;
                }
                $this->transactions->adresse1_fac     = $this->clients_adresses->adresse1;
                $this->transactions->cp_fac           = $this->clients_adresses->cp;
                $this->transactions->ville_fac        = $this->clients_adresses->ville;
                $this->transactions->id_pays_fac      = $this->clients_adresses->id_pays;
                $this->transactions->type_transaction = 8; // on signal que c'est un retrait
                $this->transactions->transaction      = 1; // transaction physique
                $this->transactions->id_transaction   = $this->transactions->create();

                $this->wallets_lines->id_lender                = $this->lenders_accounts->id_lender_account;
                $this->wallets_lines->type_financial_operation = 30; // Inscription preteur
                $this->wallets_lines->id_transaction           = $this->transactions->id_transaction;
                $this->wallets_lines->status                   = 1;
                $this->wallets_lines->type                     = 1;
                $this->wallets_lines->amount                   = '-' . ($montant * 100);
                $this->wallets_lines->id_wallet_line           = $this->wallets_lines->create();

                // Transaction physique donc on enregistre aussi dans la bank lines
                $this->bank_lines->id_wallet_line    = $this->wallets_lines->id_wallet_line;
                $this->bank_lines->id_lender_account = $this->lenders_accounts->id_lender_account;
                $this->bank_lines->status            = 1;
                $this->bank_lines->amount            = '-' . ($montant * 100);
                $this->bank_lines->create();

                // on enregistre a la demande de virement
                $this->virements->id_client      = $this->clients->id_client;
                $this->virements->id_transaction = $this->transactions->id_transaction;
                $this->virements->montant        = $montant * 100;
                $this->virements->motif          = $this->motif;
                $this->virements->type           = 1; // preteur
                $this->virements->status         = 0;
                $this->virements->create();

                $this->notifications->type            = 7; // retrait
                $this->notifications->id_lender       = $this->lenders_accounts->id_lender_account;
                $this->notifications->amount          = $montant * 100;
                $this->notifications->id_notification = $this->notifications->create();

                $this->clients_gestion_mails_notif->id_client                      = $this->clients->id_client;
                $this->clients_gestion_mails_notif->id_notif                       = 8; // retrait
                $this->clients_gestion_mails_notif->date_notif                     = date('Y-m-d H:i:s');
                $this->clients_gestion_mails_notif->id_notification                = $this->notifications->id_notification;
                $this->clients_gestion_mails_notif->id_transaction                 = $this->transactions->id_transaction;
                $this->clients_gestion_mails_notif->id_clients_gestion_mails_notif = $this->clients_gestion_mails_notif->create();

                // envoi email retrait maintenant ou non
                if ($this->clients_gestion_notifications->getNotif($this->clients->id_client, 8, 'immediatement') == true) {
                    $this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                    $this->clients_gestion_mails_notif->immediatement = 1; // on met a jour le statut immediatement
                    $this->clients_gestion_mails_notif->update();

                    $this->mails_text->get('preteur-retrait', 'lang = "' . $this->language . '" AND type');

                    $this->settings->get('Facebook', 'type');
                    $lien_fb = $this->settings->value;

                    $this->settings->get('Twitter', 'type');
                    $lien_tw = $this->settings->value;

                    $p           = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))), 0, 1);
                    $nom         = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
                    $id_client   = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
                    $motif       = mb_strtoupper($id_client . $p . $nom, 'UTF-8');
                    $pageProjets = $this->tree->getSlug(4, $this->language);

                    $varMail = array(
                        'surl'            => $this->surl,
                        'url'             => $this->lurl,
                        'prenom_p'        => $this->clients->prenom,
                        'fonds_retrait'   => $this->ficelle->formatNumber($montant),
                        'solde_p'         => $this->ficelle->formatNumber($this->solde - $montant),
                        'link_mandat'     => $this->surl . '/images/default/mandat.jpg',
                        'motif_virement'  => $motif,
                        'projets'         => $this->lurl . '/' . $pageProjets,
                        'gestion_alertes' => $this->lurl . '/profile',
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

                    if ($this->Config['env'] == 'prod') {
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($this->clients->email));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }
                }

                //******************************************//
                //*** ENVOI DU MAIL NOTIFICATION RETRAIT ***//
                //******************************************//
                $this->settings->get('Adresse notification controle fond', 'type');
                $destinataire = $this->settings->value;

                $transac = $this->loadData('transactions');
                $loans   = $this->loadData('loans');

                // on recup la somme versé a l'inscription si y en a 1
                $transac->get($this->clients->id_client, 'type_transaction = 1 AND status = 1 AND etat = 1 AND transaction = 1 AND id_client');

                $soldePrets = $loans->sumPrets($this->lenders_accounts->id_lender_account);

                // Recuperation du modele de mail
                $this->mails_text->get('notification-retrait-de-fonds', 'lang = "' . $this->language . '" AND type');

                $surl            = $this->surl;
                $url             = $this->lurl;
                $idPreteur       = $this->clients->id_client;
                $nom             = utf8_decode($this->clients->nom);
                $prenom          = utf8_decode($this->clients->prenom);
                $email           = $this->clients->email;
                $dateinscription = date('d/m/Y', strtotime($this->clients->added));
                if ($transac->montant != false) {
                    $montantInscription = $this->ficelle->formatNumber($transac->montant / 100);
                } else {
                    $montantInscription = $this->ficelle->formatNumber(0);
                }
                $montantPreteDepuisInscription = $this->ficelle->formatNumber($soldePrets);
                $montantRetirePlateforme       = $this->ficelle->formatNumber($montant);
                $solde                         = $this->ficelle->formatNumber($transac->getSolde($this->clients->id_client));

                $sujetMail = $this->mails_text->subject;
                eval("\$sujetMail = \"$sujetMail\";");

                $texteMail = $this->mails_text->content;
                eval("\$texteMail = \"$texteMail\";");

                $exp_name = $this->mails_text->exp_name;
                eval("\$exp_name = \"$exp_name\";");

                $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
                $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

                $this->email = $this->loadLib('email');
                $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                $this->email->addRecipient(trim($destinataire));
                $this->email->setSubject('=?UTF-8?B?' . base64_encode($sujetMail) . '?=');
                $this->email->setHTMLBody($texteMail);
                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
            }
            echo $verif;
        } else {
            echo 'nok';
        }
    }

    // Solde du compte preteur
    public function _solde()
    {
        $this->autoFireView = false;

        $this->transactions = $this->loadData('transactions');

        if (isset($_POST['id_client']) && $this->clients->id_client == $_POST['id_client']) {
            $solde = $this->transactions->getSolde($this->clients->id_client);
            echo $solde = $this->ficelle->formatNumber($solde);
        } else {
            echo 'nok';
        }
    }

    // mensualité preteur
    public function _load_mensual()
    {
        $this->autoFireView = false;

        if (isset($_POST['montant']) && isset($_POST['tx']) && isset($_POST['nb_echeances'])) {
            $montant = str_replace(' ', '', $_POST['montant']);
            $tx      = $_POST['tx'] / 100;

            $aRepaymentSchedule = \repayment::getRepaymentSchedule($montant, $_POST['nb_echeances'], $tx);
            echo $this->ficelle->formatNumber($aRepaymentSchedule[1]['repayment']);
        }
    }

    public function _verifEmail()
    {
        $this->autoFireView = false;

        $validMail = 'nok';
        if (isset($_POST['email']) && isset($_POST['oldemail'])) {
            $validMail = 'ok';

            if ($this->ficelle->isEmail($_POST['email']) == false) {
                $validMail = 'nok';
            } elseif ($this->clients->existEmail($_POST['email']) == false) {
                if ($_POST['email'] != $_POST['oldemail']) {
                    $validMail = 'nok';
                }
            }
        }
        echo $validMail;
    }

    public function _captcha_login()
    {
        $this->autoFireView = false;

        echo $captcha = '<iframe style="margin-top:-16px;margin-left:-5px;" class="captcha_login" width="133" height="33" src="' . $this->surl . '/images/default/securitecode.php"></iframe>';
    }

    public function _captcha()
    {
        $this->autoFireView = false;

        if (isset($_POST['security'])) {
            if (strtolower($_POST['security']) != $_SESSION['securecode']) {
                echo $captcha = '<iframe width="133" src="' . $this->surl . '/images/default/securitecode.php"></iframe>';
                //echo $_SESSION['securecode'];
            } else {
                echo 'nok';
            }
        } else {
            echo 'nok';
        }
    }

    public function _contact_form()
    {
        $this->autoFireView = false;

        $this->clients         = $this->loadData('clients');
        $this->demande_contact = $this->loadData('demande_contact');

        if (isset($_POST['name']) && isset($_POST['prenom']) && isset($_POST['email'])) {
            $form_ok = true;

            if (! isset($_POST['name']) || $_POST['name'] == '' || $_POST['name'] == $this->lng['contact']['nom']) {
                $form_ok = false;
            }

            if (! isset($_POST['prenom']) || $_POST['prenom'] == '' || $_POST['prenom'] == $this->lng['contact']['prenom']) {
                $form_ok = false;
            }

            if (! isset($_POST['email']) || $_POST['email'] == '' || $_POST['email'] == $this->lng['contact']['email']) {
                $form_ok = false;
            } elseif (! $this->ficelle->isEmail($_POST['email'])) {
                $form_ok = false;
            }

            if (! isset($_POST['security']) || $_POST['security'] == '' || $_POST['security'] == $this->lng['contact']['captcha']) {
                $form_ok = false;
            } elseif ($_SESSION['securecode'] != strtolower($_POST['security'])) {
                $form_ok = false;
            }

            if ($form_ok == true) {
                $this->demande_contact->demande   = 2;
                $this->demande_contact->nom       = $this->ficelle->majNom($_POST['name']);
                $this->demande_contact->prenom    = $this->ficelle->majNom($_POST['prenom']);
                $this->demande_contact->email     = $_POST['email'];
                $this->demande_contact->telephone = ($this->lng['contact']['telephone'] != $_POST['phone'] ? $_POST['phone'] : '');
                $this->demande_contact->societe   = ($this->lng['contact']['societe'] != $_POST['societe'] ? $_POST['societe'] : '');
                $this->demande_contact->message   = ($this->lng['contact']['message'] != $_POST['message'] ? $_POST['message'] : '');
                $this->demande_contact->create();

                $this->settings->get('Adresse preteur', 'type');
                $destinataire = $this->settings->value;

                //*****************************//
                //*** ENVOI DU MAIL CONTACT ***//
                //*****************************//
                $this->mails_text->get('demande-de-contact', 'lang = "' . $this->language . '" AND type');

                $this->settings->get('Facebook', 'type');
                $lien_fb = $this->settings->value;

                $this->settings->get('Twitter', 'type');
                $lien_tw = $this->settings->value;

                $pageProjets = $this->tree->getSlug(4, $this->language);

                $varMail = array(
                    'surl'     => $this->surl,
                    'url'      => $this->lurl,
                    'email_c'  => $this->demande_contact->email,
                    'prenom_c' => $this->demande_contact->prenom,
                    'nom_c'    => $this->demande_contact->nom,
                    'objet'    => 'Demande preteur',
                    'projets'  => $this->lurl . '/' . $pageProjets,
                    'lien_fb'  => $lien_fb,
                    'lien_tw'  => $lien_tw
                );

                $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                $this->email = $this->loadLib('email');
                $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                $this->email->setSubject(stripslashes($sujetMail));
                $this->email->setHTMLBody(stripslashes($texteMail));

                if ($this->Config['env'] == 'prod') {
                    Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->demande_contact->email, $tabFiler);
                    $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                } else {
                    $this->email->addRecipient(trim($this->demande_contact->email));
                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                }

                //***************************************//
                //*** ENVOI DU MAIL CONTACT A UNILEND ***//
                //***************************************//
                $this->mails_text->get('notification-demande-de-contact', 'lang = "' . $this->language . '" AND type');

                $surl   = $this->surl;
                $url    = $this->lurl;
                $email  = $this->demande_contact->email;
                $nom    = ($this->demande_contact->nom);
                $prenom = ($this->demande_contact->prenom);
                $objet  = ($objets[$this->demande_contact->demande]);

                $this->demande_contact->demande   = 2;
                $this->demande_contact->nom       = $this->ficelle->majNom($_POST['name']);
                $this->demande_contact->prenom    = $this->ficelle->majNom($_POST['prenom']);
                $this->demande_contact->email     = $_POST['email'];
                $this->demande_contact->telephone = ($this->lng['contact']['telephone'] != $_POST['phone'] ? $_POST['phone'] : '');
                $this->demande_contact->societe   = ($this->lng['contact']['societe'] != $_POST['societe'] ? $_POST['societe'] : '');
                $this->demande_contact->message   = ($this->lng['contact']['message'] != $_POST['message'] ? $_POST['message'] : '');

                $infos = '<ul>';
                $infos .= '<li>Type demande : Demande preteur</li>';
                $infos .= '<li>Nom : ' . utf8_decode($this->demande_contact->nom) . '</li>';
                $infos .= '<li>Prenom : ' . utf8_decode($this->demande_contact->prenom) . '</li>';
                $infos .= '<li>Email : ' . utf8_decode($this->demande_contact->email) . '</li>';
                $infos .= '<li>telephone : ' . utf8_decode($this->demande_contact->telephone) . '</li>';
                $infos .= '<li>Societe : ' . utf8_decode($this->demande_contact->societe) . '</li>';
                $infos .= '<li>Message : ' . utf8_decode($this->demande_contact->message) . '</li>';
                $infos .= '</ul>';

                $sujetMail = $this->mails_text->subject;
                eval("\$sujetMail = \"$sujetMail\";");

                $texteMail = $this->mails_text->content;
                eval("\$texteMail = \"$texteMail\";");

                $exp_name = $this->mails_text->exp_name;
                eval("\$exp_name = \"$exp_name\";");

                $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
                $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

                $this->email = $this->loadLib('email');
                $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                $this->email->addRecipient(trim($destinataire));
                $this->email->setSubject('=?UTF-8?B?' . base64_encode($sujetMail) . '?=');
                $this->email->setHTMLBody($texteMail);
                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);

                echo $captcha = '<iframe width="133" src="' . $this->surl . '/images/default/securitecode.php"></iframe>';
            } else {
                echo 'nok';
            }
        } else {
            echo 'nok';
        }
    }

    // on oblige l'utilisateur a mettre un mdp complexe
    public function _complexMdp()
    {
        $this->autoFireView = false;

        if (isset($_POST['mdp'])) {
            if ($this->ficelle->password_fo($_POST['mdp'], 6)) {
                echo 'ok';
            } else {
                echo 'nok';
            }
        } else {
            echo 'nok';
        }
    }

    // on verifie que c'est bien son mdp
    public function _controleYourMdp()
    {
        $this->autoFireView = false;

        if (isset($_POST['mdp']) && md5($_POST['mdp']) == $this->clients->password) {
            echo 'ok';
        } else {
            echo 'nok';
        }
    }

    // on verifie que c'est bien son mdp
    public function _controleAge()
    {
        $this->autoFireView = false;

        if (isset($_POST['d']) && $_POST['d'] != '' && isset($_POST['m']) && $_POST['m'] != '' && isset($_POST['y']) && $_POST['y'] != '') {

            $date = $_POST['y'] . '-' . $_POST['m'] . '-' . $_POST['d'];

            if ($this->dates->ageplus18($date) == true) {
                echo 'ok';
            } else {
                echo 'nok';
            }
        } else {
            echo 'nok';
        }
    }

    public function _accept_cgv()
    {
        $this->autoFireView = false;

        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');

        if ($this->clients->checkAccess() && isset($_POST['terms']) && isset($_POST['id_legal_doc'])) {
            if (! $this->acceptations_legal_docs->get($_POST['id_legal_doc'], 'id_client = "' . $this->clients->id_client . '" AND id_legal_doc')) {
                $this->acceptations_legal_docs->id_legal_doc = $_POST['id_legal_doc'];
                $this->acceptations_legal_docs->id_client    = $this->clients->id_client;
                $this->acceptations_legal_docs->create();

                unset($_COOKIE['accept_cgv']);
            }
        }
    }

    public function _syntheses_mouvements()
    {
        if ($this->clients->checkAccess() && isset($_POST['duree']) && in_array($_POST['duree'], array('mois', 'trimestres', 'annees')) || 5 == 5) {
            $this->lng['preteur-synthese'] = $this->ln->selectFront('preteur-synthese', $this->language, $this->App);

            $this->echeanciers      = $this->loadData('echeanciers');
            $this->lenders_accounts = $this->loadData('lenders_accounts');
            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

            ///////////// Partie vos remboursements mensuel ////////
            $this->anneeCreationCompte = date('Y', strtotime($this->clients->added));

            if ($_POST['duree'] == 'mois') {
                $this->arrayMois = array(
                    '1'  => 'JAN',
                    '2'  => 'FEV',
                    '3'  => 'MAR',
                    '4'  => 'AVR',
                    '5'  => 'MAI',
                    '6'  => 'JUIN',
                    '7'  => 'JUIL',
                    '8'  => 'AOUT',
                    '9'  => 'SEPT',
                    '10' => 'OCT',
                    '11' => 'NOV',
                    '12' => 'DEC'
                );

                $c = 1;
                $d = 0;

                // On parcourt toutes les années de la creation du compte a aujourd'hui
                for ($annee = $this->anneeCreationCompte; $annee <= date('Y'); $annee++) {
                    // Revenus mensuel
                    $tabSumRembParMois[$annee]             = $this->echeanciers->getSumRembByMonthsCapital($this->lenders_accounts->id_lender_account, $annee); // captial remboursé / mois
                    $tabSumIntbParMois[$annee]             = $this->echeanciers->getSumIntByMonths($this->lenders_accounts->id_lender_account . ' AND status_ra = 0 ', $annee); // intérets brut / mois
                    $tabSumRevenuesfiscalesParMois[$annee] = $this->echeanciers->getSumRevenuesFiscalesByMonths($this->lenders_accounts->id_lender_account . ' AND status_ra = 0 ', $annee); // revenues fiscales / mois

                    for ($i = 1; $i <= 12; $i++) {
                        $a                                            = $i;
                        $a                                            = ($i < 10 ? '0' . $a : $a);
                        $this->sumRembParMois[$annee][$i]             = number_format(($tabSumRembParMois[$annee][$a] != '' ? $tabSumRembParMois[$annee][$a] : 0), 2, '.', ''); // capital remboursé / mois
                        $this->sumIntbParMois[$annee][$i]             = number_format(($tabSumIntbParMois[$annee][$a] != '' ? $tabSumIntbParMois[$annee][$a] - $tabSumRevenuesfiscalesParMois[$annee][$a] : 0), 2, '.', ''); // interets net / mois
                        $this->sumRevenuesfiscalesParMois[$annee][$i] = number_format(($tabSumRevenuesfiscalesParMois[$annee][$a] != '' ? $tabSumRevenuesfiscalesParMois[$annee][$a] : 0), 2, '.', ''); // prelevements fiscaux

                        // on organise l'affichage
                        if ($d == 3) {
                            $d = 0;
                            $c += 1;
                        }
                        $this->lesmois[$annee . '_' . $i] = $c;

                        $nbSlides = $c;

                        $d++;
                    }
                }

                // On organise l'afichage partie 2
                $a = 1;
                for ($i = 1; $i <= $nbSlides; $i++) {

                    // On recup a partir de la date du jour
                    if ($this->lesmois[date('Y_n')] <= $i) {
                        $this->ordre[$a] = $i;
                        $a++;
                    } // On recup les dates d'avant
                    else {
                        $tabPositionsAvavant[$i] = $i;
                    }
                }

                // On recupe la derniere clé
                $this->TabTempOrdre = $this->ordre;
                end($this->TabTempOrdre);
                $lastKey = key($this->TabTempOrdre);


                // On rassemble le tout comme ca tout est dans le bon ordre d'affichage
                $position = $lastKey + 1;
                foreach ($tabPositionsAvavant as $p) {
                    $this->ordre[$position] = $p;
                    $position++;
                }
            } elseif ($_POST['duree'] == 'trimestres') {
                // On parcourt toutes les années de la creation du compte a aujourd'hui
                $nbSlides = 0;
                for ($annee = $this->anneeCreationCompte; $annee <= date('Y'); $annee++) {
                    // Revenus mensuel
                    $tabSumRembParMois[$annee]             = $this->echeanciers->getSumRembByMonthsCapital($this->lenders_accounts->id_lender_account, $annee); // captial remboursé / mois
                    $tabSumIntbParMois[$annee]             = $this->echeanciers->getSumIntByMonths($this->lenders_accounts->id_lender_account . ' AND status_ra = 0 ', $annee); // intérets brut / mois
                    $tabSumRevenuesfiscalesParMois[$annee] = $this->echeanciers->getSumRevenuesFiscalesByMonths($this->lenders_accounts->id_lender_account . ' AND status_ra = 0 ', $annee); // revenues fiscales / mois

                    // on fait le tour sur l'année
                    for ($i = 1; $i <= 12; $i++) {
                        $a                                            = $i;
                        $a                                            = ($i < 10 ? '0' . $a : $a);
                        $this->sumRembParMois[$annee][$i]             = number_format(($tabSumRembParMois[$annee][$a] != '' ? $tabSumRembParMois[$annee][$a] : 0), 2, '.', ''); // capital remboursé / mois
                        $this->sumIntParMois[$annee][$i]              = number_format(($tabSumIntbParMois[$annee][$a] != '' ? $tabSumIntbParMois[$annee][$a] - $tabSumRevenuesfiscalesParMois[$annee][$a] : 0), 2, '.', ''); // interets net / mois
                        $this->sumRevenuesfiscalesParMois[$annee][$i] = number_format(($tabSumRevenuesfiscalesParMois[$annee][$a] != '' ? $tabSumRevenuesfiscalesParMois[$annee][$a] : 0), 2, '.', ''); // prelevements fiscaux
                    }

                    $this->sumRembPartrimestre[$annee][1] = ($this->sumRembParMois[$annee][1] + $this->sumRembParMois[$annee][2] + $this->sumRembParMois[$annee][3]);
                    $this->sumRembPartrimestre[$annee][2] = ($this->sumRembParMois[$annee][4] + $this->sumRembParMois[$annee][5] + $this->sumRembParMois[$annee][6]);
                    $this->sumRembPartrimestre[$annee][3] = ($this->sumRembParMois[$annee][7] + $this->sumRembParMois[$annee][8] + $this->sumRembParMois[$annee][9]);
                    $this->sumRembPartrimestre[$annee][4] = ($this->sumRembParMois[$annee][10] + $this->sumRembParMois[$annee][11] + $this->sumRembParMois[$annee][12]);

                    $this->sumIntPartrimestre[$annee][1] = ($this->sumIntParMois[$annee][1] + $this->sumIntParMois[$annee][2] + $this->sumIntParMois[$annee][3]);
                    $this->sumIntPartrimestre[$annee][2] = ($this->sumIntParMois[$annee][4] + $this->sumIntParMois[$annee][5] + $this->sumIntParMois[$annee][6]);
                    $this->sumIntPartrimestre[$annee][3] = ($this->sumIntParMois[$annee][7] + $this->sumIntParMois[$annee][8] + $this->sumIntParMois[$annee][9]);
                    $this->sumIntPartrimestre[$annee][4] = ($this->sumIntParMois[$annee][10] + $this->sumIntParMois[$annee][11] + $this->sumIntParMois[$annee][12]);

                    $this->sumFiscalesPartrimestre[$annee][1] = ($this->sumRevenuesfiscalesParMois[$annee][1] + $this->sumRevenuesfiscalesParMois[$annee][2] + $this->sumRevenuesfiscalesParMois[$annee][3]);
                    $this->sumFiscalesPartrimestre[$annee][2] = ($this->sumRevenuesfiscalesParMois[$annee][4] + $this->sumRevenuesfiscalesParMois[$annee][5] + $this->sumRevenuesfiscalesParMois[$annee][6]);
                    $this->sumFiscalesPartrimestre[$annee][3] = ($this->sumRevenuesfiscalesParMois[$annee][7] + $this->sumRevenuesfiscalesParMois[$annee][8] + $this->sumRevenuesfiscalesParMois[$annee][9]);
                    $this->sumFiscalesPartrimestre[$annee][4] = ($this->sumRevenuesfiscalesParMois[$annee][10] + $this->sumRevenuesfiscalesParMois[$annee][11] + $this->sumRevenuesfiscalesParMois[$annee][12]);

                    $nbSlides += 1;
                }

                // On organise l'afichage partie 2
                $a = 1;
                for ($annee = $this->anneeCreationCompte; $annee <= date('Y'); $annee++) {
                    // On recup a partir de la date du jour
                    if (date('Y') <= $annee) {
                        $this->ordre[$a] = $annee;
                        $a++;
                    } // On recup les dates d'avant
                    else {
                        $tabPositionsAvavant[$annee] = $annee;
                    }
                }

                // On recupe la derniere clé
                $this->TabTempOrdre = $this->ordre;
                end($this->TabTempOrdre);
                $lastKey = key($this->TabTempOrdre);

                // On rassemble le tout comme ca tout est dans le bon ordre d'affichage
                $position = $lastKey + 1;
                foreach ($tabPositionsAvavant as $p) {
                    $this->ordre[$position] = $p;
                    $position++;
                }
            } // annee
            else {
                // debut et fin
                $this->debut = $this->anneeCreationCompte;
                $this->fin   = date('Y');

                // on organise
                $i = 1;
                $a = 0;
                for ($c = $this->debut; $c <= $this->fin; $c++) {
                    if ($a >= 3) {
                        $a = 0;
                        $i++;
                    }
                    // on recup un tableau organisé
                    $this->tab[$c] = $i;
                    $arraynb[$i] += 1;

                    $a++;
                }

                // on calcule ce qu'il manque pour le rajouter en date de fin
                foreach ($arraynb as $a) {
                    if ($a != 3) {
                        $diff = (3 - $a);
                        $this->fin += $diff;
                    }
                }

                // On relance le tableau avec toutes les dates

                $i = 1;
                $a = 0;
                for ($c = $this->debut; $c <= $this->fin; $c++) {
                    if ($a >= 3) {
                        $a = 0;
                        $i++;
                    }
                    // on recup un tableau mieux organisé
                    $this->tab[$c] = $i;
                    $a++;
                }

                $this->sumRembParAn   = $this->echeanciers->getSumRembByYearCapital($this->lenders_accounts->id_lender_account, $this->debut, $this->fin);
                $this->sumIntParAn    = $this->echeanciers->getSumIntByYear($this->lenders_accounts->id_lender_account . ' AND status_ra = 0 ', $this->debut, $this->fin);
                $this->sumFiscalParAn = $this->echeanciers->getSumRevenuesFiscalesByYear($this->lenders_accounts->id_lender_account . ' AND status_ra = 0 ', $this->debut, $this->fin);
            }
        }
    }

    public function _notifications_header()
    {
        $this->autoFireView = true;

        if (isset($_SESSION['client']['id_client']) && isset($_POST['compteur_notif']) && $_POST['compteur_notif'] != 'noMore') {
            $this->notifications    = $this->loadData('notifications');
            $this->bids             = $this->loadData('bids');
            $this->projects         = $this->loadData('projects');
            $this->lenders_accounts = $this->loadData('lenders_accounts');
            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

            if ($_POST['compteur_notif'] != 'true') {
                $_SESSION['nbNotifdisplay'] = $_POST['compteur_notif'];
            }

            $id_lender = $_POST['id_lender'];
            $debut     = $_SESSION['nbNotifdisplay'];
            $nbDisplay = 10;

            $_SESSION['nbNotifdisplay'] = ($_SESSION['nbNotifdisplay'] + $nbDisplay);

            $this->lNotifHeader = $this->notifications->select('id_lender = ' . $this->lenders_accounts->id_lender_account, 'added DESC', $debut, $nbDisplay);

            if ($this->lNotifHeader == false) {
                echo 'noMore';
                die;
            }
        } elseif (isset($_SESSION['client']['id_client']) && isset($_POST['marquerlu']) && $_POST['marquerlu'] == true) {
            $this->lenders_accounts = $this->loadData('lenders_accounts');
            $this->notifications    = $this->loadData('notifications');

            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

            // on recup tout pour changer le statut en vue
            $this->lNotif = $this->notifications->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND status = 0');
            foreach ($this->lNotif as $n) {
                $this->notifications->get($n['id_notification'], 'id_notification');
                $this->notifications->status = 1;
                $this->notifications->update();
            }

            $this->lNotifHeader = $this->notifications->select('id_lender = ' . $this->lenders_accounts->id_lender_account, 'added DESC', 0, 10);
        } else {
            die;
        }
    }

    public function _vos_operations()
    {
        $this->autoFireView = true;

        $this->transactions  = $this->loadData('transactions');
        $this->wallets_lines = $this->loadData('wallets_lines');
        $this->bids          = $this->loadData('bids');
        $this->loans         = $this->loadData('loans');
        $this->echeanciers   = $this->loadData('echeanciers');
        $this->projects      = $this->loadData('projects');
        $this->companies     = $this->loadData('companies');

        $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
        $this->lng['preteur-operations-pdf']            = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);
        $this->lng['preteur-operations']                = $this->ln->selectFront('preteur-operations', $this->language, $this->App);

        // On met en session les POST pour le PDF
        $_SESSION['filtre_vos_operations']['debut']            = $_POST['debut'];
        $_SESSION['filtre_vos_operations']['fin']              = $_POST['fin'];
        $_SESSION['filtre_vos_operations']['nbMois']           = $_POST['nbMois'];
        $_SESSION['filtre_vos_operations']['annee']            = $_POST['annee'];
        $_SESSION['filtre_vos_operations']['tri_type_transac'] = $_POST['tri_type_transac'];
        $_SESSION['filtre_vos_operations']['tri_projects']     = $_POST['tri_projects'];
        $_SESSION['filtre_vos_operations']['id_last_action']   = $_POST['id_last_action'];
        $_SESSION['filtre_vos_operations']['order']            = isset($_POST['order']) ? $_POST['order'] : '';
        $_SESSION['filtre_vos_operations']['type']             = isset($_POST['type']) ? $_POST['type'] : '';
        $_SESSION['filtre_vos_operations']['id_client']        = $this->clients->id_client;

        //////////// DEBUT PARTIE DATES //////////////
        //echo $_SESSION['id_last_action'];
        // tri debut/fin
        if (isset($_POST['id_last_action']) && in_array($_POST['id_last_action'], array('debut', 'fin'))) {

            $debutTemp = explode('/', $_POST['debut']);
            $finTemp   = explode('/', $_POST['fin']);

            $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date debut
            $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date fin

            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $_POST['id_last_action'];

        } // NB mois
        elseif (isset($_POST['id_last_action']) && $_POST['id_last_action'] == 'nbMois') {

            $nbMois = $_POST['nbMois'];

            $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, date("d"), date('Y')); // date debut
            $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin

            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $_POST['id_last_action'];
        } // Annee
        elseif (isset($_POST['id_last_action']) && $_POST['id_last_action'] == 'annee') {

            $year = $_POST['annee'];

            $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date debut

            if (date('Y') == $year) {
                $date_fin_time = mktime(0, 0, 0, date('m'), date('d'), $year);
            } // date fin
            else {
                $date_fin_time = mktime(0, 0, 0, 12, 31, $year);
            } // date fin

            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $_POST['id_last_action'];

        } // si on a une session
        elseif (isset($_SESSION['id_last_action'])) {

            if (in_array($_SESSION['id_last_action'], array('debut', 'fin'))) {
                //echo 'toto';
                $debutTemp = explode('/', $_POST['debut']);
                $finTemp   = explode('/', $_POST['fin']);

                $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date debut
                $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date fin
            } elseif ($_SESSION['id_last_action'] == 'nbMois') {
                //echo 'titi';
                $nbMois = $_POST['nbMois'];

                $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, date("d"), date('Y')); // date debut
                $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin
            } elseif ($_SESSION['id_last_action'] == 'annee') {
                //echo 'tata';
                $year = $_POST['annee'];

                $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date debut
                $date_fin_time   = mktime(0, 0, 0, 12, 31, $year); // date fin
            }
        } // Par defaut (on se base sur le 1M)
        else {
            //echo 'cc';
            $date_debut_time = mktime(0, 0, 0, date("m") - 1, date("d"), date('Y')); // date debut
            $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date fin
        }

        // on recup au format sql
        $this->date_debut = date('Y-m-d', $date_debut_time);
        $this->date_fin   = date('Y-m-d', $date_fin_time);

        // affichage dans le filtre
        $this->date_debut_display = date('d/m/Y', $date_debut_time);
        $this->date_fin_display   = date('d/m/Y', $date_fin_time);
        //////////// FIN PARTIE DATES //////////////

        $array_type_transactions = array(
            1  => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
            2  => array(
                1 => $this->lng['preteur-operations-vos-operations']['offre-en-cours'],
                2 => $this->lng['preteur-operations-vos-operations']['offre-rejetee'],
                3 => $this->lng['preteur-operations-vos-operations']['offre-acceptee']
            ),
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
            23 => $this->lng['preteur-operations-vos-operations']['remboursement-anticipe-preteur']
        );

        ////////// DEBUT PARTIE TRI TYPE TRANSAC /////////////
        $array_type_transactions_liste_deroulante = array(
            1 => '1,2,3,4,5,7,8,16,17,19,20,23',
            2 => '3,4,7,8',
            3 => '3,4,7',
            4 => '8',
            5 => '2',
            6 => '5,23'
        );

        if (isset($_POST['tri_type_transac'])) {
            $tri_type_transac = $array_type_transactions_liste_deroulante[$_POST['tri_type_transac']];
        }

        ////////// DEBUT TRI PAR PROJET /////////////
        if (isset($_POST['tri_projects'])) {
            if (in_array($_POST['tri_projects'], array(0, 1))) {
                $tri_project = '';
            } else {
                //$tri_project = ' HAVING le_id_project = '.$_POST['tri_projects'];
                $tri_project = ' AND id_projet = ' . $_POST['tri_projects'];
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
        $this->lTrans                  = $this->indexage_vos_operations->select('type_transaction IN (' . $tri_type_transac . ') AND id_client = ' . $this->clients->id_client . ' AND LEFT(date_operation,10) >= "' . $this->date_debut . '" AND LEFT(date_operation,10) <= "' . $this->date_fin . '"' . $tri_project, $order);
        $this->lProjectsLoans          = $this->indexage_vos_operations->get_liste_libelle_projet('type_transaction IN (' . $tri_type_transac . ') AND id_client = ' . $this->clients->id_client . ' AND LEFT(date_operation,10) >= "' . $this->date_debut . '" AND LEFT(date_operation,10) <= "' . $this->date_fin . '"');
    }

    public function _detail_op()
    {
        $this->autoFireView = true;

        if (isset($_POST['annee']) && strlen($_POST['annee']) == 4 && is_numeric($_POST['annee'])) {
            $this->transactions    = $this->loadData('transactions');
            $this->wallets_lines   = $this->loadData('wallets_lines');
            $this->bids            = $this->loadData('bids');
            $this->loans           = $this->loadData('loans');
            $this->echeanciers     = $this->loadData('echeanciers');
            $this->projects        = $this->loadData('projects');
            $this->companies       = $this->loadData('companies');
            $this->projects_status = $this->loadData('projects_status');

            $this->lng['preteur-operations']                = $this->ln->selectFront('preteur-operations', $this->language, $this->App);
            $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
            $this->lng['preteur-operations-pdf']            = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);
            $this->lng['preteur-operations-detail']         = $this->ln->selectFront('preteur-operations-detail', $this->language, $this->App);
            $this->lng['profile']                           = $this->ln->selectFront('preteur-profile', $this->language, $this->App);

            $annee = $_POST['annee'];

            $this->type  = $_POST['type'];
            $this->order = $_POST['order'];

            if ($this->type == "order_titre") {
                $tri = 1;
            } elseif ($this->type == "order_note") {
                $tri = 2;
            } elseif ($this->type == "order_montant") {
                $tri = 3;
            } elseif ($this->type == "order_interet") {
                $tri = 4;
            } elseif ($this->type == "order_debut") {
                $tri = 5;
            } elseif ($this->type == "order_prochaine") {
                $tri = 6;
            } elseif ($this->type == "order_fin") {
                $tri = 7;
            } elseif ($this->type == "order_mensualite") {
                $tri = 8;
            }

            $arrayTri = array(
                0 => 'next_echeance',
                1 => 'p.title',
                2 => 'p.risk',
                3 => 'amount',
                4 => 'rate',
                5 => 'debut',
                6 => 'next_echeance',
                7 => 'fin',
                8 => 'mensuel'
            );

            if ($this->order == "") {
                $this->order = "ASC";
            }

            if (false === isset($tri) || $tri == "") {
                $tri = 1;
            }

            $this->lSumLoans               = $this->loans->getSumLoansByProject($this->lenders_accounts->id_lender_account, $annee, $arrayTri[$tri] . " " . $this->order);
            $this->arrayDeclarationCreance = array(1456, 1009, 1614, 3089, 10971, 970, 7727, 374, 679, 1011);
        } else {
            echo 'nok';
            die;
        }
    }

    public function _acceptCookies()
    {
        $accept_cookies = $this->loadData('accept_cookies');
        $accept_cookies->ip                = $_SERVER['REMOTE_ADDR'];
        $accept_cookies->id_client         = $this->clients->id_client;
        $accept_cookies->id_accept_cookies = $accept_cookies->create();

        $expire = 365 * 24 * 3600; // on définit la durée du cookie, 1 an
        setcookie("acceptCookies", $accept_cookies->id_accept_cookies, time() + $expire, '/');  // on l'envoi

        $create = true;

        echo json_encode(array('reponse' => $create));

        die;
    }

    public function _reordrePays()
    {
        $pays = $this->loadData('pays_v2');
        $i    = 1;
        foreach ($pays->select('id_pays <> 1', 'fr ASC') as $p) {
            $i++;
            echo $p['fr'] . "-$i<br/>";
            $pays->get($p['id_pays']);
            $pays->ordre = $i;
            $pays->update();
        }
    }

    public function _operations_emprunteur()
    {
        $this->autoFireView = true;
        $this->lng['espace-emprunteur'] = $this->ln->selectFront('espace-emprunteur', $this->language, $this->App);

        $oClients   = $this->loadData('clients');
        $oCompanies = $this->loadData('companies');
        $oProjects  = $this->loadData('projects');

        $oClients->get($this->clients->id_client);
        $oCompanies->get($oClients->id_client, 'id_client_owner');

        if (isset($_POST['id_last_action']) && in_array($_POST['id_last_action'], array('debut', 'fin', 'nbMois', 'annee'))){

            switch ($_POST['id_last_action']) {
                case 'debut':
                case 'fin' :
                    $oStartTime                 = DateTime::createFromFormat('j/m/Y', $_POST['debut']);
                    $oEndTime                   = DateTime::createFromFormat('j/m/Y', $_POST['fin']);
                    $_SESSION['id_last_action'] = $_POST['id_last_action'];
                    break;
                case 'nbMois':
                    $oStartTime                 = new \datetime('NOW - ' . $_POST['nbMois'] . 'month');
                    $oEndTime                   = new \datetime();
                    $_SESSION['id_last_action'] = $_POST['id_last_action'];
                    break;
                case 'annee':
                    $oStartTime = new \datetime();
                    $oStartTime->setDate($_POST['annee'], '01', '01');
                    $oEndTime = new \datetime();
                    $oEndTime->setDate($_POST['annee'], '12', '31');
                    $_SESSION['id_last_action'] = $_POST['id_last_action'];
                    break;
            }

        } elseif (isset ($_SESSION['id_last_action'])) {

            switch ($_SESSION['id_last_action']) {
                case 'debut':
                case 'fin' :
                    $oStartTime = DateTime::createFromFormat('j/m/Y', $_POST['debut']);
                    $oEndTime   = DateTime::createFromFormat('j/m/Y', $_POST['fin']);
                    break;
                case 'nbMois':
                    $oStartTime = new \datetime('NOW - ' . $_POST['nbMois'] . 'month');
                    $oEndTime   = new \datetime();
                    break;
                case 'annee':
                    $oStartTime = new \datetime();
                    $oStartTime->setDate($_POST['annee'], '01', '01');
                    $oEndTime = new \datetime();
                    $oEndTime->setDate($_POST['annee'], '12', '31');
                    break;
            }
        } else {
                $oStartTime = new \datetime('NOW - 1 month');
                $oEndTime = new \datetime();
        }


        if ($_POST['tri_projects'] == 0 || $_POST['tri_projects'] == 99 ) {
            $aClientsProjects = $oProjects->select('id_company = ' . $oCompanies->id_company);
        } else {
            $aClientsProjects = $oProjects->select('id_project =' . $_POST['tri_projects']);
        }

        foreach ($aClientsProjects as $project) {
            $aClientProjectIDs[] = $project['id_project'];
        }

        $iTransaction = ($_POST['tri_type_transac'] == 99 ) ? null :  $_POST['tri_type_transac'];

        $_SESSION['operations-filter'] = array('projects' =>$aClientProjectIDs, 'start' => $oStartTime, 'end'=>$oEndTime, 'transaction'=>$iTransaction);

        $this->aBorrowerOperations = $oClients->getDataForBorrowerOperations($aClientProjectIDs, $oStartTime, $oEndTime, $iTransaction, $oClients->id_client);
        $this->sDisplayDateTimeStart = $oStartTime->format('d/m/Y');
        $this->sDisplayDateTimeEnd = $oEndTime->format('d/m/Y');

    }

}
