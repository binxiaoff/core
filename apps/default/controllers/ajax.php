<?php

class ajaxController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $_SESSION['request_url'] = $this->lurl;

        $this->autoFireHeader = false;
        $this->autoFireDebug  = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
    }

    /* Modification de la modifcation des traductions à la volée */
    public function _activeModificationsTraduction()
    {
        $this->autoFireView = false;

        $_SESSION['modification'] = $this->params[0];
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

            if ($oVille->exist(str_replace(array(' ', '-'), '', urldecode($this->params[0])), 'REPLACE(REPLACE(ville, " ", ""), "-", "")')) {
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

        $this->projects         = $this->loadData('projects');
        $this->projects_status  = $this->loadData('projects_status');
        $this->companies        = $this->loadData('companies');
        $this->favoris          = $this->loadData('favoris');
        $this->bids             = $this->loadData('bids');
        $this->loans            = $this->loadData('loans');
        $this->lenders_accounts = $this->loadData('lenders_accounts');

        $where = '';
        $ordre = $this->tabOrdreProject[$_GET['ordreProject']];

        $_SESSION['ordreProject'] = $_GET['ordreProject'];

        // sort projects by rate
        $aRateRange = array();
        if (isset($_SESSION['tri']['taux'])) {
            $key = $_SESSION['tri']['taux'];
            switch ($key) {
                case 1:
                    $aRateRange = explode('-', $this->triPartxInt[0]);
                    break;
                case 2:
                    $aRateRange = explode('-', $this->triPartxInt[1]);
                    break;
                case 3:
                    $aRateRange = explode('-', $this->triPartxInt[2]);
                    break;
                default:
                    break;
            }
        }

        // filter completed projects
        if (isset($_GET['type']) && $_GET['type'] == 4) {
            $where = ' AND p.date_fin < "' . date('Y-m-d') . '"';
        }

        $sPositionStart = filter_var($_GET['positionStart'], FILTER_SANITIZE_NUMBER_INT) + 10;
        $this->lProjetsFunding = $this->projects->selectProjectsByStatus($this->tabProjectDisplay, $where . ' AND p.status = 0 AND p.display = 0', $ordre, $aRateRange, $sPositionStart, 10);
        $affichage             = '';

        if (empty($this->lProjetsFunding)) {
            $bHasMore = false;
        } else {
            $bHasMore = true;
            foreach ($this->lProjetsFunding as $project) {
                $this->projects_status->getLastStatut($project['id_project']);
                $this->companies->get($project['id_company'], 'id_company');

                $inter = $this->dates->intervalDates(date('Y-m-d h:i:s'), $project['date_retrait_full']); // date fin 21h a chaque fois
                if ($inter['mois'] > 0) {
                    $dateRest = $inter['mois'] . ' ' . $this->lng['preteur-projets']['mois'];
                } else {
                    $dateRest = '';
                }

                // dates pour le js
                $mois_jour = $this->dates->formatDate($project['date_retrait'], 'F d');
                $annee     = $this->dates->formatDate($project['date_retrait'], 'Y');

                $avgRate  = $this->projects->getAverageInterestRate($project['id_project'], $this->projects_status->status);

                $affichage .= "
            <tr class='unProjet' id='project" . $project['id_project'] . "'>
                <td>";
                if ($this->projects_status->status < \projects_status::FUNDE) {
                    $tab_date_retrait = explode(' ', $project['date_retrait_full']);
                    $tab_date_retrait = explode(':', $tab_date_retrait[1]);
                    $heure_retrait    = $tab_date_retrait[0] . ':' . $tab_date_retrait[1];

                    $affichage .= "
                        <script>
                            var cible" . $project['id_project'] . " = new Date('" . $mois_jour . ", " . $annee . " " . $heure_retrait . ":00');
                            var letime" . $project['id_project'] . " = parseInt(cible" . $project['id_project'] . ".getTime() / 1000, 10);
                            setTimeout('decompte(letime" . $project['id_project'] . ",\"val" . $project['id_project'] . "\")', 500);
                        </script>";
                } else {
                    if ($project['date_fin'] != '0000-00-00 00:00:00') {
                        $endDateTime = new \DateTime($project['date_fin']);
                    } else {
                        $endDateTime = new \DateTime($project['date_retrait_full']);
                    }
                    $endDate  = strftime('%d %B', $endDateTime->getTimestamp());
                    $dateRest = str_replace('[#date#]', $endDate, $this->lng['preteur-projets']['termine']);
                }

                if ($project['photo_projet'] != '') {
                    $affichage .= "<a class='lien' href='" . $this->lurl . "/projects/detail/" . $project['slug'] . "'><img src='" . $this->surl . '/images/dyn/projets/72/' . $project['photo_projet'] . "' alt='" . $project['photo_projet'] . "' class='thumb'></a>";
                }

                $affichage .= "
                    <div class='description'>";
                if ($_SESSION['page_projet'] == 'projets_fo') {
                    $affichage .= "<h5><a href='" . $this->lurl . '/projects/detail/' . $project['slug'] . "'>" . $project['title'] . "</a></h5>";
                } else {
                    $affichage .= "<h5><a href='" . $this->lurl . "/projects/detail/" . $project['slug'] . "'>" . $project['title'] . "</a></h5>";
                }
                $affichage .= "<h6>" . $this->companies->city . ($this->companies->zip != '' ? ', ' : '') . $this->companies->zip . "</h6>
                        <p>" . $project['nature_project'] . "</p>
                    </div><!-- /.description -->
                </td>
                <td>
                    <a class='lien' href='" . $this->lurl . "/projects/detail/" . $project['slug'] . "'>
                        <div class='cadreEtoiles'><div class='etoile " . $this->lNotes[$project['risk']] . "'></div></div>
                    </a>
                </td>
                <td style='white-space:nowrap;'>
                    <a class='lien' href='" . $this->lurl . "/projects/detail/" . $project['slug'] . "'>
                        " . $this->ficelle->formatNumber($project['amount'], 0) . "€
                    </a>
                </td>
                <td style='white-space:nowrap;'>
                <a class='lien' href='" . $this->lurl . "/projects/detail/" . $project['slug'] . "'>
                    " . ($project['period'] == 1000000 ? $this->lng['preteur-projets']['je-ne-sais-pas'] : $project['period'] . ' ' . $this->lng['preteur-projets']['mois']) . "
                    </a>
                </td>";
                $affichage .= "<td><a class='lien' href='" . $this->lurl . "/projects/detail/" . $project['slug'] . "'>";
                $affichage .= $this->ficelle->formatNumber($avgRate, 1) . "%";
                $affichage .= "</a></td>";
                $affichage .= "<td><a class='lien' href='" . $this->lurl . "/projects/detail/" . $project['slug'] . "'>";

                if ($this->projects_status->status >= \projects_status::FUNDE) {
                    $affichage .= "<span class=\"project_ended\" id='val" . $project['id_project'] . "'>" . $dateRest . "</span></a></td>
                <td><a href='" . $this->lurl . "/projects/detail/" . $project['slug'] . "' class='btn btn-info btn-small multi grise1 btn-grise'>" . $this->lng['preteur-projets']['voir-le-projet'] . "</a>";
                } else {
                    $affichage .= "<span id='val" . $project['id_project'] . "'>" . $dateRest . "</span></a></td>
                <td><a href='" . $this->lurl . "/projects/detail/" . $project['slug'] . "' class='btn btn-info btn-small'>" . $this->lng['preteur-projets']['pretez'] . "</a>";
                }
                $affichage .= "</td>
            </tr>
            ";
            }
        }
        $table = array('affichage' => $affichage, 'positionStart' => $sPositionStart, 'hasMore' => $bHasMore);
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
        $this->companies         = $this->loadData('companies');
        $this->favoris           = $this->loadData('favoris');
        $this->lenders_accounts  = $this->loadData('lenders_accounts');
        $this->loadData('projects_status'); // Loaded for class constants

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
            $this->where = '';

            // tri temps
            if (isset($_SESSION['tri']['temps'])) {
                $this->ordreProject = $_SESSION['tri']['temps'];
                $sStatusProject     = \projects_status::EN_FUNDING;
            } else {
                $this->ordreProject = 1;
                $sStatusProject     = $this->tabProjectDisplay;
            }

            if (isset($_SESSION['tri']['type']) && $_POST['id'] != 'temps') {
                $aStatusproject = array(
                    \projects_status::FUNDE,
                    \projects_status::FUNDING_KO,
                    \projects_status::PRET_REFUSE,
                    \projects_status::REMBOURSEMENT,
                    \projects_status::REMBOURSE,
                    \projects_status::PROBLEME,
                    \projects_status::RECOUVREMENT,
                    \projects_status::PROCEDURE_SAUVEGARDE,
                    \projects_status::REMBOURSEMENT_ANTICIPE,
                    \projects_status::PROBLEME,
                    \projects_status::PROBLEME_J_X,
                    \projects_status::REDRESSEMENT_JUDICIAIRE,
                    \projects_status::LIQUIDATION_JUDICIAIRE,
                    \projects_status::DEFAUT
                );
                if ($_SESSION['tri']['type'] == 4) {
                    $sStatusProject = implode(', ', $aStatusproject);
                } else if ($_SESSION['tri']['type'] == 1) {
                    $aStatusproject[] = \projects_status::EN_FUNDING;
                    $sStatusProject = implode(', ', $aStatusproject);
                }
            }

            $_SESSION['ordreProject'] = $this->ordreProject;

            // sort projects by rate
            $aRateRange = array();
            if (isset($_SESSION['tri']['taux'])) {
                $key        = $_SESSION['tri']['taux'];
                $aRateRange = explode('-', $this->triPartxInt[$key - 1]);

                // where pour le js
                $this->where = $key;
            }

            $this->lProjetsFunding = $this->projects->selectProjectsByStatus($sStatusProject, ' AND p.status = 0 AND p.display = 0', $this->tabOrdreProject[$this->ordreProject], $aRateRange, 0, 10);
            $this->nbProjects      = $this->projects->countSelectProjectsByStatus($sStatusProject . ' AND p.status = 0 AND p.display = 0');
        } else {
            $this->ordreProject = 1;
            $this->type         = 0;

            $_SESSION['ordreProject'] = $this->ordreProject;

            $this->where           = '';
            $this->lProjetsFunding = $this->projects->selectProjectsByStatus($this->tabProjectDisplay, ' AND p.status = 0', $this->tabOrdreProject[$this->ordreProject], array(), 0, 10);
            $this->nbProjects      = $this->projects->countSelectProjectsByStatus($this->tabProjectDisplay . ' AND p.status = 0');
        }
        foreach ($this->lProjetsFunding as $iKey => $aProject) {
            $this->companies->get($aProject['id_company'], 'id_company');

            $inter = $this->dates->intervalDates(date('Y-m-d h:i:s'), $aProject['date_retrait_full']);
            if ($inter['mois'] > 0) {
                $this->lProjetsFunding[$iKey]['daterest'] = $inter['mois'] . ' ' . $this->lng['preteur-projets']['mois'];
            } else {
                if ($this->lProjetsFunding[$iKey]['date_fin'] != '0000-00-00 00:00:00') {
                    $endDateTime = new \DateTime($this->lProjetsFunding[$iKey]['date_fin']);
                } else {
                    $endDateTime = new \DateTime($this->lProjetsFunding[$iKey]['date_retrait_full']);
                }
                $endDate                                  = strftime('%d %B', $endDateTime->getTimestamp());
                $this->lProjetsFunding[$iKey]['daterest'] = str_replace('[#date#]', $endDate, $this->lng['preteur-projets']['termine']);
            }

            $this->lProjetsFunding[$iKey]['taux'] = $this->ficelle->formatNumber($this->projects->getAverageInterestRate($aProject['id_project'], $aProject['status']), 1);
        }
    }

    public function _favori()
    {
        $this->autoFireView = false;

        $this->projects          = $this->loadData('projects');
        $this->companies         = $this->loadData('companies');
        $this->favoris           = $this->loadData('favoris');

        if (isset($_POST['id_project']) && isset($_POST['id_client']) && $this->clients->get($_POST['id_client'], 'id_client') && $this->projects->get($_POST['id_project'], 'id_project')) {
            if ($this->favoris->get($this->clients->id_client, 'id_project = ' . $this->projects->id_project . ' AND id_client')) {
                $this->favoris->delete($this->clients->id_client, 'id_project = ' . $this->projects->id_project . ' AND id_client');
                $val = 'delete';
            } else {
                $this->favoris->id_client  = $this->clients->id_client;
                $this->favoris->id_project = $this->projects->id_project;
                $this->favoris->create();
                $val = 'create';
            }

            echo $val;

            $this->clients_history_actions->histo(8, 'favoris', $_POST['id_client'], serialize(array('id_client' => $_POST['id_client'], 'post' => $_POST, 'action' => $val)));
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

    // Affichage du tableau d'offres en cours mobile
    public function _displayAll_mobile()
    {
        $this->autoFireView = true;

        $this->bids                   = $this->loadData('bids');
        $this->projects               = $this->loadData('projects');
        $this->lenders_accounts       = $this->loadData('lenders_accounts');
        $oAutoBidSettingsManager      = $this->get('unilend.service.autobid_settings_manager');

        $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');
        $this->bIsAllowedToSeeAutobid = $oAutoBidSettingsManager->isQualified($this->lenders_accounts);


        $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

        $this->projects->get($this->bdd->escape_string($_POST['id']), 'id_project');

        $order = isset($_POST['tri']) ? $_POST['tri'] : 'ordre';

        if (isset($_POST['direction'])) {
            if ($_POST['direction'] == 1) {
                $direction        = 'ASC';
                $this->direction  = 2;
            } else {
                $direction        = 'DESC';
                $this->direction  = 1;
            }
        }

        if ($order == 'rate') {
            $order        = 'rate ' . $direction . ', ordre ' . $direction;
        } elseif ($order == 'amount') {
            $order        = 'amount ' . $direction . ', rate ' . $direction . ', ordre ' . $direction;
        } elseif ($order == 'status') {
            $order        = 'status ' . $direction . ', rate ' . $direction . ', ordre ' . $direction;
        } else {
            $order        = 'ordre ' . $direction;
        }

        /** @var \projects_status $oProjectStatus */
        $oProjectStatus = $this->loadData('projects_status');
        $oProjectStatus->getLastStatut($this->projects->id_project);

        $this->aBids   = $this->bids->select('id_project = ' . $this->projects->id_project, $order);
        $this->avgRate = $this->projects->getAverageInterestRate($this->projects->id_project, $oProjectStatus->status);
        $this->status  = array($this->lng['preteur-projets']['enchere-en-cours'], $this->lng['preteur-projets']['enchere-ok'], $this->lng['preteur-projets']['enchere-ko']);
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

    public function _mdp_lost()
    {
        $this->autoFireView = false;
        $clients            = $this->loadData('clients');

        if (isset($_POST['email']) && $this->ficelle->isEmail($_POST['email']) && $clients->get($_POST['email'], 'email')) {
            /** @var \settings $oSettings */
            $oSettings = $this->loadData('settings');
            $oSettings->get('Facebook', 'type');
            $lien_fb = $oSettings->value;
            $oSettings->get('Twitter', 'type');
            $lien_tw = $oSettings->value;

            $varMail = array(
                'surl'          => $this->surl,
                'url'           => $this->lurl,
                'prenom'        => $clients->prenom,
                'login'         => $clients->email,
                'link_password' => $this->lurl . '/' . $this->tree->getSlug(119, $this->language) . '/' . $clients->hash,
                'lien_fb'       => $lien_fb,
                'lien_tw'       => $lien_tw
            );

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('mot-de-passe-oublie', $varMail);
            $message->setTo($clients->email);
            $mailer = $this->get('mailer');
            $mailer->send($message);

            echo 'ok';
        } else {
            echo 'nok';
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
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');

        if (isset($_POST['mdp'], $_POST['montant']) && $this->clients->checkAccess()) {
            $this->clients->get($_SESSION['client']['id_client'], 'id_client');
            $this->clients_history_actions->histo(3, 'retrait argent', $this->clients->id_client, serialize(array('id_client' => $this->clients->id_client, 'montant' => $_POST['montant'], 'mdp' => md5($_POST['mdp']))));
            $this->clients_status->getLastStatut($this->clients->id_client);

            if ($this->clients_status->status < \clients_status::VALIDATED) {
                echo 'nok';
                die;
            }

            $verif   = 'ok';
            $montant = str_replace(',', '.', $_POST['montant']);

            if (md5($_POST['mdp']) !== $this->clients->password && false === password_verify($_POST['mdp'], $this->clients->password)) {
                $verif = 'noMdp';
            } else {
                $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

                if (false === is_numeric($montant)) {
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
                $this->motif = $this->clients->getLenderPattern($this->clients->id_client);

                $this->transactions->id_client        = $this->clients->id_client;
                $this->transactions->montant          = - $montant * 100;
                $this->transactions->id_langue        = 'fr';
                $this->transactions->date_transaction = date('Y-m-d H:i:s');
                $this->transactions->status           = 1;
                $this->transactions->etat             = 1;
                $this->transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                $this->transactions->type_transaction = \transactions_types::TYPE_LENDER_WITHDRAWAL;
                $this->transactions->create();

                $this->wallets_lines->id_lender                = $this->lenders_accounts->id_lender_account;
                $this->wallets_lines->type_financial_operation = 30; // Inscription preteur
                $this->wallets_lines->id_transaction           = $this->transactions->id_transaction;
                $this->wallets_lines->status                   = 1;
                $this->wallets_lines->type                     = 1;
                $this->wallets_lines->amount                   = - $montant * 100;
                $this->wallets_lines->create();

                // Transaction physique donc on enregistre aussi dans la bank lines
                $this->bank_lines->id_wallet_line    = $this->wallets_lines->id_wallet_line;
                $this->bank_lines->id_lender_account = $this->lenders_accounts->id_lender_account;
                $this->bank_lines->status            = 1;
                $this->bank_lines->amount            = '-' . ($montant * 100);
                $this->bank_lines->create();

                $this->virements->id_client      = $this->clients->id_client;
                $this->virements->id_transaction = $this->transactions->id_transaction;
                $this->virements->montant        = $montant * 100;
                $this->virements->motif          = $this->motif;
                $this->virements->type           = 1; // preteur
                $this->virements->status         = 0;
                $this->virements->create();

                $this->notifications->type      = \notifications::TYPE_DEBIT;
                $this->notifications->id_lender = $this->lenders_accounts->id_lender_account;
                $this->notifications->amount    = $montant * 100;
                $this->notifications->create();

                $this->clients_gestion_mails_notif->id_client       = $this->clients->id_client;
                $this->clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_DEBIT;
                $this->clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
                $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                $this->clients_gestion_mails_notif->id_transaction  = $this->transactions->id_transaction;
                $this->clients_gestion_mails_notif->create();

                /** @var \settings $oSettings */
                $oSettings = $this->loadData('settings');

                if ($this->clients_gestion_notifications->getNotif($this->clients->id_client, 8, 'immediatement') == true) {
                    $this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                    $this->clients_gestion_mails_notif->immediatement = 1;
                    $this->clients_gestion_mails_notif->update();

                    $oSettings->get('Facebook', 'type');
                    $lien_fb = $oSettings->value;
                    $oSettings->get('Twitter', 'type');
                    $lien_tw = $oSettings->value;

                    $varMail = array(
                        'surl'            => $this->surl,
                        'url'             => $this->lurl,
                        'prenom_p'        => $this->clients->prenom,
                        'fonds_retrait'   => $this->ficelle->formatNumber($montant),
                        'solde_p'         => $this->ficelle->formatNumber($this->solde - $montant),
                        'link_mandat'     => $this->surl . '/images/default/mandat.jpg',
                        'motif_virement'  => $this->clients->getLenderPattern($this->clients->id_client),
                        'projets'         => $this->lurl . '/' . $this->tree->getSlug(4, $this->language),
                        'gestion_alertes' => $this->lurl . '/profile',
                        'lien_fb'         => $lien_fb,
                        'lien_tw'         => $lien_tw
                    );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-retrait', $varMail);
                    $message->setTo($this->clients->email);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                }

                //******************************************//
                //*** ENVOI DU MAIL NOTIFICATION RETRAIT ***//
                //******************************************//
                $oSettings->get('Adresse notification controle fond', 'type');
                $destinataire = $oSettings->value;

                $transac = $this->loadData('transactions');
                $loans   = $this->loadData('loans');

                // on recup la somme versé a l'inscription si y en a 1
                $transac->get($this->clients->id_client, 'type_transaction = ' . \transactions_types::TYPE_LENDER_SUBSCRIPTION . ' AND status = 1 AND etat = 1 AND id_client');

                $varMail = array(
                    '$surl'                          => $this->surl,
                    '$url'                           => $this->lurl,
                    '$idPreteur'                     => $this->clients->id_client,
                    '$nom'                           => $this->clients->nom,
                    '$prenom'                        => $this->clients->prenom,
                    '$email'                         => $this->clients->email,
                    '$dateinscription'               => date('d/m/Y', strtotime($this->clients->added)),
                    '$montantInscription'            => (false === is_null($transac->montant)) ? $this->ficelle->formatNumber($transac->montant / 100) : $this->ficelle->formatNumber(0),
                    '$montantPreteDepuisInscription' => $this->ficelle->formatNumber($loans->sumPrets($this->lenders_accounts->id_lender_account)),
                    '$montantRetirePlateforme'       => $this->ficelle->formatNumber($montant),
                    '$solde'                         => $this->ficelle->formatNumber($transac->getSolde($this->clients->id_client))
                );
                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-retrait-de-fonds', $varMail, false);
                $message->setTo($destinataire);
                $mailer = $this->get('mailer');
                $mailer->send($message);
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

        if ($this->clients->checkAccess()) {
            echo $this->ficelle->formatNumber($this->transactions->getSolde($_SESSION['client']['id_client']));
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

        if (isset($_POST['name'], $_POST['prenom'], $_POST['email'])) {
            $this->lng['contact'] = $this->ln->selectFront('contact', $this->language, $this->App);

            if (
                empty($_POST['name'])
                || empty($_POST['prenom'])
                || empty($_POST['email'])
                || empty($_POST['security'])
                || $_POST['name'] == $this->lng['contact']['nom']
                || $_POST['prenom'] == $this->lng['contact']['prenom']
                || $_POST['email'] == $this->lng['contact']['email']
                || $_POST['security'] == $this->lng['contact']['captcha']
                || false === $this->ficelle->isEmail($_POST['email'])
                || $_SESSION['securecode'] != strtolower($_POST['security'])
            ) {
                echo 'nok';
            } else {
                $this->demande_contact->demande   = 2;
                $this->demande_contact->nom       = $this->ficelle->majNom($_POST['name']);
                $this->demande_contact->prenom    = $this->ficelle->majNom($_POST['prenom']);
                $this->demande_contact->email     = $_POST['email'];
                $this->demande_contact->telephone = ($this->lng['contact']['telephone'] != $_POST['phone'] ? $_POST['phone'] : '');
                $this->demande_contact->societe   = ($this->lng['contact']['societe'] != $_POST['societe'] ? $_POST['societe'] : '');
                $this->demande_contact->message   = ($this->lng['contact']['message'] != $_POST['message'] ? $_POST['message'] : '');
                $this->demande_contact->create();

                /** @var \settings $oSettings */
                $oSettings = $this->loadData('settings');

                $oSettings->get('Facebook', 'type');
                $lien_fb = $this->settings->value;

                $oSettings->get('Twitter', 'type');
                $lien_tw = $this->settings->value;

                $varMail = array(
                    'surl'     => $this->surl,
                    'url'      => $this->lurl,
                    'prenom_c' => $this->demande_contact->prenom,
                    'projets'  => $this->lurl . '/' . $this->tree->getSlug(4, $this->language),
                    'lien_fb'  => $lien_fb,
                    'lien_tw'  => $lien_tw
                );

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('demande-de-contact', $varMail);
                $message->setTo($this->demande_contact->email);
                $mailer = $this->get('mailer');
                $mailer->send($message);

                $infos = '<ul>';
                $infos .= '<li>Type demande : Demande preteur</li>';
                $infos .= '<li>Nom : ' . $this->demande_contact->nom . '</li>';
                $infos .= '<li>Prenom : ' . $this->demande_contact->prenom . '</li>';
                $infos .= '<li>Email : ' . $this->demande_contact->email . '</li>';
                $infos .= '<li>telephone : ' . $this->demande_contact->telephone . '</li>';
                $infos .= '<li>Societe : ' . $this->demande_contact->societe . '</li>';
                $infos .= '<li>Message : ' . $this->demande_contact->message . '</li>';
                $infos .= '</ul>';

                $oSettings->get('Adresse preteur', 'type');
                $destinataire = $oSettings->value;

                $varInternalMail = array(
                    '$infos'  => $infos
                );

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-demande-de-contact', $varInternalMail, false);
                $message->setTo($destinataire);
                $mailer = $this->get('mailer');
                $mailer->send($message);

                echo '<iframe width="133" src="' . $this->surl . '/images/default/securitecode.php"></iframe>';
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
            /** @var tax $tax */
            $tax = $this->loadData('tax');
            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

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
                    $tabSumParMois[$annee]                 = $this->echeanciers->getMonthlyScheduleByYear(array('id_lender' => $this->lenders_accounts->id_lender_account), $annee); // captial remboursé / mois
                    $tabSumRevenuesfiscalesParMois[$annee] = $tax->getTaxByMounth($this->lenders_accounts->id_lender_account, $annee); // revenues fiscales / mois

                    for ($i = 1; $i <= 12; $i++) {
                        $a                                            = $i;
                        $a                                            = ($i < 10 ? '0' . $a : $a);
                        $this->sumRembParMois[$annee][$i]             = number_format((false === empty($tabSumRembParMois[$annee][$a]) ? $tabSumRembParMois[$annee][$a] : 0), 2, '.', ''); // capital remboursé / mois
                        $this->sumIntbParMois[$annee][$i]             = number_format((false === empty($tabSumIntbParMois[$annee][$a]) ? $tabSumIntbParMois[$annee][$a] - $tabSumRevenuesfiscalesParMois[$annee][$a] : 0), 2, '.', ''); // interets net / mois
                        $this->sumRevenuesfiscalesParMois[$annee][$i] = number_format((false === empty($tabSumRevenuesfiscalesParMois[$annee][$a]) ? $tabSumRevenuesfiscalesParMois[$annee][$a] : 0), 2, '.', ''); // prelevements fiscaux

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
                    $tabSumParMois[$annee]                 = $this->echeanciers->getMonthlyScheduleByYear(array('id_lender' => $this->lenders_accounts->id_lender_account), $annee); // captial remboursé / mois
                    $tabSumRevenuesfiscalesParMois[$annee] = $tax->getTaxByMounth($this->lenders_accounts->id_lender_account, $annee); // revenues fiscales / mois

                    // on fait le tour sur l'année
                    for ($i = 1; $i <= 12; $i++) {
                        $a                                            = $i;
                        $a                                            = ($i < 10 ? '0' . $a : $a);
                        $this->sumRembParMois[$annee][$i]             = number_format((false === empty($tabSumRembParMois[$annee][$a]) ? $tabSumRembParMois[$annee][$a] : 0), 2, '.', ''); // capital remboursé / mois
                        $this->sumIntParMois[$annee][$i]              = number_format((false === empty($tabSumIntbParMois[$annee][$a]) ? $tabSumIntbParMois[$annee][$a] - $tabSumRevenuesfiscalesParMois[$annee][$a] : 0), 2, '.', ''); // interets net / mois
                        $this->sumRevenuesfiscalesParMois[$annee][$i] = number_format((false === empty($tabSumRevenuesfiscalesParMois[$annee][$a]) ? $tabSumRevenuesfiscalesParMois[$annee][$a] : 0), 2, '.', ''); // prelevements fiscaux
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
            } else { // annee
                $this->debut = $this->anneeCreationCompte;
                $this->fin   = date('Y');

                $i       = 1;
                $a       = 0;
                $arraynb = array();
                for ($c = $this->debut; $c <= $this->fin; $c++) {
                    if ($a >= 3) {
                        $a = 0;
                        $i++;
                    }
                    $this->tab[$c] = $i;
                    $arraynb[$i] = isset($arraynb[$i]) ? $arraynb[$i] + 1 : 1;

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

                $this->sumRembParAn   = $this->echeanciers->getRepaidCapitalInDateRange($this->lenders_accounts->id_lender_account, $this->debut . '-01-01 00:00:00', $this->fin . '-12-31 23:59:59');
                $this->sumIntParAn    = $this->echeanciers->getRepaidInterestsInDateRange($this->lenders_accounts->id_lender_account, $this->debut . '-01-01 00:00:00', $this->fin . '-12-31 23:59:59');
                $this->sumFiscalParAn = $tax->getTaxByYear($this->lenders_accounts->id_lender_account, $this->debut, $this->fin);
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
        $this->loadData('transactions_types'); // Loaded for class constants

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
        // tri debut/fin
        if (isset($_POST['id_last_action']) && in_array($_POST['id_last_action'], array('debut', 'fin'))) {
            $debutTemp       = explode('/', $_POST['debut']);
            $finTemp         = explode('/', $_POST['fin']);
            $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');
            $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');

            $_SESSION['id_last_action'] = $_POST['id_last_action'];
        } elseif (isset($_POST['id_last_action']) && $_POST['id_last_action'] == 'nbMois') {
            $nbMois          = $_POST['nbMois'];
            $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, date("d"), date('Y'));
            $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));

            $_SESSION['id_last_action'] = $_POST['id_last_action'];
        } elseif (isset($_POST['id_last_action']) && $_POST['id_last_action'] == 'annee') {
            $year            = $_POST['annee'];
            $date_debut_time = mktime(0, 0, 0, 1, 1, $year);

            if (date('Y') == $year) {
                $date_fin_time = mktime(0, 0, 0, date('m'), date('d'), $year);
            } else {
                $date_fin_time = mktime(0, 0, 0, 12, 31, $year);
            }

            $_SESSION['id_last_action'] = $_POST['id_last_action'];
        } elseif (isset($_SESSION['id_last_action'])) {
            if (in_array($_SESSION['id_last_action'], array('debut', 'fin'))) {
                $debutTemp       = explode('/', $_POST['debut']);
                $finTemp         = explode('/', $_POST['fin']);
                $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');
                $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');
            } elseif ($_SESSION['id_last_action'] == 'nbMois') {
                $nbMois          = $_POST['nbMois'];
                $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, date("d"), date('Y'));
                $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));
            } elseif ($_SESSION['id_last_action'] == 'annee') {
                $year            = $_POST['annee'];
                $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date debut
                $date_fin_time   = mktime(0, 0, 0, 12, 31, $year); // date fin
            }
        } else {
            $date_debut_time = mktime(0, 0, 0, date("m") - 1, date("d"), date('Y'));
            $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));
        }

        $this->date_debut         = date('Y-m-d', $date_debut_time);
        $this->date_fin           = date('Y-m-d', $date_fin_time);
        $this->date_debut_display = date('d/m/Y', $date_debut_time);
        $this->date_fin_display   = date('d/m/Y', $date_fin_time);

        $array_type_transactions_liste_deroulante = array(
            1 => array(
                \transactions_types::TYPE_LENDER_SUBSCRIPTION,
                \transactions_types::TYPE_LENDER_LOAN,
                \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
                \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
                5,
                \transactions_types::TYPE_DIRECT_DEBIT,
                \transactions_types::TYPE_LENDER_WITHDRAWAL,
                \transactions_types::TYPE_WELCOME_OFFER,
                \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION,
                \transactions_types::TYPE_SPONSORSHIP_SPONSORED_REWARD,
                \transactions_types::TYPE_SPONSORSHIP_SPONSOR_REWARD,
                \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT,
                \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT
            ),
            2 => array(
                \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
                \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
                \transactions_types::TYPE_DIRECT_DEBIT,
                \transactions_types::TYPE_LENDER_WITHDRAWAL
            ),
            3 => array(
                \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
                \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
                \transactions_types::TYPE_DIRECT_DEBIT
            ),
            4 => array(\transactions_types::TYPE_LENDER_WITHDRAWAL),
            5 => array(\transactions_types::TYPE_LENDER_LOAN),
            6 => array(
                5,
                \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT,
                \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT
            )
        );

        if (isset($_POST['tri_type_transac'])) {
            $tri_type_transac = $array_type_transactions_liste_deroulante[$_POST['tri_type_transac']];
        } else {
            $tri_type_transac = $array_type_transactions_liste_deroulante[1];
        }

        if (isset($_POST['tri_projects'])) {
            if (in_array($_POST['tri_projects'], array(0, 1))) {
                $tri_project = '';
            } else {
                $tri_project = ' AND id_projet = ' . $_POST['tri_projects'];
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
        $this->lTrans                  = $this->indexage_vos_operations->select('type_transaction IN (' . implode(', ', $tri_type_transac) . ') AND id_client = ' . $this->clients->id_client . ' AND LEFT(date_operation,10) >= "' . $this->date_debut . '" AND LEFT(date_operation,10) <= "' . $this->date_fin . '"' . $tri_project, $order);
        $this->lProjectsLoans          = $this->indexage_vos_operations->get_liste_libelle_projet('type_transaction IN (' . implode(', ', $tri_type_transac) . ') AND id_client = ' . $this->clients->id_client . ' AND LEFT(date_operation,10) >= "' . $this->date_debut . '" AND LEFT(date_operation,10) <= "' . $this->date_fin . '"');
    }

    public function _acceptCookies()
    {
        $this->autoFireView = false;

        $accept_cookies = $this->loadData('accept_cookies');
        $accept_cookies->ip                = $_SERVER['REMOTE_ADDR'];
        $accept_cookies->id_client         = $this->clients->id_client;
        $accept_cookies->id_accept_cookies = $accept_cookies->create();

        setcookie('acceptCookies', $accept_cookies->id_accept_cookies, time() + 365 * 24 * 3600, '/');

        echo json_encode(array('reponse' => true));
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

        if (isset($_POST['id_last_action']) && in_array($_POST['id_last_action'], array('debut', 'fin', 'nbMois', 'annee'))) {
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
        } elseif (isset($_SESSION['id_last_action'])) {
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

        if ($_POST['tri_projects'] == 0 || $_POST['tri_projects'] == 99) {
            $aClientsProjects = $oProjects->select('id_company = ' . $oCompanies->id_company);
        } else {
            $aClientsProjects = $oProjects->select('id_project =' . $_POST['tri_projects']);
        }

        foreach ($aClientsProjects as $project) {
            $aClientProjectIDs[] = $project['id_project'];
        }

        $iTransaction = ($_POST['tri_type_transac'] == 99 ) ? null :  $_POST['tri_type_transac'];

        $_SESSION['operations-filter'] = array('projects' =>$aClientProjectIDs, 'start' => $oStartTime, 'end'=>$oEndTime, 'transaction'=>$iTransaction);

        $this->aBorrowerOperations   = $oClients->getDataForBorrowerOperations($aClientProjectIDs, $oStartTime, $oEndTime, $iTransaction, $oClients->id_client);
        $this->sDisplayDateTimeStart = $oStartTime->format('d/m/Y');
        $this->sDisplayDateTimeEnd   = $oEndTime->format('d/m/Y');
    }

    public function _rejectedBids()
    {
        $this->hideDecoration();
        $this->autoFireView = true;

        $this->oProject = $this->loadData('projects');
        $oClient        = $this->loadData('clients');
        $oLenderAccount = $this->loadData('lenders_accounts');
        $oBids          = $this->loadData('bids');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager $oAutoBidSettingsManager */
        $oAutoBidSettingsManager             = $this->get('unilend.service.autobid_settings_manager');
        $this->bIsAllowedToSeeAutobid        = $oAutoBidSettingsManager->isQualified($this->lenders_accounts);

        if (isset($this->params[0]) && isset($this->params[1]) && $this->oProject->get($this->params[0]) && $oClient->get($this->params[1], 'hash')) {
            $oLenderAccount->get($this->clients->id_client, 'id_client_owner');
            $this->lng['preteur-synthese'] = $this->ln->selectFront('preteur-synthese', $this->language, $this->App);
            $this->aRejectedBids           = $oBids->select('id_project = ' . $this->oProject->id_project . ' AND id_lender_account = ' . $oLenderAccount->id_lender_account . ' AND status IN (' . implode(',', array(\bids::STATUS_BID_REJECTED)) . ')', 'id_bid DESC');
        }
    }

    public function _displayDetail()
    {
        $this->hideDecoration();

        if (isset($this->params[0], $this->params[1]) && is_numeric($this->params[0]) && is_numeric($this->params[1])) {
            $projectId = $this->params[0];
            $rate      = $this->params[1];
            $bid       = $this->loadData('bids');
            $sortBy    = 'ordre';
            if (isset($_GET['sort'])) {
                switch ($_GET['sort']) {
                    case 'rate':
                    case 'detail-rate':
                        $sortBy = 'rate';
                        break;
                    case 'amount':
                    case 'detail-amount':
                        $sortBy = 'amount';
                        break;
                    case 'status':
                    case 'detail-status':
                        $sortBy = 'status';
                        break;
                    default:
                        $sortBy = 'ordre';
                }
            }
            $order = 'ASC';
            if (isset($_GET['direction']) && $_GET['direction'] == 2) {
                $order = 'DESC';
            }

            $oCachePool  = $this->get('memcache.default');
            $oCachedItem = $oCachePool->getItem(\bids::CACHE_KEY_PROJECT_BIDS . $projectId . $rate . $sortBy . $order);
            if (true === $oCachedItem->isHit()) {
                $this->bidsList = $oCachedItem->get();
            } else {
                $this->bidsList = $bid->select('id_project = ' . $projectId . ' AND rate like ' . $rate, $sortBy . ' ' . $order);
                $oCachedItem->set($this->bidsList)->expiresAfter(300);
                $oCachePool->save($oCachedItem);
            }

            $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);
            $this->status                 = array($this->lng['preteur-projets']['enchere-en-cours'], $this->lng['preteur-projets']['enchere-ok'], $this->lng['preteur-projets']['enchere-ko']);

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager $oAutoBidSettingsManager */
            $oAutoBidSettingsManager      = $this->get('unilend.service.autobid_settings_manager');
            $this->bIsAllowedToSeeAutobid = $oAutoBidSettingsManager->isQualified($this->lenders_accounts);
        }
    }
}
