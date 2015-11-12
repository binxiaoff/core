<?php
/*if($_SERVER["REMOTE_ADDR"] != "93.26.42.99" && $_SERVER["HTTP_X_FORWARDED_FOR"] != "93.26.42.99")
{
	header('location:'.$this->lurl,true,301);
    die;
}*/

// Chargement des datas
$this->projects                = $this->loadData('projects');
$this->clients                 = $this->loadData('clients');
$this->clients_adresses        = $this->loadData('clients_adresses');
$this->companies               = $this->loadData('companies');
$this->companies_bilans        = $this->loadData('companies_bilans');
$this->companies_details       = $this->loadData('companies_details');
$this->companies_actif_passif  = $this->loadData('companies_actif_passif');
$this->projects_status_history = $this->loadData('projects_status_history');
$this->projects                = $this->loadData('projects');

// load des durée des prêts autorisées
$this->settings->get('Durée des prêts autorisées', 'type');
$this->dureePossible = explode(',', $this->settings->value);

if (empty($this->settings->value)) {
    $this->dureePossible = array(24, 36, 48, 60);
}
//traduction
$this->lng['landing-page'] = $this->ln->selectFront('landing-page', $this->language, $this->App);

//*********************//
//*** Infos Altares ***//
//*********************//

// Login altares
$this->settings->get('Altares login', 'type');
$login = $this->settings->value;
// Mdp altares
$this->settings->get('Altares mot de passe', 'type');
$mdp = $this->settings->value; // mdp en sha1
// Url wsdl
$this->settings->get('Altares wsdl', 'type');
$this->wsdl = $this->settings->value;
// Identification
$this->identification = $login . '|' . $mdp;


// statut
$this->tablStatus = array('Oui', 'Pas de bilan');

//*************************//
//*** Fin Infos Altares ***//
//*************************//

// source
$this->ficelle->source($_GET['utm_source'], $this->lurl . '/' . $this->params[0], $_GET['utm_source2']);

// Somme à emprunter min
$this->settings->get('Somme à emprunter min', 'type');
$this->sommeMin = $this->settings->value;

// Somme à emprunter max
$this->settings->get('Somme à emprunter max', 'type');
$this->sommeMax = $this->settings->value;

// Si on a une session d'ouverte on redirige
if (isset($_SESSION['client'])) {
    header('location:' . $this->lurl);
    die;
}

$reponse_get = false;
// Si on a les get en question
if (isset($_GET['montant']) && $_GET['montant'] != '' &&
    isset($_GET['duree']) && $_GET['duree'] != '' &&
    isset($_GET['siren']) && $_GET['siren'] != '' &&
    isset($_GET['exercices_comptables']) && $_GET['exercices_comptables'] != ''
) {
    $reponse_get = true;
}

