<?php

class pdfController extends bootstrap {

    var $Command;

    function pdfController($command, $config, $app) {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug = false;


        // Recuperation du bloc
        $this->blocs->get('pdf-contrat', 'slug');
        $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
        foreach ($lElements as $b_elt) {
            $this->elements->get($b_elt['id_element']);
            $this->bloc_pdf_contrat[$this->elements->slug] = $b_elt['value'];
            $this->bloc_pdf_contratComplement[$this->elements->slug] = $b_elt['complement'];
        }
    }

    function _default() {
        
    }

    function _mandat_preteur() {
        // chargement des datas
        $clients = $this->loadData('clients');
        $clients->get($this->params[0], 'hash');

        $vraisNom = 'MANDAT-UNILEND-' . $clients->id_client;
        print_r($this->lurl . '/pdf/mandat_html/' . $this->params[0]);
        die;
        $this->Web2Pdf->convert($this->path . 'protected/pdf/mandat/', $this->params[0], $this->bp_url . '/pdf/mandat_html/' . $this->params[0], 'mandat_preteur', $vraisNom);
    }

    // mandat emprunteur
    function _mandat() {
        // chargement des datas
        $clients = $this->loadData('clients');
        $clients_mandats = $this->loadData('clients_mandats');
        $companies = $this->loadData('companies');
        $projects = $this->loadData('projects');

        // On recup le client
        if ($clients->get($this->params[0], 'hash') && isset($this->params[1])) {
            // si on a un params 1 on check si on a une entreprise et un projet
            // on chek si le projet est bien au client
            if ($companies->get($clients->id_client, 'id_client_owner') && $projects->get($this->params[1], 'id_project') && $projects->id_company == $companies->id_company) {

                // la c'est good on peut faire le traitement

                $path = $this->path . 'protected/pdf/mandat/';
                $slug = $this->params[0];
                $urlsite = $this->bp_url . '/pdf/mandat_html/' . $this->params[0] . '/' . (isset($this->params[1]) ? $this->params[1] . '/' : '');
                $name = 'mandat';
                $param = $this->params[1];
                $sign = '';
                $vraisNom = 'MANDAT-UNILEND-' . $projects->slug . '-' . $clients->id_client;


                // on check si y a deja un traitement universign de fait
                $exist = false;
                if ($clients_mandats->get($clients->id_client, 'id_project = ' . $this->params[1] . ' AND id_client')) {
                    // Si on a affaire a un mandat charger manuelement
                    if ($clients_mandats->id_universign == 'no_universign') {
                        // on recup directement le pdf
                        $this->Web2Pdf->lecture($path . $clients_mandats->name, $clients_mandats->name);
                        die;
                    }

                    if ($clients_mandats->status > 0)
                        $sign = $clients_mandats->status;

                    $exist = true;
                }

                // On ouvre le pdf et si c'est la premiere fois on redirige sur universign pour le faire signer
                if ($this->Web2Pdf->convert($path, $slug, $urlsite, $name, $vraisNom, $param, $sign) == 'universign') {
                    $nom_fichier = $name . '-' . $slug . ".pdf";
                    if ($param != '')
                        $nom_fichier = $name . '-' . $slug . "-" . $param . ".pdf";



                    // On enregistre le mandat en statut pas encore traité
                    $clients_mandats->id_client = $clients->id_client;
                    $clients_mandats->url_pdf = '/pdf/mandat/' . $this->params[0] . '/' . (isset($this->params[1]) ? $this->params[1] . '/' : '');
                    $clients_mandats->name = $nom_fichier;
                    $clients_mandats->id_project = $projects->id_project;

                    if ($exist == false)
                        $clients_mandats->id_mandat = $clients_mandats->create();
                    else
                        $clients_mandats->update();


                    header("location:" . $url . '/universign/mandat/' . $clients_mandats->id_mandat);
                    die;
                }
            }
            else {
                // pas good on redirige
                header("location:" . $this->lurl);
                die;
            }
        } else {
            header("location:" . $this->lurl);
            die;
        }
    }

    function _mandat_html() {
        // si le client existe
        if ($this->clients->get($this->params[0], 'hash')) {
            $this->companies = $this->loadData('companies');
            $this->projects = $this->loadData('projects');
            $this->pays = $this->loadData('pays');
            $this->clients_adresses = $this->loadData('clients_adresses');
            $this->lenders_accounts = $this->loadData('lenders_accounts');

            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            $this->pays->get($this->clients->id_langue, 'id_langue');

            // preteur
            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

            $this->iban[1] = substr($this->lenders_accounts->iban, 0, 4);
            $this->iban[2] = substr($this->lenders_accounts->iban, 4, 4);
            $this->iban[3] = substr($this->lenders_accounts->iban, 8, 4);
            $this->iban[4] = substr($this->lenders_accounts->iban, 12, 4);
            $this->iban[5] = substr($this->lenders_accounts->iban, 16, 4);
            $this->iban[6] = substr($this->lenders_accounts->iban, 20, 4);
            $this->iban[7] = substr($this->lenders_accounts->iban, 24, 3);

            $this->leIban = $this->lenders_accounts->iban;

            $this->entreprise = false;
            if ($this->companies->get($this->clients->id_client, 'id_client_owner')) {

                $this->entreprise = true;

                $this->iban[1] = substr($this->companies->iban, 0, 4);
                $this->iban[2] = substr($this->companies->iban, 4, 4);
                $this->iban[3] = substr($this->companies->iban, 8, 4);
                $this->iban[4] = substr($this->companies->iban, 12, 4);
                $this->iban[5] = substr($this->companies->iban, 16, 4);
                $this->iban[6] = substr($this->companies->iban, 20, 4);
                $this->iban[7] = substr($this->companies->iban, 24, 3);

                $this->leIban = $this->companies->iban;

                // Motif mandat
                /* $nom = $this->clients->nom;
                  $id_project = str_pad($this->projects->id_project,6,0,STR_PAD_LEFT);
                  $this->motif = strtoupper('UNILEND'.$id_project.$nom); */
            }

            // pour savoir si Preteur ou emprunteur
            if (isset($this->params[1]) && $this->projects->get($this->params[1], 'id_project')) {
                // Motif mandat emprunteur
                $p = substr($this->ficelle->stripAccents(utf8_decode($this->clients->prenom)), 0, 1);
                $nom = $this->ficelle->stripAccents(utf8_decode($this->clients->nom));
                $id_project = str_pad($this->projects->id_project, 6, 0, STR_PAD_LEFT);
                $this->motif = mb_strtoupper($id_project . 'E' . $p . $nom, 'UTF-8');
                $this->motif = $this->ficelle->str_split_unicode('UNILEND' . $this->motif);
            } else {
                // Motif mandat preteur
                $p = substr($this->ficelle->stripAccents(utf8_decode($this->clients->prenom)), 0, 1);
                $nom = $this->ficelle->stripAccents(utf8_decode($this->clients->nom));
                $id_client = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
                $this->motif = mb_strtoupper($id_client . 'P' . $p . $nom, 'UTF-8');
                $this->motif = $this->ficelle->str_split_unicode('UNILEND' . $this->motif);
            }





            // Créancier adresse
            $this->settings->get('Créancier adresse', 'type');
            $this->creancier_adresse = $this->settings->value;
            // Créancier cp
            $this->settings->get('Créancier cp', 'type');
            $this->creancier_cp = $this->settings->value;
            // Créancier identifiant
            $this->settings->get('ICS de SFPMEI', 'type');
            $this->creancier_identifiant = $this->settings->value;
            // Créancier nom
            $this->settings->get('Créancier nom', 'type');
            $this->creancier = $this->settings->value;
            // Créancier pays
            $this->settings->get('Créancier pays', 'type');
            $this->creancier_pays = $this->settings->value;
            // Créancier ville
            $this->settings->get('Créancier ville', 'type');
            $this->creancier_ville = $this->settings->value;
            // Créancier code identifiant	
            $this->settings->get('Créancier code identifiant', 'type');
            $this->creancier_code_id = $this->settings->value;



            // Adresse retour	
            $this->settings->get('Adresse retour', 'type');
            $this->adresse_retour = $this->settings->value;
        }
    }

