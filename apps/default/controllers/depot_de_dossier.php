<?php

class depot_de_dossierController extends bootstrap
{

    var $Command;

    function depot_de_dossierController($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;


        // Chargement des datas
        $this->companies = $this->loadData('companies');
        $this->companies_bilans = $this->loadData('companies_bilans');
        $this->companies_details = $this->loadData('companies_details');
        $this->companies_actif_passif = $this->loadData('companies_actif_passif');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->projects = $this->loadData('projects');

        $this->navigateurActive = 3;


        //Recuperation des element de traductions
        $this->lng['depot-de-dossier-header'] = $this->ln->selectFront('depot-de-dossier-header', $this->language, $this->App);
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
    }

    function _default()
    {
        header('location:' . $this->lurl . '/depot_de_dossier/etape1');
        die;
    }

    function _etape1()
    {
        // source
        $this->ficelle->source($_GET['utm_source'], $this->lurl . '/depot_de_dossier/etape1', $_GET['utm_source2']);

        //Recuperation des element de traductions
        $this->lng['etape1'] = $this->ln->selectFront('depot-de-dossier-etape-1', $this->language, $this->App);

        // Num page
        $this->page = 1;

        // Somme à emprunter min
        $this->settings->get('Somme à emprunter min', 'type');
        $this->sommeMin = $this->settings->value;

        // Somme à emprunter max
        $this->settings->get('Somme à emprunter max', 'type');
        $this->sommeMax = $this->settings->value;

        // Declaration des metas pour l'arbo
        $this->meta_title = $this->lng['depot-de-dossier-header']['meta-title'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-title'];
        $this->meta_keywords = $this->lng['depot-de-dossier-header']['meta-title'];

        ////////////////////////////////
        // Initialisation variable
        $this->preteurCreateEmprunteur = false;

        // Si on a une session active
        if (isset($_SESSION['client']))
        {
            // On recup le mec
            $this->clients->get($_SESSION['client']['id_client'], 'id_client');

            // Si c'est un preteur
            if ($this->clients->status_pre_emp == 1)
            {
                // On controle si personne morale
                if (in_array($this->clients->type, array(2, 4)))
                {
                    $this->preteurCreateEmprunteur = true;

                    // Si personne morale on recup l'entreprise
                    $this->companies->get($this->clients->id_client, 'id_client_owner');
                    $this->companies_details->get($this->companies->id_company, 'id_company');

                    // Si a deja créer un projet on le redirige dessus
                    if ($this->clients->status_depot_dossier > 0)
                    {

                        header('location:' . $this->lurl . '/depot_de_dossier/etape' . ($this->clients->status_depot_dossier + 1) . '/' . $this->clients->hash);
                        die;
                    }
                }
            }
        }

        /////////////////////////////////
        // On valide l'etape 1 (formulaire)
        if (isset($_POST['send_form_etape_1']))
        {
            $montant = str_replace(',', '.', str_replace(' ', '', $_POST['montant']));

            $this->form_ok = true;

            // Montant
            if (!isset($_POST['montant']) || $_POST['montant'] == '' || $_POST['montant'] == $this->lng['etape1']['montant'])
            {
                $this->form_ok = false;
            }
            // Si pas numerique 
            elseif (!is_numeric($montant))
            {
                $this->form_ok = false;
            }
            // montant < ou > au min et max
            elseif ($montant < $this->sommeMin || $montant > $this->sommeMax)
            {
                $this->form_ok = false;
            }

            // siren
            if (!isset($_POST['siren']) || $_POST['siren'] == '' || $_POST['siren'] == $this->lng['etape1']['siren'])
            {
                $this->form_ok = false;
            }
            // 3 annees comptable oui ou non
            $comptable = false;
            if (!isset($_POST['comptables']) || $_POST['comptables'] == '' || $_POST['comptables'] == '0')
            {
                $comptable = true;
            }

            // form valide
            if ($this->form_ok == true)
            {
                //Si on a une session client
                if (isset($_SESSION['client']))
                {
                    // On recup le mec
                    $this->clients->get($_SESSION['client']['id_client'], 'id_client');

                    $this->clients->status_depot_dossier = 1;

                    // Si c'est un preteur
                    if ($this->clients->status_pre_emp == 1)
                    {
                        // On controle si personne physique
                        if ($this->clients->type == 1)
                        {
                            // on cache tant qu'on met pas le systeme preteur/emprunteur
                            //$_SESSION['error_pre_empr'] = 'Seule une personne morale peut créer un compte emprunteur';
                            header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                            die;
                        }
                    }
                    // Si c'est un emprunteur ou deja un preteur/emprunteur
                    elseif ($this->clients->status_pre_emp == 2 || $this->clients->status_pre_emp == 3)
                    {
                        // on cache tant qu'on met pas le systeme preteur/emprunteur
                        //$_SESSION['error_pre_empr'] = 'Vous disposez déjà d\'un compte emprunteur';
                        // Si emprunteur pas le droit de créer un autre compte en etant connecté
                        header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                        die;
                    }

                    // dans tous les cas tant qu'on a pas le syteme preteur/emprunteur on redirige si on est connecté (a supprimer lors de la mise en place du systeme)
                    header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                    die;
                }
                
                
                // 1 : activé 2 : activé mais prend pas en compte le resultat 3 : desactivé (DC)
                $this->settings->get('Altares debrayage', 'type');
                $AltaresDebrayage = $this->settings->value;

                $this->settings->get('Altares email alertes', 'type');
                $AltaresEmailAlertes = $this->settings->value;

                // 1 : activé 2 : on prend pas en compte les filtres.(DC)
                if (in_array($AltaresDebrayage, array(1, 2)))
                {
                    // Web Service Altares
                    $result = '';
                    try
                    {
                        $siren = $_POST['siren'];
                        $result = $this->ficelle->ws($this->wsdl, $this->identification, $_POST['siren']);
                    }
                    catch (Exception $e)
                    {
                        mail($AltaresEmailAlertes, '[ALERTE] ERREUR ALTARES 2', 'Date ' . date('Y-m-d H:i:s') . '' . $e->getMessage());
                        error_log("[" . date('Y-m-d H:i:s') . "] " . $e->getMessage(), 3, $this->path . '/log/error_altares.txt');
                    }

                    if ($result->exception != false)
                    {

                        $erreur = 'Siren fourni : ' . $siren . ' | ' . $result->exception->code . ' | ' . $result->exception->description . ' | ' . $result->exception->erreur;
                        mail($AltaresEmailAlertes, '[ALERTE] ERREUR ALTARES 1', 'Date ' . date('Y-m-d H:i:s') . '' . $erreur);
                        error_log("[" . date('Y-m-d H:i:s') . "] " . $erreur . "\n", 3, $this->path . '/log/error_altares.txt');
                    }

                    // Verif si erreur
                    $exception = $result->exception;

                    // que pour le statut 2 (DC)
                    if ($AltaresDebrayage == 2)
                    {
                        mail($AltaresEmailAlertes, '[ALERTE] Altares Tentative evaluation', 'Date ' . date('Y-m-d H:i:s') . ' siren : ' . $siren);
                    }
                }
                // debrayage statut 3 : altares desactivé
                else
                {
                    $exception = '';
                }
                // si altares ok
                if ($exception == '')
                {
                    // verif reponse
                    $eligibility = $result->myInfo->eligibility;
                    $score = $result->myInfo->score;
                    $identite = $result->myInfo->identite;
                    $siege = $result->myInfo->siege;

                    // clients //
                    if ($this->preteurCreateEmprunteur == false)
                    {
                        $this->clients->source = $_SESSION['utm_source'];
                        $this->clients->source2 = $_SESSION['utm_source2'];
                    }
                    $this->clients->id_langue = $this->language;
                    $this->clients->status_depot_dossier = 1;
                    if ($this->preteurCreateEmprunteur == true)
                        $this->clients->update();
                    else
                        $this->clients->id_client = $this->clients->create();
                    // fin clients //
                    //clients_adresses //
                    if ($this->preteurCreateEmprunteur == false)
                    {
                        $this->clients_adresses->id_client = $this->clients->id_client;
                        $this->clients_adresses->create();
                    }
                    // clients_adresses //
                    // Companie //
                    $this->companies->name = $identite->raisonSociale;
                    $this->companies->forme = $identite->formeJuridique;
                    $this->companies->capital = $identite->capital;
                    $this->companies->altares_eligibility = $eligibility;
                    $this->companies->altares_niveauRisque = $score->niveauRisque;
                    $this->companies->altares_scoreVingt = $score->scoreVingt;
                    $dateValeur = substr($score->dateValeur, 0, 10);
                    $this->companies->altares_dateValeur = $dateValeur;
                    $this->companies->adresse1 = $identite->rue;
                    $this->companies->city = $identite->ville;
                    $this->companies->zip = $identite->codePostal;
                    $this->companies->phone = str_replace(' ', '', $siege->telephone);
                    $this->companies->rcs = $identite->rcs;
                    $dateCreation = substr($identite->dateCreation, 0, 10);
                    $this->companies->date_creation = $dateCreation;

                    $this->companies->id_client_owner = $this->clients->id_client; // id client
                    $this->companies->siren = $_POST['siren'];
                    $this->companies->execices_comptables = $_POST['comptables'];
                    //$this->companies->activite = $identite->typeExploitationLabel;
                    //$this->companies->lieu_exploi = $identite->ville;
                    $this->companies->status_adresse_correspondance = '1';

                    if ($this->preteurCreateEmprunteur == true && $this->clients->type == 2)
                        $this->companies->update();
                    else
                        $this->companies->id_company = $this->companies->create();
                    // Fin companie //
                    // dernier bilan (companies_details) //
                    $dateDernierBilanString = substr($identite->dateDernierBilan, 0, 10);
                    $dateDernierBilan = explode('-', $dateDernierBilanSting);
                    $this->companies_details->date_dernier_bilan = $dateDernierBilanString;
                    $this->companies_details->date_dernier_bilan_mois = $dateDernierBilan[1];
                    $this->companies_details->date_dernier_bilan_annee = $dateDernierBilan[0];

                    $this->companies_details->id_company = $this->companies->id_company;
                    if ($this->preteurCreateEmprunteur == true && $this->clients->type == 2)
                        $this->companies_details->update();
                    else
                        $this->companies_details->create();
                    // fin companies_details //
                    // projects //
                    $this->projects->id_company = $this->companies->id_company;
                    $this->projects->amount = str_replace(',', '.', str_replace(' ', '', $_POST['montant']));
                    $this->projects->period = $_POST['duree'];

                    // Default analyst
                    $this->settings->get('Default analyst', 'type');
                    $default_analyst = $this->settings->value;
                    $this->projects->id_analyste = $default_analyst;

                    $this->projects->id_project = $this->projects->create();
                    // fin projects //
                    // On génère 5 lignes dans la base pour les bilans
                    $lesdates = array(date('Y') - 3, date('Y') - 2, date('Y') - 1, date('Y'), date('Y') + 1);
                    for ($i = 0; $i < 5; $i++)
                    {
                        $this->companies_bilans->id_company = $this->companies->id_company;
                        $this->companies_bilans->date = $lesdates[$i];
                        $this->companies_bilans->create();
                    }

                    // les 3 dernieres vrais années (actif/passif)
                    $date = array();
                    $date[1] = (date('Y') - 1);
                    $date[2] = (date('Y') - 2);
                    $date[3] = (date('Y') - 3);

                    foreach ($date as $k => $d)
                    {
                        $this->companies_actif_passif->annee = $d;
                        $this->companies_actif_passif->ordre = $k;
                        $this->companies_actif_passif->id_company = $this->companies->id_company;
                        $this->companies_actif_passif->create();
                    }


                    ///////////////////////////////////////////////////////////////


                    $syntheseFinanciereInfo = $result->myInfo->syntheseFinanciereInfo;
                    $syntheseFinanciereList = $result->myInfo->syntheseFinanciereInfo->syntheseFinanciereList;

                    $posteActifList = array();
                    $postePassifList = array();
                    $syntheseFinanciereInfo = array();
                    $syntheseFinanciereList = array();
                    $derniersBilans = array();
                    $i = 0;
                    foreach ($result->myInfo->bilans as $b)
                    {
                        $annee = substr($b->bilan->dateClotureN, 0, 4);
                        $posteActifList[$annee] = $b->bilanRetraiteInfo->posteActifList;
                        $postePassifList[$annee] = $b->bilanRetraiteInfo->postePassifList;
                        $syntheseFinanciereInfo[$annee] = $b->syntheseFinanciereInfo;
                        $syntheseFinanciereList[$annee] = $b->syntheseFinanciereInfo->syntheseFinanciereList;

                        $soldeIntermediaireGestionInfo[$annee] = $b->soldeIntermediaireGestionInfo->SIGList;
                        $investissement[$annee] = $b->bilan->posteList[0]->valeur;

                        // date des derniers bilans
                        $derniersBilans[$i] = $annee;

                        $i++;
                    }

                    $ldate = $lesdates;
                    // on génère un tableau avec les données
                    for ($i = 0; $i < 5; $i++) // on parcourt les 5 années
                    {
                        for ($a = 0; $a < 3; $a++)// on parcourt les 3 dernieres années
                        {
                            // si y a une année du bilan qui correxpond a une année du tableau
                            if ($derniersBilans[$a] == $ldate[$i])
                            {
                                // On recup les données de cette année
                                $montant1 = $posteActifList[$ldate[$i]][1]->montant;
                                $montant2 = $posteActifList[$ldate[$i]][2]->montant;
                                $montant3 = $posteActifList[$ldate[$i]][3]->montant;
                                $montant = $montant1 + $montant2 + $montant3;

                                $this->companies_bilans->get($this->companies->id_company, 'date = ' . $ldate[$i] . ' AND id_company');
                                $this->companies_bilans->ca = $syntheseFinanciereList[$ldate[$i]][0]->montantN;
                                $this->companies_bilans->resultat_exploitation = $syntheseFinanciereList[$ldate[$i]][1]->montantN;
                                $this->companies_bilans->resultat_brute_exploitation = $soldeIntermediaireGestionInfo[$ldate[$i]][9]->montantN;
                                $this->companies_bilans->investissements = $investissement[$ldate[$i]];
                                $this->companies_bilans->update();
                            }
                        }
                    }

                    // Debut actif/passif
                    foreach ($derniersBilans as $annees)
                    {
                        foreach ($posteActifList[$annees] as $a)
                        {
                            $ActifPassif[$annees][$a->posteCle] = $a->montant;
                        }
                        foreach ($postePassifList[$annees] as $p)
                        {
                            $ActifPassif[$annees][$p->posteCle] = $p->montant;
                        }
                    }

                    // Liste des actif passif
                    $this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_company = "' . $this->companies->id_company . '"', 'annee DESC');

                    $i = 0;
                    foreach ($this->lCompanies_actif_passif as $k => $ap)
                    {
                        if ($this->companies_actif_passif->get($ap['annee'], 'id_company = ' . $ap['id_company'] . ' AND annee'))
                        {
                            // Actif
                            $this->companies_actif_passif->immobilisations_corporelles = $ActifPassif[$ap['annee']]['posteBR_IMCOR'];
                            $this->companies_actif_passif->immobilisations_incorporelles = $ActifPassif[$ap['annee']]['posteBR_IMMINC'];
                            $this->companies_actif_passif->immobilisations_financieres = $ActifPassif[$ap['annee']]['posteBR_IMFI'];
                            $this->companies_actif_passif->stocks = $ActifPassif[$ap['annee']]['posteBR_STO'];
                            //creances_clients = Avances et acomptes + creances clients + autre creances et cca + autre creances hors exploitation
                            $this->companies_actif_passif->creances_clients = $ActifPassif[$ap['annee']]['posteBR_BV'] + $ActifPassif[$ap['annee']]['posteBR_BX'] + $ActifPassif[$ap['annee']]['posteBR_ACCCA'] + $ActifPassif[$ap['annee']]['posteBR_ACHE_'];
                            $this->companies_actif_passif->disponibilites = $ActifPassif[$ap['annee']]['posteBR_CF'];
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
                    if (in_array($eligibility, array('Société radiée', 'Non', 'Pas de RCS')))
                    //if($eligibility == 'Société radiée' || $eligibility == 'Non')
                    {
                        // ajout du statut dans l'historique : statut 5 (Note externe faible)
                        $this->projects_status_history->addStatus(-2, 5, $this->projects->id_project);

                        // pas good
                        $altares = true;
                    }
                    // $this->tablStatus = array('Oui','Pas de bilan');
                    elseif (in_array($eligibility, $this->tablStatus))
                    {
                        /* if($score->scoreVingt < 12)
                          {
                          // inferieur a 12
                          $this->projects_status_history->addStatus(-2,5,$this->projects->id_project);

                          // pas good
                          //$altares = true;
                          } */
                        if ($eligibility == 'Pas de bilan')
                        {
                            // ajout du statut dans l'historique : statut 6 (Pas 3 bilans)
                            $this->projects_status_history->addStatus(-2, 6, $this->projects->id_project);

                            // pas good
                            //$altares = true;
                        }
                        // date creation -3 ans
                        if (substr($identite->dateCreation, 0, 4) > $todayMoins3)
                        {
                            // ajout du statut dans l'historique : statut 5 (Note externe faible)
                            $this->projects_status_history->addStatus(-2, 5, $this->projects->id_project);

                            // pas good
                            $altares = true;
                        }
                    }

                    // Moins de 3 exercices comptables
                    if ($comptable == true)
                    {
                        // ajout du statut dans l'historique : statut 6 (Pas 3 bilans)
                        $this->projects_status_history->addStatus(-2, 6, $this->projects->id_project);
                    }
                }// fin altares
                else
                {
                    // clients //
                    $this->clients->source = $_SESSION['utm_source'];
                    $this->clients->source2 = $_SESSION['utm_source2'];
                    $this->clients->id_langue = $this->language;
                    $this->clients->status_depot_dossier = 1;
                    $this->clients->id_client = $this->clients->create();
                    // fin clients //
                    //clients_adresses //
                    $this->clients_adresses->id_client = $this->clients->id_client;
                    $this->clients_adresses->create();
                    // clients_adresses //
                    // Companie //
                    $this->companies->id_client_owner = $this->clients->id_client; // id client
                    $this->companies->siren = $_POST['siren'];
                    $this->companies->execices_comptables = $_POST['comptables'];
                    $this->companies->status_adresse_correspondance = '1';
                    $this->companies->id_company = $this->companies->create();
                    // Fin companie //
                    // dernier bilan (companies_details) //
                    $this->companies_details->id_company = $this->companies->id_company;
                    $this->companies_details->create();
                    // fin companies_details //
                    // projects //
                    $this->projects->id_company = $this->companies->id_company;
                    $this->projects->amount = str_replace(',', '.', str_replace(' ', '', $_POST['montant']));
                    $this->projects->period = $_POST['duree'];

                    // Default analyst
                    $this->settings->get('Default analyst', 'type');
                    $this->projects->id_analyste = $this->settings->value;

                    $this->projects->id_project = $this->projects->create();
                    // fin projects //
                    // On génère 5 lignes dans la base pour les bilans
                    $lesdates = array(date('Y') - 3, date('Y') - 2, date('Y') - 1, date('Y'), date('Y') + 1);
                    for ($i = 0; $i < 5; $i++)
                    {
                        $this->companies_bilans->id_company = $this->companies->id_company;
                        $this->companies_bilans->date = $lesdates[$i];
                        $this->companies_bilans->create();
                    }

                    // les 3 dernieres vrais années (actif/passif)
                    $date = array();
                    $date[1] = (date('Y') - 1);
                    $date[2] = (date('Y') - 2);
                    $date[3] = (date('Y') - 3);

                    foreach ($date as $k => $d)
                    {
                        $this->companies_actif_passif->annee = $d;
                        $this->companies_actif_passif->ordre = $k;
                        $this->companies_actif_passif->id_company = $this->companies->id_company;
                        $this->companies_actif_passif->create();
                    }



                    // ajout du statut dans l'historique : statut 5 (Note externe faible)
                    $this->projects_status_history->addStatus(-2, 5, $this->projects->id_project);

                    
                    //on envoi un MAIL ALERTE
                    // subject
                    $subject = '[Alerte] Webservice Altares sans reponse';

                    // message
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
                                        <td colspan="2" ><img src="'.$this->surl.'/images/default/emails/logo.png" alt="logo" /></td>
                                </tr>
                                <tr>
                                        <td colspan="2">Le Webservice Altares ne semble pas r&eacute;pondre</td>
                                </tr>

                                <tr>
                                    <td colspan="2">Projet touch&eacute; :</td>
                                </tr>
                            </table>
                            
                            <br />
                            Id Projet : '.$this->projects->id_project.'<br />
                            Nom : '.$this->projects->title.'
                                
                        </body>
                        </html>
                        ';


                    // To send HTML mail, the Content-type header must be set
                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

                    // Additional headers
                    //$headers .= 'To: Damien <d.courtier@equinoa.com>, Kelly <kelly@example.com>' . "\r\n";
                    //$headers .= 'To: equinoa <unilend@equinoa.fr>' . "\r\n";
                    $headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";

                    // multiple recipients
                    //$to  = 'aidan@example.com' . ', '; // note the comma
                    //$to .= 'wez@example.com';
                    $to = 'k1@david.equinoa.net';


                    // Recupération du destinataire
                    $this->settings->get('Adresse alerte altares erreur', 'type');
                    $to = $this->settings->value;

                    // Mail it
                    mail($to, $subject, $message, $headers);

                    // FIN ENVOI MAIL ALERTE
                    
                    
                    
                    
                    header('location:' . $this->lurl . '/depot_de_dossier/etape1/nok');      /// <---- a remettre
                    die;                    /// <---- a remettre
                    
                    // // filtre altares -  cour-circuit
                    // on autorise le passage en etape 2 temporairement
                    //header('location:'.$this->lurl.'/depot_de_dossier/etape2/'.$this->clients->hash); 	/// <---- a retirer
                    //die;	/// <---- a retirer
                }

                // Si altarest est pas good ou comptable pas good
                if ($altares == true || $comptable == true)
                {
                    header('location:' . $this->lurl . '/depot_de_dossier/etape1/nok');
                    die;
                }

                sleep(1); // pour avoir une date en décalé pour le statut
                $this->projects_status_history->addStatus(-2, 7, $this->projects->id_project); // statut abandon

                header('location:' . $this->lurl . '/depot_de_dossier/etape2/' . $this->clients->hash);
                die;
            }// fin form valide
        }// fin formulaire
    }

    function _etape2()
    {
        // Num page
        $this->page = 2;

        //Recuperation des element de traductions
        $this->lng['etape1'] = $this->ln->selectFront('depot-de-dossier-etape-1', $this->language, $this->App);
        $this->lng['etape2'] = $this->ln->selectFront('depot-de-dossier-etape-2', $this->language, $this->App);

        $this->meta_title = $this->lng['depot-de-dossier-header']['meta-title-etape-2'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-title-etape-2'];
        $this->meta_keywords = $this->lng['depot-de-dossier-header']['meta-title-etape-2'];

        $this->settings->get('Lien conditions generales depot dossier', 'type');
        $this->lienConditionsGenerales = $this->settings->value;

        // Somme à emprunter min
        $this->settings->get('Somme à emprunter min', 'type');
        $this->sommeMin = $this->settings->value;

        // Somme à emprunter max
        $this->settings->get('Somme à emprunter max', 'type');
        $this->sommeMax = $this->settings->value;

        // Liste deroulante conseil externe de l'entreprise
        $this->settings->get("Liste deroulante conseil externe de l'entreprise", 'type');
        $this->conseil_externe = $this->ficelle->explodeStr2array($this->settings->value);

        // Default analyst
        $this->settings->get('Default analyst', 'type');
        $default_analyst = $this->settings->value;

        // Datas
        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
        $this->companies = $this->loadData('companies');
        $this->companies_details = $this->loadData('companies_details');
        $this->companies_bilans = $this->loadData('companies_bilans');
        $this->companies_actif_passif = $this->loadData('companies_actif_passif');
        $this->projects = $this->loadData('projects');
        $this->projects_status_history = $this->loadData('projects_status_history');

        //////////////////////////////////
        // Initialisation variable
        $this->preteurCreateEmprunteur = false;

        // Si on a une session active
        if (isset($_SESSION['client']))
        {
            // On recup le mec
            $this->clients->get($_SESSION['client']['id_client'], 'id_client');

            // Si c'est un preteur
            if ($this->clients->status_pre_emp == 1)
            {
                // On controle si personne morale
                if (in_array($this->clients->type, array(2, 4)))
                {
                    $this->preteurCreateEmprunteur = true;

                    // Si personne morale on recup l'entreprise
                    $this->companies->get($this->clients->id_client, 'id_client_owner');
                }
                else
                {

                    // Si personne physique pas le droit
                    header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                    die;
                }

                // dans tous les cas tant qu'on a pas le syteme preteur/emprunteur on redirige si on est connecté (a supprimer lors de la mise en place du systeme)
                //header('location:'.$this->lurl.'/depot_de_dossier/etape1');
                //die;	
            }
            // Si c'est un emprunteur
            elseif ($this->clients->status_pre_emp == 2 || $this->clients->status_pre_emp == 3)
            {
                // Si emprunteur pas le droit de créer un autre compte en etant connecté

                header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                die;
            }
            // Si deja preteur/emprunteur
            else
            {
                // Si emprunteur pas le droit de créer un autre compte en etant connecté
                //header('location:'.$this->lurl.'/depot_de_dossier/etape1');
                //die;
            }

            // tant que le syteme preteur/emprunteur n'est pas en place on redirige
            header('location:' . $this->lurl . '/depot_de_dossier/etape1');
            die;
        }
        //////////////////////////////////

        if ($this->preteurCreateEmprunteur == true && $this->clients->status_depot_dossier >= 1)
        {
            $conditionOk = true;
        }
        elseif ($this->clients->get($this->params['0'], 'status = 0 AND hash') && $this->clients->status_depot_dossier >= 1)
        {
            $conditionOk = true;
        }
        else
        {

            $conditionOk = false;
            header('location:' . $this->lurl . '/depot_de_dossier/etape1');
            die;
        }

        // On récupere les infos clients
        if ($conditionOk == true)
        {
            // on recup la companie
            $this->companies->get($this->clients->id_client, 'id_client_owner');
            $this->companies_details->get($this->companies->id_company, 'id_company');

            // le projet
            $this->projects->get($this->companies->id_company, 'id_company');

            // si dirigeant
            if ($this->companies->status_client == 1)
            {

                $this->nom_dirigeant = $this->clients->nom;
                $this->prenom_dirigeant = $this->clients->prenom;
                $this->email_dirigeant = $this->clients->email;
                $this->phone_dirigeant = $this->clients->telephone;
                $this->conf_email_representative = $this->email_dirigeant;
            }
            // si pas dirigeant
            else
            {
                $this->nom_dirigeant = $this->companies->nom;
                $this->prenom_dirigeant = $this->companies->prenom;
                $this->email_dirigeant = $this->companies->email;
                $this->phone_dirigeant = $this->companies->telephone;
                $this->conf_email_representative = $this->email_dirigeant;
            }

            $this->conf_email = $this->clients->email;
            $this->email_temp = $this->clients->email;
            // email avec le id on rajoute une adresse mail pour lesenvoies de mails
            $this->lemailduclient = '';

            /* if(isset($_SESSION['fichier1']) && $_SESSION['fichier1'] != '')
              {
              $this->companies_details->fichier_derniere_liasse_fiscale = $_SESSION['fichier1'];
              if($this->error_email_representative_exist != true || $this->error_email_exist != true)
              unset($_SESSION['fichier1']);
              }
              if(isset($_SESSION['fichier2']) && $_SESSION['fichier2'] != '')
              {
              $this->companies_details->fichier_autre_1 = $_SESSION['fichier2'];
              if($this->error_email_representative_exist != true || $this->error_email_exist != true)
              unset($_SESSION['fichier2']);
              } */

            // Form depot de dossier etape 2
            if (isset($_POST['send_form_depot_dossier']))
            {
                //////////////////////////////////////////////////

                $this->companies->name = $_POST['raison-sociale'];
                $this->companies->status_client = $_POST['radio1-about'];

                $this->conf_email_representative = $_POST['conf_email_representative'];

                // si conseil externe
                if (isset($_POST['radio1-about']) && $_POST['radio1-about'] == 3)
                {
                    $this->companies->status_conseil_externe_entreprise = $_POST['autre'];

                    if (isset($_POST['autre']) && $_POST['autre'] == 3)
                        $this->companies->preciser_conseil_externe_entreprise = $_POST['autre-preciser'];

                    // partie utilisateur du compte
                    $this->clients->civilite = $_POST['sex'];
                    $this->clients->nom = $_POST['nom-famille'];
                    $this->clients->prenom = $_POST['prenom'];
                    $this->clients->email = $_POST['email'];
                    $this->clients->telephone = $_POST['phone'];
                    $this->conf_email = $_POST['conf_email'];
                    $this->clients->fonction = $_POST['fonction'];

                    // partie dirigeant
                    $this->companies->civilite = '';
                    $this->companies->nom_dirigeant = $_POST['nom_representative'];
                    $this->companies->prenom_dirigeant = $_POST['prenom_representative'];
                    $this->companies->email_dirigeant = $_POST['email_representative'];
                    $this->companies->phone_dirigeant = $_POST['phone_representative'];
                    $this->companies->fonction_dirigeant = $_POST['fonction_representative'];
                }
                // si dirigeant
                else
                {
                    $this->clients->civilite = '';
                    $this->clients->nom = $_POST['nom_representative'];
                    ;
                    $this->clients->prenom = $_POST['prenom_representative'];
                    $this->clients->email = $_POST['email_representative'];
                    $this->clients->telephone = $_POST['phone_representative'];
                    $this->clients->fonction = $_POST['fonction_representative'];

                    $this->companies->civilite = '';
                    $this->companies->nom_dirigeant = '';
                    $this->companies->prenom_dirigeant = '';
                    $this->companies->email_dirigeant = '';
                    $this->companies->phone_dirigeant = '';
                    $this->companies->fonction_dirigeant = '';
                }
                // pour l'affichage
                $this->nom_dirigeant = $this->clients->nom;
                $this->prenom_dirigeant = $this->clients->prenom;
                $this->email_dirigeant = $this->clients->email;
                $this->phone_dirigeant = $this->clients->telephone;
                $this->conf_email_representative = $this->email_dirigeant;
                $this->fonction_dirigeant = $this->clients->fonction;
                //////////////////////////////////////////////////

                if (isset($_POST['comments']) && $_POST['comments'] != $this->lng['etape2']['toutes-informations-utiles'])
                    $this->projects->comments = $_POST['comments'];
                else
                    $this->projects->comments = '';

                // variable
                $form_ok = true;

                //fichier1
                // fichier_derniere_liasse_fiscale
                if (isset($_FILES['fichier1']) && $_FILES['fichier1']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/derniere_liasse_fiscale/');
                    if ($this->upload->doUpload('fichier1'))
                    {
                        if ($this->companies_details->fichier_derniere_liasse_fiscale != '')
                            @unlink($this->path . 'protected/companies/derniere_liasse_fiscale/' . $this->companies_details->fichier_derniere_liasse_fiscale);
                        $this->companies_details->fichier_derniere_liasse_fiscale = $this->upload->getName();
                    }
                }
                if ($this->companies_details->fichier_derniere_liasse_fiscale == '')
                {
                    $form_ok = false;
                }

                //fichier2
                //fichier_autre_1
                if (isset($_FILES['fichier2']) && $_FILES['fichier2']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/autres/');
                    if ($this->upload->doUpload('fichier2'))
                    {
                        if ($this->companies_details->fichier_autre_1 != '')
                            @unlink($this->path . 'protected/companies/autres/' . $this->companies_details->fichier_autre_1);
                        $this->companies_details->fichier_autre_1 = $this->upload->getName();
                    }
                }

                // accept-cgu
                if (!isset($_POST['accept-cgu']) || $_POST['accept-cgu'] == false)
                {
                    $form_ok = false;
                }
                // raison-sociale
                if (!isset($_POST['raison-sociale']) || $_POST['raison-sociale'] == '' || $_POST['raison-sociale'] == $this->lng['etape2']['raison-sociale'])
                {
                    $form_ok = false;
                }
                // nom_representative
                if (!isset($_POST['nom_representative']) || $_POST['nom_representative'] == '' || $_POST['nom_representative'] == $this->lng['etape2']['nom'])
                {
                    $form_ok = false;
                }
                // prenom_representative
                if (!isset($_POST['prenom_representative']) || $_POST['prenom_representative'] == '' || $_POST['prenom_representative'] == $this->lng['etape2']['prenom'])
                {
                    $form_ok = false;
                }
                // phone_representative
                if (!isset($_POST['phone_representative']) || $_POST['phone_representative'] == '' || $_POST['phone_representative'] == $this->lng['etape2']['telephone'] || strlen($_POST['phone_representative']) < 9 && strlen($_POST['phone_representative']) > 14)
                {
                    $form_ok = false;
                }
                // fonction_representative
                if (!isset($_POST['fonction_representative']) || $_POST['fonction_representative'] == '' || $_POST['fonction_representative'] == $this->lng['etape2']['fonction'])
                {
                    $form_ok = false;
                }
                // email_representative
                if (!isset($_POST['email_representative']) || $_POST['email_representative'] == '' || $_POST['email_representative'] == $this->lng['etape2']['email'])
                {
                    $form_ok = false;
                }
                // verif format mail
                elseif (!$this->ficelle->isEmail($_POST['email_representative']))
                {
                    $form_ok = false;
                }
                // conf email good/pas
                elseif ($_POST['email_representative'] != $_POST['conf_email_representative'])
                {
                    $form_ok = false;
                }
                //==> le reste pour le mail un peu plus bas <==
                // si conseil externe
                if (isset($_POST['radio1-about']) && $_POST['radio1-about'] == 3)
                {
                    // externe
                    if (!isset($_POST['autre']) || $_POST['autre'] == '0')
                    {
                        $form_ok = false;
                    }
                    // civilite
                    if (!isset($_POST['sex']) || $_POST['sex'] == '')
                    {
                        $form_ok = false;
                    }
                    // nom
                    if (!isset($_POST['nom-famille']) || $_POST['nom-famille'] == '' || $_POST['nom-famille'] == $this->lng['etape2']['nom'])
                    {
                        $form_ok = false;
                    }
                    // prenom
                    if (!isset($_POST['prenom']) || $_POST['prenom'] == '' || $_POST['prenom'] == $this->lng['etape2']['prenom'])
                    {
                        $form_ok = false;
                    }
                    // fonction_representative
                    if (!isset($_POST['fonction']) || $_POST['fonction'] == '' || $_POST['fonction'] == $this->lng['etape2']['fonction'])
                    {
                        $form_ok = false;
                    }
                    // email
                    if (!isset($_POST['email']) || $_POST['email'] == '' || $_POST['email'] == $this->lng['etape2']['email'])
                    {
                        $form_ok = false;
                    }
                    // verif format mail
                    elseif (!$this->ficelle->isEmail($_POST['email']))
                    {
                        $form_ok = false;
                    }
                    // conf email good/pas
                    elseif ($_POST['email'] != $_POST['conf_email'])
                    {
                        $form_ok = false;
                    }
                    // email existe deja
                    elseif (!$this->clients->existEmail($_POST['email']))
                    {
                        if (isset($_SESSION['client']) && $this->email_temp == $_POST['email'])
                        {
                            
                        }
                        else
                        {

                            // si emprunteur on créer une adresse mail avec son id projet
                            $clients = $this->loadData('clients');
                            if ($clients->counter('email = "' . $_POST['email'] . '"') > 0)
                            {
                                $this->lemailduclient = $this->clients->email;
                                $this->clients->email .= '-' . $this->projects->id_project;
                            }
                            // sinon com d'hab on valide pas
                            else
                            {
                                $form_ok = false;
                                $this->error_email_exist = true;
                            }
                        }
                    }
                    // phone
                    if (!isset($_POST['phone']) || $_POST['phone'] == '' || $_POST['phone'] == $this->lng['etape2']['telephone'] || strlen($_POST['phone']) < 9 && strlen($_POST['phone']) > 14)
                    {
                        $form_ok = false;
                    }
                }
                else
                {
                    // email existe deja
                    if (isset($_POST['email_representative']) && !$this->clients->existEmail($_POST['email_representative']))
                    {

                        if (isset($_SESSION['client']) && $this->email_temp == $_POST['email_representative'])
                        {
                            
                        }
                        else
                        {

                            // si emprunteur on créer une adresse mail avec son id projet
                            $clients = $this->loadData('clients');
                            if ($clients->counter('email = "' . $_POST['email_representative'] . '"') > 0)
                            {
                                $this->lemailduclient = $this->clients->email;
                                $this->clients->email .= '-' . $this->projects->id_project;
                            }
                            // sinon com d'hab on valide pas
                            else
                            {
                                $form_ok = false;
                                $this->error_email_representative_exist = true;
                            }
                        }
                    }
                }

                //Si on a une session client
                if (isset($_SESSION['client']))
                {
                    // On recup le mec
                    $this->clients->get($_SESSION['client']['id_client'], 'id_client');

                    // Si c'est un preteur
                    if ($this->clients->status_pre_emp == 1)
                    {
                        // On controle si personne physique
                        if ($this->clients->type == 1)
                        {
                            $_SESSION['error_pre_empr'] = $this->lng['etape1']['seule-une-personne-morale-peut-creer-un-compte-emprunteur'];
                            // personne physique on dit non
                            header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                            die;
                        }
                    }
                    // Si c'est un emprunteur ou deja un preteur/emprunteur
                    elseif ($this->clients->status_pre_emp == 2 || $this->clients->status_pre_emp == 3)
                    {
                        $_SESSION['error_pre_empr'] = $this->lng['etape1']['vous-disposez-deja-dun-compte-emprunteur'];
                        // Si emprunteur pas le droit de créer un autre compte en etant connecté
                        header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                        die;
                    }
                }

                // Si les champs sont bien remplis
                if ($form_ok == true)
                {
                    // clients
                    $this->clients->id_langue = 'fr';
                    $this->clients->slug = $this->bdd->generateSlug($this->clients->prenom . '-' . $this->clients->nom);

                    // l'email facture est la meme que l'email client a la creation
                    $this->companies->email_facture = $this->clients->email;

                    // On fait une mise à jour
                    $this->clients->update();
                    $this->clients_adresses->update();
                    $this->companies->update();
                    $this->companies_details->update();
                    $this->projects->update();


                    // -- acceptation des cgu -- // 
                    if ($this->acceptations_legal_docs->get($this->lienConditionsGenerales, 'id_client = "' . $this->clients->id_client . '" AND id_legal_doc'))
                        $accepet_ok = true;
                    else
                        $accepet_ok = false;
                    $this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGenerales;
                    $this->acceptations_legal_docs->id_client = $this->clients->id_client;
                    if ($accepet_ok == true)
                        $this->acceptations_legal_docs->update();
                    else
                        $this->acceptations_legal_docs->create();
                    // -- fin partie cgu -- // 
                    // si good page confirmation
                    $this->projects_status_history->addStatus(-2, 10, $this->projects->id_project);

                    // Creation du mot de passe client
                    $lemotdepasse = '';
                    if (isset($_SESSION['client']))
                    {
                        $this->clients->status_pre_emp = 3;
                        $_SESSION['status_pre_emp'] = 1;
                    }
                    else
                    {
                        $this->clients->status_pre_emp = 2;
                        $lemotdepasse = $this->ficelle->generatePassword(8);
                        $this->clients->password = md5($lemotdepasse);
                    }
                    $this->clients->status_depot_dossier = 5;
                    $this->clients->status_transition = 1;
                    $this->clients->status = 1;

                    $this->clients->id_client = $this->clients->update();

                    if ($this->lemailduclient != '')
                        $this->clients->email = $this->lemailduclient;

                    //**********************************************//
                    //*** ENVOI DU MAIL CONFIRMATION INSCRIPTION ***//
                    //**********************************************//
                    // Recuperation du modele de mail
                    $this->mails_text->get('confirmation-depot-de-dossier', 'lang = "' . $this->language . '" AND type');

                    // Variables du mailing
                    $surl = $this->surl;
                    $url = $this->lurl;
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
                        'password' => $lemotdepasse,
                        'lien_fb' => $lien_fb,
                        'lien_tw' => $lien_tw);

                    // Construction du tableau avec les balises EMV
                    $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                    // Attribution des données aux variables
                    $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                    $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                    $exp_name = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

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
                    }
                    else // non nmp
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
                    $surl = $this->surl;
                    $url = $this->lurl;
                    $nom_societe = utf8_decode($this->companies->name);
                    $montant_pret = $this->projects->amount;
                    $lien = $this->aurl . '/emprunteurs/edit/' . $this->clients->id_client;

                    // Attribution des données aux variables
                    $sujetMail = htmlentities($this->mails_text->subject);
                    eval("\$sujetMail = \"$sujetMail\";");

                    $texteMail = $this->mails_text->content;
                    eval("\$texteMail = \"$texteMail\";");

                    $exp_name = $this->mails_text->exp_name;
                    eval("\$exp_name = \"$exp_name\";");

                    // Nettoyage de printemps
                    $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
                    $exp_name = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

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
                    header('location:' . $this->lurl . '/' . $this->tree->getSlug(48, $this->language));
                    die;
                }
            } // fin formulaire etape 2
        }
        else
        {
            // redirection etape 1
            //....................	
        }
    }

    function _etape3()
    {
        die;
        //////////////////////////////////
        // Initialisation variable
        $this->preteurCreateEmprunteur = false;

        // Si on a une session active
        if (isset($_SESSION['client']))
        {

            // On recup le mec
            $this->clients->get($_SESSION['client']['id_client'], 'id_client');

            // Si c'est un preteur
            if ($this->clients->status_pre_emp == 1)
            {
                // On controle si personne morale
                if ($this->clients->type == 2)
                {
                    $this->preteurCreateEmprunteur = true;

                    // Si personne morale on recup l'entreprise
                    $this->companies->get($this->clients->id_client, 'id_client_owner');
                }
                else
                {
                    // Si personne physique pas le droit
                    header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                    die;
                }
            }
            // Si c'est un emprunteur
            elseif ($this->clients->status_pre_emp == 2)
            {
                // Si emprunteur pas le droit de créer un autre compte en etant connecté
                header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                die;
            }
            // Si deja preteur/emprunteur
            else
            {
                // Si emprunteur pas le droit de créer un autre compte en etant connecté
                header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                die;
            }
        }
        //////////////////////////////////


        if ($this->preteurCreateEmprunteur == true && $this->clients->status_depot_dossier >= 2)
        {
            $conditionOk = true;
        }
        elseif ($this->clients->get($this->params['0'], 'status = 0 AND hash') && $this->clients->status_depot_dossier >= 2)
        {
            $conditionOk = true;
        }
        else
        {
            $conditionOk = false;
        }


        // On récupere les infos clients
        if ($conditionOk == true)
        {
            // recup companie
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // recup projet ($this->projects->get(...) car lors de l'inscription il n'y a qu'un projet)
            $this->projects->getFirstProject($this->companies->id_company);

            //$this->projects->get($this->companies->id_company ,'id_company ');
            //Recuperation des element de traductions
            $this->lng['etape3'] = $this->ln->selectFront('depot-de-dossier-etape-3', $this->language, $this->App);

            // Num page
            $this->page = 3;

            if (isset($_POST['send_form_etape_3']))
            {
                // enregistrement des informations
                $this->projects->amount = str_replace(',', '.', str_replace(' ', '', $_POST['montant']));
                $this->projects->period = $_POST['duree'];
                $this->projects->title = $_POST['project-title'];
                $this->projects->objectif_loan = $_POST['credit-objective'];
                $this->projects->presentation_company = $_POST['presentation'];
                $this->projects->means_repayment = $_POST['moyen'];

                $this->form_ok = true;

                if (!isset($_POST['montant']) || $_POST['montant'] == '' || $_POST['montant'] == $this->lng['etape3']['montant'])
                {
                    $this->form_ok = false;
                }
                if (!isset($_POST['duree']) || $_POST['duree'] == '' || $_POST['duree'] == 0)
                {
                    $this->form_ok = false;
                }
                if (!isset($_POST['project-title']) || $_POST['project-title'] == '' || $_POST['project-title'] == $this->lng['etape3']['titre-projet'])
                {
                    $this->form_ok = false;
                }
                if (!isset($_POST['credit-objective']) || $_POST['credit-objective'] == '' || $_POST['credit-objective'] == $this->lng['etape3']['objectif-du-credit'])
                {
                    $this->form_ok = false;
                }
                if (!isset($_POST['presentation']) || $_POST['presentation'] == '' || $_POST['presentation'] == $this->lng['etape3']['presentation-de-la-societe'])
                {
                    $this->form_ok = false;
                }
                if (!isset($_POST['moyen']) || $_POST['moyen'] == '' || $_POST['moyen'] == $this->lng['etape3']['moyen-de-remboursement-prevu'])
                {
                    $this->form_ok = false;
                }

                // Si form ok
                if ($this->form_ok == true)
                {

                    // stand-by = sauvegarde des infos avec envoie d'un mail pour reprendre là où on etait
                    if (isset($this->params['1']) && $this->params['1'] == 'stand-by')
                    {
                        $this->projects->stand_by = 1;

                        // Enregistrement des infos du projet
                        $this->projects->update();

                        //******************************//
                        //*** ENVOI DU MAIL STAND-BY ***//
                        //******************************//
                        // Recuperation du modele de mail
                        $this->mails_text->get('emprunteur-stand-by-depot-de-dossier', 'lang = "' . $this->language . '" AND type');

                        // Variables du mailing
                        $surl = $this->surl;
                        $url = $this->lurl;
                        $email = $this->clients->email;
                        $link_login = $this->lurl . '/depot_de_dossier/stand_by/' . $this->clients->hash;
                        $prenom = $this->clients->prenom;
                        $dateTime = strtotime($this->projects->added);
                        $date = date('d', $dateTime) . ' ' . $this->dates->tableauMois['fr'][date('n', $dateTime)] . ' ' . date('Y', $dateTime);

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
                            'prenom_e' => $prenom,
                            'date' => $date,
                            'link_compte_emprunteur' => $link_login,
                            'lien_fb' => $lien_fb,
                            'lien_tw' => $lien_tw);


                        // Construction du tableau avec les balises EMV
                        $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                        // Attribution des données aux variables
                        $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                        $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                        $exp_name = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

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

                        $_SESSION['confirmation']['valid'] = $this->lng['etape3']['valid-stand-by'];

                        header('location:' . $this->lurl . '/depot_de_dossier/etape3/' . $this->clients->hash);
                        die;
                    }
                    // Etape suivante
                    else
                    {

                        $this->projects->stand_by = 0;

                        // Enregistrement des infos du projet
                        $this->projects->update();

                        // si on a pas encore ete a letape suivante
                        if ($this->clients->status_depot_dossier < 3)
                        {
                            // Mise à jour du client
                            $this->clients->status_depot_dossier = 3;
                            $this->clients->update();

                            // -- Pour l'etape suivante (etape 4) -- //
                            // On génère une ligne companie detaille de l'entreprise
                            $this->companies_details->id_company = $this->companies->id_company;
                            $this->companies_details->create();

                            // On génère 5 lignes dans la base pour les bilans de l'etape suivante (etape 4)
                            for ($i = 0; $i < 5; $i++)
                            {
                                if ($i == 0)
                                    $date = date('Y') - 3;
                                if ($i == 1)
                                    $date = date('Y') - 2;
                                if ($i == 2)
                                    $date = date('Y') - 1;
                                if ($i == 3)
                                    $date = date('Y');
                                if ($i == 4)
                                    $date = date('Y') + 1;

                                $this->companies_bilans->id_company = $this->companies->id_company;
                                $this->companies_bilans->date = $date;
                                $this->companies_bilans->create();
                            }
                            // -- Fin des creations pour l'etape 4 -- //
                        }



                        header('location:' . $this->lurl . '/depot_de_dossier/etape4/' . $this->clients->hash);
                        die;
                    }
                }
            }
        }
        else
        {
            header('location:' . $this->lurl . '/depot_de_dossier/etape1');
            die;
        }
    }

    function _etape4()
    {
        die;
        //////////////////////////////////
        // Initialisation variable
        $this->preteurCreateEmprunteur = false;

        // Si on a une session active
        if (isset($_SESSION['client']))
        {

            // On recup le mec
            $this->clients->get($_SESSION['client']['id_client'], 'id_client');

            // Si c'est un preteur
            if ($this->clients->status_pre_emp == 1)
            {
                // On controle si personne morale
                if ($this->clients->type == 2)
                {
                    $this->preteurCreateEmprunteur = true;

                    // Si personne morale on recup l'entreprise
                    $this->companies->get($this->clients->id_client, 'id_client_owner');
                }
                else
                {
                    // Si personne physique pas le droit
                    header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                    die;
                }
            }
            // Si c'est un emprunteur
            elseif ($this->clients->status_pre_emp == 2)
            {
                // Si emprunteur pas le droit de créer un autre compte en etant connecté
                header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                die;
            }
            // Si deja preteur/emprunteur
            else
            {
                // Si emprunteur pas le droit de créer un autre compte en etant connecté
                header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                die;
            }
        }
        //////////////////////////////////


        if ($this->preteurCreateEmprunteur == true && $this->clients->status_depot_dossier >= 3)
        {
            $conditionOk = true;
        }
        elseif ($this->clients->get($this->params['0'], 'status = 0 AND hash') && $this->clients->status_depot_dossier >= 3)
        {
            $conditionOk = true;
        }
        else
        {
            $conditionOk = false;
        }


        // On récupere les infos clients
        if ($conditionOk == true)
        {
            // recup companie
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // Liste des bilans de l'entreprise
            $this->lBilans = $this->companies_bilans->select('id_company = ' . $this->companies->id_company, 'date asc');

            // On récupere le détaille companie
            $this->companies_details->get($this->companies->id_company, 'id_company');

            //Recuperation des element de traductions
            $this->lng['etape4'] = $this->ln->selectFront('depot-de-dossier-etape-4', $this->language, $this->App);

            // Num page
            $this->page = 4;

            // SIREN
            $this->siren = $this->companies->siren;


            $dateDernierBilan = explode('-', $this->companies_details->date_dernier_bilan);
            $this->date_dernier_bilan_jour = $dateDernierBilan[2];
            $this->date_dernier_bilan_mois = $dateDernierBilan[1];
            $this->date_dernier_bilan_annee = $dateDernierBilan[0];



            $this->altares_ok = false;

            if ($this->clients->status_depot_dossier == 3 || $_SESSION['dejaDisplay'] == false)
            {
                // Web Service Altares
                $result = $this->ficelle->ws($this->wsdl, $this->identification, $this->siren);



                // Si pas d'erreur
                if ($result->exception == '')
                {
                    // on met en session pour eviter de recharger alteres
                    $_SESSION['dejaDisplay'] = true;

                    // verif reponse
                    $eligibility = $result->myInfo->eligibility;

                    if ($eligibility == 'Oui')
                    {
                        $this->altares_ok = true;

                        // les objets
                        $identite = $result->myInfo->identite;

                        $syntheseFinanciereInfo = $result->myInfo->syntheseFinanciereInfo;
                        $syntheseFinanciereList = $result->myInfo->syntheseFinanciereInfo->syntheseFinanciereList;

                        $posteActifList = array();
                        $postePassifList = array();
                        $syntheseFinanciereInfo = array();
                        $syntheseFinanciereList = array();
                        $derniersBilans = array();
                        $i = 0;
                        foreach ($result->myInfo->bilans as $b)
                        {

                            $annee = substr($b->bilan->dateClotureN, 0, 4);
                            $posteActifList[$annee] = $b->bilanRetraiteInfo->posteActifList;
                            $postePassifList[$annee] = $b->bilanRetraiteInfo->postePassifList;
                            $syntheseFinanciereInfo[$annee] = $b->syntheseFinanciereInfo;
                            $syntheseFinanciereList[$annee] = $b->syntheseFinanciereInfo->syntheseFinanciereList;

                            $soldeIntermediaireGestionInfo[$annee] = $b->soldeIntermediaireGestionInfo->SIGList;

                            $investissement[$annee] = $b->bilan->posteList[0]->valeur;

                            // date des derniers bilans
                            $derniersBilans[$i] = $annee;

                            $i++;
                        }


                        // dernier bilan 
                        $dateDernierBilan = substr($identite->dateDernierBilan, 0, 10);
                        $dateDernierBilan = explode('-', $dateDernierBilan);


                        $this->companies_details->date_dernier_bilan = $dateDernierBilan;

                        $this->date_dernier_bilan_jour = $dateDernierBilan[2];
                        $this->date_dernier_bilan_mois = $dateDernierBilan[1];
                        $this->date_dernier_bilan_annee = $dateDernierBilan[0];




                        // date courrante
                        $date[4] = date('Y') + 1;
                        $date[3] = date('Y');
                        $date[2] = date('Y') - 1;
                        $date[1] = date('Y') - 2;
                        $date[0] = date('Y') - 3;

                        // on génère un tableau avec les données
                        for ($i = 0; $i < 5; $i++) // on parcourt les 5 années
                        {
                            for ($a = 0; $a < 3; $a++)// on parcourt les 3 dernieres années
                            {
                                // si y a une année du bilan qui correxpond a une année du tableau
                                if ($derniersBilans[$a] == $date[$i])
                                {
                                    // On recup les données de cette année

                                    $this->lBilans[$i]['ca'] = $syntheseFinanciereList[$date[$i]][0]->montantN;
                                    $this->lBilans[$i]['resultat_exploitation'] = $syntheseFinanciereList[$date[$i]][1]->montantN;
                                    $this->lBilans[$i]['resultat_brute_exploitation'] = $soldeIntermediaireGestionInfo[$date[$i]][9]->montantN;

                                    $this->lBilans[$i]['investissements'] = $investissement[$date[$i]];
                                }
                            }
                        }
                    }
                }
                else
                {
                    mail('d.courtier@equinoa.com', 'unilend erreur', 'Erreur etape 4 depot dossier : ' . $result->exception->erreur);
                }
            }

            if (isset($_POST['send_form_etape_4']))
            {

                $this->companies_details->date_dernier_bilan = $_POST['annee'] . '-' . $_POST['mois'] . '-' . $_POST['jour'];

                $this->companies_details->encours_actuel_dette_fianciere = str_replace(' ', '', $_POST['encours_actuel_dette_fianciere']);
                $this->companies_details->remb_a_venir_cette_annee = str_replace(' ', '', $_POST['remb_a_venir_cette_annee']);
                $this->companies_details->remb_a_venir_annee_prochaine = str_replace(' ', '', $_POST['remb_a_venir_annee_prochaine']);
                $this->companies_details->tresorie_dispo_actuellement = str_replace(' ', '', $_POST['tresorie_dispo_actuellement']);
                $this->companies_details->autre_demandes_financements_prevues = str_replace(' ', '', $_POST['autre_demandes_financements_prevues']);

                if ($_POST['precisions'] == $this->lng['etape4']['vous-souhaitez-apporter-des-precisions'])
                    $this->companies_details->precisions = '';
                else
                    $this->companies_details->precisions = $_POST['precisions'];

                $this->form_ok = true;

                if (!isset($_POST['mois']) || $_POST['mois'] == '' || $_POST['mois'] == 0)
                {
                    $this->form_ok = false;
                }
                if (!isset($_POST['annee']) || $_POST['annee'] == '' || $_POST['annee'] == '0000')
                {
                    $this->form_ok = false;
                }

                if ($this->form_ok == true)
                {

                    // -- Debut actif/passif -- //

                    if ($this->altares_ok == true)
                    {

                        foreach ($derniersBilans as $annees)
                        {
                            foreach ($posteActifList[$annees] as $a)
                            {
                                $ActifPassif[$annees][$a->posteCle] = $a->montant;
                            }
                            foreach ($postePassifList[$annees] as $p)
                            {
                                $ActifPassif[$annees][$p->posteCle] = $p->montant;
                            }
                        }

                        // Liste des actif passif (pour le BO)
                        $this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_company = "' . $this->companies->id_company . '"');
                        // Si existe pas on créer les champs
                        if ($this->lCompanies_actif_passif == false)
                        {
                            // les 3 dernieres vrais années
                            $date[1] = (date('Y') - 1);
                            $date[2] = (date('Y') - 2);
                            $date[3] = (date('Y') - 3);

                            $i = 1;
                            foreach ($ActifPassif as $key => $ap)
                            {
                                $this->companies_actif_passif->annee = $date[$i];
                                $this->companies_actif_passif->ordre = $i;
                                $this->companies_actif_passif->id_company = $this->companies->id_company;

                                if ($ActifPassif[$date[$i]] != false)
                                {
                                    // Actif
                                    $this->companies_actif_passif->immobilisations_corporelles = $ActifPassif[$date[$i]]['posteBR_IMCOR'];
                                    $this->companies_actif_passif->immobilisations_incorporelles = $ActifPassif[$date[$i]]['posteBR_IMMINC'];
                                    $this->companies_actif_passif->immobilisations_financieres = $ActifPassif[$date[$i]]['posteBR_IMFI'];
                                    $this->companies_actif_passif->stocks = $ActifPassif[$date[$i]]['posteBR_STO'];
                                    //creances_clients = Avances et acomptes + creances clients + autre creances et cca + autre creances hors exploitation
                                    $this->companies_actif_passif->creances_clients = $ActifPassif[$date[$i]]['posteBR_BV'] + $ActifPassif[$date[$i]]['posteBR_BX'] + $ActifPassif[$date[$i]]['posteBR_ACCCA'] + $ActifPassif[$date[$i]]['posteBR_ACHE_'];
                                    $this->companies_actif_passif->disponibilites = $ActifPassif[$date[$i]]['posteBR_CF'];
                                    $this->companies_actif_passif->valeurs_mobilieres_de_placement = $ActifPassif[$date[$i]]['posteBR_CD'];

                                    // passif
                                    // capitaux_propres = capitaux propres + non valeurs
                                    $this->companies_actif_passif->capitaux_propres = $ActifPassif[$date[$i]]['posteBR_CPRO'] + $ActifPassif[$date[$i]]['posteBR_NONVAL'];
                                    // provisions_pour_risques_et_charges = Provisions pour risques et charges + Provisions actif circulant
                                    $this->companies_actif_passif->provisions_pour_risques_et_charges = $ActifPassif[$date[$i]]['posteBR_PROVRC'] + $ActifPassif[$date[$i]]['posteBR_PROAC'];

                                    $this->companies_actif_passif->amortissement_sur_immo = $ActifPassif[$date[$i]]['posteBR_AMPROVIMMO'];
                                    // dettes_financieres = Emprunts + Dettes groupe et associés + Concours bancaires courants
                                    $this->companies_actif_passif->dettes_financieres = $ActifPassif[$date[$i]]['posteBR_EMP'] + $ActifPassif[$date[$i]]['posteBR_VI'] + $ActifPassif[$date[$i]]['posteBR_EH'];

                                    // dettes_fournisseurs = Avances et Acomptes clients + Dettes fournisseurs
                                    $this->companies_actif_passif->dettes_fournisseurs = $ActifPassif[$date[$i]]['posteBR_DW'] + $ActifPassif[$date[$i]]['posteBR_DX'];

                                    // autres_dettes = autres dettes exploi + Dettes sur immos et comptes rattachés + autres dettes hors exploi
                                    $this->companies_actif_passif->autres_dettes = $ActifPassif[$date[$i]]['posteBR_AUTDETTEXPL'] + $ActifPassif[$date[$i]]['posteBR_DZ'] + $ActifPassif[$date[$i]]['posteBR_AUTDETTHEXPL'];
                                }
                                else
                                {
                                    $this->companies_actif_passif->immobilisations_corporelles = '';
                                    $this->companies_actif_passif->immobilisations_incorporelles = '';
                                    $this->companies_actif_passif->immobilisations_financieres = '';
                                    $this->companies_actif_passif->stocks = '';
                                    $this->companies_actif_passif->creances_clients = '';
                                    $this->companies_actif_passif->disponibilites = '';
                                    $this->companies_actif_passif->valeurs_mobilieres_de_placement = '';
                                    $this->companies_actif_passif->capitaux_propres = '';
                                    $this->companies_actif_passif->provisions_pour_risques_et_charges = '';
                                    $this->companies_actif_passif->amortissement_sur_immo = '';
                                    $this->companies_actif_passif->dettes_financieres = '';
                                    $this->companies_actif_passif->dettes_fournisseurs = '';
                                    $this->companies_actif_passif->autres_dettes = '';
                                }
                                $this->companies_actif_passif->create();
                                $i++;
                            }
                        }
                    }
                    // -- Fin actif/passif -- //
                    // On enregistre les bilans
                    foreach ($this->lBilans as $k => $b)
                    {
                        $this->companies_bilans->get($b['id_bilan'], 'id_bilan');

                        $this->companies_bilans->ca = str_replace(' ', '', $_POST['ca-' . $k]);
                        $this->companies_bilans->resultat_brute_exploitation = str_replace(' ', '', $_POST['resultat_brute_exploitation-' . $k]);
                        $this->companies_bilans->resultat_exploitation = str_replace(' ', '', $_POST['resultat_exploitation-' . $k]);
                        $this->companies_bilans->investissements = str_replace(' ', '', $_POST['investissements-' . $k]);

                        $this->companies_bilans->update();
                    }

                    // On enregistre le detail
                    $this->companies_details->update();

                    // si stand by on envoie un mail pour y retourner plus tard
                    if (isset($this->params['1']) && $this->params['1'] == 'stand-by')
                    {

                        // recup projet ($this->projects->get(...) car lors de l'inscription il n'y a qu'un projet)
                        $this->projects->get($this->companies->id_company, 'id_company ');
                        $this->projects->stand_by = 1;
                        $this->projects->update();

                        //******************************//
                        //*** ENVOI DU MAIL STAND-BY ***//
                        //******************************//
                        // Recuperation du modele de mail
                        $this->mails_text->get('emprunteur-stand-by-depot-de-dossier', 'lang = "' . $this->language . '" AND type');


                        // Variables du mailing
                        $surl = $this->surl;
                        $url = $this->lurl;
                        $email = $this->clients->email;
                        $link_login = $this->lurl . '/depot_de_dossier/stand_by/' . $this->clients->hash;
                        $prenom = $this->clients->prenom;
                        $dateTime = strtotime($this->projects->added);
                        $date = date('d', $dateTime) . ' ' . $this->dates->tableauMois['fr'][date('n', $dateTime)] . ' ' . date('Y', $dateTime);

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
                            'prenom_e' => $prenom,
                            'date' => $date,
                            'link_compte_emprunteur' => $link_login,
                            'lien_fb' => $lien_fb,
                            'lien_tw' => $lien_tw);


                        // Construction du tableau avec les balises EMV
                        $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                        // Attribution des données aux variables
                        $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                        $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                        $exp_name = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

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

                        $_SESSION['confirmation']['valid'] = $this->lng['etape4']['valid-stand-by'];

                        header('location:' . $this->lurl . '/depot_de_dossier/etape4/' . $this->clients->hash);
                        die;
                    }
                    else
                    {

                        // recup projet ($this->projects->get(...) car lors de l'inscription il n'y a qu'un projet)
                        $this->projects->get($this->companies->id_company, 'id_company ');
                        $this->projects->stand_by = 0;
                        $this->projects->update();

                        // Mise à jour du client
                        if ($this->clients->status_depot_dossier < 4)
                        {
                            $this->clients->status_depot_dossier = 4;
                            $this->clients->update();
                        }

                        header('location:' . $this->lurl . '/depot_de_dossier/etape5/' . $this->clients->hash);
                        die;
                    }
                }
            }
        }
        else
        {
            header('location:' . $this->lurl . '/depot_de_dossier/etape1');
            die;
        }
    }

