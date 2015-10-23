<?php

class projectsController extends bootstrap
{
    var $Command;

    function projectsController($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        //Recuperation des element de traductions
        $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

        // Heure fin periode funding
        $this->settings->get('Heure fin periode funding', 'type');
        $this->heureFinFunding = $this->settings->value;

        $this->page = 'projects';
    }

    function _default()
    {
        // On prend le header account
        $this->setHeader('header_account');

        $_SESSION['page_projet'] = $this->page;

        // On check si y a un compte
        if (!$this->clients->checkAccess()) {
            header('Location:' . $this->lurl);
            die;
        } else {
            // check preteur ou emprunteur (ou les deux)
            $this->clients->checkStatusPreEmp($this->clients->status_pre_emp, 'preteur');
        }

        // Chargement des datas
        $this->projects = $this->loadData('projects');
        $this->projects_status = $this->loadData('projects_status');
        $this->companies = $this->loadData('companies');
        $this->companies_details = $this->loadData('companies_details');
        $this->favoris = $this->loadData('favoris');
        $this->bids = $this->loadData('bids');
        $this->loans = $this->loadData('loans');

        // tri par taux
        $this->settings->get('Tri par taux', 'type');
        $this->triPartx = $this->settings->value;
        $this->triPartx = explode(';', $this->triPartx);

        // tri par taux intervalles
        $this->settings->get('Tri par taux intervalles', 'type');
        $this->triPartxInt = $this->settings->value;
        $this->triPartxInt = explode(';', $this->triPartxInt);

        // page projet tri
        // 1 : terminé bientot
        // 2 : nouveauté
        $this->ordreProject = 1;
        $this->type = 0;

        $_SESSION['ordreProject'] = $this->ordreProject;
        $aElementsProjects = $this->projects->getProjectsStatusAndCount($this->tabProjectDisplay, $this->tabOrdreProject[$this->ordreProject], 0, 10);

        $this->lProjetsFunding = $aElementsProjects['lProjectsFunding'];
        $this->nbProjects      = $aElementsProjects['nbProjects'];
    }