    function _pouvoir() {
        // si le client existe
        if ($this->clients->get($this->params[0], 'hash') && isset($this->params[1])) {
            $this->companies = $this->loadData('companies');
            $this->projects = $this->loadData('projects');
            $projects_pouvoir = $this->loadData('projects_pouvoir');

            //on recup l'entreprise
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            if ($this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')) {

                $path = $this->path . 'protected/pdf/pouvoir/'; // path d'enregistrement
                $slug = $this->params[0]; // hash client
                $urlsite = $this->bp_url . '/pdf/pouvoir_html/' . $this->params[0] . '/' . $this->params[1] . '/'; // URL page HTML
                $name = 'pouvoir'; // nom du pdf (type PDF)
                $param = $this->params[1]; // id_project
                $entete = $this->lurl . '/pdf/entete/'; // URL page entete du PDF 
                $piedpage = $this->lurl . '/pdf/piedpage/'; // URL page pied de page du PDF 
                $vraisNom = 'POUVOIR-UNILEND-' . $this->projects->slug . '-' . $this->clients->id_client; // nom du PDF lors de l'enregistrement
                $sign = ''; // PDF non signé
                //$exist = false;
                // on check si y a deja un pouvoir
                $leproject_pouvoir = $projects_pouvoir->select('id_project = ' . $this->projects->id_project, 'added ASC');
                $nbpouvoir = count($leproject_pouvoir);

                // si on a une ligne deja de crée (ou plusieurs car c'est ca qui pose pb !)
                if ($nbpouvoir > 0) {

                    // On parcourt les lignes (1 seule normalement mais on sait jamais)
                    $i = 0;
                    foreach ($leproject_pouvoir as $pouv) {
                        // On garde le premier ligne trouvée
                        if ($i == 0) {
                            // si c'est un upload manuel du BO on affiche directement
                            if ($pouv['id_universign'] == 'no_universign') {
                                $this->Web2Pdf->lecture($path . $pouv['name'], $vraisNom);
                                die;
                            }

                            // Si pouvoir signé
                            if ($pouv['status'] > 0)
                                $sign = $pouv['status'];

                            // On recup la ligne du pouvoir
                            $id_pouvoir = $pouv['id_pouvoir'];
                        }
                        // si y en a dautre on les supprimes !
                        else {
                            $projects_pouvoir->delete($pouv['id_pouvoir'], 'id_pouvoir'); // plus de doublons comme ca !
                        }

                        $i++;
                    }
                }
                // Si pas de pouvoir on créer une ligne
                else {

                    $nom_fichier = $name . '-' . $slug . ".pdf";
                    if ($param != '')
                        $nom_fichier = $name . '-' . $slug . "-" . $param . ".pdf";

                    $projects_pouvoir->id_project = $this->projects->id_project;
                    $projects_pouvoir->url_pdf = '/pdf/pouvoir/' . $this->params[0] . '/' . $this->params[1] . '/';
                    $projects_pouvoir->name = $nom_fichier;
                    $projects_pouvoir->id_pouvoir = $projects_pouvoir->create();

                    $id_pouvoir = $projects_pouvoir->id_pouvoir;
                }


                if ($this->Web2Pdf->convert($path, $slug, $urlsite, $name, $vraisNom, $param, $sign, $entete, $piedpage) == 'universign') {
                    // param pour savoir si on doit générer un nouveau universign
                    $regenerationUniversign = '';

                    // on recupère le contenu de se qu'on vient de créer
                    $projects_pouvoir->get($id_pouvoir, 'id_pouvoir');


                    // si on une ligne pouvoir non signée et qui n'est pas effectué depuis le BO
                    if ($projects_pouvoir->id_universign != 'no_universign' && $projects_pouvoir->status == 0) {
                        /////////////////////////////////////////////////////
                        //   	  On met a jour les dates d'echeances      //
                        // en se basant sur la date de creation du pouvoir //
                        /////////////////////////////////////////////////////
                        if (date('Y-m-d', strtotime($projects_pouvoir->updated)) == date('Y-m-d')) {
                            $regenerationUniversign = '/NoUpdateUniversign'; // On crée pas de nouveau universign
                        }
                        // Ici on creera un nouveau universign car la date est différente
                        else {

                            // On met a jour la date des echeances en se basant sur la date de signature du pouvoir c'est a dire aujourd'hui
                            $this->updateEcheances($projects_pouvoir->id_project, date('Y-m-d H:i:s'));

                            // On met a jour la ligne pouvoir, pour changer la date update
                            $projects_pouvoir->update();
                        }
                        ///////////////////////////////
                        // FIN mise a jour echeances //
                        ///////////////////////////////
                    }

                    // On redirige sur universign
                    header("location:" . $url . '/universign/pouvoir/' . $projects_pouvoir->id_pouvoir . $regenerationUniversign);
                    die;
                }
            } else {
                header('Location:' . $this->lurl);
                die;
            }
        } else {
            header('Location:' . $this->lurl);
            die;
        }
    }