    function _etape5()
    {
        die;
        // Chargement des datas
        $this->users = $this->loadData('users');
        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');

        //////////////////////////////////
        // Initialisation variable
        $this->preteurCreateEmprunteur = false;

        // Si on a une session active
        if (isset($_SESSION['client']))
        {

            // On recup le mec
            $this->clients->get($_SESSION['client']['id_client'], 'id_client');

            // Si c'est un preteur
            if ($this->clients->status_pre_emp == 1)
            {
                // On controle si personne morale
                if ($this->clients->type == 2)
                {
                    $this->preteurCreateEmprunteur = true;

                    // Si personne morale on recup l'entreprise
                    $this->companies->get($this->clients->id_client, 'id_client_owner');
                }
                else
                {
                    // Si personne physique pas le droit
                    header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                    die;
                }
            }
            // Si c'est un emprunteur
            elseif ($this->clients->status_pre_emp == 2)
            {
                // Si emprunteur pas le droit de créer un autre compte en etant connecté
                header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                die;
            }
            // Si deja preteur/emprunteur
            else
            {
                // Si emprunteur pas le droit de créer un autre compte en etant connecté
                header('location:' . $this->lurl . '/depot_de_dossier/etape1');
                die;
            }
        }
        //////////////////////////////////


        if ($this->preteurCreateEmprunteur == true && $this->clients->status_depot_dossier >= 4)
        {
            $conditionOk = true;
        }
        elseif ($this->clients->get($this->params['0'], 'status = 0 AND hash') && $this->clients->status_depot_dossier >= 4)
        {
            $conditionOk = true;
        }
        else
        {
            $conditionOk = false;
        }


        // On récupere les infos clients
        if ($conditionOk == true)
        {
            // recup companie
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // On récupere le détaille companie
            $this->companies_details->get($this->companies->id_company, 'id_company');

            // recup projet (GET car lors de l'inscription il n'y a qu'un projet)
            $this->projects->get($this->companies->id_company, 'id_company ');

            //Recuperation des element de traductions
            $this->lng['etape5'] = $this->ln->selectFront('depot-de-dossier-etape-5', $this->language, $this->App);

            // Num page
            $this->page = 5;

            $this->settings->get('Lien conditions generales depot dossier', 'type');
            $this->lienConditionsGenerales = $this->settings->value;

            if (isset($_POST['send_form_upload']))
            {
                // extrait_kbis
                if (isset($_FILES['fichier1']) && $_FILES['fichier1']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/extrait_kbis/');
                    if ($this->upload->doUpload('fichier1'))
                    {
                        if ($this->companies_details->fichier_extrait_kbis != '')
                            @unlink($this->path . 'protected/companies/extrait_kbis/' . $this->companies_details->fichier_extrait_kbis);
                        $this->companies_details->fichier_extrait_kbis = $this->upload->getName();
                    }
                }
                // fichier_rib 
                if (isset($_FILES['fichier2']) && $_FILES['fichier2']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/rib/');
                    if ($this->upload->doUpload('fichier2'))
                    {
                        if ($this->companies_details->fichier_rib != '')
                            @unlink($this->path . 'protected/companies/rib/' . $this->companies_details->fichier_rib);
                        $this->companies_details->fichier_rib = $this->upload->getName();
                    }
                }
                // fichier_delegation_pouvoir 
                if (isset($_FILES['fichier3']) && $_FILES['fichier3']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/delegation_pouvoir/');
                    if ($this->upload->doUpload('fichier3'))
                    {
                        if ($this->companies_details->fichier_delegation_pouvoir != '')
                            @unlink($this->path . 'protected/companies/delegation_pouvoir/' . $this->companies_details->fichier_delegation_pouvoir);
                        $this->companies_details->fichier_delegation_pouvoir = $this->upload->getName();
                    }
                }
                // fichier_logo_societe 
                if (isset($_FILES['fichier4']) && $_FILES['fichier4']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'public/default/var/images/logos_companies/');
                    if ($this->upload->doUpload('fichier4'))
                    {
                        if ($this->companies_details->fichier_logo_societe != '')
                            @unlink($this->path . 'public/default/var/images/logos_companies/' . $this->companies_details->fichier_logo_societe);
                        $this->companies_details->fichier_logo_societe = $this->upload->getName();
                    }
                }
                // fichier_photo_dirigeant 
                if (isset($_FILES['fichier5']) && $_FILES['fichier5']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/photo_dirigeant/');
                    if ($this->upload->doUpload('fichier5'))
                    {
                        if ($this->companies_details->fichier_photo_dirigeant != '')
                            @unlink($this->path . 'protected/companies/photo_dirigeant/' . $this->companies_details->fichier_photo_dirigeant);
                        $this->companies_details->fichier_photo_dirigeant = $this->upload->getName();
                    }
                }


                // fichier_cni_passeport
                if (isset($_FILES['fichier6']) && $_FILES['fichier6']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/cni_passeport/');
                    if ($this->upload->doUpload('fichier6'))
                    {
                        if ($this->companies_details->fichier_cni_passeport != '')
                            @unlink($this->path . 'protected/companies/cni_passeport/' . $this->companies_details->fichier_cni_passeport);
                        $this->companies_details->fichier_cni_passeport = $this->upload->getName();
                    }
                }
                // fichier_derniere_liasse_fiscale
                if (isset($_FILES['fichier7']) && $_FILES['fichier7']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/derniere_liasse_fiscale/');
                    if ($this->upload->doUpload('fichier7'))
                    {
                        if ($this->companies_details->fichier_derniere_liasse_fiscale != '')
                            @unlink($this->path . 'protected/companies/derniere_liasse_fiscale/' . $this->companies_details->fichier_derniere_liasse_fiscale);
                        $this->companies_details->fichier_derniere_liasse_fiscale = $this->upload->getName();
                    }
                }
                // fichier_derniers_comptes_approuves
                if (isset($_FILES['fichier8']) && $_FILES['fichier8']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/derniers_comptes_approuves/');
                    if ($this->upload->doUpload('fichier8'))
                    {
                        if ($this->companies_details->fichier_derniers_comptes_approuves != '')
                            @unlink($this->path . 'protected/companies/derniers_comptes_approuves/' . $this->companies_details->fichier_derniers_comptes_approuves);
                        $this->companies_details->fichier_derniers_comptes_approuves = $this->upload->getName();
                    }
                }
                // fichier_derniers_comptes_consolides_groupe 
                if (isset($_FILES['fichier9']) && $_FILES['fichier9']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/derniers_comptes_consolides_groupe/');
                    if ($this->upload->doUpload('fichier9'))
                    {
                        if ($this->companies_details->fichier_derniers_comptes_consolides_groupe != '')
                            @unlink($this->path . 'protected/companies/derniers_comptes_consolides_groupe/' . $this->companies_details->fichier_derniers_comptes_consolides_groupe);
                        $this->companies_details->fichier_derniers_comptes_consolides_groupe = $this->upload->getName();
                    }
                }
                // fichier_annexes_rapport_special_commissaire_compte 
                if (isset($_FILES['fichier10']) && $_FILES['fichier10']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/annexes_rapport_special_commissaire_compte/');
                    if ($this->upload->doUpload('fichier10'))
                    {
                        if ($this->companies_details->fichier_annexes_rapport_special_commissaire_compte != '')
                            @unlink($this->path . 'protected/companies/annexes_rapport_special_commissaire_compte/' . $this->companies_details->fichier_annexes_rapport_special_commissaire_compte);
                        $this->companies_details->fichier_annexes_rapport_special_commissaire_compte = $this->upload->getName();
                    }
                }
                // fichier_arret_comptable_recent 
                if (isset($_FILES['fichier11']) && $_FILES['fichier11']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/arret_comptable_recent/');
                    if ($this->upload->doUpload('fichier11'))
                    {
                        if ($this->companies_details->fichier_arret_comptable_recent != '')
                            @unlink($this->path . 'protected/companies/arret_comptable_recent/' . $this->companies_details->fichier_arret_comptable_recent);
                        $this->companies_details->fichier_arret_comptable_recent = $this->upload->getName();
                    }
                }
                // fichier_budget_exercice_en_cours_a_venir 
                if (isset($_FILES['fichier12']) && $_FILES['fichier12']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/budget_exercice_en_cours_a_venir/');
                    if ($this->upload->doUpload('fichier12'))
                    {
                        if ($this->companies_details->fichier_budget_exercice_en_cours_a_venir != '')
                            @unlink($this->path . 'protected/companies/budget_exercice_en_cours_a_venir/' . $this->companies_details->fichier_budget_exercice_en_cours_a_venir);
                        $this->companies_details->fichier_budget_exercice_en_cours_a_venir = $this->upload->getName();
                    }
                }
                // fichier_notation_banque_france 
                if (isset($_FILES['fichier13']) && $_FILES['fichier13']['name'] != '')
                {
                    $this->upload->setUploadDir($this->path, 'protected/companies/notation_banque_france/');
                    if ($this->upload->doUpload('fichier13'))
                    {
                        if ($this->companies_details->fichier_notation_banque_france != '')
                            @unlink($this->path . 'protected/companies/notation_banque_france/' . $this->companies_details->fichier_notation_banque_france);
                        $this->companies_details->fichier_notation_banque_france = $this->upload->getName();
                    }
                }


                // Enregistrement des images
                $this->companies_details->update();
            }

            if (isset($_POST['send_form_etape_5']))
            {
                // Creation du mot de passe client
                //$lemotdepasse = $this->ficelle->generatePassword(8);
                //$this->clients->password = md5($lemotdepasse);
                // On precise que c'est un emprunteur
                $this->clients->status_pre_emp = 2;

                // si deja un preteur on le passe en mode 3 (preteur/emprunteur)
                if ($this->preteurCreateEmprunteur == true)
                    $this->clients->status_pre_emp = 3;


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
                $surl = $this->surl;
                $url = $this->lurl;
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
                    'lien_tw' => $lien_tw);


                // Construction du tableau avec les balises EMV
                $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                // Attribution des données aux variables
                $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                $exp_name = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

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
                $surl = $this->surl;
                $url = $this->lurl;
                $nom_societe = utf8_decode($this->companies->name);
                $montant_pret = $this->projects->amount;
                $lien = $this->aurl . '/emprunteurs/edit/' . $this->clients->id_client;

                // Attribution des données aux variables
                $sujetMail = htmlentities($this->mails_text->subject);
                eval("\$sujetMail = \"$sujetMail\";");

                $texteMail = $this->mails_text->content;
                eval("\$texteMail = \"$texteMail\";");

                $exp_name = $this->mails_text->exp_name;
                eval("\$exp_name = \"$exp_name\";");

                // Nettoyage de printemps
                $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
                $exp_name = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

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
                $this->clients->status = 1;
                $this->clients->status_transition = 1;
                $this->clients->status_depot_dossier = 5;
                $this->clients->update();

                // -- acceptation des cgu -- // 
                if ($this->acceptations_legal_docs->get($this->lienConditionsGenerales, 'id_client = "' . $this->clients->id_client . '" AND id_legal_doc'))
                    $accepet_ok = true;
                else
                    $accepet_ok = false;
                $this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGenerales;
                $this->acceptations_legal_docs->id_client = $this->clients->id_client;
                if ($accepet_ok == true)
                    $this->acceptations_legal_docs->update();
                else
                    $this->acceptations_legal_docs->create();
                // -- fin partie cgu -- // 

                header('location:' . $this->lurl . '/' . $this->tree->getSlug(48, $this->language));
                die;
            }
        }
        else
        {
            header('location:' . $this->lurl . '/depot_de_dossier/etape1');
            die;
        }
    }

    function _stand_by()
    {
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash'))
        {
            header('location:' . $this->lurl . '/depot_de_dossier/etape' . ($this->clients->status_depot_dossier + 1) . '/' . $this->clients->hash);
            die;
        }
        else
        {
            header('location:' . $this->lurl);
            die;
        }
    }

    function _error()
    {
        //Recuperation des element de traductions
        $this->lng['etape1'] = $this->ln->selectFront('depot-de-dossier-etape-1', $this->language, $this->App);
    }

}
