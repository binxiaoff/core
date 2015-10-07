<?php

use Unilend\librairies\ULogger;

class depot_de_dossierController extends bootstrap
{
    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        $this->companies               = $this->loadData('companies');
        $this->companies_bilans        = $this->loadData('companies_bilans');
        $this->companies_details       = $this->loadData('companies_details');
        $this->companies_actif_passif  = $this->loadData('companies_actif_passif');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->projects_status         = $this->loadData('projects_status');
        $this->projects                = $this->loadData('projects');
        $this->clients                 = $this->loadData('clients');
        $this->prescripteurs           = $this->loadData('prescripteurs');
        $this->attachment              = $this->loadData('attachment');
        $this->attachment_type         = $this->loadData('attachment_type');

        $this->navigateurActive = 3;

        $this->lng['depot-de-dossier-header'] = $this->ln->selectFront('depot-de-dossier-header', $this->language, $this->App);
        $this->lng['etape1']                  = $this->ln->selectFront('depot-de-dossier-etape-1', $this->language, $this->App);
        $this->lng['etape2']                  = $this->ln->selectFront('depot-de-dossier-etape-2', $this->language, $this->App);
        $this->lng['etape3']                  = $this->ln->selectFront('depot-de-dossier-etape-3', $this->language, $this->App);
        $this->lng['espace-emprunteur']       = $this->ln->selectFront('depot-de-dossier-espace-emprunteur', $this->language, $this->App);

        $this->settings->get('Altares login', 'type');
        $login = $this->settings->value;

        $this->settings->get('Altares mot de passe', 'type');
        $mdp = $this->settings->value; // mdp en sha1

        $this->settings->get('Altares wsdl', 'type');
        $this->wsdl = $this->settings->value;