    function _pouvoir_html() {
        // si le client existe
        if ($this->clients->get($this->params[0], 'hash')) {
            //Recuperation des element de traductions
            $this->lng['pdf-pouvoir'] = $this->ln->selectFront('pdf-pouvoir', $this->language, $this->App);

            // Recuperation du bloc
            $this->blocs->get('pouvoir', 'slug');
            $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
            foreach ($lElements as $b_elt) {
                $this->elements->get($b_elt['id_element']);
                $this->bloc_pouvoir[$this->elements->slug] = $b_elt['value'];
                $this->bloc_pouvoirComplement[$this->elements->slug] = $b_elt['complement'];
            }

            $this->companies = $this->loadData('companies');
            $this->companies_details = $this->loadData('companies_details');
            $this->projects = $this->loadData('projects');
            $this->loans = $this->loadData('loans');
            $this->echeanciers = $this->loadData('echeanciers');
            $this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
            $this->lenders_accounts = $this->loadData('lenders_accounts');
            $this->companies_actif_passif = $this->loadData('companies_actif_passif');
            $this->projects_pouvoir = $this->loadData('projects_pouvoir');

            //on recup l'entreprise
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            if ($this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')) {
                $this->companies_details->get($this->companies->id_company, 'id_company');


                // date_dernier_bilan_mois
                $date_dernier_bilan = explode('-', $this->companies_details->date_dernier_bilan);
                $this->date_dernier_bilan_annee = $date_dernier_bilan[0];
                $this->date_dernier_bilan_mois = $date_dernier_bilan[1];
                $this->date_dernier_bilan_jour = $date_dernier_bilan[2];

                // Montant prété a l'emprunteur
                $this->montantPrete = $this->projects->amount;

                // moyenne pondéré
                $montantHaut = 0;
                $montantBas = 0;
                // si fundé ou remboursement

                foreach ($this->loans->select('id_project = ' . $this->projects->id_project) as $b) {
                    $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                    $montantBas += ($b['amount'] / 100);
                }
                $this->taux = ($montantHaut / $montantBas);

                $this->nbLoans = $this->loans->counter('id_project = ' . $this->projects->id_project);

                // Remb emprunteur par mois
                $this->echeanceEmprun = $this->echeanciers_emprunteur->select('id_project = ' . $this->projects->id_project . ' AND ordre = 1');

                $this->rembByMonth = $this->echeanciers->getMontantRembEmprunteur($this->echeanceEmprun[0]['montant'], $this->echeanceEmprun[0]['commission'], $this->echeanceEmprun[0]['tva']);
                $this->rembByMonth = ($this->rembByMonth / 100);

                // date premiere echance
                //$this->dateLastEcheance = $this->echeanciers->getDateDerniereEcheance($this->projects->id_project);
                $this->dateLastEcheance = $this->echeanciers->getDateDerniereEcheancePreteur($this->projects->id_project);


                // liste des echeances emprunteur par mois

                $this->lRemb = $this->echeanciers_emprunteur->select('id_project = ' . $this->projects->id_project, 'ordre ASC');

                $this->capital = 0;
                foreach ($this->lRemb as $r) {
                    $this->capital += $r['capital'];
                }
                //echo $this->capital;
                // Liste des actif passif
                $this->l_AP = $this->companies_actif_passif->select('id_company = "' . $this->companies->id_company . '" AND annee = ' . $this->date_dernier_bilan_annee, 'annee DESC');



                $this->totalActif = ($this->l_AP[0]['immobilisations_corporelles'] + $this->l_AP[0]['immobilisations_incorporelles'] + $this->l_AP[0]['immobilisations_financieres'] + $this->l_AP[0]['stocks'] + $this->l_AP[0]['creances_clients'] + $this->l_AP[0]['disponibilites'] + $this->l_AP[0]['valeurs_mobilieres_de_placement']);

                $this->totalPassif = ($this->l_AP[0]['capitaux_propres'] + $this->l_AP[0]['provisions_pour_risques_et_charges'] + $this->l_AP[0]['amortissement_sur_immo'] + $this->l_AP[0]['dettes_financieres'] + $this->l_AP[0]['dettes_fournisseurs'] + $this->l_AP[0]['autres_dettes']);

                // liste des encheres
                $this->lLenders = $this->loans->select('id_project = ' . $this->projects->id_project, 'rate ASC');

                //if($this->projects_pouvoir->get($this->projects->id_project,'id_project'))
                //$this->dateRemb = date('d/m/Y',strtotime($this->projects_pouvoir->updated));
                //else 
                $this->dateRemb = date('d/m/Y');
            } else {
                header('Location:' . $this->lurl);
                die;
            }
        } else {
            header('Location:' . $this->lurl);
            die;
        }
    }

    function _contrat() {
        // preteur
        if ($this->clients->checkAccess() && $this->clients->hash == $this->params[0]) {
            
        }
        // admin
        elseif (isset($_SESSION['user']['id_user']) && $_SESSION['user']['id_user'] != '') {
            
        } else {
            header('Location:' . $this->lurl);
            die;
        }

        $projects = $this->loadData('projects');
        $clients = $this->loadData('clients');
        $loans = $this->loadData('loans');
        $lenders_accounts = $this->loadData('lenders_accounts');

        // on recup ca
        $clients->get($this->params[0], 'hash');
        $lenders_accounts->get($clients->id_client, 'id_client_owner');
        $loans->get($this->params[1], 'id_lender = ' . $lenders_accounts->id_lender_account . ' AND id_loan');
        $projects->get($loans->id_project, 'id_project');

        $vraisNom = 'CONTRAT-UNILEND-' . $projects->slug . '-' . $loans->id_loan;

        $this->Web2Pdf->convert($this->path . 'protected/pdf/contrat/', $this->params[0], $this->bp_url . '/pdf/contrat_html/' . $this->params[0] . '/' . $this->params[1] . '/', 'contrat', $vraisNom, $this->params[1]);
    }