    function _detail()
    {
        // restriction pour capital
        if ($this->lurl == 'http://prets-entreprises-unilend.capital.fr' || $this->lurl == 'http://partenaire.unilend.challenges.fr') {
            //if($this->lurl == 'http://capital.unilend.fr' || $this->lurl == 'http://challenges.unilend.fr'){
            $this->autoFireHeader = true;
            $this->autoFireDebug = false;
            $this->autoFireHead = true;
            $this->autoFireFooter = true;

            $this->url_form = $this->Config['url'][$this->Config['env']]['default'];

            if ($this->lurl == 'http://prets-entreprises-unilend.capital.fr') {
                $this->utm_source = '/?utm_source=capital';
            } else {
                $this->utm_source = '/?utm_source=challenge';
            }
        } else {
            $this->url_form = $this->lurl;
            $this->utm_source = '';
        }

        // Chargement des datas
        $this->projects = $this->loadData('projects');
        $this->companies = $this->loadData('companies');
        $this->companies_details = $this->loadData('companies_details');
        $this->favoris = $this->loadData('favoris');
        $this->emprunteur = $this->loadData('clients');
        $this->projects_status = $this->loadData('projects_status');
        $this->companies_actif_passif = $this->loadData('companies_actif_passif');
        $this->companies_bilans = $this->loadData('companies_bilans');
        $this->transactions = $this->loadData('transactions');
        $this->wallets_lines = $this->loadData('wallets_lines');
        $this->loans = $this->loadData('loans');
        $this->bids = $this->loadData('bids');
        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->echeanciers = $this->loadData('echeanciers');
        $this->notifications = $this->loadData('notifications');
        $this->clients_status = $this->loadData('clients_status');
        $this->prospects = $this->loadData('prospects');
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications'); // add gestion alertes
        $this->clients_gestion_mails_notif = $this->loadData('clients_gestion_mails_notif'); // add gestion alertes
        $this->projects_status_history = $this->loadData('projects_status_history');

        //traduction
        $this->lng['landing-page'] = $this->ln->selectFront('landing-page', $this->language, $this->App);

        if ($this->clients->checkAccess()) {
            // On prend le header account
            $this->setHeader('header_account');
        }

        // restriction mise en prod
        if (in_array($_SERVER['REMOTE_ADDR'], $this->Config['ip_admin'][$this->Config['env']])) //if($_SERVER['REMOTE_ADDR'] == '93.26.42.99')
        {
            $this->restriction_ip = true;
            //$this->restriction_ip = false;
        } else {
            $this->restriction_ip = false;
        }

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'slug') && $this->projects->status == '0') {
            //title de la page
            $this->meta_title = $this->projects->title . ' - Unilend';

            // source
            $this->ficelle->source($_GET['utm_source'], $this->lurl . '/' . $this->params[0], $_GET['utm_source2']);

            // Pret min
            $this->settings->get('Pret min', 'type');
            $this->pretMin = $this->settings->value;

            // Cron fin funding minutes suplémentaires avant traitement
            $this->settings->get('Cron fin funding minutes suplémentaires avant traitement', 'type');
            $this->minutesEnPlus = $this->settings->value;

            // Liste deroulante secteurs
            $this->settings->get('Liste deroulante secteurs', 'type');
            $lSecteurs = explode(';', $this->settings->value);
            $i = 1;
            foreach ($lSecteurs as $s) {
                $this->lSecteurs[$i] = $s;
                $i++;
            }

            // On recup la companie
            $this->companies->get($this->projects->id_company, 'id_company');
            $this->companies_details->get($this->companies->id_company, 'id_company');
            // l'emprunteur
            $this->emprunteur->get($this->companies->id_client_owner, 'id_client');
            // On recupere le lender
            $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');
            // Statut du projet
            $this->projects_status->getLastStatut($this->projects->id_project);
            // statut client
            $this->clients_status->getLastStatut($this->clients->id_client);

            // On recupere le dernier statut histo du projet pour la date de remb anticipé(DC)
            $this->lastStatushisto = $this->projects_status_history->select('id_project = ' . $this->projects->id_project, 'added DESC', 0, 1);
            $this->lastStatushisto = $this->lastStatushisto[0];

            // si le status est inferieur a "a funder" on autorise pas a voir le projet
            if ($this->projects_status->status < 40) {
                header('Location:' . $this->lurl);
                die;
            }

            // On récupère desormais la date full et pas la date avec l'heure en params
            $tabdateretrait = explode(':', $this->projects->date_retrait_full);
            $dateRetrait = $tabdateretrait[0] . ':' . $tabdateretrait[1];
            $today = date('Y-m-d H:i');

            // pour fin projet manuel
            if ($this->projects->date_fin != '0000-00-00 00:00:00') $dateRetrait = $this->projects->date_fin;

            $today = strtotime($today);
            $dateRetrait = strtotime($dateRetrait);

            // page d'attente entre la cloture du projet et le traitement de cloture du projet
            $this->page_attente = false;
            if ($dateRetrait <= $today && $this->projects_status->status == 50) {
                $this->page_attente = true;
            }

            ////////////////////////
            // Formulaire de pret //
            ////////////////////////

            if (isset($_POST['send_pret'])) {

                // Histo client //
                $serialize = serialize(array('id_client' => $this->clients->id_client, 'post' => $_POST, 'id_projet' => $this->projects->id_project));
                $this->clients_history_actions->histo(9, 'bid', $this->clients->id_client, $serialize);
                ////////////////


                // Si la date du jour est egale ou superieur a la date de retrait on redirige.
                if ($today >= $dateRetrait) {
                    $_SESSION['messFinEnchere'] = $this->lng['preteur-projets']['mess-fin-enchere'];
                    // message l'alerte
                    //mail('d.courtier@equinoa.com','Alert unilend Pret','Un mec a voulu preter apres la datede retrait '.$this->clients->id_client.' Le :'.date('Y-m-d H:i:s'));

                    header('location:' . $this->lurl . '/projects/detail/' . $this->projects->slug);
                    die;
                } elseif ($this->clients_status->status < 60) { // preteur non activé
                    header('location:' . $this->lurl . '/projects/detail/' . $this->projects->slug);
                    die;
                }

                $montant_p = str_replace(' ', '', $_POST['montant_pret']);
                $montant_p = str_replace(',', '.', $montant_p);

                $montant_p = explode('.', $montant_p);
                $montant_p = $montant_p[0];

                $tx_p = $_POST['taux_pret'];

                $this->form_ok = true;

                // On verifie les champs
                if (!isset($_POST['taux_pret']) || $_POST['taux_pret'] == '') {
                    $this->form_ok = false;
                } elseif ($_POST['taux_pret'] == '-') {
                    $this->form_ok = false;
                } elseif ($_POST['taux_pret'] > 10) { // taux a 10% max
                    $this->form_ok = false;
                }

                if (!isset($_POST['montant_pret']) || $_POST['montant_pret'] == '' || $_POST['montant_pret'] == '0') {
                    $this->form_ok = false;
                } elseif (!is_numeric($montant_p)) {
                    $this->form_ok = false;
                } elseif ($montant_p < $this->pretMin) {
                    $this->form_ok = false;
                } elseif ($this->solde < $montant_p) {
                    $this->form_ok = false;
                } elseif ($montant_p >= $this->projects->amount) {
                    $this->form_ok = false;
                }
                if ($this->projects_status->status != 50) {
                    $this->form_ok = false;
                }

                if (isset($this->params['1']) && $this->params['1'] == 'fast' && $this->form_ok == false) {
                    header('location:' . $this->lurl . '/synthese');
                    die;
                }

                // Si tout est ok let's go
                if ($this->form_ok == true && isset($_SESSION['tokenBid']) && $_SESSION['tokenBid'] == $_POST['send_pret']) {
                    unset($_SESSION['tokenBid']);
                    // On enregistre la transaction virtuelle
                    $this->transactions->id_client = $this->clients->id_client;
                    $this->transactions->montant = '-' . ($montant_p * 100);
                    $this->transactions->id_langue = 'fr';
                    $this->transactions->date_transaction = date('Y-m-d H:i:s');
                    $this->transactions->status = '1';
                    $this->transactions->etat = '1';
                    $this->transactions->id_project = $this->projects->id_project;
                    $this->transactions->transaction = '2';
                    $this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
                    $this->transactions->type_transaction = '2';
                    $this->transactions->id_transaction = $this->transactions->create();

                    // On enrgistre aussi dans le wallet line
                    $this->wallets_lines->id_lender = $this->lenders_accounts->id_lender_account;
                    $this->wallets_lines->type_financial_operation = 20; // enchere
                    $this->wallets_lines->id_transaction = $this->transactions->id_transaction;
                    $this->wallets_lines->status = 1;
                    $this->wallets_lines->type = 2; // transaction virtuelle
                    $this->wallets_lines->amount = '-' . ($montant_p * 100);
                    $this->wallets_lines->id_project = $this->projects->id_project;
                    $this->wallets_lines->id_wallet_line = $this->wallets_lines->create();

                    // ordre des bids
                    $numBid = $this->bids->counter('id_project = ' . $this->projects->id_project);
                    $numBid += 1;

                    // on enregistre l'enchere
                    $this->bids->id_lender_account = $this->lenders_accounts->id_lender_account;
                    $this->bids->id_project = $this->projects->id_project;
                    $this->bids->id_lender_wallet_line = $this->wallets_lines->id_wallet_line;
                    $this->bids->amount = $montant_p * 100;
                    $this->bids->rate = $tx_p;
                    $this->bids->ordre = $numBid;
                    $this->bids->id_bid = $this->bids->create();


                    /// OFFRE DE BIENVENUE ///
                    $offres_bienvenues_details = $this->loadData('offres_bienvenues_details');

                    // Liste des offres non utilisées
                    $lOffres = $offres_bienvenues_details->select('id_client = ' . $this->clients->id_client . ' AND status = 0');
                    if ($lOffres != false) {
                        $totaux_restant = $montant_p;
                        $totaux_offres = 0;
                        foreach ($lOffres as $o) {

                            // Tant que le total des offres est infèrieur
                            if ($totaux_offres <= $montant_p) {

                                $totaux_offres += ($o['montant'] / 100); // total des offres
                                $totaux_restant -= $montant_p;        // total du bid

                                $offres_bienvenues_details->get($o['id_offre_bienvenue_detail'], 'id_offre_bienvenue_detail');
                                $offres_bienvenues_details->status = 1;
                                $offres_bienvenues_details->id_bid = $this->bids->id_bid;
                                $offres_bienvenues_details->update();

                                // Apres addition de la derniere offre on se rend compte que le total depasse
                                if ($totaux_offres > $montant_p) {
                                    // On fait la diff et on créer un remb du trop plein d'offres
                                    $montant_coupe_a_remb = $totaux_offres - $montant_p;
                                    $offres_bienvenues_details->id_offre_bienvenue = 0;
                                    $offres_bienvenues_details->id_client = $this->lenders_accounts->id_client_owner;
                                    $offres_bienvenues_details->id_bid = 0;
                                    $offres_bienvenues_details->id_bid_remb = $this->bids->id_bid;
                                    $offres_bienvenues_details->status = 0;
                                    $offres_bienvenues_details->type = 1;
                                    $offres_bienvenues_details->montant = ($montant_coupe_a_remb * 100);
                                    $offres_bienvenues_details->create();
                                }

                            } else {
                                break;
                            }
                        }
                    }

                    ///// NOTIFICATION OFFRE PLACEE ///////

                    $this->notifications->type = 3; // offre placée
                    $this->notifications->id_lender = $this->lenders_accounts->id_lender_account;
                    $this->notifications->id_project = $this->projects->id_project;
                    $this->notifications->amount = $montant_p * 100;
                    $this->notifications->id_bid = $this->bids->id_bid;
                    $this->notifications->id_notification = $this->notifications->create();

                    ///// FIN NOTIFICATION OFFRE PLACEE ///////

                    if ($this->clients_gestion_notifications->getNotif($this->clients->id_client, 2, 'immediatement') == true) {

                        // Motif virement
                        $p = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))), 0, 1);
                        $nom = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
                        $id_client = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
                        $motif = mb_strtoupper($id_client . $p . $nom, 'UTF-8');

                        //*********************************//
                        //*** ENVOI DU MAIL CONFIRM BID ***//
                        //*********************************//

                        // Recuperation du modele de mail
                        $this->mails_text->get('confirmation-bid', 'lang = "' . $this->language . '" AND type');

                        // FB
                        $this->settings->get('Facebook', 'type');
                        $lien_fb = $this->settings->value;

                        // Twitter
                        $this->settings->get('Twitter', 'type');
                        $lien_tw = $this->settings->value;

                        $timeAdd = strtotime($this->bids->added);
                        $month = $this->dates->tableauMois['fr'][date('n', $timeAdd)];

                        $pageProjets = $this->tree->getSlug(4, $this->language);

                        // Variables du mailing
                        $varMail = array(
                            'surl' => $this->surl,
                            'url' => $this->lurl,
                            'prenom_p' => $this->clients->prenom,
                            'nom_entreprise' => $this->companies->name,
                            'valeur_bid' => number_format($montant_p, 2, ',', ' '),
                            'taux_bid' => number_format($tx_p, 1, ',', ' '),
                            'date_bid' => date('d', $timeAdd) . ' ' . $month . ' ' . date('Y', $timeAdd),
                            'heure_bid' => date('H:i:s', strtotime($this->bids->added)),
                            'projet-p' => $this->lurl . '/' . $pageProjets,
                            'motif_virement' => $motif,
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
                        } else // non nmp
                        {
                            $this->email->addRecipient(trim($this->clients->email));
                            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        }
                        // fin mail confirmation bid //

                        $this->clients_gestion_mails_notif->immediatement = 1;
                    } else {
                        $this->clients_gestion_mails_notif->immediatement = 0;
                    }

                    //////// GESTION ALERTES //////////
                    $this->clients_gestion_mails_notif->id_client = $this->clients->id_client;
                    $this->clients_gestion_mails_notif->id_notif = 2; // offre placée
                    $this->clients_gestion_mails_notif->date_notif = date('Y-m-d H:i:s');
                    $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                    $this->clients_gestion_mails_notif->id_transaction = $this->transactions->id_transaction;
                    $this->clients_gestion_mails_notif->create();
                    //////// FIN GESTION ALERTES //////////

                    $_SESSION['messPretOK'] = $this->lng['preteur-projets']['mess-pret-conf'];


                    header('location:' . $this->lurl . '/projects/detail/' . $this->projects->slug);
                    die;
                }
                ////// FIN ENREGISTREMENT BID /////
            } elseif (isset($_POST['send_inscription_project_detail'])) {
                // INSCRIPTION PRETEUR //
                $nom = $_POST['nom'];
                $prenom = $_POST['prenom'];
                $email = $_POST['email'];
                $email2 = $_POST['conf_email'];

                $form_valid = true;

                if (!isset($nom) or $nom == $this->lng['landing-page']['nom']) {
                    $form_valid = false;
                    $this->retour_form = $this->lng['landing-page']['champs-obligatoires'];
                }

                if (!isset($prenom) or $prenom == $this->lng['landing-page']['prenom']) {
                    $form_valid = false;
                    $this->retour_form = $this->lng['landing-page']['champs-obligatoires'];
                }


                if (!isset($email) || $email == '' || $email == $this->lng['landing-page']['email']) {
                    $form_valid = false;
                    $this->retour_form = $this->lng['landing-page']['champs-obligatoires'];
                } elseif (!$this->ficelle->isEmail($email)) {// verif format mail
                    $form_valid = false;
                    $this->retour_form = $this->lng['landing-page']['email-erreur-format'];
                } elseif ($email != $email2) {// conf email good/pas
                    $form_valid = false;
                    $this->retour_form = $this->lng['landing-page']['confirmation-email-erreur'];
                } elseif ($this->clients->existEmail($email) == false) {// si exite ou pas
                    $form_valid = false;
                    $this->retour_form = $this->lng['landing-page']['email-existe-deja'];
                }

                if ($form_valid) {
                    $_SESSION['landing_client']['prenom'] = $prenom;
                    $_SESSION['landing_client']['nom'] = $nom;
                    $_SESSION['landing_client']['email'] = $email;

                    $this->prospects = $this->loadData('prospects');
                    $this->prospects->id_langue = 'fr';
                    $this->prospects->prenom = $prenom;
                    $this->prospects->nom = $nom;
                    $this->prospects->email = $email;
                    $this->prospects->source = $_SESSION['utm_source'];
                    $this->prospects->source2 = $_SESSION['utm_source2'];
                    $this->prospects->create();

                    $_SESSION['landing_page'] = true;

                    header('location:' . $this->lurl . '/inscription_preteur/etape1/' . $this->clients->hash);
                    die;
                }
            }
            // FIN INSCRIPTION PRETEUR //

            // Nb projets en funding
            $this->nbProjects = $this->projects->countSelectProjectsByStatus($this->tabProjectDisplay . ', 75', ' AND p.status = 0 AND p.display = 0');

            // dates pour le js
            $this->mois_jour = $this->dates->formatDate($this->projects->date_retrait, 'F d');
            $this->annee = $this->dates->formatDate($this->projects->date_retrait, 'Y');

            // intervalle aujourd'hui et retrait
            $inter = $this->dates->intervalDates(date('Y-m-d H:i:s'), $this->projects->date_retrait . ' ' . $this->heureFinFunding);
            if ($inter['mois'] > 0) $this->dateRest = $inter['mois'] . ' mois';
            else $this->dateRest = '';

            // Date de retrait complete
            if ($this->projects_status->status == 50) {
                $this->date_retrait = $this->dates->formatDateComplete($this->projects->date_retrait . ' ' . $this->heureFinFunding);
                $this->heure_retrait = substr($this->heureFinFunding, 0, 2);
            } else {
                $this->date_retrait = $this->dates->formatDateComplete($this->projects->date_fin);
                $this->heure_retrait = $this->dates->formatDate($this->projects->date_fin, 'G');
            }

            $this->ordreProject = 1;
            if (isset($_SESSION['ordreProject'])) {
                $this->ordreProject = $_SESSION['ordreProject'];
            }

            // id_project avant et apres
            $this->positionProject = $this->projects->positionProject($this->projects->id_project, $this->tabProjectDisplay, $this->tabOrdreProject[$this->ordreProject]);

            // favori
            if ($this->favoris->get($this->clients->id_client, 'id_project = ' . $this->projects->id_project . ' AND id_client')) {
                $this->favori = 'active';
            } else {
                $this->favori = '';
            }

            $dateDernierBilan = explode('-', $this->companies_details->date_dernier_bilan);
            $dateDernierBilan = $dateDernierBilan[0];

            // Liste des actif passif
            $this->listAP = $this->companies_actif_passif->select('id_company = "' . $this->companies->id_company . '" AND annee <= ' . $dateDernierBilan, 'annee DESC');

            // Totaux actif/passif
            $this->totalAnneeActif = array();
            $this->totalAnneePassif = array();
            $i = 1;
            foreach ($this->listAP as $ap) {
                $this->totalAnneeActif[$i] = ($ap['immobilisations_corporelles'] + $ap['immobilisations_incorporelles'] + $ap['immobilisations_financieres'] + $ap['stocks'] + $ap['creances_clients'] + $ap['disponibilites'] + $ap['valeurs_mobilieres_de_placement']);
                $this->totalAnneePassif[$i] = ($ap['capitaux_propres'] + $ap['provisions_pour_risques_et_charges'] + $ap['dettes_financieres'] + $ap['dettes_fournisseurs'] + $ap['autres_dettes'] + $ap['amortissement_sur_immo']);
                $i++;
            }

            // Bilans
            $lBilans = $this->companies_bilans->select('id_company = "' . $this->companies->id_company . '" AND date <= ' . $dateDernierBilan, 'date DESC', 0, 3);
            foreach ($lBilans as $b) {
                $this->lBilans[$b['date']] = $b;
            }

            $dateBilan = $lBilans[0]['date'];

            $this->anneeToday[1] = ($dateBilan);
            $this->anneeToday[2] = ($dateBilan - 1);
            $this->anneeToday[3] = ($dateBilan - 2);

            // la sum des encheres
            $this->soldeBid = $this->bids->getSoldeBid($this->projects->id_project);

            // solde payé
            $this->payer = $this->soldeBid;

            // Reste a payer
            $this->resteApayer = ($this->projects->amount - $this->soldeBid);

            $this->pourcentage = ((1 - ($this->resteApayer / $this->projects->amount)) * 100);

            $this->decimales = 0;
            $this->decimalesPourcentage = 1;
            $this->txLenderMax = '10.0';
            if ($this->soldeBid >= $this->projects->amount) {
                $this->payer = $this->projects->amount;
                $this->resteApayer = 0;
                $this->pourcentage = 100;
                $this->decimales = 0;
                $this->decimalesPourcentage = 0;

                $this->lEnchereRate = $this->bids->select('id_project = ' . $this->projects->id_project, 'rate ASC,added ASC');
                $leSoldeE = 0;
                foreach ($this->lEnchereRate as $k => $e) {
                    // on parcour les encheres jusqu'au montant de l'emprunt
                    if ($leSoldeE < $this->projects->amount) {
                        // le solde total des encheres
                        $leSoldeE += ($e['amount'] / 100);
                        $this->txLenderMax = $e['rate'];
                    }
                }
            }

            // Liste des encheres enregistrées
            $this->lEnchere = $this->bids->select('id_project = ' . $this->projects->id_project, 'ordre ASC');

            $this->CountEnchere = $this->bids->counter('id_project = ' . $this->projects->id_project);

            $this->avgAmount = $this->bids->getAVG($this->projects->id_project, 'amount', '0');
            if ($this->avgAmount == false) $this->avgAmount = 0;

            // moyenne pondéré
            $montantHaut = 0;
            $tauxBas = 0;
            $montantBas = 0;
            // funding ko

            if ($this->projects_status->status == 70) {
                foreach ($this->bids->select('id_project = ' . $this->projects->id_project) as $b) {
                    $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                    $montantBas += ($b['amount'] / 100);
                    $tauxBas += $b['rate'];
                }
            } elseif ($this->projects_status->status == 75) {// emprunt refusé
                foreach ($this->bids->select('id_project = ' . $this->projects->id_project . ' AND status = 1') as $b) {
                    $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                    $montantBas += ($b['amount'] / 100);
                    $tauxBas += $b['rate'];
                }
            } else {
                foreach ($this->bids->select('id_project = ' . $this->projects->id_project . ' AND status = 0') as $b) {
                    $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                    $tauxBas += $b['rate'];
                    $montantBas += ($b['amount'] / 100);
                }
            }
            if ($montantHaut > 0 && $montantBas > 0) {
                $this->avgRate = ($montantHaut / $montantBas);
            } else {
                $this->avgRate = 0;
            }

            // status enchere
            $this->status = array($this->lng['preteur-projets']['enchere-en-cours'], $this->lng['preteur-projets']['enchere-ok'], $this->lng['preteur-projets']['enchere-ko']);

            if ($this->lenders_accounts->id_lender_account != false) $this->bidsEncours = $this->bids->getBidsEncours($this->projects->id_project, $this->lenders_accounts->id_lender_account);

            // liste des bids du lender pour le projet
            if ($this->lenders_accounts->id_lender_account != false) $this->lBids = $this->bids->select('id_lender_account = ' . $this->lenders_accounts->id_lender_account . ' AND id_project = ' . $this->projects->id_project . ' AND status = 0', 'added ASC');

            ///////////////////////////////
            // Si le projet est en fundé // ou remb
            ///////////////////////////////
            if ($this->projects_status->status == 60 || $this->projects_status->status >= 80) {
                // Retourne un tableau avec le nb d'encheres valides et le solde des encheres validées
                if ($this->lenders_accounts->id_lender_account != false) $this->bidsvalid = $this->loans->getBidsValid($this->projects->id_project, $this->lenders_accounts->id_lender_account);

                // Nb preteurs validés
                $this->NbPreteurs = $this->loans->getNbPreteurs($this->projects->id_project);

                $montantHaut = 0;
                $montantBas = 0;
                foreach ($this->loans->select('id_project = ' . $this->projects->id_project) as $b) {
                    $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                    $montantBas += ($b['amount'] / 100);
                }
                $this->AvgLoans = ($montantHaut / $montantBas);

                // Taux moyen des encheres validés du preteur
                if ($this->lenders_accounts->id_lender_account != false) {
                    $this->AvgLoansPreteur = $this->loans->getAvgLoansPreteur($this->projects->id_project, $this->lenders_accounts->id_lender_account, 'rate');
                }
                if ($this->projects->date_publication_full != '0000-00-00 00:00:00') {
                    $date1 = strtotime($this->projects->date_publication_full);
                } else {
                    $date1 = strtotime($this->projects->date_publication . ' 00:00:00');
                }

                if ($this->projects->date_retrait_full != '0000-00-00 00:00:00') {
                    $date2 = strtotime($this->projects->date_retrait_full);
                } else {
                    $date2 = strtotime($this->projects->date_fin);
                }
                $this->interDebutFin = $this->dates->dateDiff($date1, $date2);

                // Si en remboursement
                if ($this->lenders_accounts->id_lender_account != false) {
                    $this->sumRemb = $this->echeanciers->sumARembByProject($this->lenders_accounts->id_lender_account, $this->projects->id_project . ' AND status_ra = 0');
                    $this->sumRemb += $this->echeanciers->sumARembByProjectCapital($this->lenders_accounts->id_lender_account, $this->projects->id_project . ' AND status_ra = 1'); // (add 17/07/2015)
                    $this->sumRestanteARemb = $this->echeanciers->getSumRestanteARembByProject($this->lenders_accounts->id_lender_account, $this->projects->id_project);
                    $this->nbPeriod = $this->echeanciers->counterPeriodRestantes($this->lenders_accounts->id_lender_account, $this->projects->id_project);
                }
            }
        } else {
            header('location:' . $this->lurl . '/projects');
            die;
        }
    }
}