        $this->identification = $login . '|' . $mdp;
    }

    public function _default()
    {
        header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
    }

    public function _etape1()
    {
        $this->checkClient();

        $this->page = 1;

        $this->lng['landing-page'] = $this->ln->selectFront('landing-page', $this->language, $this->App);

        if (false === isset($_SESSION['forms']['depot-de-dossier']['values'])) {
            header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
            die;
        }

        $iAmount = $_SESSION['forms']['depot-de-dossier']['values']['montant'];
        $iSIREN  = $_SESSION['forms']['depot-de-dossier']['values']['siren'];
        $sEmail  = isset($_SESSION['forms']['depot-de-dossier']['values']['email']) && $this->ficelle->isEmail($_SESSION['forms']['depot-de-dossier']['values']['email']) ? $_SESSION['forms']['depot-de-dossier']['values']['email'] : null;

        // @todo unset $_SESSION['forms']['depot-de-dossier']['values'] ?

        //create client, company and project independent from eligibility

        /*if ($this->companies->exist($iSIREN, $field = 'siren')) {
            $this->companies->get($iSIREN, 'siren');
            //then get the client from that company in case it has not already been found by email before
            if ($this->clients->id_client == '') {
                $this->clients->get($this->companies->id_client_owner);
            }
        }
        //if there is a client, check if the client is not already a "preteur", in this case send back with error message
        if (is_numeric($this->clients->id_client) && $this->clients->status_pre_emp === 1) {

            $_SESSION['error_pre_empr'] = $this->lng['etape1']['seule-une-personne-morale-peut-creer-un-compte-emprunteur'];
            header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
        }*/

        $this->clients->id_langue            = $this->language;
        $this->clients->status_depot_dossier = 1;
        $this->clients->slug_origine         = $this->tree->slug;

        if ($this->preteurCreateEmprunteur == false) {
            $this->clients->source  = $_SESSION['utm_source'];
            $this->clients->source2 = $_SESSION['utm_source2'];
        }

        if (false === is_null($sEmail)) {
            $this->clients->email = $sEmail;
        }

        if ($this->clients->id_client == '') {
            $this->clients->id_client = $this->clients->create();
        }

        $this->companies->id_client_owner               = $this->clients->id_client;
        $this->companies->siren                         = $iSIREN;
        $this->companies->status_adresse_correspondance = '1';

        if ($this->companies->id_company == '') {
            $this->companies->id_company = $this->companies->create();
        }

        $this->projects->id_company = $this->companies->id_company;
        if ($this->prescripteurs->id_prescripteur == '') {
            $this->projects->id_prescripteur = $this->prescripteurs->id_prescripteur;
        }

        $this->projects->amount     = $iAmount;
        $this->projects->id_company = $this->companies->id_company;
        $this->projects->id_project = $this->projects->create();

        // 1 : activé 2 : activé mais prend pas en compte le resultat 3 : desactivé (DC)
        $this->settings->get('Altares debrayage', 'type');
        $AltaresDebrayage = $this->settings->value;

        $this->settings->get('Altares email alertes', 'type');
        $AltaresEmailAlertes = $this->settings->value;

        // 1 : activé - 2 : on prend pas en compte les filtres.(DC)
        if (in_array($AltaresDebrayage, array(1, 2))) {
            $result = '';
            try {
                $result = $this->ficelle->ws($this->wsdl, $this->identification, $iSIREN);
            } catch (\Exception $e) {
                $oLogger = new ULogger('connection', $this->logPath, 'altares.log');
                $oLogger->addRecord(ULogger::ALERT, $e->getMessage(), array('siren' => $iSIREN));

                mail($AltaresEmailAlertes, '[ALERTE] ERREUR ALTARES 2', 'Date ' . date('Y-m-d H:i:s') . '' . $e->getMessage());
            }

            if ($result->exception != false) {
                $oLogger = new ULogger('connection', $this->logPath, 'altares.log');
                $oLogger->addRecord(ULogger::ALERT, $result->exception->code . ' | ' . $result->exception->description . ' | ' . $result->exception->erreur, array('siren' => $iSIREN));

                mail($AltaresEmailAlertes, '[ALERTE] ERREUR ALTARES 1', 'Date ' . date('Y-m-d H:i:s') . 'SIREN : ' . $iSIREN . ' | ' . $result->exception->code . ' | ' . $result->exception->description . ' | ' . $result->exception->erreur);
            }

            $exception = $result->exception;

            if ($AltaresDebrayage == 2) {
                $oLogger = new ULogger('connection', $this->logPath, 'altares.log');
                $oLogger->addRecord(ULogger::INFO, 'Tentative évaluation', array('siren' => $iSIREN));

                mail($AltaresEmailAlertes, '[ALERTE] Altares Tentative evaluation', 'Date ' . date('Y-m-d H:i:s') . ' siren : ' . $iSIREN);
            }
        }

        if (false === empty($exception)) {
            $this->emailAltares($this->projects->id_project, $this->projects->title);
            $this->redirectEtape1('/depot_de_dossier/nok', projects_status::NOTE_EXTERNE_FAIBLE);
        }

        $this->projects->retour_altares = $result->myInfo->eligibility;
        $this->projects->update();

        switch ($result->myInfo->eligibility) {
            case '1_Etablissement Inactif':
            case '7_SIREN inconnu':
                $this->redirectEtape1('/depot_de_dossier/nok/no-siren', projects_status::NOTE_EXTERNE_FAIBLE);
                break;
            case '2_Etablissement sans RCS':
                $this->redirectEtape1('/depot_de_dossier/nok/no-rcs', projects_status::NOTE_EXTERNE_FAIBLE);
                break;
            case '3_Procédure Active':
            case '4_Bilan de plus de 450 jours':
            case '9_bilan sup 450 jours':
            default:
                $this->redirectEtape1('/depot_de_dossier/nok', projects_status::NOTE_EXTERNE_FAIBLE);
                break;
            case '5_Fonds Propres Négatifs':
            case '6_EBE Négatif':
                $this->redirectEtape1('/depot_de_dossier/nok/rex-nega', projects_status::NOTE_EXTERNE_FAIBLE);
                break;
            case '8_Eligible':
                $this->clients_adresses->id_client = $this->clients->id_client;
                $this->clients_adresses->create();

                $oIdentite = $result->myInfo->identite;
                $oScore    = $result->myInfo->score;
                $oSiege    = $result->myInfo->siege;

                //TODO review logic of saving data
                //maybe create an altares libarary so
                $this->companies->name                          = $oIdentite->raisonSociale;
                $this->companies->forme                         = $oIdentite->formeJuridique;
                $this->companies->capital                       = $oIdentite->capital;
                $this->companies->code_naf                      = $oIdentite->naf5EntreCode;
                $this->companies->libelle_naf                   = $oIdentite->naf5EntreLibelle;
                $this->companies->adresse1                      = $oIdentite->rue;
                $this->companies->city                          = $oIdentite->ville;
                $this->companies->zip                           = $oIdentite->codePostal;
                $this->companies->phone                         = str_replace(' ', '', $oSiege->telephone);
                $this->companies->rcs                           = $oIdentite->rcs;
                $this->companies->siret                         = $oIdentite->siret;
                $this->companies->status_adresse_correspondance = '1';
                $this->companies->date_creation                 = substr($oIdentite->dateCreation, 0, 10);
                $this->companies->altares_eligibility           = $result->myInfo->eligibility;
                $this->companies->altares_niveauRisque          = $oScore->niveauRisque;
                $this->companies->altares_scoreVingt            = $oScore->scoreVingt;
                $this->companies->score_sectoriel_altatres      = $oScore->scoreSectorielVingt;
                $this->companies->score_sectoriel_xerfirisk     = $oScore->scoreSectorielCent;
                $this->companies->altares_dateValeur            = substr($oScore->dateValeur, 0, 10);

                $dateDernierBilanString                             = substr($oIdentite->dateDernierBilan, 0, 10);
                $dateDernierBilan                                   = explode('-', $dateDernierBilanString);
                $this->companies_details->date_dernier_bilan        = $dateDernierBilanString;
                $this->companies_details->date_dernier_bilan_mois   = $dateDernierBilan[1];
                $this->companies_details->date_dernier_bilan_annee  = $dateDernierBilan[0];
                $this->companies_details->date_dernier_bilan_publie = $dateDernierBilanString;
                $this->companies_details->id_company                = $this->companies->id_company;

                if ($this->preteurCreateEmprunteur == true && $this->clients->type == 2) {
                    $this->companies_details->update();
                } else {
                    $this->companies_details->create();
                }

                // On génère 5 lignes dans la base pour les bilans
                $lesdates = array(date('Y') - 3, date('Y') - 2, date('Y') - 1, date('Y'), date('Y') + 1);
                for ($i = 0; $i < 5; $i++) {
                    $this->companies_bilans->id_company = $this->companies->id_company;
                    $this->companies_bilans->date       = $lesdates[$i];
                    $this->companies_bilans->create();
                }

                // les 3 dernieres vrais années (actif/passif)
                $date    = array();
                $date[1] = (date('Y') - 1);
                $date[2] = (date('Y') - 2);
                $date[3] = (date('Y') - 3);

                foreach ($date as $k => $d) {
                    $this->companies_actif_passif->annee      = $d;
                    $this->companies_actif_passif->ordre      = $k;
                    $this->companies_actif_passif->id_company = $this->companies->id_company;
                    $this->companies_actif_passif->create();
                }

                $posteActifList         = array();
                $postePassifList        = array();
                $syntheseFinanciereInfo = array();
                $syntheseFinanciereList = array();
                $derniersBilans         = array();

                if (isset($result->myInfo->bilans) && is_array($result->myInfo->bilans)) {
                    $i = 0;
                    foreach ($result->myInfo->bilans as $b) {
                        $annee                                 = substr($b->bilan->dateClotureN, 0, 4);
                        $posteActifList[$annee]                = $b->bilanRetraiteInfo->posteActifList;
                        $postePassifList[$annee]               = $b->bilanRetraiteInfo->postePassifList;
                        $syntheseFinanciereInfo[$annee]        = $b->syntheseFinanciereInfo;
                        $syntheseFinanciereList[$annee]        = $b->syntheseFinanciereInfo->syntheseFinanciereList;
                        $soldeIntermediaireGestionInfo[$annee] = $b->soldeIntermediaireGestionInfo->SIGList;
                        $investissement[$annee]                = $b->bilan->posteList[0]->valeur;
                        $derniersBilans[$i++]                  = $annee;
                    }
                }

                $ldate = $lesdates;
                // on génère un tableau avec les données
                for ($i = 0; $i < 5; $i++) { // on parcourt les 5 années
                    for ($a = 0; $a < 3; $a++) { // on parcourt les 3 dernieres années
                        // si y a une année du bilan qui correxpond a une année du tableau
                        if ($derniersBilans[$a] == $ldate[$i]) {
                            // On recup les données de cette année
                            $montant1 = $posteActifList[$ldate[$i]][1]->montant;
                            $montant2 = $posteActifList[$ldate[$i]][2]->montant;
                            $montant3 = $posteActifList[$ldate[$i]][3]->montant;
                            $montant  = $montant1 + $montant2 + $montant3;

                            $this->companies_bilans->get($this->companies->id_company, 'date = ' . $ldate[$i] . ' AND id_company');
                            $this->companies_bilans->ca                          = $syntheseFinanciereList[$ldate[$i]][0]->montantN;
                            $this->companies_bilans->resultat_exploitation       = $syntheseFinanciereList[$ldate[$i]][1]->montantN;
                            $this->companies_bilans->resultat_brute_exploitation = $soldeIntermediaireGestionInfo[$ldate[$i]][9]->montantN;
                            $this->companies_bilans->investissements             = $investissement[$ldate[$i]];
                            $this->companies_bilans->update();
                        }
                    }
                }

                foreach ($derniersBilans as $annees) {
                    foreach ($posteActifList[$annees] as $a) {
                        $ActifPassif[$annees][$a->posteCle] = $a->montant;
                    }
                    foreach ($postePassifList[$annees] as $p) {
                        $ActifPassif[$annees][$p->posteCle] = $p->montant;
                    }
                }

                $this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_company = "' . $this->companies->id_company . '"', 'annee DESC');
                $i                             = 0;
                foreach ($this->lCompanies_actif_passif as $k => $ap) {
                    if ($this->companies_actif_passif->get($ap['annee'], 'id_company = ' . $ap['id_company'] . ' AND annee')) {
                        // Actif
                        $this->companies_actif_passif->immobilisations_corporelles   = $ActifPassif[$ap['annee']]['posteBR_IMCOR'];
                        $this->companies_actif_passif->immobilisations_incorporelles = $ActifPassif[$ap['annee']]['posteBR_IMMINC'];
                        $this->companies_actif_passif->immobilisations_financieres   = $ActifPassif[$ap['annee']]['posteBR_IMFI'];
                        $this->companies_actif_passif->stocks                        = $ActifPassif[$ap['annee']]['posteBR_STO'];
                        //creances_clients = Avances et acomptes + creances clients + autre creances et cca + autre creances hors exploitation
                        $this->companies_actif_passif->creances_clients                = $ActifPassif[$ap['annee']]['posteBR_BV'] + $ActifPassif[$ap['annee']]['posteBR_BX'] + $ActifPassif[$ap['annee']]['posteBR_ACCCA'] + $ActifPassif[$ap['annee']]['posteBR_ACHE_'];
                        $this->companies_actif_passif->disponibilites                  = $ActifPassif[$ap['annee']]['posteBR_CF'];
                        $this->companies_actif_passif->valeurs_mobilieres_de_placement = $ActifPassif[$ap['annee']]['posteBR_CD'];

                        // passif
                        // capitaux_propres = capitaux propres + non valeurs
                        $this->companies_actif_passif->capitaux_propres = $ActifPassif[$ap['annee']]['posteBR_CPRO'] + $ActifPassif[$ap['annee']]['posteBR_NONVAL'];
                        // provisions_pour_risques_et_charges = Provisions pour risques et charges + Provisions actif circulant
                        $this->companies_actif_passif->provisions_pour_risques_et_charges = $ActifPassif[$ap['annee']]['posteBR_PROVRC'] + $ActifPassif[$ap['annee']]['posteBR_PROAC'];
                        $this->companies_actif_passif->amortissement_sur_immo             = $ActifPassif[$ap['annee']]['posteBR_AMPROVIMMO'];
                        // dettes_financieres = Emprunts + Dettes groupe et associés + Concours bancaires courants
                        $this->companies_actif_passif->dettes_financieres = $ActifPassif[$ap['annee']]['posteBR_EMP'] + $ActifPassif[$ap['annee']]['posteBR_VI'] + $ActifPassif[$ap['annee']]['posteBR_EH'];

                        // dettes_fournisseurs = Avances et Acomptes clients + Dettes fournisseurs
                        $this->companies_actif_passif->dettes_fournisseurs = $ActifPassif[$ap['annee']]['posteBR_DW'] + $ActifPassif[$ap['annee']]['posteBR_DX'];

                        // autres_dettes = autres dettes exploi + Dettes sur immos et comptes rattachés + autres dettes hors exploi
                        $this->companies_actif_passif->autres_dettes = $ActifPassif[$ap['annee']]['posteBR_AUTDETTEXPL'] + $ActifPassif[$ap['annee']]['posteBR_DZ'] + $ActifPassif[$ap['annee']]['posteBR_AUTDETTHEXPL'];
                        $this->companies_actif_passif->update();
                    }
                    $i++;
                }
                $this->companies->update();

                //check on creation date
                $oDatetime1 = date_create_from_format('Y-m-d', substr($oIdentite->dateCreation, 0, 10));
                $oDatetime2 = date_create();
                $oInterval  = date_diff($oDatetime1, $oDatetime2);

                //if création moins de 720 jours -> demande de coordonnées puis message dédié
                if ($oInterval->days < 720) {
                    $this->redirectEtape1('/depot_de_dossier/prospect/' . $this->projects->hash, projects_status::PAS_3_BILANS);
                } elseif ($oInterval->days > 720 && $oInterval->days < 1080) {
                    // question 3 bilans
                    $this->redirectEtape1('/depot_de_dossier/etape2/' . $this->projects->hash . '/1080', projects_status::COMPLETUDE_ETAPE_2);
                }

                $this->redirectEtape1('/depot_de_dossier/etape2/' . $this->projects->hash, projects_status::COMPLETUDE_ETAPE_2);
                break;
        }
    }

    private function redirectEtape1($sPage, $iProjectStatus)
    {
        $this->projects_status_history->addStatus(-2, $iProjectStatus, $this->projects->id_project);
        unset($_SESSION['forms']['depot-de-dossier']);
        header('Location: ' . $this->lurl . $sPage);
        die;
    }

    public function _etape2()
    {
        $this->checkClient();

        $this->page = 2;

        $this->lng['etape1'] = $this->ln->selectFront('depot-de-dossier-etape-1', $this->language, $this->App);
        $this->lng['etape2'] = $this->ln->selectFront('depot-de-dossier-etape-2', $this->language, $this->App);

        $this->meta_title       = $this->lng['depot-de-dossier-header']['meta-title-etape-2'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-title-etape-2'];
        $this->meta_keywords    = $this->lng['depot-de-dossier-header']['meta-title-etape-2'];

        $this->settings->get('Lien conditions generales depot dossier', 'type');
        $this->lienConditionsGenerales = $this->settings->value;

        $this->settings->get('Durée des prêts autorisées', 'type');
        $this->dureePossible = empty($this->settings->value) ? array(24, 36, 48, 60) : explode(',', $this->settings->value);

        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');

        $this->projects->get($this->params['0'], 'hash');
        $this->companies->get($this->projects->id_company);
        $this->clients->get($this->companies->id_client_owner);

        if (is_numeric($this->projects->id_prescripteur)) {
            $this->prescripteurs->get($this->projects->id_prescripteur, 'id_prescripteur');
        }

        if ($this->preteurCreateEmprunteur === true && $this->clients->status_depot_dossier >= 1) {
            $bConditionOk = true;

        } elseif (intval($this->clients->status) === 0 && $this->clients->status_depot_dossier >= 1) {
            $bConditionOk = true;
        } else {
            header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
            die;
        }

        // TODO decide how data will be provided for the function
        //data only for development purposes. if data comes from settings or from a special table needs to be decided
        $aMinMaxDuree = array(array('min' => 0, 'max' => 50000, 'heures' => 96), array('min' => 50001, 'max' => 80000, 'heures' => 192), array('min' => 80001, 'max' => 120000, 'heures' => 264), array('min' => 120001, 'max' => 1000000, 'heures' => 5 * 24));

        foreach ($aMinMaxDuree as $line) {
            if ($line['min'] <= $this->projects->amount && $this->projects->amount <= $line['max']) {
                $this->iDuree = ($line['heures'] / 24);
            } else {
                //arbitrary choice
                $this->iDuree = 10;
            }
        }

        if ($bConditionOk === true) {
            // Form depot de dossier etape 2
            if (isset($_POST['send_form_depot_dossier'])) {

                $bForm_ok = true;

                if (!isset($_POST['sex_representative']) || $_POST['sex_representative'] == '') {
                    $bForm_ok = false;
                }
                if (!isset($_POST['nom_representative']) || $_POST['nom_representative'] == '' || $_POST['nom_representative'] == $this->lng['etape2']['nom']) {
                    $bForm_ok = false;
                }
                if (!isset($_POST['prenom_representative']) || $_POST['prenom_representative'] == '' || $_POST['prenom_representative'] == $this->lng['etape2']['prenom']) {
                    $bForm_ok = false;
                }
                if (!isset($_POST['portable_representative']) ||
                    $_POST['portable_representative'] == '' ||
                    $_POST['portable_representative'] == $this->lng['etape2']['telephone'] ||
                    strlen($_POST['portable_representative']) < 9 ||
                    strlen($_POST['portable_representative']) > 14
                ) {
                    $bForm_ok = false;
                }
                if (!isset($_POST['fonction_representative']) || $_POST['fonction_representative'] == '' || $_POST['fonction_representative'] == $this->lng['etape2']['fonction']) {
                    $bForm_ok = false;
                }
                if (!isset($_POST['email_representative']) ||
                    $_POST['email_representative'] == '' ||
                    $_POST['email_representative'] == $this->lng['etape2']['email'] ||
                    $this->ficelle->isEmail($_POST['email_representative']) == false ||
                    $_POST['email_representative'] != $_POST['conf_email_representative']
                ) {
                    $bForm_ok = false;
                }

                $this->clients->civilite = $_POST['sex_representative'];
                $this->clients->nom      = $_POST['nom_representative'];
                $this->clients->prenom   = $_POST['prenom_representative'];
                $this->clients->fonction = $_POST['fonction_representative'];
                $this->clients->mobile   = $_POST['portable_representative'];
                $this->clients->email    = $_POST['email_representative'];

                if (!isset($_POST['raison-sociale']) || $_POST['raison-sociale'] == '' || $_POST['raison-sociale'] == $this->lng['etape2']['raison-sociale']) {
                    $bForm_ok = false;
                }
                $this->companies->name = $_POST['raison-sociale'];

                // if it si not a gerant, its a prescripteur so the form needs to be validated.`
                // CGU are only visible if its a gerant, so it is checked in the else.
                if (isset($_POST['gerant']) && $_POST['gerant'] == 3) {
                    if (!isset($_POST['gender_prescripteur']) || $_POST['gender_prescripteur'] == '') {
                        $bForm_ok = false;
                    }
                    if (!isset($_POST['prescripteur_nom']) || $_POST['prescripteur_nom'] == '' || $_POST['prescripteur_nom'] == $this->lng['etape2']['nom']) {
                        $bForm_ok = false;
                    }
                    if (!isset($_POST['prescripteur_prenom']) || $_POST['prescripteur_prenom'] == '' || $_POST['prescripteur_prenom'] == $this->lng['etape2']['prenom']) {
                        $bForm_ok = false;
                    }

                    if (!isset($_POST['prescripteur_email']) ||
                        $_POST['prescripteur_email'] == '' ||
                        $_POST['prescripteur_email'] == $this->lng['etape2']['email'] ||
                        $this->ficelle->isEmail($_POST['prescripteur_email']) == false ||
                        $_POST['prescripteur_email'] != $_POST['prescripteur_conf_email'] ||
                        $this->clients->existEmail($_POST['prescripteur_email']) == false
                    ) {
                        $bForm_ok = false;
                    }

                    if (!isset($_POST['prescripteur_phone']) ||
                        $_POST['prescripteur_phone'] == '' ||
                        $_POST['prescripteur_phone'] == $this->lng['etape2']['telephone'] ||
                        strlen($_POST['prescripteur_phone']) < 9 ||
                        strlen($_POST['prescripteur_phone']) > 14
                    ) {
                        $bForm_ok = false;
                    }

                    $this->prescripteurs->civilite = $_POST['gender_prescripteur'];
                    $this->prescripteurs->nom      = $_POST['prescripteur_nom'];
                    $this->prescripteurs->prenom   = $_POST['prescripteur_prenom'];
                    $this->prescripteurs->mobile   = $_POST['prescripteur_phone'];
                    $this->prescripteurs->email    = $_POST['prescripteur_email'];
                } else {
                    if (!isset($_POST['accept-cgu']) || $_POST['accept-cgu'] != true) {
                        $bForm_ok = false;
                    }
                }

                if (isset($_POST['comments']) && $_POST['comments'] != $this->lng['etape2']['toutes-informations-utiles']) {
                    $this->projects_comments = $_POST['comments'];
                } else {
                    $this->projects_comments = '';
                }

                // if there is the question about 3 bilans, it needs to be answered
                if (isset($_POST['trois_bilans'])) {
                    if (!isset($_POST['comptables']) || $_POST['comptables'] == '') {
                        $bForm_ok = false;
                    }
                }

                if (!isset($_POST['duree']) || $_POST['duree'] == 0 || in_array($_POST['duree'], $this->dureePossible) == false) {
                    $bForm_ok = false;
                }
                $this->projects->period = $_POST['duree'];

                if ($bForm_ok) {
                    // only gerant needs to accept CGU, not the prescripteur
                    if ($_POST['gerant'] == 3) {
                        if (is_numeric($this->prescripteurs->id)) {
                            $this->prescripteurs->update();
                        } else {
                            $this->prescripteurs->id_prescripteur = $this->prescripteurs->create();
                        }
                        $this->projects->id_prescripteur = $this->prescripteurs->id_prescripteur;
                    } else {
                        // -- acceptation des cgu -- //
                        $this->acceptations_legal_docs->get($this->lienConditionsGenerales, 'id_client = "' . $this->clients->id_client . '" AND id_legal_doc') ? $bAccept_ok = true : $bAccept_ok = false;
                        $this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGenerales;
                        $this->acceptations_legal_docs->id_client    = $this->clients->id_client;
                        $bAccept_ok == true ? $this->acceptations_legal_docs->update() : $this->acceptations_legal_docs->create();
                        // -- fin partie cgu -- //
                    }

                    $bComptables = true;
                    if (isset($_POST['trois_bilans'])) {
                        (intval($_POST['comptables']) === 0) ? $bComptables = false : $bComptables = true;
                    }
                    // clients
                    $this->clients->id_langue = 'fr';
                    $this->clients->slug      = $this->bdd->generateSlug($this->clients->prenom . '-' . $this->clients->nom);

                    // l'email facture est la meme que l'email client a la creation
                    $this->companies->email_facture = $this->clients->email;

                    // si good page confirmation
                    $this->projects_status_history->addStatus(-2, projects_status::COMPLETUDE_ETAPE_2, $this->projects->id_project);

                    // Creation du mot de passe client
                    $lemotdepasse = '';
                    if (isset($_SESSION['client'])) {
                        $this->clients->status_pre_emp = 3;
                        $_SESSION['status_pre_emp']    = 1;
                    } else {
                        $this->clients->status_pre_emp = 2;
                        $lemotdepasse                  = $this->ficelle->generatePassword(8);
                        $this->clients->password       = md5($lemotdepasse);
                    }

                    // put in 3 so he can't got back on to etape 2
                    $this->clients->status_depot_dossier = 2;

                    //used in bootstrap and ajax depot de dossier
                    $this->clients->status_transition = 1;

                    $this->companies->update();
                    $this->companies_details->update();
                    $this->projects->update();
                    $this->prescripteurs->update();
                    $this->clients->update();

                    // todo change person recieving the email from client or prescripteur if there is one

                    // if 3 bilans are ok, we send the email, otherwise redirect to "not eligible page" but all data is saved. No email is sent.
                    if ($bComptables) {
                        //**********************************************//
                        //*** ENVOI DU MAIL CONFIRMATION INSCRIPTION ***//
                        //**********************************************//
                        // Recuperation du modele de mail
                        $this->mails_text->get('confirmation-depot-de-dossier', 'lang = "' . $this->language . '" AND type');

                        // Variables du mailing
                        $surl  = $this->surl;
                        $url   = $this->lurl;
                        $login = $this->clients->email;
                        //$mdp = $lemotdepasse;

                        $this->settings->get('Facebook', 'type');
                        $lien_fb = $this->settings->value;

                        $this->settings->get('Twitter', 'type');
                        $lien_tw = $this->settings->value;

                        // Variables du mailing
                        $varMail = array(
                            'surl' => $surl,
                            'url' => $url,
                            'password' => $lemotdepasse,
                            'lien_fb' => $lien_fb,
                            'lien_tw' => $lien_tw);

                        // Construction du tableau avec les balises EMV
                        $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                        // Attribution des données aux variables
                        $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                        $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                        $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                        // Envoi du mail
                        $this->email = $this->loadLib('email', array());
                        $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                        $this->email->setSubject(stripslashes($sujetMail));
                        $this->email->setHTMLBody(stripslashes($texteMail));

                        if ($this->Config['env'] == 'prod') // nmp
                        {
                            Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                            // Injection du mail NMP dans la queue
                            $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                        } else // non nmp
                        {
                            $this->email->addRecipient(trim($this->clients->email));
                            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        }
                        // fin mail
                        //**********************************************//
                        //*** ENVOI DU MAIL NOTIFICATION INSCRIPTION ***//
                        //**********************************************//
                        // destinataire
                        $this->settings->get('Adresse notification inscription emprunteur', 'type');
                        $destinataire = $this->settings->value;

                        // Recuperation du modele de mail
                        $this->mails_text->get('notification-depot-de-dossier', 'lang = "' . $this->language . '" AND type');

                        // Variables du mailing
                        $surl         = $this->surl;
                        $url          = $this->lurl;
                        $nom_societe  = utf8_decode($this->companies->name);
                        $montant_pret = $this->projects->amount;
                        $lien         = $this->aurl . '/emprunteurs/edit/' . $this->clients->id_client;

                        // Attribution des données aux variables
                        $sujetMail = htmlentities($this->mails_text->subject);
                        eval("\$sujetMail = \"$sujetMail\";");

                        $texteMail = $this->mails_text->content;
                        eval("\$texteMail = \"$texteMail\";");

                        $exp_name = $this->mails_text->exp_name;
                        eval("\$exp_name = \"$exp_name\";");

                        // Nettoyage de printemps
                        $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
                        $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

                        // Envoi du mail
                        $this->email = $this->loadLib('email', array());
                        $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                        $this->email->addRecipient(trim($destinataire));
                        //$this->email->addBCCRecipient('');

                        $this->email->setSubject('=?UTF-8?B?' . base64_encode(html_entity_decode($sujetMail)) . '?=');
                        $this->email->setHTMLBody($texteMail);
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        // fin mail
                        // Page confirmation
                        header('Location: ' . $this->lurl . '/depot_de_dossier/etape3/' . $this->projects->hash);
                        die;
                    } else {
                        $this->projects_status_history->addStatus(-2, projects_status::PAS_3_BILANS, $this->projects->id_project);
                        header('Location: ' . $this->lurl . '/depot_de_dossier/nok/pas-3-bilans');
                        die;
                    }
                }
            }
        }
    }

    public function _prospect()
    {
        $this->checkClient();

        $this->lng['etape1']           = $this->ln->selectFront('depot-de-dossier-etape-1', $this->language, $this->App);
        $this->lng['etape2']           = $this->ln->selectFront('depot-de-dossier-etape-2', $this->language, $this->App);
        $this->lng['depot-de-dossier'] = $this->ln->selectFront('depot-de-dossier', $this->language, $this->App);

        $this->settings->get('Lien conditions generales depot dossier', 'type');
        $this->lienConditionsGenerales = $this->settings->value;

        $this->projects->get($this->params['0'], 'hash');
        $this->companies->get($this->projects->id_company);
        $this->clients->get($this->companies->id_client_owner);

        if (is_numeric($this->projects->id_prescripteur)) {
            $this->prescripteurs->get($this->projects->id_prescripteur, 'id_prescripteur');
        }

        if ($this->preteurCreateEmprunteur == true && $this->clients->status_depot_dossier >= 1) {
            $conditionOk = true;
        } elseif (intval($this->clients->status) === 0 && $this->clients->status_depot_dossier >= 1) {
            $conditionOk = true;
        } else {
            header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
        }

        // load date for form (client, company, project and prescripteur
        if ($conditionOk == true) {
            // Form depot de dossier etape 2
            if (isset($_POST['send_form_coordonnees'])) {
                $bForm_ok = true;

                if (!isset($_POST['sex_representative']) || $_POST['sex_representative'] == '') {
                    $bForm_ok = false;
                }
                if (!isset($_POST['nom_representative']) || $_POST['nom_representative'] == '' || $_POST['nom_representative'] == $this->lng['etape2']['nom']) {
                    $bForm_ok = false;
                }
                if (!isset($_POST['prenom_representative']) || $_POST['prenom_representative'] == '' || $_POST['prenom_representative'] == $this->lng['etape2']['prenom']) {
                    $bForm_ok = false;
                }
                if (!isset($_POST['portable_representative']) ||
                    $_POST['portable_representative'] == '' ||
                    $_POST['portable_representative'] == $this->lng['etape2']['telephone'] ||
                    strlen($_POST['portable_representative']) < 9 ||
                    strlen($_POST['portable_representative']) > 14
                ) {
                    $bForm_ok = false;
                }
                if (!isset($_POST['fonction_representative']) || $_POST['fonction_representative'] == '' || $_POST['fonction_representative'] == $this->lng['etape2']['fonction']) {
                    $bForm_ok = false;
                }
                if (!isset($_POST['email_representative']) ||
                    $_POST['email_representative'] == '' ||
                    $_POST['email_representative'] == $this->lng['etape2']['email'] ||
                    $this->ficelle->isEmail($_POST['email_representative']) == false ||
                    $_POST['email_representative'] != $_POST['conf_email_representative']
                ) {
                    $bForm_ok = false;
                }

                $this->clients->civilite = $_POST['sex_representative'];
                $this->clients->nom      = $_POST['nom_representative'];
                $this->clients->prenom   = $_POST['prenom_representative'];
                $this->clients->fonction = $_POST['fonction_representative'];
                $this->clients->mobile   = $_POST['portable_representative'];
                $this->clients->email    = $_POST['email_representative'];

                if (!isset($_POST['raison-sociale']) || $_POST['raison-sociale'] == '' || $_POST['raison-sociale'] == $this->lng['etape2']['raison-sociale']) {
                    $bForm_ok = false;
                }
                $this->companies->name = $_POST['raison-sociale'];


                if (isset($_POST['gerant']) && $_POST['gerant'] == 3) {
                    if (!isset($_POST['gender_prescripteur']) || $_POST['gender_prescripteur'] == '') {
                        $bForm_ok = false;
                    }
                    if (!isset($_POST['prescripteur_nom']) || $_POST['prescripteur_nom'] == '' || $_POST['prescripteur_nom'] == $this->lng['etape2']['nom']) {
                        $bForm_ok = false;
                    }
                    if (!isset($_POST['prescripteur_prenom']) || $_POST['prescripteur_prenom'] == '' || $_POST['prescripteur_prenom'] == $this->lng['etape2']['prenom']) {
                        $bForm_ok = false;
                    }

                    if (!isset($_POST['prescripteur_email']) ||
                        $_POST['prescripteur_email'] == '' ||
                        $_POST['prescripteur_email'] == $this->lng['etape2']['email'] ||
                        $this->ficelle->isEmail($_POST['prescripteur_email']) == false ||
                        $_POST['prescripteur_email'] != $_POST['prescripteur_conf_email'] ||
                        $this->clients->existEmail($_POST['prescripteur_email']) == false
                    ) {
                        $bForm_ok = false;
                    }

                    if (!isset($_POST['prescripteur_phone']) ||
                        $_POST['prescripteur_phone'] == '' ||
                        $_POST['prescripteur_phone'] == $this->lng['etape2']['telephone'] ||
                        strlen($_POST['prescripteur_phone']) < 9 ||
                        strlen($_POST['prescripteur_phone']) > 14
                    ) {
                        $bForm_ok = false;
                    }

                    $this->prescripteurs->civilite = $_POST['gender_prescripteur'];
                    $this->prescripteurs->nom      = $_POST['prescripteur_nom'];
                    $this->prescripteurs->prenom   = $_POST['prescripteur_prenom'];
                    $this->prescripteurs->mobile   = $_POST['prescripteur_phone'];
                    $this->prescripteurs->email    = $_POST['prescripteur_email'];

                } // end if prescripteur

                if ($bForm_ok) {

                    if ($_POST['gerant'] == 3) {
                        if (is_numeric($this->prescripteurs->id_prescripteur)) {
                            $this->prescripteurs->update();
                        } else {
                            $this->prescripteurs->id_prescripteur = $this->prescripteurs->create();
                        }
                        $this->projects->id_prescripteur = $this->prescripteurs->id_prescripteur;
                    }

                    // clients
                    $this->clients->id_langue = 'fr';
                    $this->clients->slug      = $this->bdd->generateSlug($this->clients->prenom . '-' . $this->clients->nom);

                    // l'email facture est la meme que l'email client a la creation
                    $this->companies->email_facture = $this->clients->email;

                    // On fait une mise à jour
                    $this->clients->update();
                    $this->companies->update();
                    $this->companies_details->update();
                    $this->projects->update();
                    $this->prescripteurs->update();

                    // si good page confirmation
                    $this->projects_status_history->addStatus(-2, projects_status::PAS_3_BILANS, $this->projects->id_project);
                    header('Location: ' . $this->lurl . '/depot_de_dossier/merci/prospect');
                }
            }
        }
    }

    public function _etape3()
    {
        $this->checkClient();

        $this->projects->get($this->params['0'], 'hash');
        $this->companies->get($this->projects->id_company);
        $this->clients->get($this->companies->id_client_owner);

        if ($this->preteurCreateEmprunteur === true && $this->clients->status_depot_dossier >= 2) {
            $bConditionOk = true;
        } elseif (intval($this->clients->status) === 0 && $this->clients->status_depot_dossier >= 2) {
            $bConditionOk = true;
        } else {
            header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
        }

        $this->page = 3;

        $this->lng['etape1'] = $this->ln->selectFront('depot-de-dossier-etape-1', $this->language, $this->App);
        $this->lng['etape2'] = $this->ln->selectFront('depot-de-dossier-etape-2', $this->language, $this->App);
        $this->lng['etape3'] = $this->ln->selectFront('depot-de-dossier-etape-3', $this->language, $this->App);

        $this->projects->get($this->params['0'], 'hash');
        $this->companies->get($this->projects->id_company);
        $this->clients->get($this->companies->id_client_owner);

        //Calcul de la mensualite en tenant compte du montant/ duree / taux min et taux max et frais

        $this->financialClass = $this->loadLib('financial');
        $this->settings->get('Tri par taux intervalles', 'type');
        $sTauxIntervalles = $this->settings->value;

        $iMontant = $this->projects->amount;
        $iDuree   = $this->projects->period;

        $iTauxMax = (substr($sTauxIntervalles, -2) / 100);
        $iTauxMin = (substr($sTauxIntervalles, 0, 1) / 100);
        $iTauxCom = 0.01;
        $iTva     = 0.02;

        $iMensualite_min = $this->financialClass->PMT(($iTauxMin / 12), $iDuree, ($iMontant * -1));
        $iMensualite_max = $this->financialClass->PMT(($iTauxMax / 12), $iDuree, ($iMontant * -1));
        $iCommission     = ($this->financialClass->PMT(($iTauxCom / 12), $iDuree, ($iMontant * -1))) - ($this->financialClass->PMT(0, $iDuree, ($iMontant * -1)));

        $this->mensualite_min_ttc = round($iMensualite_min + $iCommission * (1 + $iTva));
        $this->mensualite_max_ttc = round($iMensualite_max + $iCommission * (1 + $iTva));


        //year considered for "latest liasse fiscal" necessary to get the information from Bilan and actif_passif
        $iYear = (date('Y', time()) - 1);

        $aCompaniesBilan         = $this->companies_bilans->select('id_company = ' . $this->companies->id_company . ' AND date = ' . $iYear);
        $aCompanies_actif_passif = $this->companies_actif_passif->select('id_company = ' . $this->companies->id_company . ' AND annee = ' . $iYear);

        $this->iRex          = $aCompaniesBilan[0]['resultat_exploitation'];
        $this->iCa           = $aCompaniesBilan[0]['ca'];
        $this->iFondsPropres = $aCompanies_actif_passif[0]['capitaux_propres'];

        if ($bConditionOk && isset($_POST['send_form_etape_3'])) {
            $bFormOk = true;

            if (!isset($_POST['fonds_propres']) || $_POST['fonds_propres'] == '') {
                $bFormOk = false;
            }
            if (!isset($_POST['ca']) || $_POST['ca'] == '') {
                $bFormOk = false;
            }
            if (!isset($_POST['resultat_brute_exploitation']) || $_POST['resultat_brute_exploitation'] == '') {
                $bFormOk = false;
            }

            if (!isset($_FILES['liasse_fiscal']) && $_FILES['liasse_fiscal']['name'] == '') {
                $bFormOk = false;
            }
            $this->iRex          = $_POST['resultat_brute_exploitation'];
            $this->iCa           = $_POST['ca'];
            $this->iFondsPropres = $_POST['fonds_propres'];

            if ($bFormOk) {

                $this->uploadAttachment($this->projects->id_project, 'liasse_fiscal', attachment_type::DERNIERE_LIASSE_FISCAL);

                if (empty($_FILES['autre']) == false) {
                    $this->uploadAttachment($this->projects->id_project, 'autre', attachment_type::AUTRE1);
                }
                $this->clients->status_depot_dossier = 3;

                $this->projects->fonds_propres_declara_client         = $this->iFondsPropres;
                $this->projects->resultat_exploitation_declara_client = $this->iRex;
                $this->projects->ca_declara_client                    = $this->iCa;


                $this->clients->update();
                $this->projects->update();

                if ($this->projects->resultat_exploitation_declara_client < 0 || $this->projects->ca_declara_client < 100000 || $this->projects->fonds_propres_declara_client < 10000) {
                    $this->projects_status_history->addStatus(-2, projects_status::NOTE_EXTERNE_FAIBLE, $this->projects->id_project);
                    header('Location: ' . $this->lurl . '/depot_de_dossier/nok/rex-nega');
                    die;
                }


                if (isset($_POST['procedure_acceleree'])) {
                    $this->projects->process_fast = 1;
                    $this->projects->update();
                    //TODO une fois que le status et la constante sont crées
                    //$this->projects_status_history->addStatus(-2, projects_status::COMPLETUDE_ETAPE_3, $this->projects->id_project);
                    header('Location: ' . $this->lurl . '/depot_de_dossier/fichiers/' . $this->projects->hash);
                    die;
                } else {
                    //TODO envoi de mail pour reprise de dossier

                    //client stauts change to online has been done in former functions and seems to be used to validate a projet.
                    $this->clients->status = 1;
                    $this->clients->update();
                    $this->projects_status_history->addStatus(-2, projects_status::A_TRAITER, $this->projects->id_project);
                    header('Location: ' . $this->lurl . '/depot_de_dossier/merci');
                    die;
                }
            }
        }
    }

    public function _fichiers()
    {
        $this->checkClient();


        if ($this->preteurCreateEmprunteur === true && $this->clients->status_depot_dossier >= 3) {
            $bConditionOk = true;
        } elseif (intval($this->clients->status) === 0 && $this->clients->status_depot_dossier >= 3) {
            $bConditionOk = true;
        } else {
            header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
        }

        $this->projects->get($this->params['0'], 'hash');
        $this->companies->get($this->projects->id_company);
        $this->clients->get($this->companies->id_client_owner);

        if ($this->projects_status->getLastStatut($this->projects->id_project) === true && intval($this->projects_status->status) === projects_status::ABANDON) {
            header('Location: ' . $this->lurl . '/depot_de_dossier/merci/abandon');
        }
        if ($this->projects_status->getLastStatut($this->projects->id_project) === true && intval($this->projects_status->status) >= projects_status::REVUE_ANALYSTE) {
            header('Location: ' . $this->lurl . '/depot_de_dossier/merci/analyse');
        }

        // if you change the method in attachement_type think of adding the new attachement types in the upload function below
        // TODO use trads for Types of files
        $this->aAttachmentTypes = $this->attachment_type->getAllTypesForProjects();

        if (isset($_POST['submit_files']) && !empty($_FILES)) {

            //reformat $_FILES so it can be treated by the upload function
            $aPostFiles = $_FILES['files'];

            $aFiles     = array();
            $iFileCount = count($aPostFiles['name']);
            $aFileKeys  = array_keys($aPostFiles);

            for ($i = 0; $i < $iFileCount; $i++) {
                foreach ($aFileKeys as $key) {
                    $aFiles[$i][$key] = $aPostFiles[$key][$i];
                }
            }

            foreach ($_POST['type_document'] as $key => $iAttachmentType) {
                $this->uploadAttachment($this->projects->id_project, $key, $iAttachmentType, $aFiles);
            }
            $this->projects_status_history->addStatus(-2, projects_status::A_TRAITER, $this->projects->id_project);
            header('Location: ' . $this->lurl . '/depot_de_dossier/merci/procedure-accelere');
        }

    }

    public function _merci()
    {
        $this->checkClient();

        $this->lng['depot-de-dossier'] = $this->ln->selectFront('depot-de-dossier', $this->language, $this->App);

        // the idea is to display exactly the same contact form as in the contact section of the page.

        //all data that is needed for the form, just as it is called in the root controller
        //Recuperation des element de traductions
        $this->lng['contact']  = $this->ln->selectFront('contact', $this->language, $this->App);
        $this->demande_contact = $this->loadData('demande_contact');
        $contenu               = $this->tree_elements->select('id_tree = 47 AND id_langue = "' . $this->language . '"');
        foreach ($contenu as $elt) {
            $this->elements->get($elt['id_element']);
            $this->content[$this->elements->slug]    = $elt['value'];
            $this->complement[$this->elements->slug] = $elt['complement'];
        }
        // Creation du breadcrumb
        $this->breadCrumb   = $this->tree->getBreadCrumb('47', $this->language);
        $this->nbBreadCrumb = count($this->breadCrumb);

        //TODO get rid of the contact messages

        if (isset($_POST['send_form_contact'])) {

            include $this->path . 'apps/default/controllers/root.php';
            $oCommand = new Command('root', '_default', array(), $this->language);
            $oRoot    = new rootController($oCommand, $this->Config, 'default');
            $oRoot->contact();
            $this->confirmation = $this->lng['contact']['confirmation'];
        }
    }


    public function _stand_by()
    {
        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'hash')) {
            $this->companies->get($this->projects->id_company);
            $this->clients->get($this->companies->id_client_owner);
            header('Location: ' . $this->lurl . '/depot_de_dossier/etape' . ($this->clients->status_depot_dossier + 1) . '/' . $this->projects->hash);
        } else {
            header('Location: ' . $this->lurl);
        }
    }

    public function _error()
    {
        $this->lng['etape1'] = $this->ln->selectFront('depot-de-dossier-etape-1', $this->language, $this->App);
    }

    public function _nok()
    {
        $this->lng['etape1']               = $this->ln->selectFront('depot-de-dossier-etape-1', $this->language, $this->App);
        $this->lng['etape2']               = $this->ln->selectFront('depot-de-dossier-etape-2', $this->language, $this->App);
        $this->lng['depot-de-dossier-nok'] = $this->ln->selectFront('depot-de-dossier-nok', $this->language, $this->App);
    }

    private function emailAltares($id_project, $project_title)
    {
        $subject = '[Alerte] Webservice Altares sans reponse';
        $message = '
                        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                        <html xmlns="http://www.w3.org/1999/xhtml">
                        <head>
                            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                            <title>Webservice Altares sans r&eacute;ponse</title>
                        </head>
                        <style>
                            table {width: 100%;}
                            th {height: 50px;}
                        </style>

                        <body >
                            <table border="0" width="450" style="margin:auto;">
                                <tr>
                                        <td colspan="2" ><img src="' . $this->surl . '/images/default/emails/logo.png" alt="logo" /></td>
                                </tr>
                                <tr>
                                        <td colspan="2">Le Webservice Altares ne semble pas r&eacute;pondre</td>
                                </tr>

                                <tr>
                                    <td colspan="2">Projet touch&eacute; :</td>
                                </tr>
                            </table>

                            <br />
                            Id Projet : ' . $id_project . '<br />
                            Nom : ' . $project_title . '

                        </body>
                        </html>';

        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";

        $this->settings->get('Adresse alerte altares erreur', 'type');
        $to = $this->settings->value;

        mail($to, $subject, $message, $headers);
    }

    /**
     * @param integer $iOwnerId
     * @param integer $field
     * @param integer $iAttachmentType
     * @param array $aFiles
     * @return bool
     */
    private function uploadAttachment($iOwnerId, $field, $iAttachmentType, $aFiles = null)
    {
        if ($aFiles === null) {
            $aFiles = $_FILES;
        }

        if (false === isset($this->attachmentHelper) || false === $this->attachmentHelper instanceof attachment_helper) {
            $this->attachmentHelper = $this->loadLib('attachment_helper');
        }

        if (false === isset($this->upload) || false === $this->upload instanceof upload) {
            $this->upload = $this->loadLib('upload');
        }

        if (false === isset($this->attachment) || false === $this->attachment instanceof attachment) {
            $this->attachment = $this->loadData('attachment');
        }

        $basePath = 'protected/projects/';

        //add the new name for each file
        foreach ($aFiles as $f => $file) {

            $aNom_tmp                  = explode('.', $aFiles[$f]['name']);
            $aFiles[$f]['no_ext_name'] = $aNom_tmp[0];
        }

        switch ($iAttachmentType) {
            case attachment_type::RELEVE_BANCAIRE_MOIS_N :
                $uploadPath = $basePath . 'releve_bancaire_mois_n/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::RELEVE_BANCAIRE_MOIS_N_1 :
                $uploadPath = $basePath . 'releve_bancaire_mois_n_1/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::RELEVE_BANCAIRE_MOIS_N_2:
                $uploadPath = $basePath . 'releve_bancaire_mois_n_2/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::PRESENTATION_ENTRERPISE:
                $uploadPath = $basePath . 'presentation_entreprise/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::ETAT_ENDETTEMENT:
                $uploadPath = $basePath . 'etat_endettement/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::DERNIERE_LIASSE_FISCAL :
                $uploadPath = $basePath . 'liasse_fiscal/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::LIASSE_FISCAL_N_1:
                $uploadPath = $basePath . 'liasse_fiscal_n_1/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::LIASSE_FISCAL_N_2:
                $uploadPath = $basePath . 'liasse_fiscal_n_2/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::RAPPORT_CAC:
                $uploadPath = $basePath . 'rapport_cac/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::PREVISIONNEL:
                $uploadPath = $basePath . 'previsionnel/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::CNI_PASSPORTE_DIRIGEANT :
                $uploadPath = $basePath . 'cni_passeport_dirigeant/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::CNI_PASSPORTE_VERSO :
                $uploadPath = $basePath . 'cni_passeport_dirigeant_verso/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::RIB :
                $uploadPath = $basePath . 'rib/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::KBIS :
                $uploadPath = $basePath . 'extrait_kbis/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::AUTRE1 :
                $uploadPath = $basePath . 'autre/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::AUTRE2 :
                $uploadPath = $basePath . 'autre2/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::AUTRE3:
                $uploadPath = $basePath . 'autre3/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::BALANCE_CLIENT:
                $uploadPath = $basePath . 'balance_client/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::BALANCE_FOURNISSEUR:
                $uploadPath = $basePath . 'balance_fournisseur/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            case attachment_type::ETAT_PRIVILEGES_NANTISSEMENTS:
                $uploadPath = $basePath . 'etat_privileges_nantissements/';
                $sNewName   = $aFiles[$field]['no_ext_name'] . '_' . $iOwnerId;
                break;
            default :
                return false;
        }

        $resultUpload = $this->attachmentHelper->upload($iOwnerId, attachment::PROJECT, $iAttachmentType, $field, $this->path, $uploadPath, $this->upload, $this->attachment, $sNewName, $aFiles);

        if (false === $resultUpload) {
            $this->form_ok = false;

            $this->error_fichier = true;
        }

        return $resultUpload;
    }

    private function checkClient()
    {
        $this->preteurCreateEmprunteur = false;

        if (isset($_SESSION['client'])) {
            $this->clients->get($_SESSION['client']['id_client'], 'id_client');

            switch ($this->clients->status_pre_emp) {
                case '1':
                    $_SESSION['error_pre_empr'] = $this->lng['etape1']['seule-une-personne-morale-peut-creer-un-compte-emprunteur'];
                    break;
                case '2':
                case '3':
                    $_SESSION['error_pre_empr'] = $this->lng['etape1']['vous-disposez-deja-dun-compte-emprunteur'];
                    break;
                default:
                    $_SESSION['error_pre_empr'] = $this->lng['etape1']['seule-une-personne-morale-peut-creer-un-compte-emprunteur'];
                    break;
            }
            header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
        }
    }
}