    function _contrat_html() {

        // si le client existe
        if ($this->clients->get($this->params[0], 'hash')) {
            $this->loans = $this->loadData('loans');
            $this->lenders_accounts = $this->loadData('lenders_accounts');
            $this->echeanciers = $this->loadData('echeanciers');
            $this->projects = $this->loadData('projects');
            $this->companiesEmprunteur = $this->loadData('companies');
            $this->companies_detailsEmprunteur = $this->loadData('companies_details');
            $this->companiesPreteur = $this->loadData('companies');
            $this->emprunteur = $this->loadData('clients');
            $this->clients_adresses = $this->loadData('clients_adresses');
            $this->companies_actif_passif = $this->loadData('companies_actif_passif');
            $this->projects_pouvoir = $this->loadData('projects_pouvoir');
            $this->projects_status_history = $this->loadData('projects_status_history');
            // on recup adresse preteur
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            // preteur
            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

            // si le loan existe
            if ($this->loans->get($this->params[1], 'id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND id_loan')) {
                // On recup le projet
                $this->projects->get($this->loans->id_project, 'id_project');
                // On recup l'entreprise
                $this->companiesEmprunteur->get($this->projects->id_company, 'id_company');
                // On recup le detail entreprise emprunteur
                $this->companies_detailsEmprunteur->get($this->projects->id_company, 'id_company');

                // On recup l'emprunteur
                $this->emprunteur->get($this->companiesEmprunteur->id_client_owner, 'id_client');

                // Si preteur morale
                if ($this->clients->type == 2) {
                    // entreprise preteur;

                    $this->companiesPreteur->get($this->clients->id_client, 'id_client_owner');
                }

                // date premiere echance
                //$this->dateFirstEcheance = $this->echeanciers->getDatePremiereEcheance($this->projects->id_project);
                $this->dateLastEcheance = $this->echeanciers->getDateDerniereEcheancePreteur($this->projects->id_project);

                // date_dernier_bilan_mois
                $date_dernier_bilan = explode('-', $this->companies_detailsEmprunteur->date_dernier_bilan);
                $this->date_dernier_bilan_annee = $date_dernier_bilan[0];
                $this->date_dernier_bilan_mois = $date_dernier_bilan[1];
                $this->date_dernier_bilan_jour = $date_dernier_bilan[2];

                // Liste des actif passif
                //$this->l_AP = $this->companies_actif_passif->select('id_company = "'.$this->companies->id_company.'" AND annee = '.$this->date_dernier_bilan_annee,'annee DESC');
                // Liste des actif passif
                $this->l_AP = $this->companies_actif_passif->select('id_company = "' . $this->companiesEmprunteur->id_company . '" AND annee = ' . $this->date_dernier_bilan_annee, 'annee DESC');

                $this->totalActif = ($this->l_AP[0]['immobilisations_corporelles'] + $this->l_AP[0]['immobilisations_incorporelles'] + $this->l_AP[0]['immobilisations_financieres'] + $this->l_AP[0]['stocks'] + $this->l_AP[0]['creances_clients'] + $this->l_AP[0]['disponibilites'] + $this->l_AP[0]['valeurs_mobilieres_de_placement']);

                $this->totalPassif = ($this->l_AP[0]['capitaux_propres'] + $this->l_AP[0]['provisions_pour_risques_et_charges'] + $this->l_AP[0]['amortissement_sur_immo'] + $this->l_AP[0]['dettes_financieres'] + $this->l_AP[0]['dettes_fournisseurs'] + $this->l_AP[0]['autres_dettes']);


                // les remb d'une enchere
                $this->lRemb = $this->echeanciers->select('id_loan = ' . $this->loans->id_loan, 'ordre ASC');

                $this->capital = 0;
                foreach ($this->lRemb as $r) {
                    $this->capital += $r['capital'];
                }

                // si on a le pouvoir
                if ($this->projects_pouvoir->get($this->projects->id_project, 'id_project')) {
                    //$this->dateContrat = date('d/m/Y',strtotime($this->projects_pouvoir->updated));
                    //$this->dateRemb = date('d/m/Y',strtotime($this->projects_pouvoir->updated));
                } else {
                    //$this->dateContrat = date('d/m/Y');
                    //$this->dateRemb = date('d/m/Y');
                }

                // On recup la date de statut remb
                $remb = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = 8', 'added ASC', 0, 1);

                if ($remb[0]['added'] != "") {
                    $this->dateRemb = date('d/m/Y', strtotime($remb[0]['added']));
                } else {
                    $this->dateRemb = date('d/m/Y');
                }

                $this->dateContrat = $this->dateRemb;
            } else {
                header('Location:' . $this->lurl);
                die;
            }
        } else {
            header('Location:' . $this->lurl);
            die;
        }
    }

    function _entete() {
        
    }

    function _piedpage() {
        // Recuperation du bloc
        $this->blocs->get('pouvoir', 'slug');
        $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
        foreach ($lElements as $b_elt) {
            $this->elements->get($b_elt['id_element']);
            $this->bloc_pouvoir[$this->elements->slug] = $b_elt['value'];
            $this->bloc_pouvoirComplement[$this->elements->slug] = $b_elt['complement'];
        }
    }

    function _declarationContratPret_html() {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug = false;

        $this->loans = $this->loadData('loans');
        $this->projects = $this->loadData('projects');
        $this->companiesEmp = $this->loadData('companies');
        $this->emprunteur = $this->loadData('clients');
        $this->lender = $this->loadData('lenders_accounts');
        $this->preteur = $this->loadData('clients');
        $this->preteurCompanie = $this->loadData('companies');
        $this->preteur_adresse = $this->loadData('clients_adresses');
        $this->echeanciers = $this->loadData('echeanciers');

        if (isset($this->params[0]) && $this->loans->get($this->params[0], 'status = "0" AND id_loan')) {

            $this->settings->get('Declaration contrat pret - adresse', 'type');
            $this->adresse = $this->settings->value;

            $this->settings->get('Declaration contrat pret - raison sociale', 'type');
            $this->raisonSociale = $this->settings->value;

            // Coté emprunteur
            // On recup le projet
            $this->projects->get($this->loans->id_project, 'id_project');
            // On recup la companie
            $this->companiesEmp->get($this->projects->id_company, 'id_company');
            // On recup l'emprunteur
            $this->emprunteur->get($this->companiesEmp->id_client_owner, 'id_client');


            // Coté preteur
            // On recup le lender
            $this->lender->get($this->loans->id_lender, 'id_lender_account');
            // On recup le preteur
            $this->preteur->get($this->lender->id_client_owner, 'id_client');
            // On recup l'adresse preteur
            $this->preteur_adresse->get($this->lender->id_client_owner, 'id_client');

            $this->lEcheances = $this->echeanciers->getSumByAnnee($this->loans->id_loan);

            if ($this->preteur->type == 2) {
                $this->preteurCompanie->get($this->lender->id_company_owner, 'id_company');

                $this->nomPreteur = $this->preteurCompanie->name;
                $this->adressePreteur = $this->preteurCompanie->adresse1;
                $this->cpPreteur = $this->preteurCompanie->zip;
                $this->villePreteur = $this->preteurCompanie->city;
            } else {
                $this->nomPreteur = $this->preteur->prenom . ' ' . $this->preteur->nom;
                $this->adressePreteur = $this->preteur_adresse->adresse1;
                $this->cpPreteur = $this->preteur_adresse->cp;
                $this->villePreteur = $this->preteur_adresse->ville;
            }

            /* echo '<pre>';
              print_r($leche);
              echo '</pre>'; */
        }
    }

    function _facture_EF() {
        // si le client existe
        if ($this->clients->get($this->params[0], 'hash') && isset($this->params[1])) {
            $this->companies = $this->loadData('companies');
            $this->projects = $this->loadData('projects');

            //on recup l'entreprise
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // et on recup le projet
            if ($this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')) {

                $vraisNom = 'FACTURE-UNILEND-' . $this->projects->slug;

                // fonction pdf
                //if($_SERVER['REMOTE_ADDR'] == '93.26.42.99'){
                $this->Web2Pdf->convert_factures($this->path . 'protected/pdf/facture/', $this->params[0], $this->bp_url . '/pdf/facture_EF_html/' . $this->params[0] . '/' . $this->params[1] . '/', 'facture_EF', $vraisNom, $this->params[1], '', '', $this->lurl . '/pdf/footer_facture/');
                //}
                /* else{
                  $this->Web2Pdf->convert($this->path.'protected/pdf/facture/',$this->params[0],$this->lurl.'/pdf/facture_EF_html/'.$this->params[0].'/'.$this->params[1].'/','facture_EF',$vraisNom,$this->params[1],'','',$this->lurl.'/pdf/footer_facture/');
                  } */
            }
        }
    }