// On récupère le formulaire d'inscription de la page
if (isset($_POST['spy_inscription_landing_page_depot_dossier']) || $reponse_get == true) {
    if ($reponse_get == true) {
        $montant              = str_replace(',', '.', str_replace(' ', '', $_GET['montant']));
        $duree                = $_GET['duree'];
        $siren                = $_GET['siren'];
        $exercices_comptables = $_GET['exercices_comptables'];
    } else {
        $montant              = str_replace(',', '.', str_replace(' ', '', $_POST['montant']));
        $duree                = $_POST['duree'];
        $siren                = $_POST['siren'];
        $exercices_comptables = $_POST['exercices_comptables'];
    }

    $form_valid = true;

    if (!isset($montant) or $montant == $this->lng['landing-page']['montant-souhaite']) {
        $form_valid        = false;
        $this->retour_form = $this->lng['landing-page']['champs-obligatoires'];
    } // Si pas numerique
    elseif (!is_numeric($montant)) {
        $form_valid        = false;
        $this->retour_form = $this->lng['landing-page']['champs-obligatoires'];
    } // montant < ou > au min et max
    elseif ($montant < $this->sommeMin || $montant > $this->sommeMax) {
        $this->form_ok = false;
    }

    if (!isset($duree) or $duree == 0 or !in_array($duree, $this->dureePossible)) {
        $form_valid        = false;
        $this->retour_form = $this->lng['landing-page']['champs-obligatoires'];
    }

    if (!isset($siren) or $siren == $this->lng['landing-page']['siren'] || strlen($siren) != 9) {
        $form_valid        = false;
        $this->retour_form = $this->lng['landing-page']['champs-obligatoires'];
    }

    // 3 annees comptable oui ou non
    $comptable = false;
    if (!isset($exercices_comptables) || $exercices_comptables == '' || $exercices_comptables == '0') {
        $comptable = true;
    }


    if ($form_valid) {
        // Web Service Altares
        $result = '';
        try {
            $result = $this->ficelle->ws($this->wsdl, $this->identification, $siren);
            //mail('courtier.damien@gmail.com','debug result',serialize($result));
        } catch (Exception $e) {
            //mail('courtier.damien@gmail.com','debug error',serialize($e));
        }

        // Verif si erreur
        $exception = $result->exception;

        // si altares ok
        if ($exception == '') {
            //mail('courtier.damien@gmail.com','debug exception',serialize($exception));
            // verif reponse
            $eligibility = $result->myInfo->eligibility;
            $score       = $result->myInfo->score;
            $identite    = $result->myInfo->identite;
            $siege       = $result->myInfo->siege;


            /*mail('courtier.damien@gmail.com','debug eligibility',serialize($eligibility));
            mail('courtier.damien@gmail.com','debug score',serialize($score));
            mail('courtier.damien@gmail.com','debug identite',serialize($identite));
            mail('courtier.damien@gmail.com','debug siege',serialize($siege));*/

            // clients //
            $this->clients->source               = $_SESSION['utm_source'];
            $this->clients->source2              = $_SESSION['utm_source2'];
            $this->clients->slug_origine         = $this->tree->slug;
            $this->clients->id_langue            = $this->language;
            $this->clients->status_depot_dossier = 1;
            $this->clients->id_client            = $this->clients->create();
            // fin clients //

            //clients_adresses //
            $this->clients_adresses->id_client = $this->clients->id_client;
            $this->clients_adresses->create();
            // clients_adresses //

            // Companie //
            $this->companies->name                 = $identite->raisonSociale;
            $this->companies->forme                = $identite->formeJuridique;
            $this->companies->capital              = $identite->capital;
            $this->companies->altares_eligibility  = $eligibility;
            $this->companies->altares_niveauRisque = $score->niveauRisque;
            $this->companies->altares_scoreVingt   = $score->scoreVingt;
            $dateValeur                            = substr($score->dateValeur, 0, 10);
            $this->companies->altares_dateValeur   = $dateValeur;
            $this->companies->adresse1             = $identite->rue;
            $this->companies->city                 = $identite->ville;
            $this->companies->zip                  = $identite->codePostal;
            $this->companies->phone                = str_replace(' ', '', $siege->telephone);
            $this->companies->rcs                  = $identite->rcs;
            $dateCreation                          = substr($identite->dateCreation, 0, 10);
            $this->companies->date_creation        = $dateCreation;

            $this->companies->id_client_owner     = $this->clients->id_client; // id client
            $this->companies->siren               = $siren;
            $this->companies->siret               = $identite->siret;
            $this->companies->execices_comptables = $exercices_comptables;
            //$this->companies->activite = $identite->typeExploitationLabel;
            //$this->companies->lieu_exploi = $identite->ville;
            $this->companies->status_adresse_correspondance = '1';

            if ($this->preteurCreateEmprunteur == true && $this->clients->type == 2) {
                $this->companies->update();
            } else {
                $this->companies->id_company = $this->companies->create();
            }
            // Fin companie //

            // dernier bilan (companies_details) //
            $dateDernierBilanString                            = substr($identite->dateDernierBilan, 0, 10);
            $dateDernierBilan                                  = explode('-', $dateDernierBilanString);
            $this->companies_details->date_dernier_bilan       = $dateDernierBilanString;
            $this->companies_details->date_dernier_bilan_mois  = $dateDernierBilan[1];
            $this->companies_details->date_dernier_bilan_annee = $dateDernierBilan[0];

            $this->companies_details->id_company = $this->companies->id_company;
            if ($this->preteurCreateEmprunteur == true && $this->clients->type == 2) {
                $this->companies_details->update();
            } else {
                $this->companies_details->create();
            }
            // fin companies_details //

            // projects //
            $this->projects->id_company = $this->companies->id_company;
            $this->projects->amount     = $montant;
            $this->projects->period     = $duree;

            // Default analyst
            $this->settings->get('Default analyst', 'type');
            $default_analyst             = $this->settings->value;
            $this->projects->id_analyste = $default_analyst;

            $this->projects->id_project = $this->projects->create();
            // fin projects //


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


            ///////////////////////////////////////////////////////////////


            $syntheseFinanciereInfo = $result->myInfo->syntheseFinanciereInfo;
            $syntheseFinanciereList = $result->myInfo->syntheseFinanciereInfo->syntheseFinanciereList;

            $posteActifList         = array();
            $postePassifList        = array();
            $syntheseFinanciereInfo = array();
            $syntheseFinanciereList = array();
            $derniersBilans         = array();
            $i                      = 0;

            foreach ($result->myInfo->bilans as $b) {
                $annee                          = substr($b->bilan->dateClotureN, 0, 4);
                $posteActifList[$annee]         = $b->bilanRetraiteInfo->posteActifList;
                $postePassifList[$annee]        = $b->bilanRetraiteInfo->postePassifList;
                $syntheseFinanciereInfo[$annee] = $b->syntheseFinanciereInfo;
                $syntheseFinanciereList[$annee] = $b->syntheseFinanciereInfo->syntheseFinanciereList;

                $soldeIntermediaireGestionInfo[$annee] = $b->soldeIntermediaireGestionInfo->SIGList;
                $investissement[$annee]                = $b->bilan->posteList[0]->valeur;

                // date des derniers bilans
                $derniersBilans[$i] = $annee;

                $i++;
            }

            $ldate = $lesdates;
            // on génère un tableau avec les données
            for ($i = 0; $i < 5; $i++) {// on parcourt les 5 années
                for ($a = 0; $a < 3; $a++) {// on parcourt les 3 dernieres années
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

            $i = 0;
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

                    $this->companies_actif_passif->amortissement_sur_immo = $ActifPassif[$ap['annee']]['posteBR_AMPROVIMMO'];
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
            // Fin actif/passif

            ///////////////////////////////////////////////////////////////

            // date -3 ans
            $todayMoins3 = date('Y') - 3;

            // || $eligibility == 'Non'
            //if($eligibility == 'Société radiée' || $eligibility == 'Non')
            if (in_array($eligibility, array('Société radiée', 'Non', 'Pas de RCS'))) {
                // ajout du statut dans l'historique : statut 5 (Note externe faible)
                $this->projects_status_history->addStatus(-2, 5, $this->projects->id_project);

                // pas good
                $altares = true;
            } elseif (in_array($eligibility, $this->tablStatus)) {
                /*if($score->scoreVingt < 12)
                {
                    // inferieur a 12
                    $this->projects_status_history->addStatus(-2,5,$this->projects->id_project);

                    // pas good
                    //$altares = true;
                }*/
                if ($eligibility == 'Pas de bilan') {
                    // ajout du statut dans l'historique : statut 6 (Pas 3 bilans)
                    $this->projects_status_history->addStatus(-2, 6, $this->projects->id_project);

                    // pas good
                    //$altares = true;
                }
                // date creation -3 ans
                if (substr($identite->dateCreation, 0, 4) > $todayMoins3) {
                    // ajout du statut dans l'historique : statut 5 (Note externe faible)
                    $this->projects_status_history->addStatus(-2, 5, $this->projects->id_project);

                    // pas good
                    $altares = true;
                }
            }

            // Moins de 3 exercices comptables
            if ($comptable == true) {
                // ajout du statut dans l'historique : statut 6 (Pas 3 bilans)
                $this->projects_status_history->addStatus(-2, 6, $this->projects->id_project);
            }

        } else {// fin altares
            // clients //
            $this->clients->source               = $_SESSION['utm_source'];
            $this->clients->source2              = $_SESSION['utm_source2'];
            $this->clients->id_langue            = $this->language;
            $this->clients->status_depot_dossier = 1;
            $this->clients->id_client            = $this->clients->create();
            // fin clients //

            //clients_adresses //
            $this->clients_adresses->id_client = $this->clients->id_client;
            $this->clients_adresses->create();
            // clients_adresses //

            // Companie //
            $this->companies->id_client_owner               = $this->clients->id_client; // id client
            $this->companies->siren                         = $siren;
            $this->companies->execices_comptables           = $exercices_comptables;
            $this->companies->status_adresse_correspondance = '1';
            $this->companies->id_company                    = $this->companies->create();
            // Fin companie //

            // dernier bilan (companies_details) //
            $this->companies_details->id_company = $this->companies->id_company;
            $this->companies_details->create();
            // fin companies_details //

            // projects //
            $this->projects->id_company = $this->companies->id_company;
            $this->projects->amount     = $montant;
            $this->projects->period     = $duree;

            // Default analyst
            $this->settings->get('Default analyst', 'type');
            $this->projects->id_analyste = $this->settings->value;

            $this->projects->id_project = $this->projects->create();
            // fin projects //

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

            // ajout du statut dans l'historique : statut 5 (Note externe faible)
            $this->projects_status_history->addStatus(-2, 5, $this->projects->id_project);

            header('location:' . $this->lurl . '/depot_de_dossier/etape1/nok/' . $this->clients->hash);
            die;
        }


        // Si altarest est pas good ou comptable pas good
        if ($altares == true || $comptable == true) {
            header('location:' . $this->lurl . '/depot_de_dossier/etape1/nok/' . $this->clients->hash);
            die;
        }

        // si good page confirmation
        //$this->projects_status_history->addStatus(-2,10,$this->projects->id_project); // ajouté meme normalement present en étape 2
        sleep(1);
        $this->projects_status_history->addStatus(-2, 7, $this->projects->id_project); // statut abandon

        header('location:' . $this->lurl . '/depot_de_dossier/etape2/' . $this->clients->hash);
        die;
    }
}


// page projet tri
// 1 : terminé bientot
// 2 : nouveauté
//$this->tabOrdreProject[....] <--- dans le bootstrap pour etre accessible partout (page default et ajax)

$this->ordreProject = 1;
$this->type         = 0;

// Liste des projets en funding
$this->lProjetsFunding = $this->projects->selectProjectsByStatus('50,60,80', ' AND p.status = 0 AND p.display = 0', $this->tabOrdreProject[$this->ordreProject], 0, 6);

// Nb projets en funding
$this->nbProjects = $this->projects->countSelectProjectsByStatus('50,60,80', ' AND p.status = 0 AND p.display = 0');
