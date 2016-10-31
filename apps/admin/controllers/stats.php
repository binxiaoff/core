<?php

class statsController extends bootstrap
{
    public function initialize()
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 1200);

        parent::initialize();

        $this->catchAll = true;

        $this->users->checkAccess('stats');

        $this->menu_admin = 'stats';
    }

    public function _default()
    {
        // Chargement de la lib google
        $this->ga = $this->loadLib('gapi', array(
            $this->google_mail,
            $this->google_password,
            (isset($_SESSION['ga_auth_token']) ? $_SESSION['ga_auth_token'] : null)
        ));

        // Mise en session de la connexion GA
        $_SESSION['ga_auth_token'] = $this->ga->getAuthToken();

        // Recuperation de l'ID
        $this->ga->requestAccountData();

        foreach ($this->ga->getResults() as $result) {
            $this->id_profile = $result->getProfileId();
        }

        // Traitement des dates du formulaire
        if (isset($_POST['next'])) {
            $this->mois  = intval($_POST['mois']);
            $this->annee = intval($_POST['annee']);

            $this->mois++;

            if ($this->mois > 12) {
                $this->mois = 1;
                $this->annee++;
            }

            $this->deb_jour  = 1;
            $this->deb_mois  = $this->fin_mois = $this->mois;
            $this->deb_annee = $this->fin_annee = $this->annee;
            $this->fin_jour  = $this->dates->nb_jour_dans_mois($this->mois, $this->annee);

            if ($this->deb_jour < 10) {
                $this->deb_jour = '0' . $this->deb_jour;
            }
            if ($this->deb_mois < 10) {
                $this->deb_mois = '0' . $this->deb_mois;
            }
            if ($this->fin_jour < 10) {
                $this->fin_jour = '0' . $this->fin_jour;
            }
            if ($this->fin_mois < 10) {
                $this->fin_mois = '0' . $this->fin_mois;
            }
        } elseif (isset($_POST['prev'])) {
            $this->mois  = intval($_POST['mois']);
            $this->annee = intval($_POST['annee']);

            $this->mois--;

            if ($this->mois < 1) {
                $this->mois = 12;
                $this->annee--;
            }

            $this->deb_jour  = 1;
            $this->deb_mois  = $this->fin_mois = $this->mois;
            $this->deb_annee = $this->fin_annee = $this->annee;
            $this->fin_jour  = $this->dates->nb_jour_dans_mois($this->mois, $this->annee);

            if ($this->deb_jour < 10) {
                $this->deb_jour = '0' . $this->deb_jour;
            }
            if ($this->deb_mois < 10) {
                $this->deb_mois = '0' . $this->deb_mois;
            }
            if ($this->fin_jour < 10) {
                $this->fin_jour = '0' . $this->fin_jour;
            }
            if ($this->fin_mois < 10) {
                $this->fin_mois = '0' . $this->fin_mois;
            }
        } elseif (isset($_POST['voir'])) {
            $this->mois  = intval($_POST['mois']);
            $this->annee = intval($_POST['annee']);

            $this->deb_jour  = 1;
            $this->deb_mois  = $this->fin_mois = $this->mois;
            $this->deb_annee = $this->fin_annee = $this->annee;
            $this->fin_jour  = $this->dates->nb_jour_dans_mois($this->mois, $this->annee);

            if ($this->deb_jour < 10) {
                $this->deb_jour = '0' . $this->deb_jour;
            }
            if ($this->deb_mois < 10) {
                $this->deb_mois = '0' . $this->deb_mois;
            }
            if ($this->fin_jour < 10) {
                $this->fin_jour = '0' . $this->fin_jour;
            }
            if ($this->fin_mois < 10) {
                $this->fin_mois = '0' . $this->fin_mois;
            }
        } elseif (isset($_POST['intervalle'])) {
            $this->deb_jour  = $_POST['du-jour'];
            $this->deb_mois  = $_POST['du-mois'];
            $this->deb_annee = $_POST['du-annee'];
            $this->fin_jour  = $_POST['au-jour'];
            $this->fin_mois  = $_POST['au-mois'];
            $this->fin_annee = $_POST['au-annee'];

            $this->mois  = $this->deb_mois;
            $this->annee = $this->deb_annee;

            if ($this->deb_jour < 10) {
                $this->deb_jour = '0' . $this->deb_jour;
            }
            if ($this->deb_mois < 10) {
                $this->deb_mois = '0' . $this->deb_mois;
            }
            if ($this->fin_jour < 10) {
                $this->fin_jour = '0' . $this->fin_jour;
            }
            if ($this->fin_mois < 10) {
                $this->fin_mois = '0' . $this->fin_mois;
            }
        } else {
            // Attribution jour, mois, année par défaut
            $this->deb_jour  = 1;
            $this->deb_mois  = $this->fin_mois = date('m');
            $this->deb_annee = $this->fin_annee = date('Y');
            $this->fin_jour  = $this->dates->nb_jour_dans_mois(date('m'), date('Y'));

            $_POST['du-jour']  = $this->deb_jour;
            $_POST['du-mois']  = $this->deb_mois;
            $_POST['du-annee'] = $this->deb_annee;
            $_POST['au-jour']  = $this->fin_jour;
            $_POST['au-mois']  = $this->fin_mois;
            $_POST['au-annee'] = $this->fin_annee;

            $this->mois  = $this->deb_mois;
            $this->annee = $this->deb_annee;

            if ($this->deb_jour < 10) {
                $this->deb_jour = '0' . $this->deb_jour;
            }
            if ($this->fin_jour < 10) {
                $this->fin_jour = '0' . $this->fin_jour;
            }
        }

        // Recuperation du nombre de jours
        $this->nb_jours = $this->dates->intervalleJours($this->deb_jour, $this->deb_mois, $this->deb_annee, $this->fin_jour, $this->fin_mois, $this->fin_annee);

        if ($this->nb_jours == 0) {
            $this->deb_jour  = 1;
            $this->deb_mois  = $this->fin_mois = date('m');
            $this->deb_annee = $this->fin_annee = date('Y');
            $this->fin_jour  = $this->dates->nb_jour_dans_mois(date('m'), date('Y'));

            $_POST['du-jour']  = $this->deb_jour;
            $_POST['du-mois']  = $this->deb_mois;
            $_POST['du-annee'] = $this->deb_annee;
            $_POST['au-jour']  = $this->fin_jour;
            $_POST['au-mois']  = $this->fin_mois;
            $_POST['au-annee'] = $this->fin_annee;

            $this->mois  = $this->deb_mois;
            $this->annee = $this->deb_annee;

            if ($this->deb_jour < 10) {
                $this->deb_jour = '0' . $this->deb_jour;
            }
            if ($this->fin_jour < 10) {
                $this->fin_jour = '0' . $this->fin_jour;
            }

            $this->nb_jours = $this->dates->intervalleJours($this->deb_jour, $this->deb_mois, $this->deb_annee, $this->fin_jour, $this->fin_mois, $this->fin_annee);
        }

        // Recupearation d'un rapport GA
        $this->ga->requestReportData($this->id_profile, array('visitCount'), array(
            'pageviews',
            'visits',
            'newVisits'
        ), null, null, $this->deb_annee . '-' . $this->deb_mois . '-' . $this->deb_jour, $this->fin_annee . '-' . $this->fin_mois . '-' . $this->fin_jour);
    }


    // Ressort un csv avec les process des users
    public function _etape_inscription()
    {
        // Récup des dates
        if (isset($_POST['date1']) && $_POST['date1'] != '') {
            $d1    = explode('/', $_POST['date1']);
            $date1 = $d1[2] . '-' . $d1[1] . '-' . $d1[0];
        } else {
            $_POST['date1'] = date('d/m/Y', strtotime('first day of this month')); //"01/08/2014";
            $date1          = date('Y-m-d', strtotime('first day of this month')); //"2014-08-01";

        }

        if (isset($_POST['date2']) && $_POST['date2'] != '') {
            $d2    = explode('/', $_POST['date2']);
            $date2 = $d2[2] . '-' . $d2[1] . '-' . $d2[0];
        } else {
            $_POST['date2'] = date('d/m/Y', strtotime('last day of this month')); //"31/08/2014";
            $date2          = date('Y-m-d', strtotime('last day of this month')); //"2014-08-31";

        }

        // récup de tous les clients créés depuis le 1 aout
        $sql = 'SELECT
                        c.id_client,
                        c.nom,
                        c.prenom,
                        c.email,
                        c.telephone,
                        c.mobile,
                        c.added,
                            IF (
                                c.etape_inscription_preteur = 3,
                                IF (
                                    la.type_transfert = 1, "3. Virement","3. CB"),
                                    c.etape_inscription_preteur
                                    ) as etape_inscription_preteur2,
                        c.source,
                        c.source2
                            FROM clients c
                            LEFT JOIN lenders_accounts la ON (la.id_client_owner = c.id_client)
                            WHERE c.etape_inscription_preteur > 0 AND c.status = 1 AND c.added >= "' . $date1 . ' 00:00:00' . '" AND c.added <= "' . $date2 . ' 23:59:59";';

        $result = $this->bdd->query($sql);

        $this->L_clients = array();
        while ($record = $this->bdd->fetch_assoc($result)) {
            $this->L_clients[] = $record;
        }

        if (isset($_POST['recup'])) {
            $this->autoFireView = false;
            $this->hideDecoration();

            header("Content-type: application/vnd.ms-excel");
            header("Content-disposition: attachment; filename=\"Export_etape_inscription.csv\"");

            // Récup des dates
            if ($_POST['spy_date1'] != '') {
                $d1    = explode('/', $_POST['spy_date1']);
                $date1 = $d1[2] . '-' . $d1[1] . '-' . $d1[0];
            } else {
                $date1 = date('Y-m-d', strtotime('first day of this month')); //"2014-08-01";
            }

            if ($_POST['spy_date2'] != '') {
                $d2    = explode('/', $_POST['spy_date2']);
                $date2 = $d2[2] . '-' . $d2[1] . '-' . $d2[0];
            } else {
                $date2 = date('Y-m-d', strtotime('last day of this month')); //"2014-08-31";
            }

            $sql = 'SELECT
                        c.id_client,
                        c.nom,
                        c.prenom,
                        c.email,
                        c.telephone,
                        c.mobile,
                        c.added,
                            IF (
                                c.etape_inscription_preteur = 3,
                                IF (
                                    la.type_transfert = 1, "3. Virement","3. CB"),
                                    c.etape_inscription_preteur
                                    ) as etape_inscription_preteur2,
                        c.source,
                        c.source2
                            FROM clients c
                            LEFT JOIN lenders_accounts la ON (la.id_client_owner = c.id_client)
                            WHERE c.etape_inscription_preteur > 0 AND c.status = 1 AND c.added >= "' . $date1 . ' 00:00:00' . '" AND c.added <= "' . $date2 . ' 23:59:59";';

            $result = $this->bdd->query($sql);

            $this->L_clients = array();
            while ($record = $this->bdd->fetch_assoc($result)) {
                $this->L_clients[] = $record;
            }

            $csv = "id_client;nom;prenom;email;tel;date_inscription;etape_inscription;Source;Source 2;\n";
            // construction de chaque ligne
            foreach ($this->L_clients as $u) {
                // on concatene a $csv
                $csv .= utf8_decode($u['id_client']) . ';' . utf8_decode($u['nom']) . ';' . utf8_decode($u['prenom']) . ';' . utf8_decode($u['email']) . ';' . utf8_decode($u['telephone'] . ' ' . $u['mobile']) . ';' . utf8_decode($this->dates->formatDate($u['added'], 'd/m/Y')) . ';' . utf8_decode($u['etape_inscription_preteur2']) . ';' . $u['source'] . ';' . $u['source2'] . ';' . "\n";
            }

            print($csv);
        }
    }

    public function _requete_donnees_financieres()
    {
        $this->lEmpr = $this->bdd->query("
            SELECT p.id_project as id_project, c.name,
            (SELECT cli.source FROM clients cli WHERE cli.id_client = c.id_client_owner) as source,
            title, p.added,
            (SELECT label FROM projects_status ps WHERE ps.id_project_status = (SELECT id_project_status FROM projects_status_history psh WHERE psh.id_project = p.id_project ORDER BY id_project_status_history DESC LIMIT 1)) AS status,
            IFNULL(cr.value, 0) AS score_altares,
            c.risk, p.amount,p.period,
            (SELECT ca FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as ca2011,
            (SELECT ca FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as ca2012,
            (SELECT ca FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as ca2013,

            (SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as rbe2011,
            (SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as rbe2012,
            (SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as rbe2013,

            (SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as rex2011,
            (SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as rex2012,
            (SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as rex2013,

            (SELECT investissements FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as invest2011,
            (SELECT investissements FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as invest2012,
            (SELECT investissements FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as invest2013,

            (SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immocorp2011,
            (SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immocorp2012,
            (SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immocorp2013,

            (SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immoincorp2011,
            (SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immoincorp2012,
            (SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immoincorp2013,

            (SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immofin2011,
            (SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immofin2012,
            (SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immofin2013,

            (SELECT stocks FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as stock2011,
            (SELECT stocks FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as stock2012,
            (SELECT stocks FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as stock2013,

            (SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as creances2011,
            (SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as creances2012,
            (SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as creances2013,

            (SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dispo2011,
            (SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dispo2012,
            (SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dispo2013,

            (SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as valeursmob2011,
            (SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as valeursmob2012,
            (SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as valeursmob2013,

            (SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as cp2011,
            (SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as cp2012,
            (SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as cp2013,

            (SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as provisions2011,
            (SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as provisions2012,
            (SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as provisions2013,

            (SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as ammort2011,
            (SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as ammort2012,
            (SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as ammort2013,

            (SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dettesfin2011,
            (SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dettesfin2012,
            (SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dettesfin2013,

            (SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dettesfour2011,
            (SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dettesfour2012,
            (SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dettesfour2013,

            (SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as autresdettes2011,
            (SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as autresdettes2012,
            (SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as autresdettes2013,
            c.forme,c.date_creation
            FROM projects p
            LEFT JOIN company_rating cr ON p.id_company_rating_history > 0 AND cr.id_company_rating_history = p.id_company_rating_history AND cr.type = 'score_altares'
            JOIN companies c ON c.id_company = p.id_company
            WHERE id_project IN (SELECT id_project FROM projects_status_history psh)");
    }

    public function _requete_donnees_financieres_csv()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        $this->lEmpr = $this->bdd->query("
            SELECT p.id_project as id_project, c.name,
            (SELECT cli.source FROM clients cli WHERE cli.id_client = c.id_client_owner) as source,
            title, p.added,
            (SELECT label FROM projects_status ps WHERE ps.id_project_status = (SELECT id_project_status FROM projects_status_history psh WHERE psh.id_project = p.id_project ORDER BY id_project_status_history DESC LIMIT 1)) AS status,
            IFNULL(cr.value, 0) AS score_altares,
            c.risk, p.amount,p.period,
            (SELECT ca FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as ca2011,
            (SELECT ca FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as ca2012,
            (SELECT ca FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as ca2013,

            (SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as rbe2011,
            (SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as rbe2012,
            (SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as rbe2013,

            (SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as rex2011,
            (SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as rex2012,
            (SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as rex2013,

            (SELECT investissements FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as invest2011,
            (SELECT investissements FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as invest2012,
            (SELECT investissements FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as invest2013,

            (SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immocorp2011,
            (SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immocorp2012,
            (SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immocorp2013,

            (SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immoincorp2011,
            (SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immoincorp2012,
            (SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immoincorp2013,

            (SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immofin2011,
            (SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immofin2012,
            (SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immofin2013,

            (SELECT stocks FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as stock2011,
            (SELECT stocks FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as stock2012,
            (SELECT stocks FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as stock2013,

            (SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as creances2011,
            (SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as creances2012,
            (SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as creances2013,

            (SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dispo2011,
            (SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dispo2012,
            (SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dispo2013,

            (SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as valeursmob2011,
            (SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as valeursmob2012,
            (SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as valeursmob2013,

            (SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as cp2011,
            (SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as cp2012,
            (SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as cp2013,

            (SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as provisions2011,
            (SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as provisions2012,
            (SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as provisions2013,

            (SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as ammort2011,
            (SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as ammort2012,
            (SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as ammort2013,

            (SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dettesfin2011,
            (SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dettesfin2012,
            (SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dettesfin2013,

            (SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dettesfour2011,
            (SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dettesfour2012,
            (SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dettesfour2013,

            (SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as autresdettes2011,
            (SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as autresdettes2012,
            (SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as autresdettes2013,
            c.forme,c.date_creation

            FROM projects p
            LEFT JOIN company_rating cr ON p.id_company_rating_history > 0 AND cr.id_company_rating_history = p.id_company_rating_history AND cr.type = 'score_altares'
            JOIN companies c ON c.id_company = p.id_company
            WHERE id_project IN (SELECT id_project FROM projects_status_history psh)");

        $csv = "";
        $i   = 1;
        while ($e = $this->bdd->fetch_array($this->lEmpr)) {
            if ($i == 1) {
                foreach ($e as $key => $field) {
                    if ( ! is_numeric($key)) {
                        $csv .= $key . "; ";
                    }
                }
                $csv .= " \n";
            }

            foreach ($e as $key => $field) {
                if ( ! is_numeric($key)) {
                    $csv .= $field . "; ";
                }
            }
            $csv .= " \n";
            $i++;
        }

        $titre = 'requete_dossiers' . date('Ymd');
        header("Content-type: application/vnd.ms-excel");
        header("Content-disposition: attachment; filename=\"" . $titre . ".csv\"");

        print(utf8_decode($csv));
    }

    public function _requete_beneficiaires()
    {
        $this->companies        = $this->loadData('companies');
        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->insee            = $this->loadData('insee');
        $this->villes           = $this->loadData('villes');
        $this->pays             = $this->loadData('pays_v2');
        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->loans            = $this->loadData('loans');
        $this->insee_pays       = $this->loadData('insee_pays');

        /** @var \tax_type $taxTypes */
        $taxTypes = $this->loadData('tax_type');
        $taxTypes->get(\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE);
        $this->retenuesource = $taxTypes->rate;
        $this->lPre          = $this->clients->selectPreteursByStatus('20, 30, 40, 50, 60', '(
                SELECT COUNT(*) 
                FROM transactions 
                WHERE status = 1 
                    AND etat = 1 
                    AND type_transaction IN (' . implode(', ', [
                \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL,
                \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS,
                \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT,
                \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT
            ]) . ') 
                    AND id_client = c.id_client
                    AND added BETWEEN "' . date('Y') . '-01-01" AND "' . (date('Y') + 1) . '-01-01" 
            ) >= 1');
    }

    public function _requete_beneficiaires_csv()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        $this->companies        = $this->loadData('companies');
        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->insee            = $this->loadData('insee');
        $this->villes           = $this->loadData('villes');
        $this->pays             = $this->loadData('pays_v2');
        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->loans            = $this->loadData('loans');
        $this->insee_pays       = $this->loadData('insee_pays');
        /** @var \tax_type $taxTypes */
        $taxTypes = $this->loadData('tax_type');
        $taxTypes->get(\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE);
        $this->retenuesource = $taxTypes->rate;
        $this->lPre          = $this->clients->selectPreteursByStatus('20, 30, 40, 50, 60', '(
                SELECT COUNT(*) 
                FROM transactions 
                WHERE status = 1 
                    AND etat = 1 
                    AND type_transaction IN (' . implode(', ', [
                \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL,
                \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS,
                \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT,
                \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT
            ]) . ') 
                    AND id_client = c.id_client
                    AND added BETWEEN "' . date('Y') . '-01-01" AND "' . (date('Y') + 1) . '-01-01" 
            ) >= 1');

        $aData = array();
        foreach ($this->lPre as $e) {
            $this->clients_adresses->get($e['id_client'], 'id_client');
            $this->lenders_accounts->get($e['id_client'], 'id_client_owner');
            $entreprise = false;
            if ($this->companies->get($e['id_client'], 'id_client_owner') && in_array($e['type'], array(2, 4))) {
                $entreprise = true;
                if ($this->companies->id_pays == 0) {
                    $this->companies->id_pays = 1;
                }
                $this->pays->get($this->companies->id_pays, 'id_pays');
                $isoFiscal = $this->pays->iso;

                $ville_paysFiscal = $this->companies->city;

                $cp = substr($this->companies->zip, 0, 2);
                if ($cp[0] == 0) {
                    $cp = substr($cp, 1);
                }

                // Code commune insee ville
                $codeCom          = $this->villes->getInseeCode($this->companies->zip, $this->companies->city);
                $codeComNaissance = '';
                $retenuesource    = '';
                $sLieuNaissance   = '';
            } else {
                $this->etranger = 0;

                // fr/resident etranger
                if ($e['id_nationalite'] <= 1 && $this->clients_adresses->id_pays_fiscal > 1) {
                    $this->etranger = 1;
                } // no fr/resident etranger
                elseif ($e['id_nationalite'] > 1 && $this->clients_adresses->id_pays_fiscal > 1) {
                    $this->etranger = 2;
                }

                // on veut adresse fiscal
                if ($this->clients_adresses->meme_adresse_fiscal == 1) {
                    $adresse_fiscal = trim($this->clients_adresses->adresse1);
                    $cp_fiscal      = trim($this->clients_adresses->cp);
                    $ville_fiscal   = trim($this->clients_adresses->ville);
                    $id_pays_fiscal = ($this->clients_adresses->id_pays == 0 ? 1 : $this->clients_adresses->id_pays);
                } else {
                    $adresse_fiscal = trim($this->clients_adresses->adresse_fiscal);
                    $cp_fiscal      = trim($this->clients_adresses->cp_fiscal);
                    $ville_fiscal   = trim($this->clients_adresses->ville_fiscal);
                    $id_pays_fiscal = ($this->clients_adresses->id_pays_fiscal == 0 ? 1 : $this->clients_adresses->id_pays_fiscal);
                }

                // date naissance
                $nais      = explode('-', $e['naissance']);
                $naissance = $nais[2] . '/' . $nais[1] . '/' . $nais[0];

                // Iso fiscal
                if ($this->clients_adresses->id_pays_fiscal == 0) {
                    $this->clients_adresses->id_pays_fiscal = 1;
                }
                $this->pays->get($this->clients_adresses->id_pays_fiscal, 'id_pays');
                $isoFiscal = $this->pays->iso;

                if ($e['id_pays_naissance'] == 0) {
                    $id_pays_naissance = 1;
                } else {
                    $id_pays_naissance = $e['id_pays_naissance'];
                }
                $this->pays->get($id_pays_naissance, 'id_pays');
                $isoNaissance = $this->pays->iso;

                if ($this->etranger == 0) {
                    // Code commune insee ville
                    $codeCom          = $this->villes->getInseeCode($cp_fiscal, $ville_fiscal);
                    $commune          = '';
                    $cp               = $cp_fiscal;
                    $retenuesource    = '';
                    $ville_paysFiscal = $ville_fiscal;
                } else {
                    $codeCom = $cp_fiscal;
                    $commune = $ville_fiscal;

                    if ($id_pays_fiscal == 0) {
                        $id_pays = 1;
                    } else {
                        $id_pays = $id_pays_fiscal;
                    }
                    $this->pays->get($id_pays, 'id_pays');

                    $this->insee_pays->getByCountryIso(trim($this->pays->iso));
                    $cp = $this->insee_pays->COG;

                    $retenuesource = $this->ficelle->formatNumber($this->retenuesource) . '%';

                    if ($id_pays_fiscal == 0) {
                        $id_pays = 1;
                    } else {
                        $id_pays = $id_pays_fiscal;
                    }
                    $this->pays->get($id_pays, 'id_pays');
                    $paysFiscal = $this->pays->fr;

                    $ville_paysFiscal = $paysFiscal;
                }

                if (1 >= $e['id_pays_naissance']) {
                    $sLieuNaissance = $e['ville_naissance'];
                } else {
                    $this->pays->get($e['id_pays_naissance'], 'id_pays');
                    $sLieuNaissance = $this->pays->fr;
                }

                $this->clients->get($e['id_client'], 'id_client');
                $codeComNaissance = $this->clients->insee_birth == '' ? '00000' : $this->clients->insee_birth;
                $depNaiss         = substr($codeComNaissance, 0, 2);
            } // fin particulier

            $p         = substr($this->ficelle->stripAccents(utf8_decode(trim($e['prenom']))), 0, 1);
            $nom       = $this->ficelle->stripAccents(utf8_decode(trim($e['nom'])));
            $id_client = $e['id_client'];
            $motif     = mb_strtoupper($id_client . $p . $nom, 'UTF-8');
            $motif     = substr($motif, 0, 10);

            if ($entreprise == true) {
                $aData[] = array(
                    $motif,
                    $this->companies->name,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $this->companies->siret,
                    $isoFiscal,
                    '',
                    str_replace(';', ',', $this->companies->adresse1),
                    $codeCom,
                    '',
                    $this->companies->zip,
                    $ville_paysFiscal,
                    '',
                    $isoFiscal,
                    'X',
                    $retenuesource,
                    'N',
                    $this->companies->phone,
                    '',
                    $this->lenders_accounts->iban,
                    $this->lenders_accounts->bic,
                    $e['email'],
                    ''
                );
            } else {
                $aData[] = array(
                    $motif,
                    $e['nom'],
                    $e['civilite'],
                    $e['nom'],
                    $e['prenom'],
                    $naissance,
                    $depNaiss,
                    $codeComNaissance,
                    $sLieuNaissance,
                    '',
                    '',
                    $isoFiscal,
                    '',
                    str_replace(';', ',', $adresse_fiscal),
                    $codeCom,
                    $commune,
                    $cp,
                    $ville_paysFiscal,
                    '',
                    $isoNaissance,
                    'X',
                    $retenuesource,
                    'N',
                    $e['telephone'],
                    '',
                    $this->lenders_accounts->iban,
                    $this->lenders_accounts->bic,
                    $e['email'],
                    ''
                );
            }
        }

        $this->exportCSV($aData, 'requete_beneficiaires' . date('Ymd'), array(
            'Cbene',
            'Nom',
            'Qualité',
            'NomJFille',
            'Prénom',
            'DateNaissance',
            'DépNaissance',
            'ComNaissance',
            'LieuNaissance',
            'NomMari',
            'Siret',
            'AdISO',
            'Adresse',
            'Voie',
            'CodeCommune',
            'Commune',
            'CodePostal',
            'Ville / nom pays',
            'IdFiscal',
            'PaysISO',
            'Entité',
            'ToRS',
            'Plib',
            'Tél',
            'Banque',
            'IBAN',
            'BIC',
            'EMAIL',
            'Obs',
            ''
        ));
    }

    public function _requete_infosben()
    {
        $oLendersAccounts = $this->loadData('lenders_accounts');

        $iYear = date('Y');
        if (isset($this->params[0]) && is_numeric($this->params[0])) {
            $iYear = (int) $this->params[0];
        }

        $this->aLenders = $oLendersAccounts->getInfosben($iYear);
    }

    public function _requete_infosben_csv()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        $oLendersAccounts = $this->loadData('lenders_accounts');

        $iYear = date('Y');
        if (isset($this->params[0]) && is_numeric($this->params[0])) {
            $iYear = (int) $this->params[0];
        }

        $this->aLenders = $oLendersAccounts->getInfosben($iYear);

        $header = "Cdos;Cbéné;CEtabl;CGuichet;RéfCompte;NatCompte;TypCompte;CDRC;";

        $csv = "";
        $csv .= $header . " \n";

        foreach ($this->aLenders as $aLender) {
            // Motif
            $sPrenom = substr($this->ficelle->stripAccents(trim($aLender['prenom'])), 0, 1);
            $sNom    = $this->ficelle->stripAccents(trim($aLender['nom']));
            $motif   = mb_strtoupper($aLender['id_client'] . $sPrenom . $sNom, 'UTF-8');
            $motif   = substr($motif, 0, 10);

            $csv .= "1;" . $motif . ";14378;;" . $aLender['id_client'] . ";4;6;P;";
            $csv .= " \n";
        }

        $titre = 'requete_infosben' . date('Ymd');
        header("Content-type: application/vnd.ms-excel");
        header("Content-disposition: attachment; filename=\"" . $titre . ".csv\"");

        print(utf8_decode($csv));
    }

    public function _requete_revenus_csv()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        /** @var \clients $clients */
        $clients = $this->loadData('clients');

        /** @var \pays_v2 $countriesEntity */
        $countriesEntity    = $this->loadData('pays_v2');
        $countries          = $countriesEntity->getZoneB040Countries();
        $zoneB040CountryIds = [];

        foreach ($countries as $country) {
            $zoneB040CountryIds[] = $country['id_pays'];
        }

        $year = in_array(date('m'), ['01', '02', '03']) ? (date('Y') - 1) : date('Y');

        $commonValues = [
            'CodeEntreprise' => 1, //official code of SFPMEI
            'Date'           => '31/12/' . $year,
            'Monnaie'        => 'EURO'
        ];

        $row = 1;
        /** @var \PHPExcel $csvFile */
        $csvFile     = new \PHPExcel();
        $activeSheet = $csvFile->setActiveSheetIndex(0);
        $activeSheet->setCellValueByColumnAndRow(0, $row, 'Code Entreprise');
        $activeSheet->setCellValueByColumnAndRow(1, $row, 'CodeBénéficiaire');
        $activeSheet->setCellValueByColumnAndRow(2, $row, 'CodeV');
        $activeSheet->setCellValueByColumnAndRow(3, $row, 'Date');
        $activeSheet->setCellValueByColumnAndRow(4, $row, 'Montant');
        $activeSheet->setCellValueByColumnAndRow(5, $row, 'Monnaie');
        $row += 1;

        $sql = '
              SELECT
                c.id_client,
                SUM(ROUND(t.montant/100, 2)) AS interests,
                SUM(ROUND(retenues_source.amount / 100, 2)) AS retenues_source,
                SUM(ROUND(prelevements_obligatoires.amount / 100, 2)) AS prlv_obligatoire
              FROM clients c
                LEFT JOIN transactions t ON c.id_client = t.id_client AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
                LEFT JOIN tax retenues_source ON retenues_source.id_transaction = t.id_transaction AND retenues_source.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE . '
                LEFT JOIN tax prelevements_obligatoires ON prelevements_obligatoires.id_transaction = t.id_transaction AND prelevements_obligatoires.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX . '
              WHERE YEAR(t.date_transaction) = ' . $year . '
              GROUP BY c.id_client';
        $resultat = $this->bdd->query($sql);

        while ($record = $this->bdd->fetch_array($resultat)) {
            $commonValues['lenderPattern'] = $clients->getLenderPattern($record['id_client']);

            $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '53');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($record['interests'], 2, ',', ''));
            $row += 1;

            if ($record['retenues_source'] > 0) {
                $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '53');
                $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($record['retenues_source'], 2, ',', ''));
                $row += 1;
            }

            if ($record['prlv_obligatoire'] > 0) {
                $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '54');
                $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($record['prlv_obligatoire'], 2, ',', ''));
                $row += 1;
            }

            $this->addRepaymentBasedLinesToRevenuesQueryCSV($activeSheet, $record['id_client'], $year, $zoneB040CountryIds, $row, $commonValues);
            unset($commonValues['lenderPattern']);
        }

        $this->addLoansToRevenueQueryCSV($activeSheet, $row, $commonValues, $year, $clients);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=requete_revenus' . date('Ymd') . '.csv');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = PHPExcel_IOFactory::createWriter($csvFile, 'CSV');
        $writer->setUseBOM(true);
        $writer->setDelimiter(';');
        $writer->save('php://output');
    }

    private function addCommonCellValuesToRevenueQueryCSV(\PHPExcel_Worksheet &$activeSheet, $row, $commonValues)
    {
        $activeSheet->setCellValueByColumnAndRow(0, $row, $commonValues['CodeEntreprise']);
        $activeSheet->setCellValueByColumnAndRow(1, $row, $commonValues['lenderPattern']);
        $activeSheet->setCellValueByColumnAndRow(3, $row, $commonValues['Date']);
        $activeSheet->setCellValueByColumnAndRow(5, $row, $commonValues['Monnaie']);
    }

    private function addLoansToRevenueQueryCSV(\PHPExcel_Worksheet &$activeSheet, &$row, $commonValues, $year, \clients $clients)
    {
        $sql = '
          SELECT
            c.id_client,
            SUM(lo.amount) AS montant
          FROM loans lo
            INNER JOIN
            (
              SELECT psh.id_project, MIN(psh.added) as first_added
              FROM projects_status_history psh
                INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
              WHERE ps.status = ' . \projects_status::REMBOURSEMENT . '
              GROUP BY psh.id_project
              HAVING YEAR(first_added) = ' . $year . '
            ) p ON p.id_project = lo.id_project
            INNER JOIN lenders_accounts la ON la.id_lender_account = lo.id_lender
            INNER JOIN clients c ON la.id_client_owner = c.id_client
            GROUP BY c.id_client';

        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $commonValues['lenderPattern'] = $clients->getLenderPattern($record['id_client']);
            $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '117');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format(($record['montant'] / 100), 2, ',', ''));
            $row += 1;
        }
    }

    private function addRepaymentBasedLinesToRevenuesQueryCSV(\PHPExcel_Worksheet &$activeSheet, $clientId, $year, $zoneB040CountryIds, &$row, $commonValues)
    {
        $sum66  = 0;
        $sum81  = 0;
        $sum82  = 0;
        $sum118 = 0;

        $sql = 'SELECT
                  la.id_lender_account,
                  ROUND(t.montant/100, 2) as interets,
                  ROUND(retenues_source.amount / 100, 2) as retenues_source,
                  t.date_transaction,
                  c.type,
                  t.id_echeancier
                FROM lenders_accounts la
                INNER JOIN clients c ON la.id_client_owner = c.id_client
                LEFT JOIN transactions t ON c.id_client = t.id_client AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
                LEFT JOIN tax retenues_source ON retenues_source.id_transaction = t.id_transaction AND retenues_source.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE . '
                WHERE YEAR(t.date_transaction) = ' . $year . '
                  AND c.id_client =  ' . $clientId;

        $resultat = $this->bdd->query($sql);

        /** @var \transactions $transactions */
        $transactions = $this->loadData('transactions');

        while ($record = $this->bdd->fetch_array($resultat)) {
            $capitalTransaction = $transactions->select('id_echeancier = ' . $record['id_echeancier'] . ' AND type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL)[0];

            if (\clients::TYPE_PERSON == $record['type'] || \clients::TYPE_PERSON_FOREIGNER == $record['type']) {
                /** @var \lenders_imposition_history $lenderImpositionHistory */
                $lenderImpositionHistory = $this->loadData('lenders_imposition_history');

                $foreigner       = false;
                $zoneB040Country = false;
                $situation       = $lenderImpositionHistory->getTaxationSituationAtDate($record['id_lender_account'], $record['date_transaction']);

                if (false === empty($situation)) {
                    $situation = $situation[0];
                    if (0 < $situation['resident_etranger']) {
                        $foreigner = true;
                        if (in_array($situation['id_pays'], $zoneB040CountryIds)) {
                            $zoneB040Country = true;
                        }
                    }
                }

                unset($situation);

                if (false === $foreigner) { //code 66
                    $sum66 += $record['interets'];
                } else {
                    if (true === $zoneB040Country) {
                        $sum81 += $record['interets'] - $record['retenues_source'] * 100;
                    }
                }

                if (true === $zoneB040Country) {
                    $sum82 += round(bcdiv($capitalTransaction['montant'], 100, 3), 2);
                }
            }
            $sum118 += round(bcdiv(bcdiv($capitalTransaction['montant'], 100, 3), 0.844, 5), 2);

            unset($record, $capitalTransaction);
        }

        $recoveryPayments = $transactions->sum('id_client = ' . $clientId . ' AND type_transaction = ' . \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT, 'montant');
        $sum118 += round(bcdiv($recoveryPayments, 100, 3), 2);

        if ($sum66 > 0) {
            $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '66');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($sum66, 2, ',', ''));
            $row += 1;
        }

        if ($sum81 > 0) {
            $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '81');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($sum81, 2, ',', ''));
            $row += 1;
        }

        if ($sum82 > 0) {
            $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '82');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($sum82, 2, ',', ''));
            $row += 1;
        }

        if ($sum118 > 0) {
            $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '118');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($sum118, 2, ',', ''));
            $row += 1;
        }
    }

    public function _requete_encheres()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        $header = "id_project;id_bid;id_client;added;statut;amount;rate;";
        $header = utf8_encode($header);

        $csv = "";
        $csv .= $header . " \n";

        $sql = 'SELECT id_project, id_bid, (SELECT id_client_owner FROM lenders_accounts la WHERE la.id_lender_account = b.id_lender_account) as id_client, added, (case status when 0 then "En cours" when 1 then "OK" when 2 then "KO" end) as Statut, ROUND((amount/100),0), REPLACE(rate,".",",") as rate FROM bids b';

        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_array($resultat)) {
            for ($i = 0; $i <= 6; $i++) {
                $csv .= $record[$i] . ";";
            }
            $csv .= " \n";
        }

        $titre = 'toutes_les_encheres_' . date('Ymd');
        header("Content-type: application/vnd.ms-excel");
        header("Content-disposition: attachment; filename=\"" . $titre . ".csv\"");

        print(utf8_decode($csv));
    }

    public function _tous_echeanciers_pour_projet()
    {
        if (isset($_POST['form_envoi_params']) && $_POST['form_envoi_params'] == "ok" && false == empty($_POST['id_projet'])) {
            $this->autoFireView = false;
            $this->hideDecoration();

            $header = "Id_echeancier;Id_lender;Id_projet;Id_loan;Ordre;Montant;Capital;Capital_restant;Interets;Prelevements_obligatoires;Retenues_source;CSG;Prelevements_sociaux;Contributions_additionnelles;Prelevements_solidarite;CRDS;Date_echeance;Date_echeance_reel;Date_echeance_emprunteur;Date_echeance_emprunteur_reel;Status;";
            $header = utf8_encode($header);

            $csv = "";
            $csv .= $header . " \n";

            $sql = 'SELECT 
                      e.id_echeancier,
                      e.id_lender,
                      e.id_project,
                      e.id_loan,
                      e.ordre,
                      e.montant,
                      e.capital,
                      SUM(e.capital - e.capital_rembourse) AS capitalRestant,
                      e.interets,
                      ROUND(prelevements_obligatoires.amount / 100, 2) AS prelevements_obligatoires,
                      ROUND(retenues_source.amount / 100, 2) AS retenues_source,
                      ROUND(csg.amount / 100, 2) AS csg,
                      ROUND(prelevements_sociaux.amount / 100, 2) AS prelevements_sociaux,
                      ROUND(contributions_additionnelles.amount / 100, 2) AS contributions_additionnelles,
                      ROUND(prelevements_solidarite.amount / 100, 2) AS prelevements_solidarite,
                      ROUND(crds.amount / 100, 2) AS crds,
                      e.date_echeance,
                      e.date_echeance_reel,
                      e.date_echeance_emprunteur,
                      e.date_echeance_emprunteur_reel,
                      status
                  FROM echeanciers e
                      LEFT JOIN transactions t ON t.id_echeancier = e.id_echeancier AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
                      LEFT JOIN tax prelevements_obligatoires ON prelevements_obligatoires.id_transaction = t.id_transaction AND prelevements_obligatoires.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX . '
                      LEFT JOIN tax retenues_source ON retenues_source.id_transaction = t.id_transaction AND retenues_source.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE . '
                      LEFT JOIN tax csg ON csg.id_transaction = t.id_transaction AND csg.id_tax_type = ' . \tax_type::TYPE_CSG . '
                      LEFT JOIN tax prelevements_sociaux ON prelevements_sociaux.id_transaction = t.id_transaction AND prelevements_sociaux.id_tax_type = ' . \tax_type::TYPE_SOCIAL_DEDUCTIONS . '
                      LEFT JOIN tax contributions_additionnelles ON contributions_additionnelles.id_transaction = t.id_transaction AND contributions_additionnelles.id_tax_type = ' . \tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS . '
                      LEFT JOIN tax prelevements_solidarite ON prelevements_solidarite.id_transaction = t.id_transaction AND prelevements_solidarite.id_tax_type = ' . \tax_type::TYPE_SOLIDARITY_DEDUCTIONS . '
                      LEFT JOIN tax crds ON crds.id_transaction = t.id_transaction AND crds.id_tax_type = ' . \tax_type::TYPE_CRDS . '
                  WHERE e.id_project=' . $_POST['id_projet'];

            $resultat = $this->bdd->query($sql);
            while ($record = $this->bdd->fetch_array($resultat)) {
                $csv .= $record['id_echeancier'] . ";" . $record['id_lender'] . ";" . $record['id_project'] . ";" . $record['id_loan'] . ";" . $record['ordre'] . ";" . $record['montant'] . ";" . $record['capital'] . ";" . $record['capitalRestant'] . ";" . $record['interets'] . ";" . $record['prelevements_obligatoires'] . ";" . $record['retenues_source'] . ";" . $record['csg'] . ";" . $record['prelevements_sociaux'] . ";" . $record['contributions_additionnelles'] . ";" . $record['prelevements_solidarite'] . ";" . $record['crds'] . ";" . $record['date_echeance'] . ";" . $record['date_echeance_reel'] . ";" . $record['date_echeance_emprunteur'] . ";" . $record['date_echeance_emprunteur_reel'] . ";" . $record['status'] . ";";
                $csv .= " \n";
            }

            $titre = 'tous_echeanciers_pour_projet_' . date('Ymd');
            header("Content-type: application/vnd.ms-excel");
            header("Content-disposition: attachment; filename=\"" . $titre . ".csv\"");

            print(utf8_decode($csv));
        }
    }

    public function _donnees_financieres_emprumteurs()
    {
        $this->sql = 'SELECT p.id_project as id_project, c.name,
            (SELECT cli.source FROM clients cli WHERE cli.id_client = c.id_client_owner) as source,
            title, p.added,
            (SELECT label FROM projects_status ps WHERE ps.id_project_status = (SELECT id_project_status FROM projects_status_history psh WHERE psh.id_project = p.id_project ORDER BY id_project_status_history LIMIT 1)) as status,
            IFNULL(cr.value, 0) AS score_altares,
            c.risk, p.amount,p.period,
            (SELECT ca FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as ca2011,
            (SELECT ca FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as ca2012,
            (SELECT ca FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as ca2013,
            (SELECT ca FROM companies_bilans cb WHERE date=2014 and cb.id_company = c.id_company) as ca2014,

            (SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as rbe2011,
            (SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as rbe2012,
            (SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as rbe2013,
            (SELECT resultat_brute_exploitation FROM companies_bilans cb WHERE date=2014 and cb.id_company = c.id_company) as rbe2014,

            (SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as rex2011,
            (SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as rex2012,
            (SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as rex2013,
            (SELECT resultat_exploitation FROM companies_bilans cb WHERE date=2014 and cb.id_company = c.id_company) as rex2014,

            (SELECT investissements FROM companies_bilans cb WHERE date=2011 and cb.id_company = c.id_company) as invest2011,
            (SELECT investissements FROM companies_bilans cb WHERE date=2012 and cb.id_company = c.id_company) as invest2012,
            (SELECT investissements FROM companies_bilans cb WHERE date=2013 and cb.id_company = c.id_company) as invest2013,
            (SELECT investissements FROM companies_bilans cb WHERE date=2014 and cb.id_company = c.id_company) as invest2014,

            (SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immocorp2011,
            (SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immocorp2012,
            (SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immocorp2013,
            (SELECT immobilisations_corporelles FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as immocorp2014,

            (SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immoincorp2011,
            (SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immoincorp2012,
            (SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immoincorp2013,
            (SELECT immobilisations_incorporelles FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as immoincorp2014,

            (SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as immofin2011,
            (SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as immofin2012,
            (SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as immofin2013,
            (SELECT immobilisations_financieres FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as immofin2014,

            (SELECT stocks FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as stock2011,
            (SELECT stocks FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as stock2012,
            (SELECT stocks FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as stock2013,
            (SELECT stocks FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as stock2014,

            (SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as creances2011,
            (SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as creances2012,
            (SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as creances2013,
            (SELECT creances_clients FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as creances2014,

            (SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dispo2011,
            (SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dispo2012,
            (SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dispo2013,
            (SELECT disponibilites FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as dispo2014,

            (SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as valeursmob2011,
            (SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as valeursmob2012,
            (SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as valeursmob2013,
            (SELECT valeurs_mobilieres_de_placement FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as valeursmob2014,

            (SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as cp2011,
            (SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as cp2012,
            (SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as cp2013,
            (SELECT capitaux_propres FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as cp2014,

            (SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as provisions2011,
            (SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as provisions2012,
            (SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as provisions2013,
            (SELECT provisions_pour_risques_et_charges FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as provisions2014,

            (SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as ammort2011,
            (SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as ammort2012,
            (SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as ammort2013,
            (SELECT amortissement_sur_immo FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as ammort2014,

            (SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dettesfin2011,
            (SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dettesfin2012,
            (SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dettesfin2013,
            (SELECT dettes_financieres FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as dettesfin2014,

            (SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as dettesfour2011,
            (SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as dettesfour2012,
            (SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as dettesfour2013,
            (SELECT dettes_fournisseurs FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as dettesfour2014,

            (SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2011 and cap.id_company = c.id_company) as autresdettes2011,
            (SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2012 and cap.id_company = c.id_company) as autresdettes2012,
            (SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2013 and cap.id_company = c.id_company) as autresdettes2013,
            (SELECT autres_dettes FROM companies_actif_passif cap WHERE annee=2014 and cap.id_company = c.id_company) as autresdettes2014,
            c.forme,c.date_creation

            FROM projects p
            LEFT JOIN company_rating cr ON p.id_company_rating_history > 0 AND cr.id_company_rating_history = p.id_company_rating_history AND cr.type = \'score_altares\'
            JOIN companies c ON c.id_company = p.id_company
            WHERE id_project IN (SELECT id_project FROM projects_status_history psh)';

        if (isset($this->params[0]) && $this->params[0] == 'csv') {
            $this->autoFireView = false;
            $this->hideDecoration();

            $titre = 'donnees_financieres_emprunteurs_' . date('Ymd');
            header("Content-type: application/vnd.ms-excel");
            header("Content-disposition: attachment; filename=\"" . $titre . ".csv\"");

            $header = "id_project;name;source;title;added;status;altares_scoreVingt;risk;amount;period;ca2011;ca2012;ca2013;ca2014;rbe2011;rbe2012;rbe2013;rbe2014;rex2011;rex2012;rex2013;rex2014;invest2011;invest2012;invest2013;invest2014;immocorp2011;immocorp2012;immocorp2013;immocorp2014;immoincorp2011;immoincorp2012;immoincorp2013;immoincorp2014;immofin2011;immofin2012;immofin2013;immofin2014;stock2011;stock2012;stock2013;stock2014;creances2011;creances2012;creances2013;creances2014;dispo2011;dispo2012;dispo2013;dispo2014;valeursmob2011;valeursmob2012;valeursmob2013;valeursmob2014;cp2011;cp2012;cp2013;cp2014;provisions2011;provisions2012;provisions2013;provisions2014;ammort2011;ammort2012;ammort2013;ammort2014;dettesfin2011;dettesfin2012;dettesfin2013;dettesfin2014;dettesfour2011;dettesfour2012;dettesfour2013;dettesfour2014;autresdettes2011;autresdettes2012;autresdettes2013;autresdettes2014;forme;date_creation";
            $header = utf8_encode($header);

            $csv = "";
            $csv .= $header . " \n";

            $resultat = $this->bdd->query($this->sql);
            while ($record = $this->bdd->fetch_array($resultat)) {
                for ($a = 0; $a <= 45; $a++) {
                    $csv .= $record[$a] . ";";
                }
                $csv .= " \n";
            }

            print(utf8_decode($csv));
        }
    }

    private function exportQueryCSV($sQuery, $sFileName, array $aHeaders = null)
    {
        $aResult = array();
        $rQuery  = $this->bdd->query($sQuery);
        while ($aRow = $this->bdd->fetch_assoc($rQuery)) {
            $aResult[] = $aRow;
        }

        if (count($aResult) > 0 && is_null($aHeaders)) {
            $aHeaders = array_keys($aResult[0]);
        }
        $this->exportCSV($aResult, $sFileName, $aHeaders);
    }

    private function exportCSV($aData, $sFileName, array $aHeaders = null)
    {
        $this->bdd->close();

        PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp, array(
                'memoryCacheSize' => '2048MB',
                'cacheTime'       => 1200
            ));

        $oDocument    = new PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);

        if (count($aHeaders) > 0) {
            foreach ($aHeaders as $iIndex => $sColumnName) {
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

        die;
    }

    public function _autobid_statistic()
    {
        $oProject = $this->loadData('projects');

        if (isset($_POST['date_from'], $_POST['date_to']) && false === empty($_POST['date_from']) && false === empty($_POST['date_to'])) {
            $aProjectList = $oProject->getAutoBidProjectStatistic(\DateTime::createFromFormat('d/m/Y H:i:s', $_POST['date_from'] . ' 00:00:00'), \DateTime::createFromFormat('d/m/Y H:i:s', $_POST['date_to'] . ' 23:59:59'));

            $this->aProjectList = [];
            foreach ($aProjectList as $aProject) {
                $fRisk                = constant('\projects::RISK_' . trim($aProject['risk']));
                $this->aProjectList[] = [
                    'id_project'                => $aProject['id_project'],
                    'percentage'                => round(($aProject['amount_total_autobid'] / $aProject['amount_total']) * 100, 2) . ' %',
                    'period'                    => $aProject['period'],
                    'risk'                      => $fRisk,
                    'bids_nb'                   => $aProject['bids_nb'],
                    'avg_amount'                => $aProject['avg_amount'],
                    'weighted_avg_rate'         => round($aProject['weighted_avg_rate'], 1),
                    'avg_amount_autobid'        => $aProject['avg_amount_autobid'],
                    'weighted_avg_rate_autobid' => false === empty($aProject['weighted_avg_rate_autobid']) ? round($aProject['weighted_avg_rate_autobid'], 2) : '',
                    'status_label'              => $aProject['status_label'],
                    'date_fin'                  => $aProject['date_fin']
                ];
            }

            if (isset($_POST['extraction_csv'])) {
                $aHeader = array(
                    'id_project',
                    'pourcentage',
                    'period',
                    'risk',
                    'nombre de bids',
                    'montant moyen',
                    'taux moyen pondéré',
                    'montant moyen autolend',
                    'taux moyen pondéré autolend',
                    'status',
                    'date fin de projet'
                );
                $this->exportCSV($this->aProjectList, 'statistiques_autolends' . date('Ymd'), $aHeader);
            }
        }
    }

    public function _requete_source_emprunteurs()
    {
        /** @var \clients $oClient */
        $oClient          = $this->loadData('clients');
        $this->aBorrowers = array();

        if (isset($_POST['dateStart'], $_POST['dateEnd']) && false === empty($_POST['dateStart']) && false === empty($_POST['dateEnd'])) {
            $oDateTimeStart = \DateTime::createFromFormat('d/m/Y', $_POST['dateStart']);
            $oDateTimeEnd   = \DateTime::createFromFormat('d/m/Y', $_POST['dateEnd']);

            if (isset($_POST['queryOptions']) && 'allLines' == $_POST['queryOptions']) {
                $this->aBorrowers = $oClient->getBorrowersContactDetailsAndSource($oDateTimeStart, $oDateTimeEnd, false);
            }
            if (isset($_POST['queryOptions']) && in_array($_POST['queryOptions'], array(
                    'groupBySirenWithDetails',
                    'groupBySiren'
                ))
            ) {
                $this->aBorrowers = $oClient->getBorrowersContactDetailsAndSource($oDateTimeStart, $oDateTimeEnd, true);

                if ('groupBySirenWithDetails' == $_POST['queryOptions']) {
                    foreach ($this->aBorrowers as $iKey => $aBorrower) {
                        if ($aBorrower['countSiren'] > 1) {
                            $this->aBorrowers[$iKey]['firstEntrySource'] = $oClient->getFirstSourceForSiren($aBorrower['siren'], $oDateTimeStart, $oDateTimeEnd);
                            $this->aBorrowers[$iKey]['lastEntrySource']  = $oClient->getLastSourceForSiren($aBorrower['siren'], $oDateTimeStart, $oDateTimeEnd);
                            $this->aBorrowers[$iKey]['lastLabel']        = $this->aBorrowers[$iKey]['label'];
                            $aHeaderExtended                             = array_keys(($this->aBorrowers[$iKey]));
                        }
                    }
                }
            }

            if (isset($_POST['extraction_csv'])) {
                $aHeader = isset($aHeaderExtended) ? $aHeaderExtended : array_keys(array_shift($this->aBorrowers));
                $this->exportCSV($this->aBorrowers, 'requete_source_emprunteurs' . date('Ymd'), $aHeader);
            }
        }
    }
}