    function _footer_facture() {
        $this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture', $this->language, $this->App);

        // titulaire du compte
        $this->settings->get('titulaire du compte', 'type');
        $this->titreUnilend = mb_strtoupper($this->settings->value, 'UTF-8');

        // Declaration contrat pret - raison sociale
        $this->settings->get('Declaration contrat pret - raison sociale', 'type');
        $this->raisonSociale = mb_strtoupper($this->settings->value, 'UTF-8');

        // Facture - SFF PME
        $this->settings->get('Facture - SFF PME', 'type');
        $this->sffpme = mb_strtoupper($this->settings->value, 'UTF-8');

        // Facture - capital
        $this->settings->get('Facture - capital', 'type');
        $this->capital = mb_strtoupper($this->settings->value, 'UTF-8');

        // Declaration contrat pret - adresse
        $this->settings->get('Declaration contrat pret - adresse', 'type');
        $this->raisonSocialeAdresse = mb_strtoupper($this->settings->value, 'UTF-8');

        // Facture - telephone
        $this->settings->get('Facture - telephone', 'type');
        $this->telephone = mb_strtoupper($this->settings->value, 'UTF-8');

        // Facture - RCS
        $this->settings->get('Facture - RCS', 'type');
        $this->rcs = mb_strtoupper($this->settings->value, 'UTF-8');

        // Facture - TVA INTRACOMMUNAUTAIRE
        $this->settings->get('Facture - TVA INTRACOMMUNAUTAIRE', 'type');
        $this->tvaIntra = mb_strtoupper($this->settings->value, 'UTF-8');
    }

    function _facture_EF_html() {
        $this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture', $this->language, $this->App);

        // si le client existe
        if ($this->clients->get($this->params[0], 'hash') && isset($this->params[1])) {

            $this->companies = $this->loadData('companies');
            $this->projects = $this->loadData('projects');
            $this->compteur_factures = $this->loadData('compteur_factures');
            $this->transactions = $this->loadData('transactions');
            $this->projects_status_history = $this->loadData('projects_status_history');
            $this->factures = $this->loadData('factures');

            // TVA
            $this->settings->get('TVA', 'type');
            $this->tva = $this->settings->value;

            //on recup l'entreprise
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // et on recup le projet
            if ($this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')) {

                $histoRemb = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = 8', 'added DESC', 0, 1);

                if ($histoRemb != false) {
                    $this->dateRemb = $histoRemb[0]['added'];

                    $timeDateRemb = strtotime($this->dateRemb);


                    $compteur = $this->compteur_factures->compteurJournalier($this->projects->id_project);

                    $this->num_facture = 'FR-E' . date('Ymd', $timeDateRemb) . str_pad($compteur, 5, "0", STR_PAD_LEFT);

                    $this->transactions->get($this->projects->id_project, 'type_transaction = 9 AND status = 1 AND etat = 1 AND id_project');

                    $this->ttc = ($this->transactions->montant_unilend / 100);

                    $cm = ($this->tva + 1); // CM
                    $this->ht = ($this->ttc / $cm); // HT
                    $this->taxes = ($this->ttc - $this->ht); // TVA

                    $montant = ((str_replace('-', '', $this->transactions->montant) + $this->transactions->montant_unilend) / 100); // Montant pret
                    $txCom = round(($this->ht / $montant) * 100, 0); // taux commission

                    if (!$this->factures->get($this->projects->id_project, 'type_commission = 1 AND id_company = ' . $this->companies->id_company . ' AND id_project')) {
                        $this->factures->num_facture = $this->num_facture;
                        $this->factures->date = $this->dateRemb;
                        $this->factures->id_company = $this->companies->id_company;
                        $this->factures->id_project = $this->projects->id_project;
                        $this->factures->ordre = 0;
                        $this->factures->type_commission = 1; // financement
                        $this->factures->commission = $txCom;
                        $this->factures->montant_ht = ($this->ht * 100);
                        $this->factures->tva = ($this->taxes * 100);
                        $this->factures->montant_ttc = ($this->ttc * 100);
                        $this->factures->create();
                    }
                }
            }
        }
    }

    function _facture_ER() {
        // si le client existe
        if ($this->clients->get($this->params[0], 'hash') && isset($this->params[1])) {
            $this->companies = $this->loadData('companies');
            $this->projects = $this->loadData('projects');
            $this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');

            //on recup l'entreprise
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // et on recup le projet
            if ($this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')) {
                // on recup l'echeance concernée
                if ($this->echeanciers_emprunteur->get($this->projects->id_project, 'ordre = ' . $this->params[2] . '  AND id_project')) {
                    $vraisNom = 'FACTURE-UNILEND-' . $this->projects->slug . '-' . $this->params[2];

                    // fonction pdf
                    $this->Web2Pdf->convert_factures($this->path . 'protected/pdf/facture/', $this->params[0], $this->bp_url . '/pdf/facture_ER_html/' . $this->params[0] . '/' . $this->params[1] . '/' . $this->params[2] . '/', 'facture_ER', $vraisNom, $this->params[1] . '-' . $this->params[2], '', '', $this->lurl . '/pdf/footer_facture/');
                    //$this->Web2Pdf->convert($this->path.'protected/pdf/facture/',$this->params[0],$this->lurl.'/pdf/facture_ER_html/'.$this->params[0].'/'.$this->params[1].'/'.$this->params[2].'/','facture_ER',$vraisNom,$this->params[1].'-'.$this->params[2],'','',$this->lurl.'/pdf/footer_facture/');
                }
            }
        }
    }

    function _facture_ER_html() {
        $this->lng['pdf-facture'] = $this->ln->selectFront('pdf-facture', $this->language, $this->App);

        // si le client existe
        if ($this->clients->get($this->params[0], 'hash') && isset($this->params[1]) && isset($this->params[2])) {
            $this->companies = $this->loadData('companies');
            $this->projects = $this->loadData('projects');
            $this->compteur_factures = $this->loadData('compteur_factures');
            $this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
            $this->echeanciers = $this->loadData('echeanciers');
            $this->factures = $this->loadData('factures');

            // TVA
            $this->settings->get('TVA', 'type');
            $this->tva = $this->settings->value;

            // Commission remboursement
            $this->settings->get('Commission remboursement', 'type');
            $txcom = $this->settings->value;



            //on recup l'entreprise
            $this->companies->get($this->clients->id_client, 'id_client_owner');

            // et on recup le projet
            if ($this->projects->get($this->params[1], 'id_company = ' . $this->companies->id_company . ' AND id_project')) {
                $uneEcheancePreteur = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND ordre = ' . $this->params[2], '', 0, 1);
                $this->date_echeance_reel = $uneEcheancePreteur[0]['date_echeance_reel'];

                $time_date_echeance_reel = strtotime($this->date_echeance_reel);

                // on recup l'echeance concernée
                if ($this->echeanciers_emprunteur->get($this->projects->id_project, 'ordre = ' . $this->params[2] . '  AND id_project')) {
                    $compteur = $this->compteur_factures->compteurJournalier($this->projects->id_project);

                    $this->num_facture = 'FR-E' . date('Ymd', $time_date_echeance_reel) . str_pad($compteur, 5, "0", STR_PAD_LEFT);

                    $this->ht = ($this->echeanciers_emprunteur->commission / 100);
                    $this->taxes = ($this->echeanciers_emprunteur->tva / 100);
                    $this->ttc = ($this->ht + $this->taxes);


                    if (!$this->factures->get($this->projects->id_project, 'ordre = ' . $this->params[2] . ' AND  type_commission = 2 AND id_company = ' . $this->companies->id_company . ' AND id_project')) {
                        $this->factures->num_facture = $this->num_facture;
                        $this->factures->date = $this->date_echeance_reel;
                        $this->factures->id_company = $this->companies->id_company;
                        $this->factures->id_project = $this->projects->id_project;
                        $this->factures->ordre = $this->params[2];
                        $this->factures->type_commission = 2; // remboursement
                        $this->factures->commission = ($txcom * 100);
                        $this->factures->montant_ht = ($this->ht * 100);
                        $this->factures->tva = ($this->taxes * 100);
                        $this->factures->montant_ttc = ($this->ttc * 100);
                        $this->factures->create();
                    }
                }
            }
        }
    }

