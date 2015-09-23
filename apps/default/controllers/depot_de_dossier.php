<?php

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

        $this->navigateurActive = 3;

        $this->lng['depot-de-dossier-header'] = $this->ln->selectFront('depot-de-dossier-header', $this->language, $this->App);

        // Altares
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
        header('location: ' . $this->lurl . '/lp-depot-de-dossier');
    }

    public function _interrogation()
    {
        $this->checkClient();

        $this->page = 1;

        $this->settings->get('Somme à emprunter min', 'type');
        $this->sommeMin = $this->settings->value;

        $this->settings->get('Somme à emprunter max', 'type');
        $this->sommeMax = $this->settings->value;

        $this->lng['landing-page'] = $this->ln->selectFront('landing-page', $this->language, $this->App);

        // source -> mets utm_source dans la session
        $this->ficelle->source($_GET['utm_source'], $this->lurl . '/' . $this->params[0], $_GET['utm_source2']);

        $reponse_get = false;

        // Si on a les get en question
        if (isset($_GET['montant']) && $_GET['montant'] != '' &&
            isset($_GET['siren']) && $_GET['siren'] != ''
        ) {
            $reponse_get = true;
        }

        // On récupère le formulaire d'inscription de la page
        if (isset($_POST['spy_inscription_landing_page_depot_dossier']) || $reponse_get == true) {
            if ($reponse_get == true) {
                $montant = str_replace(',', '.', str_replace(' ', '', $_GET['montant']));
                $siren   = $_GET['siren'];
                $email   = $_GET['email'];
            } else {
                $montant = str_replace(',', '.', str_replace(' ', '', $_POST['montant']));
                $siren   = $_POST['siren'];
                $email   = ($_POST['email'] == 'Email') ? '' : $_POST['email'];
            }

            if (isset($email) && $this->ficelle->isEmail($email) === true && $this->clients->existEmail($email) === false) {
                $this->clients->get($email, 'email');
            }

            if (isset($email) && $this->ficelle->isEmail($email) && $this->prescripteurs->exist($email, 'email') === false) {
                $this->prescripteurs->get($email, 'email');
            }

            $form_valid = true;

            if (!isset($montant) or $montant == $this->lng['landing-page']['montant-souhaite']) {
                $form_valid        = false;
                $this->retour_form = $this->lng['landing-page']['champs-obligatoires'];
            }

            if (!is_numeric($montant)) {
                $form_valid        = false;
                $this->retour_form = $this->lng['landing-page']['champs-obligatoires'];
            } elseif ($montant < $this->sommeMin || $montant > $this->sommeMax) {
                $this->form_ok = false;
            }

            if (!isset($siren) or $siren == $this->lng['landing-page']['siren'] || strlen($siren) != 9) {
                $form_valid        = false;
                $this->retour_form = $this->lng['landing-page']['champs-obligatoires'];
            }

            if ($form_valid) {
                //create client, company and project independent from eligibility

                if ($this->companies->exist($siren, $field = 'siren')) {
                    $this->companies->get($siren, 'siren');
                    //then get the client from that company in case it has not already been found by email before
                    if ($this->clients->id_client == '') {
                        $this->clients->get($this->companies->id_client_owner);
                    }
                }
                //if there is a client, check if the client is not already a "preteur", in this case send back with error message
                if (is_numeric($this->clients->id_client) && $this->clients->status_pre_emp === 1) {

                    $_SESSION['error_pre_empr'] = $this->lng['etape1']['seule-une-personne-morale-peut-creer-un-compte-emprunteur'];
                    header('Location: ' . $this->lurl . '/depot_de_dossier/lp-depot-de-dossier');
                }

                $this->clients->id_langue            = $this->language;
                $this->clients->status_depot_dossier = 1;
                $this->clients->slug_origine         = $this->tree->slug;

                // clients //
                if ($this->preteurCreateEmprunteur == false) {
                    $this->clients->source  = $_SESSION['utm_source'];
                    $this->clients->source2 = $_SESSION['utm_source2'];
                }

                if (isset($email) && $this->clients->email == '') {
                    $this->clients->email = $email;
                }

                if ($this->clients->id_client == '') {
                    $this->clients->id_client = $this->clients->create();
                }

                $this->companies->id_client_owner               = $this->clients->id_client; // id client
                $this->companies->siren                         = $siren;
                $this->companies->status_adresse_correspondance = '1';

                if ($this->companies->id_company == '') {
                    $this->companies->id_company = $this->companies->create();
                }

                $this->projects->id_company = $this->companies->id_company;
                if ($this->prescripteurs->id_prescripteurs == '') {
                    $this->projects->id_prescripteur = $this->prescripteurs->id_prescripteur;
                }
                $this->projects->amount     = $montant;
                $this->projects->id_company = $this->companies->id_company;
                $this->projects->id_project = $this->projects->create();

                // 1 : activé 2 : activé mais prend pas en compte le resultat 3 : desactivé (DC)
                $this->settings->get('Altares debrayage', 'type');
                $AltaresDebrayage = $this->settings->value;

                $this->settings->get('Altares email alertes', 'type');
                $AltaresEmailAlertes = $this->settings->value;

                // 1 : activé 2 : on prend pas en compte les filtres.(DC)
                if (in_array($AltaresDebrayage, array(1, 2))) {
                    $result = '';
                    try {
                        $result = $this->ficelle->ws($this->wsdl, $this->identification, $siren);
                    } catch (Exception $e) {
                        mail($AltaresEmailAlertes, '[ALERTE] ERREUR ALTARES 2', 'Date ' . date('Y-m-d H:i:s') . '' . $e->getMessage());
                        error_log("[" . date('Y-m-d H:i:s') . "] " . $e->getMessage(), 3, $this->path . '/log/error_altares.txt');
                    }

                    if ($result->exception != false) {
                        $erreur = 'Siren fourni : ' . $siren . ' | ' . $result->exception->code . ' | ' . $result->exception->description . ' | ' . $result->exception->erreur;
                        mail($AltaresEmailAlertes, '[ALERTE] ERREUR ALTARES 1', 'Date ' . date('Y-m-d H:i:s') . '' . $erreur);
                        error_log("[" . date('Y-m-d H:i:s') . "] " . $erreur . "\n", 3, $this->path . '/log/error_altares.txt');
                    }

                    // Verif si erreur
                    $exception = $result->exception;

                    // que pour le statut 2 (DC)
                    if ($AltaresDebrayage == 2) {
                        mail($AltaresEmailAlertes, '[ALERTE] Altares Tentative evaluation', 'Date ' . date('Y-m-d H:i:s') . ' siren : ' . $siren);
                    }
                } // debrayage statut 3 : altares desactivé
                else {
                    $exception = '';
                }

                // si altares ok
                if ($exception == '') {
                    $sEligibility = $result->myInfo->eligibility;
                    $this->projects->retour_altares = $sEligibility;
                    $this->projects->update();

                    switch ($sEligibility) {
                        case '1_Etablissement Inactif':
                            $this->projects_status_history->addStatus(-2, projects_status::NOTE_EXTERNE_FAIBLE, $this->projects->id_project);
                            header('Location: ' . $this->lurl . '/depot_de_dossier/nok/no-siren');
                            break;
                        case '2_Etablissement sans RCS':
                            $this->projects_status_history->addStatus(-2, projects_status::NOTE_EXTERNE_FAIBLE, $this->projects->id_project);
                            header('Location: ' . $this->lurl . '/depot_de_dossier/nok/no-rcs');
                            break;
                        case '3_Procedure Active':
                        case '4_Bilan de plus de 450 jours':
                            $this->projects_status_history->addStatus(-2, projects_status::NOTE_EXTERNE_FAIBLE, $this->projects->id_project);
                            header('Location: ' . $this->lurl . '/depot_de_dossier/nok');
                            break;
                        case '5_Fonds Propres Négatifs':
                        case '6_EBE Négatif':
                            $this->projects_status_history->addStatus(-2, projects_status::NOTE_EXTERNE_FAIBLE, $this->projects->id_project);
                            header('Location: ' . $this->lurl . '/depot_de_dossier/nok/rex-nega');
                            break;
                        case '7_SIREN inconnu':
                            $this->projects_status_history->addStatus(-2, projects_status::NOTE_EXTERNE_FAIBLE, $this->projects->id_project);
                            header('Location: ' . $this->lurl . '/depot_de_dossier/nok/no-siren');
                            break;
                        case '9_bilan sup 450 jours':
                            $this->projects_status_history->addStatus(-2, projects_status::NOTE_EXTERNE_FAIBLE, $this->projects->id_project);
                            header('Location: ' . $this->lurl . '/depot_de_dossier/nok');
                            break;
                        case '8_Eligible':
                            $this->clients_adresses->id_client = $this->clients->id_client;
                            $this->clients_adresses->create();

                            $oIdentite = $result->myInfo->identite;
                            $oScore    = $result->myInfo->score;

                            $this->companies->name                          = $oIdentite->raisonSociale;
                            $this->companies->forme                         = $oIdentite->formeJuridique;
                            $this->companies->capital                       = $oIdentite->capital;
                            $this->companies->code_naf                      = $oIdentite->naf5EntreCode;
                            $this->companies->libelle_naf                   = $oIdentite->naf5EntreLibelle;
                            $this->companies->adresse1                      = $oIdentite->rue;
                            $this->companies->city                          = $oIdentite->ville;
                            $this->companies->zip                           = $oIdentite->codePostal;
                            $this->companies->phone                         = str_replace(' ', '', $siege->telephone);
                            $this->companies->rcs                           = $oIdentite->rcs;
                            $this->companies->siret                         = $oIdentite->siret;
                            $this->companies->status_adresse_correspondance = '1';
                            $this->companies->date_creation                 = substr($oIdentite->dateCreation, 0, 10);
                            $this->companies->altares_eligibility           = $sEligibility;
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

                            $this->companies_details->id_company = $this->companies->id_company;
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


                            $syntheseFinanciereInfo = $result->myInfo->syntheseFinanciereInfo;
                            $syntheseFinanciereList = $result->myInfo->syntheseFinanciereInfo->syntheseFinanciereList;

                            $posteActifList         = array();
                            $postePassifList        = array();
                            $syntheseFinanciereInfo = array();
                            $syntheseFinanciereList = array();
                            $derniersBilans         = array();
                            $i                      = 0;

                            if ($result->myInfo->bilans != '') {
                                foreach ($result->myInfo->bilans as $b) {
                                    $annee                                 = substr($b->bilan->dateClotureN, 0, 4);
                                    $posteActifList[$annee]                = $b->bilanRetraiteInfo->posteActifList;
                                    $postePassifList[$annee]               = $b->bilanRetraiteInfo->postePassifList;
                                    $syntheseFinanciereInfo[$annee]        = $b->syntheseFinanciereInfo;
                                    $syntheseFinanciereList[$annee]        = $b->syntheseFinanciereInfo->syntheseFinanciereList;
                                    $soldeIntermediaireGestionInfo[$annee] = $b->soldeIntermediaireGestionInfo->SIGList;
                                    $investissement[$annee]                = $b->bilan->posteList[0]->valeur;

                                    // date des derniers bilans
                                    $derniersBilans[$i] = $annee;

                                    $i++;
                                }
                            }

                            $ldate = $lesdates;
                            // on génère un tableau avec les données
                            for ($i = 0; $i < 5; $i++) // on parcourt les 5 années
                            {
                                for ($a = 0; $a < 3; $a++)// on parcourt les 3 dernieres années
                                {
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
                            // Debut actif/passif
                            foreach ($derniersBilans as $annees) {
                                foreach ($posteActifList[$annees] as $a) {
                                    $ActifPassif[$annees][$a->posteCle] = $a->montant;
                                }
                                foreach ($postePassifList[$annees] as $p) {
                                    $ActifPassif[$annees][$p->posteCle] = $p->montant;
                                }
                            }
                            // Liste des actif passif
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

                            //check on creation date
                            $sAnneecreation = substr($oIdentite->dateCreation, 0, 10);
                            $oDatetime1     = date_create_from_format('Y-m-d', $sAnneecreation);
                            $oDatetime2     = date_create();
                            $oInterval      = date_diff($oDatetime1, $oDatetime2);

                            //if création moins de 720 jours -> demande de coordonnées puis message dédié
                            if ($oInterval->days < 720) {
                                $this->projects_status_history->addStatus(-2, projects_status::PAS_3_BILANS, $this->projects->id_project);
                                header('Location: ' . $this->lurl . '/depot_de_dossier/prospect/' . $this->projects->hash);
                            } //ifelse création entre 720 et 1080 jours -> question 3 bilans
                            elseif ($oInterval->days > 720 && $interval->days < 1080) {
                                $this->projects_status_history->addStatus(-2, projects_status::COMPLETUDE_ETAPE_2, $this->projects->id_project);
                                header('Location: ' . $this->lurl . '/depot_de_dossier/etape2/' . $this->projects->hash . '/1080');
                            } else {
                                sleep(1);
                                $this->projects_status_history->addStatus(-2, projects_status::COMPLETUDE_ETAPE_2, $this->projects->id_project);
                                header('Location: ' . $this->lurl . '/depot_de_dossier/etape2/' . $this->projects->hash);
                            }
                            // end eligible
                            break;
                        default:
                            $this->projects_status_history->addStatus(-2, projects_status::NOTE_EXTERNE_FAIBLE, $this->projects->id_project);
                            header('Location: ' . $this->lurl . '/depot_de_dossier/nok');
                            break;
                    }
                } else {
                    // ajout du statut dans l'historique
                    $this->projects_status_history->addStatus(-2, projects_status::NOTE_EXTERNE_FAIBLE, $this->projects->id_project);

                    //on envoie email erreur
                    $this->emailAltares($this->projects->id_project, $this->projects->title);

                    header('Location: ' . $this->lurl . '/depot_de_dossier/nok');
                }
            }
        }
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
        $this->companies               = $this->loadData('companies');
        $this->companies_details       = $this->loadData('companies_details');
        $this->companies_bilans        = $this->loadData('companies_bilans');
        $this->companies_actif_passif  = $this->loadData('companies_actif_passif');
        $this->projects                = $this->loadData('projects');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->prescripteurs           = $this->loadData('prescripteurs');

        $this->projects->get($this->params['0'], 'hash');
        $this->companies->get($this->projects->id_company);
        $this->clients->get($this->companies->id_client_owner);

        if (is_numeric($this->projects->id_prescripteur)) {
            $this->prescripteurs->get($this->projects->id_prescripteur, 'id_prescripteur');
        }

        if ($this->preteurCreateEmprunteur === true && $this->clients->status_depot_dossier >= 1) {
            $conditionOk = true;

        } elseif ($this->clients->status === 0 && $this->clients->status_depot_dossier >= 1) {
            $conditionOk = true;
        } else {
            var_dump(__LINE__);
            die;
            header('Location: ' . $this->lurl . '/depot_de_dossier/lp-depot-de-dossier');
        }

        // TODO decide how data will be provided for the function
        //data only for developement purposes. if data comes from settings or from a specia table needs to be decided
        $aMinMaxDuree = array(array('min' => 0, 'max' => 50000, 'heures' => 96), array('min' => 50001, 'max' => 80000, 'heures' => 192), array('min' => 80001, 'max' => 120000, 'heures' => 264), array('min' => 120001, 'max' => 1000000, 'heures' => 5 * 24));

        foreach ($aMinMaxDuree as $line) {
            if ($line['min'] <= $this->projects->amount && $this->projects->amount <= $line['max']) {
                $this->iDuree = ($line['heures'] / 24);
            } else {
                $this->iDuree = 10;
            }
        }

        if ($conditionOk == true) {
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
                    // if it is not the gerant, update the prescripteur data and if it is the gerant save legal docs (no legal docs for prescripteur)
                    if ($_POST['gerant'] == 3) {
                        if (is_numeric($this->prescripteurs->id)) {
                            $this->prescripteurs->update();
                        } else {
                            $this->prescripteurs->id_prescripteur = $this->prescripteurs->create();
                        }
                        $this->projects->id_prescripteur = $this->prescripteurs->id_prescripteur;
                    } else {
                        // -- acceptation des cgu -- //
                        if ($this->acceptations_legal_docs->get($this->lienConditionsGenerales, 'id_client = "' . $this->clients->id_client . '" AND id_legal_doc')) {
                            $accepet_ok = true;
                        } else {
                            $accepet_ok = false;
                        }
                        $this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGenerales;
                        $this->acceptations_legal_docs->id_client    = $this->clients->id_client;
                        if ($accepet_ok == true) {
                            $this->acceptations_legal_docs->update();
                        } else {
                            $this->acceptations_legal_docs->create();
                        }
                        // -- fin partie cgu -- //
                    }

                    $bComptables = true;
                    if (isset($_POST['trois_bilans'])) {
                        if ($_POST['comptables'] == '0') {
                            $bComptables = false;
                        } else {
                            $bComptables = true;
                        }
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
                    $this->clients->status_depot_dossier = 5;
                    $this->clients->status_transition    = 1;
                    $this->clients->status               = 1;

                    $this->companies->update();
                    $this->companies_details->update();
                    $this->projects->update();
                    $this->prescripteurs->update();
                    $this->clients->id_client = $this->clients->update();

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
                    } else {
                        $this->projects_status_history->addStatus(-2, projects_status::PAS_3_BILANS, $this->projects->id_project);
                        header('Location: ' . $this->lurl . '/depot_de_dossier/nok/pas-3-bilans');
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

        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
        $this->companies               = $this->loadData('companies');
        $this->projects                = $this->loadData('projects');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->prescripteurs           = $this->loadData('prescripteurs');

        $this->projects->get($this->params['0'], 'hash');
        $this->companies->get($this->projects->id_company);
        $this->clients->get($this->companies->id_client_owner);

        if (is_numeric($this->projects->id_prescripteur)) {
            $this->prescripteurs->get($this->projects->id_prescripteur, 'id_prescripteur');
        }

        if ($this->preteurCreateEmprunteur == true && $this->clients->status_depot_dossier >= 1) {
            $conditionOk = true;
        } elseif ($this->clients->status == 0 && $this->clients->status_depot_dossier >= 1) {
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
                    // TODO redirect to thank you page (create page)
                }
            }
        }
    }

    public function _etape3()
    {
        $this->checkClient();

        $this->page = 3;

        $this->lng['etape1'] = $this->ln->selectFront('depot-de-dossier-etape-1', $this->language, $this->App);
        $this->lng['etape2'] = $this->ln->selectFront('depot-de-dossier-etape-2', $this->language, $this->App);
        $this->lng['etape3'] = $this->ln->selectFront('depot-de-dossier-etape-3', $this->language, $this->App);

        $this->companies               = $this->loadData('companies');
        $this->companies_details       = $this->loadData('companies_details');
        $this->companies_bilans        = $this->loadData('companies_bilans');
        $this->companies_actif_passif  = $this->loadData('companies_actif_passif');
        $this->projects                = $this->loadData('projects');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->prescripteur            = $this->loadData('prescripteur');
        $this->attachment              = $this->loadData('attachment');
        $this->attachment_type         = $this->loadData('attachment_type');

        //TODO calcul de la mensualite en tenant compte du montant/ duree / taux min et taux max et frais
        // $this->mensualite_min, $this->mensualite_max

        $this->projects->get($this->params['0'], 'hash');
        $this->companies->get($this->projects->id_company);
        $this->clients->get($this->companies->id_client_owner);

        //TODO load data bilans

        if ($this->preteurCreateEmprunteur === true && $this->clients->status_depot_dossier >= 1) {
            $conditionOk = true;
        } elseif ($this->clients->status == 0 && $this->clients->status_depot_dossier >= 1) {
            $conditionOk = true;
        } else {
            header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
        }

        if ($conditionOk && isset($_POST['send_form_etape_3'])) {
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
            if (!isset($_POST['fonds_propres']) || $_POST['fonds_propres'] == '') {
                $bFormOk = false;
            }
            if ($_FILES['liasse_fiscal'] == false) {
                $bFormOk = false;
            }

            if ($bFormOk) {
                $this->uploadAttachment($this->projects->id_project, attachment_type::DERNIERE_LIASSE_FISCAL);

                if (isset($_FILES['autre'])) {
                    $this->uploadAttachment($this->projects->id_project, attachment_type::AUTRE1);
                }

                $iRex          = $_POST['resultat_brute_exploitation'];
                $iCa           = $_POST['ca'];
                $iFondsPropres = $_POST['fonds_propres'];

                if ($iRex < 0 || $iCa < 100000 || $iFondsPropres < 10000) {
                    $this->projects_status_history->addStatus(-2, projects_status::NOTE_EXTERNE_FAIBLE, $this->projects->id_project);
                    header('Location: ' . $this->lurl . '/depot_de_dossier/nok/rex-nega');
                }

                if (isset($_POST['procedure_acceleree'])) {
                    $this->projects->process_fast = 1;
                    $this->projects_status_history->addStatus(-2, projects_status::A_TRAITER, $this->projects->id_project);
                    header('Location: ' . $this->lurl . '/depot_de_dossier/fichiers');
                } else {
                    //TODO envoi de mail pour reprise de dossier
                    die;
                }
            }
        }
    }

    public function _fichiers()
    {
        $this->checkClient();

        $this->lng['etape1']            = $this->ln->selectFront('depot-de-dossier-etape-1', $this->language, $this->App);
        $this->lng['etape2']            = $this->ln->selectFront('depot-de-dossier-etape-2', $this->language, $this->App);
        $this->lng['etape3']            = $this->ln->selectFront('depot-de-dossier-etape-3', $this->language, $this->App);
        $this->lng['espace-emprunteur'] = $this->ln->selectFront('depot-de-dossier-espace-emprunteur', $this->language, $this->App);

        $this->companies               = $this->loadData('companies');
        $this->projects                = $this->loadData('projects');
        $this->projects_status         = $this->loadData('projects_status');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->attachment              = $this->loadData('attachment');
        $this->attachment_type         = $this->loadData('attachment_type');

        //TODO calcul de la mensualite en tenant compte du montant/ duree / taux min et taux max et frais
        // $this->mensualite_min, $this->mensualite_max

        $this->projects->get($this->params['0'], 'hash');
        $this->companies->get($this->projects->id_company);
        $this->clients->get($this->companies->id_client_owner);

        if ($this->preteurCreateEmprunteur === true && $this->clients->status_depot_dossier >= 1) {
            $conditionOk = true;
        } elseif ($this->clients->status == 0 && $this->clients->status_depot_dossier >= 1) {
            $conditionOk = true;
        } else {
            header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
        }

        if ($this->projects_status->getLastStatut($this->projects->id_project) === projects_status::ABANDON) {
            //TODO redirect to page with message to contact commercial
        }

        $this->aAttachmentTypes = $this->attachment_type->getAllTypesForProjects();
    }

    public function _etape5()
    {
        die;
        $this->users                   = $this->loadData('users');
        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');

        if (isset($_SESSION['client'])) {
            $this->clients->get($_SESSION['client']['id_client'], 'id_client');

            // Si c'est un preteur
            if ($this->clients->status_pre_emp == 1) {
                // On controle si personne morale
                if ($this->clients->type == 2) {
                    $this->preteurCreateEmprunteur = true;

                    // Si personne morale on recup l'entreprise
                    $this->companies->get($this->clients->id_client, 'id_client_owner');
                } else {
                    // Si personne physique pas le droit
                    header('Location: ' . $this->lurl . '/depot_de_dossier/etape1');
                    die;
                }
            } // Si c'est un emprunteur
            elseif ($this->clients->status_pre_emp == 2) {
                // Si emprunteur pas le droit de créer un autre compte en etant connecté
                header('Location: ' . $this->lurl . '/depot_de_dossier/etape1');
                die;
            } // Si deja preteur/emprunteur
            else {
                // Si emprunteur pas le droit de créer un autre compte en etant connecté
                header('Location: ' . $this->lurl . '/depot_de_dossier/etape1');
                die;
            }
        }
        //////////////////////////////////


        if ($this->preteurCreateEmprunteur == true && $this->clients->status_depot_dossier >= 4) {
            $conditionOk = true;
        } elseif ($this->clients->get($this->params['0'], 'status = 0 AND hash') && $this->clients->status_depot_dossier >= 4) {
            $conditionOk = true;
        } else {
            $conditionOk = false;
        }

        $conditionOk = true;

        // On récupere les infos clients
        if ($conditionOk == true) {
            // recup companie
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // On récupere le détaille companie
            $this->companies_details->get($this->companies->id_company, 'id_company');

            // recup projet (GET car lors de l'inscription il n'y a qu'un projet)
            $this->projects->get($this->companies->id_company, 'id_company ');

            $this->lng['etape5'] = $this->ln->selectFront('depot-de-dossier-etape-5', $this->language, $this->App);

            // Num page
            $this->page = 5;

            $this->settings->get('Lien conditions generales depot dossier', 'type');
            $this->lienConditionsGenerales = $this->settings->value;

            if (isset($_POST['send_form_upload'])) {
                // extrait_kbis
                if (isset($_FILES['fichier1']) && $_FILES['fichier1']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/companies/extrait_kbis/');
                    if ($this->upload->doUpload('fichier1')) {
                        if ($this->companies_details->fichier_extrait_kbis != '') {
                            @unlink($this->path . 'protected/companies/extrait_kbis/' . $this->companies_details->fichier_extrait_kbis);
                        }
                        $this->companies_details->fichier_extrait_kbis = $this->upload->getName();
                    }
                }
                // fichier_rib
                if (isset($_FILES['fichier2']) && $_FILES['fichier2']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/companies/rib/');
                    if ($this->upload->doUpload('fichier2')) {
                        if ($this->companies_details->fichier_rib != '') {
                            @unlink($this->path . 'protected/companies/rib/' . $this->companies_details->fichier_rib);
                        }
                        $this->companies_details->fichier_rib = $this->upload->getName();
                    }
                }
                // fichier_delegation_pouvoir
                if (isset($_FILES['fichier3']) && $_FILES['fichier3']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/companies/delegation_pouvoir/');
                    if ($this->upload->doUpload('fichier3')) {
                        if ($this->companies_details->fichier_delegation_pouvoir != '') {
                            @unlink($this->path . 'protected/companies/delegation_pouvoir/' . $this->companies_details->fichier_delegation_pouvoir);
                        }
                        $this->companies_details->fichier_delegation_pouvoir = $this->upload->getName();
                    }
                }
                // fichier_logo_societe
                if (isset($_FILES['fichier4']) && $_FILES['fichier4']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'public/default/var/images/logos_companies/');
                    if ($this->upload->doUpload('fichier4')) {
                        if ($this->companies_details->fichier_logo_societe != '') {
                            @unlink($this->path . 'public/default/var/images/logos_companies/' . $this->companies_details->fichier_logo_societe);
                        }
                        $this->companies_details->fichier_logo_societe = $this->upload->getName();
                    }
                }
                // fichier_photo_dirigeant
                if (isset($_FILES['fichier5']) && $_FILES['fichier5']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/companies/photo_dirigeant/');
                    if ($this->upload->doUpload('fichier5')) {
                        if ($this->companies_details->fichier_photo_dirigeant != '') {
                            @unlink($this->path . 'protected/companies/photo_dirigeant/' . $this->companies_details->fichier_photo_dirigeant);
                        }
                        $this->companies_details->fichier_photo_dirigeant = $this->upload->getName();
                    }
                }


                // fichier_cni_passeport
                if (isset($_FILES['fichier6']) && $_FILES['fichier6']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/companies/cni_passeport/');
                    if ($this->upload->doUpload('fichier6')) {
                        if ($this->companies_details->fichier_cni_passeport != '') {
                            @unlink($this->path . 'protected/companies/cni_passeport/' . $this->companies_details->fichier_cni_passeport);
                        }
                        $this->companies_details->fichier_cni_passeport = $this->upload->getName();
                    }
                }
                // fichier_derniere_liasse_fiscale
                if (isset($_FILES['fichier7']) && $_FILES['fichier7']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/companies/derniere_liasse_fiscale/');
                    if ($this->upload->doUpload('fichier7')) {
                        if ($this->companies_details->fichier_derniere_liasse_fiscale != '') {
                            @unlink($this->path . 'protected/companies/derniere_liasse_fiscale/' . $this->companies_details->fichier_derniere_liasse_fiscale);
                        }
                        $this->companies_details->fichier_derniere_liasse_fiscale = $this->upload->getName();
                    }
                }
                // fichier_derniers_comptes_approuves
                if (isset($_FILES['fichier8']) && $_FILES['fichier8']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/companies/derniers_comptes_approuves/');
                    if ($this->upload->doUpload('fichier8')) {
                        if ($this->companies_details->fichier_derniers_comptes_approuves != '') {
                            @unlink($this->path . 'protected/companies/derniers_comptes_approuves/' . $this->companies_details->fichier_derniers_comptes_approuves);
                        }
                        $this->companies_details->fichier_derniers_comptes_approuves = $this->upload->getName();
                    }
                }
                // fichier_derniers_comptes_consolides_groupe
                if (isset($_FILES['fichier9']) && $_FILES['fichier9']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/companies/derniers_comptes_consolides_groupe/');
                    if ($this->upload->doUpload('fichier9')) {
                        if ($this->companies_details->fichier_derniers_comptes_consolides_groupe != '') {
                            @unlink($this->path . 'protected/companies/derniers_comptes_consolides_groupe/' . $this->companies_details->fichier_derniers_comptes_consolides_groupe);
                        }
                        $this->companies_details->fichier_derniers_comptes_consolides_groupe = $this->upload->getName();
                    }
                }
                // fichier_annexes_rapport_special_commissaire_compte
                if (isset($_FILES['fichier10']) && $_FILES['fichier10']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/companies/annexes_rapport_special_commissaire_compte/');
                    if ($this->upload->doUpload('fichier10')) {
                        if ($this->companies_details->fichier_annexes_rapport_special_commissaire_compte != '') {
                            @unlink($this->path . 'protected/companies/annexes_rapport_special_commissaire_compte/' . $this->companies_details->fichier_annexes_rapport_special_commissaire_compte);
                        }
                        $this->companies_details->fichier_annexes_rapport_special_commissaire_compte = $this->upload->getName();
                    }
                }
                // fichier_arret_comptable_recent
                if (isset($_FILES['fichier11']) && $_FILES['fichier11']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/companies/arret_comptable_recent/');
                    if ($this->upload->doUpload('fichier11')) {
                        if ($this->companies_details->fichier_arret_comptable_recent != '') {
                            @unlink($this->path . 'protected/companies/arret_comptable_recent/' . $this->companies_details->fichier_arret_comptable_recent);
                        }
                        $this->companies_details->fichier_arret_comptable_recent = $this->upload->getName();
                    }
                }
                // fichier_budget_exercice_en_cours_a_venir
                if (isset($_FILES['fichier12']) && $_FILES['fichier12']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/companies/budget_exercice_en_cours_a_venir/');
                    if ($this->upload->doUpload('fichier12')) {
                        if ($this->companies_details->fichier_budget_exercice_en_cours_a_venir != '') {
                            @unlink($this->path . 'protected/companies/budget_exercice_en_cours_a_venir/' . $this->companies_details->fichier_budget_exercice_en_cours_a_venir);
                        }
                        $this->companies_details->fichier_budget_exercice_en_cours_a_venir = $this->upload->getName();
                    }
                }
                // fichier_notation_banque_france
                if (isset($_FILES['fichier13']) && $_FILES['fichier13']['name'] != '') {
                    $this->upload->setUploadDir($this->path, 'protected/companies/notation_banque_france/');
                    if ($this->upload->doUpload('fichier13')) {
                        if ($this->companies_details->fichier_notation_banque_france != '') {
                            @unlink($this->path . 'protected/companies/notation_banque_france/' . $this->companies_details->fichier_notation_banque_france);
                        }
                        $this->companies_details->fichier_notation_banque_france = $this->upload->getName();
                    }
                }

                // Enregistrement des images
                $this->companies_details->update();
            }

            if (isset($_POST['send_form_etape_5'])) {
                // Creation du mot de passe client
                //$lemotdepasse = $this->ficelle->generatePassword(8);
                //$this->clients->password = md5($lemotdepasse);
                // On precise que c'est un emprunteur
                $this->clients->status_pre_emp = 2;

                // si deja un preteur on le passe en mode 3 (preteur/emprunteur)
                if ($this->preteurCreateEmprunteur == true) {
                    $this->clients->status_pre_emp = 3;
                }

                // On recupere l'analyste par defaut
                // Default analyst
                $this->settings->get('Default analyst', 'type');
                $default_analyst = $this->settings->value;

                //$this->users->get(1,'default_analyst');
                $this->projects->id_analyste = $default_analyst;
                $this->projects->update();

                // ajout du statut dans l'historique : statut 10 (non lu)
                $this->projects_status_history = $this->loadData('projects_status_history');
                $this->projects_status_history->addStatus(-2, 10, $this->projects->id_project);

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
                // FB
                $this->settings->get('Facebook', 'type');
                $lien_fb = $this->settings->value;

                // Twitter
                $this->settings->get('Twitter', 'type');
                $lien_tw = $this->settings->value;

                // Variables du mailing
                $varMail = array(
                    'surl' => $surl,
                    'url' => $url,
                    'lien_fb' => $lien_fb,
                    'lien_tw' => $lien_tw
                );

                // Construction du tableau avec les balises EMV
                $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                // Attribution des données aux variables
                $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                // Envoi du mail
                $this->email = $this->loadLib('email', array());
                $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                //$this->email->addRecipient(trim($this->clients->email));
                //$this->email->addBCCRecipient($this->clients->email);

                $this->email->setSubject(stripslashes($sujetMail));
                $this->email->setHTMLBody(stripslashes($texteMail));
                Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);

                // Injection du mail NMP dans la queue
                $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
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
                // Mise à jour du client
                $this->clients->status               = 1;
                $this->clients->status_transition    = 1;
                $this->clients->status_depot_dossier = 5;
                $this->clients->update();

                // -- acceptation des cgu -- //
                if ($this->acceptations_legal_docs->get($this->lienConditionsGenerales, 'id_client = "' . $this->clients->id_client . '" AND id_legal_doc')) {
                    $accepet_ok = true;
                } else {
                    $accepet_ok = false;
                }
                $this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGenerales;
                $this->acceptations_legal_docs->id_client    = $this->clients->id_client;
                if ($accepet_ok == true) {
                    $this->acceptations_legal_docs->update();
                } else {
                    $this->acceptations_legal_docs->create();
                }
                // -- fin partie cgu -- //

                header('Location: ' . $this->lurl . '/' . $this->tree->getSlug(48, $this->language));
                die;
            }
        } else {
            header('Location: ' . $this->lurl . '/depot_de_dossier/etape1');
            die;
        }
    }

    public function _stand_by()
    {
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash')) {
            header('Location: ' . $this->lurl . '/depot_de_dossier/etape' . ($this->clients->status_depot_dossier + 1) . '/' . $this->clients->hash);
            die;
        } else {
            header('Location: ' . $this->lurl);
            die;
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
     * @param integer $iAttachmentType
     * @return bool
     */
    private function uploadAttachment($iOwnerId, $iAttachmentType)
    {
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

        switch ($iAttachmentType) {
            case attachment_type::CNI_PASSPORTE :
                $field      = 'cni_passeport';
                $uploadPath = $basePath . 'cni_passeport/';
                break;
            case attachment_type::CNI_PASSPORTE_VERSO :
                $field      = 'cni_passeport_verso';
                $uploadPath = $basePath . 'cni_passeport_verso/';
                break;
            case attachment_type::RIB :

                break;
            case attachment_type::KBIS :
                $field      = 'extrait_kbis';
                $uploadPath = $basePath . 'extrait_kbis/';
                break;
            case attachment_type::DERNIERE_LIASSE_FISCAL :
                $field      = 'liasse_fiscal';
                $uploadPath = $basePath . 'liasse_fiscal/';
                break;
            case attachment_type::AUTRE1 :
                $field      = 'autre';
                $uploadPath = $basePath . 'autre/';
                break;
            case attachment_type::AUTRE2 :
                $field      = 'autre2';
                $uploadPath = $basePath . 'autre2/';
                break;
            case attachment_type::AUTRE3:
                $field      = 'autre3';
                $uploadPath = $basePath . 'autre3/';
                break;
            default :
                return false;
        }

        $resultUpload = $this->attachmentHelper->upload($iOwnerId, attachment::PROJECT, $iAttachmentType, $field, $this->path, $uploadPath, $this->upload, $this->attachment);

        if (false === $resultUpload) {
            $this->form_ok       = false;
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