    function _testupdate() {

        $this->updateEcheances(3384, '2014-11-28 17:26:26');
        die;
    }

    // Mise a jour des dates echeances preteurs et emprunteur (utilisé pour se baser sur la date de creation du pouvoir)
    function updateEcheances($id_project, $dateRemb) {

        ini_set('max_execution_time', 300); //300 seconds = 5 minutes
        // chargement des datas
        $projects = $this->loadData('projects');
        $projects_status = $this->loadData('projects_status');
        $projects_status_history = $this->loadData('projects_status_history');
        $echeanciers = $this->loadData('echeanciers');
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');

        // Chargement des librairies
        $jo = $this->loadLib('jours_ouvres');

        // On definit le nombre de mois et de jours apres la date de fin pour commencer le remboursement
        $this->settings->get('Nombre de mois apres financement pour remboursement', 'type');
        $nb_mois = $this->settings->value;
        $this->settings->get('Nombre de jours apres financement pour remboursement', 'type');
        $nb_jours = $this->settings->value;

        // ID PROJECT //
        $id_project = $id_project;

        // On recup le projet
        $projects->get($id_project, 'id_project');

        // On recupere le statut
        $projects_status->getLastStatut($projects->id_project);

        // si c'est fundé
        if ($projects_status->status == 60) {
            //echo 'ici';
            // On recup la date de statut remb
            //$remb = $projects_status_history->select('id_project = '.$projects->id_project.' AND id_project_status = 8','added DESC',0,1);
            //$dateRemb = $remb[0]['added'];
            // on parcourt les mois
            for ($ordre = 1; $ordre <= $projects->period; $ordre++) {

                // on prend le nombre de jours dans le mois au lieu du mois
                $nbjourstemp = mktime(0, 0, 0, date("m") + $ordre, 1, date("Y"));
                $nbjoursMois += date('t', $nbjourstemp);

                // Date d'echeance preteur
                $date_echeance = $this->dates->dateAddMoisJours($dateRemb, 0, $nb_jours + $nbjoursMois);
                $date_echeance = date('Y-m-d H:i', $date_echeance) . ':00';

                // Date d'echeance emprunteur
                $date_echeance_emprunteur = $this->dates->dateAddMoisJours($dateRemb, 0, $nbjoursMois);


                // on retire 6 jours ouvrés
                $date_echeance_emprunteur = $jo->display_jours_ouvres($date_echeance_emprunteur, 6);
                $date_echeance_emprunteur = date('Y-m-d H:i', $date_echeance_emprunteur) . ':00';

                /* echo '------------------<br>';
                  echo $date_echeance.'<br>';
                  echo $date_echeance_emprunteur.'<br>'; */

                // Update echeanciers preteurs
                $echeanciers->onMetAjourLesDatesEcheances($projects->id_project, $ordre, $date_echeance, $date_echeance_emprunteur);

                // Update echeanciers emprunteurs
                $echeanciers_emprunteur->onMetAjourLesDatesEcheancesE($id_project, $ordre, $date_echeance_emprunteur);
            }
        }
    }

    function _declaration_de_creances_old() {
        die;

        // si le client existe
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash') && isset($this->params[1])) {
            $this->lenders_accounts = $this->loadData('lenders_accounts');
            $this->loans = $this->loadData('loans');

            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

            if ($this->loans->get($this->lenders_accounts->id_lender_account, 'id_loan = ' . $this->params[1] . ' AND id_lender')) {
                $vraisNom = 'DECLARATION-DE-CREANCES-UNILEND-' . $this->clients->hash . '-' . $this->loans->id_loan;

                $this->Web2Pdf->convert($this->path . 'protected/pdf/declaration_de_creances/', $this->params[0], $this->bp_url . '/pdf/declaration_de_creances_html/' . $this->params[0] . '/' . $this->params[1] . '/', 'declaration-de-creances', $vraisNom, $this->params[1]);
            }
        }
        die;
    }

    function _declaration_de_creances_html_old() {
        die;

        // si le client existe
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash') && isset($this->params[1])) {
            $this->lenders_accounts = $this->loadData('lenders_accounts');
            $this->loans = $this->loadData('loans');
            $this->clients_adresses = $this->loadData('clients_adresses');
            $this->pays = $this->loadData('pays_v2');
            $this->projects = $this->loadData('projects');
            $this->echeanciers = $this->loadData('echeanciers');
            $this->companies = $this->loadData('companies');

            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

            if ($this->loans->get($this->lenders_accounts->id_lender_account, 'id_loan = ' . $this->params[1] . ' AND id_lender')) {

                // particulier
                if (in_array($this->clients->type, array(1, 4))) {

                    // client adresse
                    $this->clients_adresses->get($this->clients->id_client, 'id_client');

                    // pays fiscal
                    if ($this->clients_adresses->id_pays_fiscal == 0)
                        $this->clients_adresses->id_pays_fiscal = 1;
                    $this->pays->get($this->clients_adresses->id_pays_fiscal, 'id_pays');
                    $this->pays_fiscal = $this->pays->fr;
                }
                // entreprise
                else {
                    $this->companies->get($this->clients->id_client, 'id_client_owner');

                    // pays fiscal
                    if ($this->companies->id_pays == 0)
                        $this->companies->id_pays = 1;
                    $this->pays->get($this->companies->id_pays, 'id_pays');
                    $this->pays_fiscal = $this->pays->fr;
                }

                // projet
                $this->projects->get($this->loans->id_project, 'id_project');

                // echu
                $this->echu = $this->echeanciers->getSumARemb($this->lenders_accounts->id_lender_account . ' AND LEFT(date_echeance,10) >= "2014-12-08" AND LEFT(date_echeance,10) <= "' . date('Y-m-d') . '" AND id_loan = ' . $this->loans->id_loan, 'montant');

                // echoir
                $this->echoir = $this->echeanciers->getSumARemb($this->lenders_accounts->id_lender_account . ' AND LEFT(date_echeance,10) > "' . date('Y-m-d') . '" AND id_loan = ' . $this->loans->id_loan, 'capital');

                // total
                $this->total = ($this->echu + $this->echoir);

                // last echeance
                $lastEcheance = $this->echeanciers->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND id_loan = ' . $this->loans->id_loan, 'ordre DESC', 0, 1);
                $this->lastEcheance = date('d/m/Y', strtotime($lastEcheance[0]['date_echeance']));
            } else
                die;
        } else
            die;
    }

    function _declaration_de_creances() {
        // si le client existe
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash') && isset($this->params[1])) {
            $this->lenders_accounts = $this->loadData('lenders_accounts');
            $this->loans = $this->loadData('loans');

            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

            if ($this->loans->get($this->lenders_accounts->id_lender_account, 'id_loan = ' . $this->params[1] . ' AND id_lender')) {
                $vraisNom = 'DECLARATION-DE-CREANCES-UNILEND-' . $this->clients->hash . '-' . $this->loans->id_loan;


                $path = $this->path . 'protected/pdf/declaration_de_creances/' . $this->loans->id_project . '/';

                // Si le dossier existe pas on créer
                if (!file_exists($path))
                    mkdir($path);

                // Ancien mode tout est dans le meme dossier pour ce projet
                if ($this->loans->id_project == '1456') {
                    $newpath = $path;
                } else {
                    // Si le dossier existe pas on créer
                    if (!file_exists($path . $this->clients->id_client . '/'))
                        mkdir($path . $this->clients->id_client . '/');

                    $newpath = $path . $this->clients->id_client . '/';
                }

                $this->Web2Pdf->convert($newpath, $this->params[0], $this->bp_url . '/pdf/declaration_de_creances_html/' . $this->params[0] . '/' . $this->params[1] . '/', 'declaration-de-creances', $vraisNom, $this->params[1]);
            }
        }
        die;
    }

    function _declaration_de_creances_html() {


        // si le client existe
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash') && isset($this->params[1])) {
            $this->lenders_accounts = $this->loadData('lenders_accounts');
            $this->loans = $this->loadData('loans');
            $this->clients_adresses = $this->loadData('clients_adresses');
            $this->pays = $this->loadData('pays_v2');
            $this->projects = $this->loadData('projects');
            $this->echeanciers = $this->loadData('echeanciers');
            $this->companies = $this->loadData('companies');
            $this->companiesEmpr = $this->loadData('companies');
            $this->projects_status_history = $this->loadData('projects_status_history');
            $this->projects_status_history_informations = $this->loadData('projects_status_history_informations');

            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

            if ($this->loans->get($this->lenders_accounts->id_lender_account, 'id_loan = ' . $this->params[1] . ' AND id_lender')) {

                // particulier
                if (in_array($this->clients->type, array(1, 4))) {

                    // client adresse
                    $this->clients_adresses->get($this->clients->id_client, 'id_client');

                    // pays fiscal
                    if ($this->clients_adresses->id_pays_fiscal == 0)
                        $this->clients_adresses->id_pays_fiscal = 1;
                    $this->pays->get($this->clients_adresses->id_pays_fiscal, 'id_pays');
                    $this->pays_fiscal = $this->pays->fr;
                }
                // entreprise
                else {
                    $this->companies->get($this->clients->id_client, 'id_client_owner');

                    // pays fiscal
                    if ($this->companies->id_pays == 0)
                        $this->companies->id_pays = 1;
                    $this->pays->get($this->companies->id_pays, 'id_pays');
                    $this->pays_fiscal = $this->pays->fr;
                }

                // projet
                $this->projects->get($this->loans->id_project, 'id_project');

                // entreprise de l'emprunteur
                $this->companiesEmpr->get($this->projects->id_company, 'id_company');

                // 26 : PS , 27 RJ , 28 LJ
                $retour = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status IN(26,27,28)', 'added DESC', 0, 1);
                if ($retour != false) {
                    $this->projects_status_history_informations->get($retour[0]['id_project_status_history'], 'id_project_status_history');

                    // mandataire personalisé
                    $this->mandataires_var = $this->projects_status_history_informations->mandataire;

                    // Nature
                    $id_projet_status = $retour[0]['id_project_status'];
                    if ($id_projet_status == 26) {
                        $this->nature_var = "Procédure de sauvegarde";
                    } elseif ($id_projet_status == 27) {
                        $this->nature_var = "Redressement judiciaire";
                    } elseif ($id_projet_status == 28) {
                        $this->nature_var = "Liquidation judiciaire";
                    }
                    $date = date('d/m/Y', strtotime($this->projects_status_history_informations->date));
                    $this->arrayDeclarationCreance = array($this->projects->id_project => $date);
                } else {
                    // Nature
                    $this->nature_var = "Procédure de sauvegarde";

                    // mandataire personalisé
                    $this->mandataires_var = "";

                    $this->arrayDeclarationCreance = array(1456 => '27/11/2014',
                        1009 => '15/04/2015',
                        1614 => '27/05/2015',
                        3089 => '29/06/2015');

                    if ($this->loans->id_project == 1614) {
                        //plus de mandataire dans le pdf, on l'aura que dans le mail (Note BT: 17793)
                        //$this->mandataires_var = "
                        //    Me ROUSSEL Bernard 
                        //    <br />
                        //    850, rue Etienne Lenoir. Km Delta 
                        //    <br />
                        //    30 900 Nîmes   
                        //    ";
                        // Nature
                        $this->nature_var = "Liquidation judiciaire";
                    }
                    if ($this->loans->id_project == 3089)
                        $this->nature_var = "Procédure de sauvegarde";
                }

                // echu
                $this->echu = $this->echeanciers->getSumARemb($this->lenders_accounts->id_lender_account . ' AND LEFT(date_echeance,10) >= "2015-04-19" AND LEFT(date_echeance,10) <= "' . date('Y-m-d') . '" AND id_loan = ' . $this->loans->id_loan, 'montant');

                // echoir
                $this->echoir = $this->echeanciers->getSumARemb($this->lenders_accounts->id_lender_account . ' AND LEFT(date_echeance,10) > "' . date('Y-m-d') . '" AND id_loan = ' . $this->loans->id_loan, 'capital');

                // total
                $this->total = ($this->echu + $this->echoir);

                // last echeance
                $lastEcheance = $this->echeanciers->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND id_loan = ' . $this->loans->id_loan, 'ordre DESC', 0, 1);
                $this->lastEcheance = date('d/m/Y', strtotime($lastEcheance[0]['date_echeance']));
            } else
                die;
        } else
            die;
    }

    function _vos_operations_pdf_indexation() {

        /* $post_debut 			= $_SESSION['filtre_vos_operations']['debut'];
          $post_fin 				= $_SESSION['filtre_vos_operations']['fin'];
          $post_nbMois 			= $_SESSION['filtre_vos_operations']['nbMois'];
          $post_annee 			= $_SESSION['filtre_vos_operations']['annee'];
          $post_tri_type_transac 	= $_SESSION['filtre_vos_operations']['tri_type_transac'];
          $post_tri_projects 		= $_SESSION['filtre_vos_operations']['tri_projects'];
          $post_id_last_action 	= $_SESSION['filtre_vos_operations']['id_last_action'];
          $post_order 			= $_SESSION['filtre_vos_operations']['order'];
          $post_type				= $_SESSION['filtre_vos_operations']['type']; */
        $post_id_client = $_SESSION['filtre_vos_operations']['id_client'];



        $filtre_vos_operations = serialize($_SESSION['filtre_vos_operations']);
        $filtre_vos_operations = $this->ficelle->base64url_encode($filtre_vos_operations);

        // Dossier id client
        $path = $this->path . 'protected/operations_export_pdf/' . $post_id_client . '/';

        // Si le dossier existe pas on créer
        if (!file_exists($path))
            mkdir($path);

        $nom = 'vos_operations_' . date('Y-m-d') . '.pdf';

        // URL PDF a générer
        $url = $this->bp_url . '/pdf/vos_operations_pdf_html_indexation/' . $filtre_vos_operations;
        /* if($_SERVER['REMOTE_ADDR']=='93.26.42.99' or $_SERVER["HTTP_X_FORWARDED_FOR"] == "93.26.42.99")
          {
          mail('k1@david.equinoa.net','DEBUG pdf url',$url);
          } */
        //print_r($url);
        //die;
        $this->Web2Pdf->convertSimple($url, $path, $nom);
        $this->Web2Pdf->lecture($path . '/' . $nom, $nom);
    }

    function _vos_operations_pdf_html_indexation() {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireFooter = false;
        $this->autoFireHead = true;

        if (isset($this->params[0])) {

            $this->transactions = $this->loadData('transactions');
            $this->wallets_lines = $this->loadData('wallets_lines');
            $this->bids = $this->loadData('bids');
            $this->loans = $this->loadData('loans');
            $this->echeanciers = $this->loadData('echeanciers');
            $this->projects = $this->loadData('projects');
            $this->companies = $this->loadData('companies');
            $this->clients = $this->loadData('clients');
            $this->clients_adresses = $this->loadData('clients_adresses');
            $this->lenders_accounts = $this->loadData('lenders_accounts');

            $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
            $this->lng['preteur-operations-pdf'] = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);


            $filtre_vos_operations = $this->ficelle->base64url_decode($this->params[0]);
            $filtre_vos_operations = unserialize($filtre_vos_operations);

            $post_debut = $filtre_vos_operations['debut'];
            $post_fin = $filtre_vos_operations['fin'];
            $post_nbMois = $filtre_vos_operations['nbMois'];
            $post_annee = $filtre_vos_operations['annee'];
            $post_tri_type_transac = $filtre_vos_operations['tri_type_transac'];
            $post_tri_projects = $filtre_vos_operations['tri_projects'];
            $post_id_last_action = $filtre_vos_operations['id_last_action'];
            $post_order = $filtre_vos_operations['order'];
            $post_type = $filtre_vos_operations['type'];
            $post_id_client = $filtre_vos_operations['id_client'];


            /* echo '<pre>';
              print_r($filtre_vos_operations);
              echo '</pre>'; */

            $this->clients->get($post_id_client, 'id_client');
            $this->clients_adresses->get($post_id_client, 'id_client');
            $this->lenders_accounts->get($post_id_client, 'id_client_owner');

            //////////// DEBUT PARTIE DATES //////////////
            //echo $_SESSION['id_last_action'];
            // tri debut/fin



            if (isset($post_id_last_action) && in_array($post_id_last_action, array('debut', 'fin'))) {

                $debutTemp = explode('/', $post_debut);
                $finTemp = explode('/', $post_fin);

                $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');  // date debut
                $date_fin_time = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');    // date fin
                // On sauvegarde la derniere action
                $_SESSION['id_last_action'] = $post_id_last_action;
            }
            // NB mois
            elseif (isset($post_id_last_action) && $post_id_last_action == 'nbMois') {

                $nbMois = $post_nbMois;

                $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, date("d"), date('Y')); // date debut
                $date_fin_time = mktime(0, 0, 0, date("m"), date("d"), date('Y')); // date fin
                // On sauvegarde la derniere action
                $_SESSION['id_last_action'] = $post_id_last_action;
            }
            // Annee
            elseif (isset($post_id_last_action) && $post_id_last_action == 'annee') {

                $year = $post_annee;

                $date_debut_time = mktime(0, 0, 0, 1, 1, $year); // date debut

                if (date('Y') == $year)
                    $date_fin_time = mktime(0, 0, 0, date('m'), date('d'), $year); // date fin
                else
                    $date_fin_time = mktime(0, 0, 0, 12, 31, $year); // date fin




                    
// On sauvegarde la derniere action
                $_SESSION['id_last_action'] = $post_id_last_action;
            }
            // si on a une session
            elseif (isset($post_id_last_action)) {

                if ($post_debut != "" && $post_fin != "") {
                    //echo 'toto';
                    $debutTemp = explode('/', $post_debut);
                    $finTemp = explode('/', $post_fin);

                    $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');  // date debut
                    $date_fin_time = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');    // date fin
                } elseif ($post_id_last_action == 'nbMois') {
                    //echo 'titi';
                    $nbMois = $post_nbMois;

                    $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, date("d"), date('Y')); // date debut
                    $date_fin_time = mktime(0, 0, 0, date("m"), date("d"), date('Y')); // date fin
                } elseif ($post_id_last_action == 'annee') {
                    //echo 'tata';
                    $year = $post_annee;

                    $date_debut_time = mktime(0, 0, 0, 1, 1, $year); // date debut
                    $date_fin_time = mktime(0, 0, 0, 12, 31, $year); // date fin
                }
            }
            // Par defaut (on se base sur le 1M)
            else {
                //echo 'cc';
                $date_debut_time = mktime(0, 0, 0, date("m") - 1, date("d"), date('Y')); // date debut
                $date_fin_time = mktime(0, 0, 0, date("m"), date("d"), date('Y')); // date fin
            }

            // on recup au format sql
            $this->date_debut = date('Y-m-d', $date_debut_time);
            $this->date_fin = date('Y-m-d', $date_fin_time);
            //////////// FIN PARTIE DATES //////////////




            $array_type_transactions = array(
                1 => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
                2 => array(1 => $this->lng['preteur-operations-vos-operations']['offre-en-cours'], 2 => $this->lng['preteur-operations-vos-operations']['offre-rejetee'], 3 => $this->lng['preteur-operations-vos-operations']['offre-acceptee']),
                3 => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
                4 => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
                5 => $this->lng['preteur-operations-vos-operations']['remboursement'],
                7 => $this->lng['preteur-operations-vos-operations']['depot-de-fonds'],
                8 => $this->lng['preteur-operations-vos-operations']['retrait-dargents'],
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
            if (isset($post_type) && isset($post_order)) {

                $this->type = $post_type;
                $this->order = $post_order;

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


            // On va chercher ce qu'on a dans la table d'indexage
            $this->lTrans = $this->indexage_vos_operations->select('type_transaction IN (' . $tri_type_transac . ') AND id_client = ' . $this->clients->id_client . ' AND LEFT(date_operation,10) >= "' . $this->date_debut . '" AND LEFT(date_operation,10) <= "' . $this->date_fin . '"' . $tri_project, $order);

            // filtre secondaire
            $this->lProjectsLoans = $this->indexage_vos_operations->get_liste_libelle_projet('type_transaction IN (' . $tri_type_transac . ') AND id_client = ' . $this->clients->id_client . ' AND LEFT(date_operation,10) >= "' . $this->date_debut . '" AND LEFT(date_operation,10) <= "' . $this->date_fin . '"');
        }
    }

}
