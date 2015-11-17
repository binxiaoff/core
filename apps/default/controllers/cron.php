<?php

use Unilend\librairies\ULogger;

class cronController extends bootstrap
{
    /**
     * @var string $sHeadersDebug headers for mail to debug
     */
    private $sHeadersDebug;

    /**
     * @var string $sDestinatairesDebug Destinataires for mail to debug
     */
    private $sDestinatairesDebug;

    /**
     * @var int
     */
    private $iStartTime;

    /**
     * @var settings
     */
    private $oSemaphore;

    /**
     * @var ULogger
     */
    private $oLogger;

    public function __construct($command, $config)
    {
        parent::__construct($command, $config, 'default');

        // Inclusion controller pdf
        include_once $this->path . '/apps/default/controllers/pdf.php';

        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireView   = false;
        $this->autoFireDebug  = false;

        $this->sDestinatairesDebug = implode(',', $this->Config['DebugMailIt']);
        $this->sHeadersDebug       = 'MIME-Version: 1.0' . "\r\n";
        $this->sHeadersDebug .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $this->sHeadersDebug .= 'From: ' . key($this->Config['DebugMailFrom']) . ' <' . $this->Config['DebugMailFrom'][key($this->Config['DebugMailFrom'])] . '>' . "\r\n";
    }

    public function _default()
    {
        die;
    }

    public function _queueNMP()
    {
        if ($this->startCron('queueNMP', 10)) {
            if ($this->Config['env'] == 'prod') {
                $this->tnmp->processQueue();
            }
            $this->stopCron();
        }
        die;
    }

    // Les taches executées toutes les minutes
    // Lors que les offres acceptées évoluent après l'indexation, on à un soucis avec les offres acceptées qui n'ont pas de date d'opération. Cette fonction va donc chercher une date dans la table loan pour mettre à jour celle de l'indexation
    public function _minute()
    {
        if (true === $this->startCron('correctionOffreAccepteAucuneDate', 5)) {

            $this->indexage_vos_operations = $this->loadData('indexage_vos_operations');
            $this->loans                   = $this->loadData('loans');
            $this->transactions            = $this->loadData('transactions');

            $this->L_offres_acceptees_no_dated = $this->indexage_vos_operations->select('libelle_operation = "Offre acceptée" AND date_operation = "0000-00-00 00:00:00"', '', 0, 500);

            $this->oLogger->addRecord(ULogger::INFO, 'Affected rows count: ' . count($this->L_offres_acceptees_no_dated), array('ID' => $this->iStartTime));

            if (count($this->L_offres_acceptees_no_dated) > 0) {
                foreach ($this->L_offres_acceptees_no_dated as $offre) {
                    $this->loans->get($offre['bdc'], 'id_loan');

                    $this->indexage_vos_operations_boucle = $this->loadData('indexage_vos_operations');

                    $this->indexage_vos_operations_boucle->get($offre['id'], 'id');
                    $this->indexage_vos_operations_boucle->date_operation = $this->loans->updated;
                    $this->indexage_vos_operations_boucle->solde          = $this->transactions->getSoldeDateLimite_fulldate($offre['id_client'], $this->loans->updated) * 100;
                    $this->indexage_vos_operations_boucle->update();
                }
            }

            $this->stopCron();
        }
        die;
    }

    // toutes les minute on check //
    // on regarde si il y a des projets au statut "a funder" et on les passe en statut "en funding"
    public function _check_projet_a_funder()
    {
        if (true === $this->startCron('check_projet_a_funder', 5)) {
            $this->projects                = $this->loadData('projects');
            $this->projects_status         = $this->loadData('projects_status');
            $this->projects_status_history = $this->loadData('projects_status_history');

            $this->settings->get('Heure debut periode funding', 'type');
            $this->heureDebutFunding = $this->settings->value;

            $this->lProjects = $this->projects->selectProjectsByStatus(40);

            foreach ($this->lProjects as $projects) {
                $tabdatePublication = explode(':', $projects['date_publication_full']);
                $datePublication    = $tabdatePublication[0] . ':' . $tabdatePublication[1];
                $today              = date('Y-m-d H:i');
                echo 'datePublication : ' . $datePublication . '<br>';
                echo 'today : ' . $today . '<br><br>';

                if ($datePublication == $today) {// on lance en fonction de l'heure definie dans le bo
                    $this->projects_status_history->addStatus(-1, 50, $projects['id_project']);

                    // Zippage pour groupama
                    $this->zippage($projects['id_project']);
                    $this->nouveau_projet($projects['id_project']);
                }
            }
            $oCache = \Unilend\librairies\Cache::getInstance();
            $sKey = $oCache->makeKey(\Unilend\librairies\Cache::LIST_PROJECTS, $this->tabProjectDisplay);
            $oCache->delete($sKey);

            $this->stopCron();
        }
    }

    // toutes les 5 minutes on check // (old 10 min)
    // On check les projet a faire passer en fundé ou en funding ko
    public function _check_projet_en_funding()
    {
        $oLogger = new ULogger('cron', $this->logPath, 'cron_check_projet_en_funding.log');

        $this->bids                      = $this->loadData('bids');
        $this->loans                     = $this->loadData('loans');
        $this->wallets_lines             = $this->loadData('wallets_lines');
        $this->transactions              = $this->loadData('transactions');
        $this->companies                 = $this->loadData('companies');
        $this->lenders_accounts          = $this->loadData('lenders_accounts');
        $this->projects                  = $this->loadData('projects');
        $this->projects_status           = $this->loadData('projects_status');
        $this->projects_status_history   = $this->loadData('projects_status_history');
        $this->notifications             = $this->loadData('notifications');
        $this->offres_bienvenues_details = $this->loadData('offres_bienvenues_details');

        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications'); // add gestion alertes
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif'); // add gestion alertes

        $this->settings->get('Heure fin periode funding', 'type');
        $this->heureFinFunding = $this->settings->value;

        // Cron fin funding minutes suplémentaires avant traitement
        $this->settings->get('Cron fin funding minutes suplémentaires avant traitement', 'type');
        $this->minutesEnPlus = $this->settings->value;

        $this->settings->get('Facebook', 'type');
        $lien_fb = $this->settings->value;

        $this->settings->get('Twitter', 'type');
        $lien_tw = $this->settings->value;

        $this->lProjects = $this->projects->selectProjectsByStatus(50);
        foreach ($this->lProjects as $projects) {
            $tabdateretrait = explode(':', $projects['date_retrait_full']);
            $dateretrait    = $tabdateretrait[0] . ':' . $tabdateretrait[1];
            $today          = date('Y-m-d H:i');

            if ($projects['date_fin'] != '0000-00-00 00:00:00') {
                $tabdatefin  = explode(':', $projects['date_fin']);
                $datefin     = $tabdatefin[0] . ':' . $tabdatefin[1];
                $dateretrait = $datefin;
            }

            if ($dateretrait <= $today) {// on termine a 16h00
                $this->projects->get($projects['id_project'], 'id_project');
                $this->projects->date_fin = date('Y-m-d H:i:s');
                $this->projects->update();

                // Solde total obtenue dans l'enchere
                $solde = $this->bids->getSoldeBid($projects['id_project']);

                // Fundé
                if ($solde >= $projects['amount']) {
                    // on passe le projet en fundé
                    $this->projects_status_history->addStatus(-1, 60, $projects['id_project']);

                    $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' is now changed to status funded.');

                    $this->lEnchere = $this->bids->select('id_project = ' . $projects['id_project'] . ' AND status = 0', 'rate ASC,added ASC');
                    $leSoldeE       = 0;

                    $iBidNbTotal = count($this->lEnchere);
                    $iTreatedBitNb = 0;
                    $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' : ' . $iBidNbTotal . ' bids in total.');

                    foreach ($this->lEnchere as $k => $e) {
                        if ($leSoldeE < $projects['amount']) {
                            $amount = $e['amount'];

                            $leSoldeE += ($e['amount'] / 100);

                            // Pour la partie qui depasse le montant de l'emprunt ( ca cest que pour le mec a qui on decoupe son montant)
                            if ($leSoldeE > $projects['amount']) {
                                $diff = $leSoldeE - $projects['amount'];
                                // on retire le trop plein et ca nous donne la partie de montant a recup
                                $amount = ($e['amount'] / 100) - $diff;

                                $amount = $amount * 100;

                                // Montant a redonner au preteur
                                $montant_a_crediter = ($diff * 100);

                                $this->lenders_accounts->get($e['id_lender_account'], 'id_lender_account');
                                $this->bids->get($e['id_bid'], 'id_bid');

                                if ($this->bids->status == '0') {
                                    mail('k1@david.equinoa.net', 'debug cron degel', $this->lenders_accounts->id_client_owner . ' - id bid :' . $e['id_bid']);
                                    $this->transactions->id_client        = $this->lenders_accounts->id_client_owner;
                                    $this->transactions->montant          = $montant_a_crediter;
                                    $this->transactions->id_bid_remb      = $e['id_bid'];
                                    $this->transactions->id_langue        = 'fr';
                                    $this->transactions->date_transaction = date('Y-m-d H:i:s');
                                    $this->transactions->status           = '1';
                                    $this->transactions->etat             = '1';
                                    $this->transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                    $this->transactions->type_transaction = 2;
                                    $this->transactions->id_project       = $e['id_project'];
                                    $this->transactions->transaction      = 2; // transaction virtuelle
                                    $this->transactions->id_transaction   = $this->transactions->create();

                                    $this->wallets_lines->id_lender                = $e['id_lender_account'];
                                    $this->wallets_lines->type_financial_operation = 20;
                                    $this->wallets_lines->id_transaction           = $this->transactions->id_transaction;
                                    $this->wallets_lines->status                   = 1;
                                    $this->wallets_lines->type                     = 2;
                                    $this->wallets_lines->id_bid_remb              = $e['id_bid'];
                                    $this->wallets_lines->amount                   = $montant_a_crediter;
                                    $this->wallets_lines->id_project               = $e['id_project'];
                                    $this->wallets_lines->id_wallet_line           = $this->wallets_lines->create();

                                    $this->notifications->type       = 1; // rejet
                                    $this->notifications->id_lender  = $e['id_lender_account'];
                                    $this->notifications->id_project = $e['id_project'];
                                    $this->notifications->amount     = $montant_a_crediter;
                                    $this->notifications->id_bid     = $e['id_bid'];
                                    $this->notifications->create();

                                    $this->clients_gestion_mails_notif->id_client       = $this->lenders_accounts->id_client_owner;
                                    $this->clients_gestion_mails_notif->id_notif        = 3; // offre refusée
                                    $this->clients_gestion_mails_notif->id_project      = $e['id_project'];
                                    $this->clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
                                    $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                                    $this->clients_gestion_mails_notif->id_transaction  = $this->transactions->id_transaction;
                                    $this->clients_gestion_mails_notif->create();

                                    $sumOffres = $this->offres_bienvenues_details->sum('id_client = ' . $this->lenders_accounts->id_client_owner . ' AND id_bid = ' . $e['id_bid'], 'montant');

                                    if ($sumOffres > 0) {
                                        if ($sumOffres >= $amount) {
                                            $montant_offre_coupe_a_remb = $sumOffres - $amount;

                                            $this->offres_bienvenues_details->montant            = $montant_offre_coupe_a_remb;
                                            $this->offres_bienvenues_details->id_offre_bienvenue = 0;
                                            $this->offres_bienvenues_details->id_client          = $this->lenders_accounts->id_client_owner;
                                            $this->offres_bienvenues_details->id_bid             = 0;
                                            $this->offres_bienvenues_details->id_bid_remb        = $e['id_bid'];
                                            $this->offres_bienvenues_details->status             = 0;
                                            $this->offres_bienvenues_details->type               = 2;

                                            $this->offres_bienvenues_details->create();
                                        }
                                    }
                                }
                            }

                            if ($this->loans->get($e['id_bid'], 'id_bid') == false) {
                                $this->bids->get($e['id_bid'], 'id_bid');
                                $this->bids->status = 1;
                                $this->bids->update();

                                $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' : The bid (' . $e['id_bid'] . ') status has been updated to 1');

                                $this->loans->id_bid     = $e['id_bid'];
                                $this->loans->id_lender  = $e['id_lender_account'];
                                $this->loans->id_project = $e['id_project'];
                                $this->loans->amount     = $amount;

                                $this->loans->rate = $e['rate'];
                                $this->loans->create();

                                $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' : bid (' . $e['id_bid'] . ') has been transferred to loan (' . $this->loans->id_loan . ').');
                            }
                        } else {// Pour les encheres qui depassent on rend l'argent
                            $this->bids->get($e['id_bid'], 'id_bid');

                            // On regarde si on a pas deja un remb pour ce bid
                            if ($this->bids->status == '0') {
                                $this->bids->status = 2;
                                $this->bids->update();

                                $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' : The bid (' . $e['id_bid'] . ') status has been updated to 2');

                                $this->lenders_accounts->get($e['id_lender_account'], 'id_lender_account');

                                $this->transactions->id_client        = $this->lenders_accounts->id_client_owner;
                                $this->transactions->id_bid_remb      = $e['id_bid'];
                                $this->transactions->montant          = $e['amount'];
                                $this->transactions->id_langue        = 'fr';
                                $this->transactions->date_transaction = date('Y-m-d H:i:s');
                                $this->transactions->status           = '1';
                                $this->transactions->etat             = '1';
                                $this->transactions->id_project       = $e['id_project'];
                                $this->transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                $this->transactions->type_transaction = 2;
                                $this->transactions->transaction      = 2; // transaction virtuelle
                                $this->transactions->id_transaction   = $this->transactions->create();

                                $this->wallets_lines->id_lender                = $e['id_lender_account'];
                                $this->wallets_lines->type_financial_operation = 20;
                                $this->wallets_lines->id_transaction           = $this->transactions->id_transaction;
                                $this->wallets_lines->status                   = 1;
                                $this->wallets_lines->type                     = 2;
                                $this->wallets_lines->id_project               = $e['id_project'];
                                $this->wallets_lines->id_bid_remb              = $e['id_bid'];
                                $this->wallets_lines->amount                   = $e['amount'];
                                $this->wallets_lines->id_wallet_line           = $this->wallets_lines->create();

                                $this->notifications->type       = 1; // rejet
                                $this->notifications->id_lender  = $e['id_lender_account'];
                                $this->notifications->id_project = $e['id_project'];
                                $this->notifications->amount     = $e['amount'];
                                $this->notifications->id_bid     = $e['id_bid'];
                                $this->notifications->create();

                                $this->clients_gestion_mails_notif->id_client       = $this->lenders_accounts->id_client_owner;
                                $this->clients_gestion_mails_notif->id_notif        = 3; // offre refusée
                                $this->clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
                                $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                                $this->clients_gestion_mails_notif->id_transaction  = $this->transactions->id_transaction;
                                $this->clients_gestion_mails_notif->create();

                                $sumOffres = $this->offres_bienvenues_details->sum('id_client = ' . $this->lenders_accounts->id_client_owner . ' AND id_bid = ' . $e['id_bid'], 'montant');
                                if ($sumOffres > 0) {
                                    if ($sumOffres <= $e['amount']) {
                                        $this->offres_bienvenues_details->montant = $sumOffres;
                                    } else {// Si montant des offres superieur au remb on remb le montant a crediter
                                        $this->offres_bienvenues_details->montant = $e['amount'];
                                    }

                                    $this->offres_bienvenues_details->id_offre_bienvenue = 0;
                                    $this->offres_bienvenues_details->id_client          = $this->lenders_accounts->id_client_owner;
                                    $this->offres_bienvenues_details->id_bid             = 0;
                                    $this->offres_bienvenues_details->id_bid_remb        = $e['id_bid'];
                                    $this->offres_bienvenues_details->status             = 0;
                                    $this->offres_bienvenues_details->type               = 2;

                                    $this->offres_bienvenues_details->create();
                                }
                            }
                        }
                        $iTreatedBitNb ++;
                        $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' : ' . $iTreatedBitNb . '/' . $iBidNbTotal . ' bids treated.');
                    }

                    $this->create_echeances($projects['id_project']);
                    $this->createEcheancesEmprunteur($projects['id_project']);

                    $e                      = $this->loadData('clients');
                    $loan                   = $this->loadData('loans');
                    $project                = $this->loadData('projects');
                    $companie               = $this->loadData('companies');
                    $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');

                    $project->get($projects['id_project'], 'id_project');
                    $companie->get($project->id_company, 'id_company');
                    $this->mails_text->get('emprunteur-dossier-funde-et-termine', 'lang = "' . $this->language . '" AND type');

                    $e->get($companie->id_client_owner, 'id_client');

                    $montantHaut = 0;
                    $montantBas  = 0;
                    foreach ($loan->select('id_project = ' . $project->id_project) as $b) {
                        $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                        $montantBas += ($b['amount'] / 100);
                    }
                    $taux_moyen = ($montantHaut / $montantBas);

                    $echeanciers_emprunteur->get($project->id_project, 'ordre = 1 AND id_project');
                    $mensualite = $echeanciers_emprunteur->montant + $echeanciers_emprunteur->commission + $echeanciers_emprunteur->tva;
                    $mensualite = ($mensualite / 100);

                    $surl         = $this->surl;
                    $url          = $this->lurl;
                    $projet       = $project->title;
                    $link_mandat  = $this->lurl . '/pdf/mandat/' . $e->hash . '/' . $project->id_project;
                    $link_pouvoir = $this->lurl . '/pdf/pouvoir/' . $e->hash . '/' . $project->id_project;

                    $varMail = array(
                        'surl'                   => $surl,
                        'url'                    => $url,
                        'prenom_e'               => $e->prenom,
                        'nom_e'                  => $companie->name,
                        'mensualite'             => $this->ficelle->formatNumber($mensualite),
                        'montant'                => $this->ficelle->formatNumber($project->amount, 0),
                        'taux_moyen'             => $this->ficelle->formatNumber($taux_moyen),
                        'link_compte_emprunteur' => $this->lurl . '/projects/detail/' . $project->id_project,
                        'link_mandat'            => $link_mandat,
                        'link_pouvoir'           => $link_pouvoir,
                        'projet'                 => $projet,
                        'lien_fb'                => $lien_fb,
                        'lien_tw'                => $lien_tw
                    );

                    $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                    $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                    $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                    $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                    $this->email = $this->loadLib('email');
                    $this->email->setFrom($this->mails_text->exp_email, $exp_name);

                    $this->email->setSubject(stripslashes($sujetMail));
                    $this->email->setHTMLBody(stripslashes($texteMail));

                    if ($e->status == 1) {
                        if ($this->Config['env'] == 'prod') {
                            Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $e->email, $tabFiler);
                            $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                        } else {
                            $this->email->addRecipient(trim($e->email));
                            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        }
                        $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' : email emprunteur-dossier-funde-et-termine sent');
                    }

                    $this->projects->get($projects['id_project'], 'id_project');
                    $this->companies->get($this->projects->id_company, 'id_company');
                    $this->clients->get($this->companies->id_client_owner, 'id_client');

                    $this->settings->get('Adresse notification projet funde a 100', 'type');
                    $destinataire    = $this->settings->value;
                    $montant_collect = $this->bids->getSoldeBid($this->projects->id_project);

                    // si le solde des enchere est supperieur au montant du pret on affiche le montant du pret
                    if (($montant_collect / 100) >= $this->projects->amount) {
                        $montant_collect = $this->projects->amount;
                    }

                    $this->nbPeteurs = $this->loans->getNbPreteurs($this->projects->id_project);

                    $this->mails_text->get('notification-projet-funde-a-100', 'lang = "' . $this->language . '" AND type');

                    $surl = $this->surl;
                    $url = $this->lurl;
                    $id_projet = $this->projects->id_project;
                    $title_projet = utf8_decode($this->projects->title);
                    $nbPeteurs = $this->nbPeteurs;
                    $tx = $taux_moyen;
                    $montant_pret = $this->projects->amount;
                    $montant = $montant_collect;
                    $periode = $this->projects->period;

                    $sujetMail = htmlentities($this->mails_text->subject);
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

                    $this->email->setSubject('=?UTF-8?B?' . base64_encode(html_entity_decode($sujetMail)) . '?=');
                    $this->email->setHTMLBody($texteMail);
                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);

                    $preteur     = $this->loadData('clients');
                    $companies   = $this->loadData('companies');
                    $echeanciers = $this->loadData('echeanciers');

                    // on parcourt les bids ok du projet et on envoie les mail
                    $lLoans = $this->loans->select('id_project = ' . $projects['id_project']);
                    $iLoanNbTotal = count($lLoans);
                    $iTreatedLoanNb = 0;
                    $oLogger->addRecord(ULogger::INFO, 'project : ' .  $iLoanNbTotal . ' loans to send email');
                    foreach ($lLoans as $l) {
                        $this->projects->get($l['id_project'], 'id_project');
                        $this->lenders_accounts->get($l['id_lender'], 'id_lender_account');
                        $preteur->get($this->lenders_accounts->id_client_owner, 'id_client');
                        $companies->get($this->projects->id_company, 'id_company');

                        $p         = substr($this->ficelle->stripAccents(utf8_decode(trim($preteur->prenom))), 0, 1);
                        $nom       = $this->ficelle->stripAccents(utf8_decode(trim($preteur->nom)));
                        $id_client = str_pad($preteur->id_client, 6, 0, STR_PAD_LEFT);
                        $motif     = mb_strtoupper($id_client . $p . $nom, 'UTF-8');

                        $lecheancier = $echeanciers->getPremiereEcheancePreteurByLoans($l['id_project'], $l['id_lender'], $l['id_loan']);

                        $this->mails_text->get('preteur-bid-ok', 'lang = "' . $this->language . '" AND type');

                        $surl = $this->surl;
                        $url = $this->lurl;
                        $prenom = $preteur->prenom;
                        $projet = $this->projects->title;
                        $montant_pret = $this->ficelle->formatNumber($l['amount'] / 100);
                        $taux = $this->ficelle->formatNumber($l['rate']);
                        $entreprise = $companies->name;
                        $date = $this->dates->formatDate($l['added'], 'd/m/Y');
                        $heure = $this->dates->formatDate($l['added'], 'H');
                        $duree = $this->projects->period;

                        $timeAdd = strtotime($lecheancier['date_echeance']);
                        $month   = $this->dates->tableauMois['fr'][date('n', $timeAdd)];

                        $varMail = array(
                            'surl'           => $this->surl,
                            'url'            => $this->lurl,
                            'prenom_p'       => $preteur->prenom,
                            'valeur_bid'     => $this->ficelle->formatNumber($l['amount'] / 100),
                            'taux_bid'       => $this->ficelle->formatNumber($l['rate']),
                            'nom_entreprise' => $companies->name,
                            'nbre_echeance'  => $this->projects->period,
                            'mensualite_p'   => $this->ficelle->formatNumber($lecheancier['montant'] / 100),
                            'date_debut'     => date('d', $timeAdd) . ' ' . strtolower($month) . ' ' . date('Y', $timeAdd),
                            'compte-p'       => $this->lurl,
                            'projet-p'       => $this->lurl . '/projects/detail/' . $this->projects->slug,
                            'motif_virement' => $motif,
                            'lien_fb'        => $lien_fb,
                            'lien_tw'        => $lien_tw
                        );

                        $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                        $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                        $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                        $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                        $this->email = $this->loadLib('email');
                        $this->email->setFrom($this->mails_text->exp_email, $exp_name);

                        $this->email->setSubject(stripslashes($sujetMail));
                        $this->email->setHTMLBody(stripslashes($texteMail));

                        if ($preteur->status == 1) {
                            if ($this->Config['env'] == 'prod') {
                                Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $preteur->email, $tabFiler);
                                $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                            } else {
                                $this->email->addRecipient(trim($preteur->email));
                                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                            }
                            $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' : email preteur-bid-ok sent for loan (' . $l['id_loan'] . ')');
                        }
                        $iTreatedLoanNb++;

                        $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' : ' . $iTreatedLoanNb . '/' . $iLoanNbTotal . ' loan notification mail sent');
                    }
                } else {// Funding KO (le solde demandé n'a pas ete atteint par les encheres)
                    // On passe le projet en funding ko
                    $this->projects_status_history->addStatus(-1, 70, $projects['id_project']);

                    $this->projects->get($projects['id_project'], 'id_project');
                    $this->companies->get($this->projects->id_company, 'id_company');
                    $this->clients->get($this->companies->id_client_owner, 'id_client');

                    $this->mails_text->get('emprunteur-dossier-funding-ko', 'lang = "' . $this->language . '" AND type');

                    $surl   = $this->surl;
                    $url    = $this->lurl;
                    $projet = $this->projects->title;

                    $varMail = array(
                        'surl'     => $surl,
                        'url'      => $url,
                        'prenom_e' => $this->clients->prenom,
                        'projet'   => $projet,
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

                    if ($this->clients->status == 1) {
                        if ($this->Config['env'] == 'prod') {
                            Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                            $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                        } else {
                            $this->email->addRecipient(trim($this->clients->email));
                            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        }
                        $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' : email emprunteur-dossier-funding-ko sent');
                    }

                    $this->lEnchere = $this->bids->select('id_project = ' . $projects['id_project'], 'rate ASC,added ASC');

                    $iBidNbTotal = count($this->lEnchere);
                    $iTreatedBitNb = 0;
                    $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' : ' . $iBidNbTotal . 'bids in total.');

                    foreach ($this->lEnchere as $k => $e) {
                        $this->lenders_accounts->get($e['id_lender_account'], 'id_lender_account');

                        $this->transactions->id_client        = $this->lenders_accounts->id_client_owner;
                        $this->transactions->montant          = $e['amount'];
                        $this->transactions->id_langue        = 'fr';
                        $this->transactions->date_transaction = date('Y-m-d H:i:s');
                        $this->transactions->status           = '1';
                        $this->transactions->id_project       = $e['id_project'];
                        $this->transactions->etat             = '1';
                        $this->transactions->id_bid_remb      = $e['id_bid'];
                        $this->transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                        $this->transactions->type_transaction = 2;
                        $this->transactions->transaction      = 2; // transaction virtuelle
                        $this->transactions->id_transaction   = $this->transactions->create();

                        $this->wallets_lines->id_lender                = $e['id_lender_account'];
                        $this->wallets_lines->type_financial_operation = 20;
                        $this->wallets_lines->id_transaction           = $this->transactions->id_transaction;
                        $this->wallets_lines->status                   = 1;
                        $this->wallets_lines->id_project               = $e['id_project'];
                        $this->wallets_lines->type                     = 2;
                        $this->wallets_lines->id_bid_remb              = $e['id_bid'];
                        $this->wallets_lines->amount                   = $e['amount'];
                        $this->wallets_lines->id_wallet_line           = $this->wallets_lines->create();

                        $this->bids->get($e['id_bid'], 'id_bid');
                        $this->bids->status = 2;
                        $this->bids->update();

                        $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' : The bid (' . $e['id_bid'] . ') status has been updated to 2');

                        $this->notifications->type            = 1; // rejet
                        $this->notifications->id_lender       = $e['id_lender_account'];
                        $this->notifications->id_project      = $e['id_project'];
                        $this->notifications->amount          = $e['amount'];
                        $this->notifications->id_bid          = $e['id_bid'];
                        $this->notifications->id_notification = $this->notifications->create();

                        $this->clients_gestion_mails_notif->id_client       = $this->lenders_accounts->id_client_owner;
                        $this->clients_gestion_mails_notif->id_notif        = 3; // offre refusée
                        $this->clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
                        $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                        $this->clients_gestion_mails_notif->id_transaction  = $this->transactions->id_transaction;
                        $this->clients_gestion_mails_notif->create();

                        $sumOffres = $this->offres_bienvenues_details->sum('id_client = ' . $this->lenders_accounts->id_client_owner . ' AND id_bid = ' . $e['id_bid'], 'montant');
                        if ($sumOffres > 0) {
                            // sum des offres inferieur au montant a remb
                            if ($sumOffres <= $e['amount']) {
                                $this->offres_bienvenues_details->montant = $sumOffres;
                            } else {// Si montant des offres superieur au remb on remb le montant a crediter
                                $this->offres_bienvenues_details->montant = $e['amount'];
                            }

                            $this->offres_bienvenues_details->id_offre_bienvenue = 0;
                            $this->offres_bienvenues_details->id_client          = $this->lenders_accounts->id_client_owner;
                            $this->offres_bienvenues_details->id_bid             = 0;
                            $this->offres_bienvenues_details->id_bid_remb        = $e['id_bid'];
                            $this->offres_bienvenues_details->status             = 0;
                            $this->offres_bienvenues_details->type               = 2;

                            $this->offres_bienvenues_details->create();
                        }

                        $this->projects->get($e['id_project'], 'id_project');
                        $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');

                        // Motif virement
                        $p         = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))), 0, 1);
                        $nom       = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
                        $id_client = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
                        $motif     = mb_strtoupper($id_client . $p . $nom, 'UTF-8');

                        $solde_p = $this->transactions->getSolde($this->clients->id_client);

                        $this->mails_text->get('preteur-dossier-funding-ko', 'lang = "' . $this->language . '" AND type');

                        $timeAdd = strtotime($e['added']);
                        $month   = $this->dates->tableauMois['fr'][date('n', $timeAdd)];

                        $this->settings->get('Facebook', 'type');
                        $lien_fb = $this->settings->value;

                        $this->settings->get('Twitter', 'type');
                        $lien_tw = $this->settings->value;

                        $varMail = array(
                            'surl'                  => $this->surl,
                            'url'                   => $this->lurl,
                            'prenom_p'              => $this->clients->prenom,
                            'entreprise'            => $this->companies->name,
                            'projet'                => $this->projects->title,
                            'montant'               => $this->ficelle->formatNumber($e['amount'] / 100),
                            'proposition_pret'      => $this->ficelle->formatNumber(($e['amount'] / 100)),
                            'date_proposition_pret' => date('d', $timeAdd) . ' ' . $month . ' ' . date('Y', $timeAdd),
                            'taux_proposition_pret' => $e['rate'],
                            'compte-p'              => '/projets-a-financer',
                            'motif_virement'        => $motif,
                            'solde_p'               => $solde_p,
                            'lien_fb'               => $lien_fb,
                            'lien_tw'               => $lien_tw
                        );

                        $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                        $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                        $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                        $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                        $this->email = $this->loadLib('email');
                        $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                        $this->email->setSubject(stripslashes($sujetMail));
                        $this->email->setHTMLBody(stripslashes($texteMail));

                        if ($this->clients->status == 1) {
                            if ($this->Config['env'] == 'prod') {
                                Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                                $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                            } else {
                                $this->email->addRecipient(trim($this->clients->email));
                                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                            }
                            $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' : email preteur-dossier-funding-ko sent');
                        }

                        $iTreatedBitNb ++;
                        $oLogger->addRecord(ULogger::INFO, 'project : ' . $projects['id_project'] . ' : ' . $iTreatedBitNb . '/' . $iBidNbTotal . 'bids treated.');
                    }
                } // fin funding ko

                $this->projects->get($projects['id_project'], 'id_project');
                $this->companies->get($this->projects->id_company, 'id_company');
                $this->clients->get($this->companies->id_client_owner, 'id_client');

                $this->settings->get('Adresse notification projet fini', 'type');
                $destinataire = $this->settings->value;

                $montant_collect = $this->bids->getSoldeBid($this->projects->id_project);

                // si le solde des enchere est supperieur au montant du pret on affiche le montant du pret
                if (($montant_collect / 100) >= $this->projects->amount) {
                    $montant_collect = $this->projects->amount;
                }

                $this->nbPeteurs = $this->loans->getNbPreteurs($this->projects->id_project);

                $this->mails_text->get('notification-projet-fini', 'lang = "' . $this->language . '" AND type');

                $surl = $this->surl;
                $url = $this->lurl;
                $id_projet = $this->projects->id_project;
                $title_projet = utf8_decode($this->projects->title);
                $nbPeteurs = $this->nbPeteurs;
                $tx = $this->projects->target_rate;
                $montant_pret = $this->projects->amount;
                $montant = $montant_collect;
                $sujetMail = htmlentities($this->mails_text->subject);

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

                $this->email->setSubject('=?UTF-8?B?' . base64_encode(html_entity_decode($sujetMail)) . '?=');
                $this->email->setHTMLBody($texteMail);
                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
            }
        }
    }

    // On créer les echeances des futures remb
    public function create_echeances($id_project)
    {
        ini_set('max_execution_time', 300); //300 seconds = 5 minutes
        ini_set('memory_limit', '512M');
        $oLogger = new ULogger('cron', $this->logPath, 'cron_check_projet_en_funding.log');
        // chargement des datas
        $this->loans           = $this->loadData('loans');
        $this->projects        = $this->loadData('projects');
        $this->projects_status = $this->loadData('projects_status');
        $this->echeanciers     = $this->loadData('echeanciers');

        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->lenders_accounts = $this->loadData('lenders_accounts');

        // Chargement des librairies
        $this->remb = $this->loadLib('remb');
        $jo         = $this->loadLib('jours_ouvres');

        $this->settings->get('Commission remboursement', 'type');
        $com = $this->settings->value;

        // On definit le nombre de mois et de jours apres la date de fin pour commencer le remboursement
        $this->settings->get('Nombre de mois apres financement pour remboursement', 'type');
        $nb_mois = $this->settings->value;
        $this->settings->get('Nombre de jours apres financement pour remboursement', 'type');
        $nb_jours = $this->settings->value;

        // tva (0.196)
        $this->settings->get('TVA', 'type');
        $tva = $this->settings->value;

        // EQ-Acompte d'impôt sur le revenu
        $this->settings->get("EQ-Acompte d'impôt sur le revenu", 'type');
        $prelevements_obligatoires = $this->settings->value;

        // EQ-Contribution additionnelle au Prélèvement Social
        $this->settings->get('EQ-Contribution additionnelle au Prélèvement Social', 'type');
        $contributions_additionnelles = $this->settings->value;

        // EQ-CRDS
        $this->settings->get('EQ-CRDS', 'type');
        $crds = $this->settings->value;

        // EQ-CSG
        $this->settings->get('EQ-CSG', 'type');
        $csg = $this->settings->value;

        // EQ-Prélèvement de Solidarité
        $this->settings->get('EQ-Prélèvement de Solidarité', 'type');
        $prelevements_solidarite = $this->settings->value;

        // EQ-Prélèvement social
        $this->settings->get('EQ-Prélèvement social', 'type');
        $prelevements_sociaux = $this->settings->value;

        // EQ-Retenue à la source
        $this->settings->get('EQ-Retenue à la source', 'type');
        $retenues_source = $this->settings->value;

        // On recupere le statut
        $this->projects_status->getLastStatut($id_project);

        // Si le projet est bien en funde on créer les echeances
        if ($this->projects_status->status == 60) {
            // On recupere le projet
            $this->projects->get($id_project, 'id_project');

            echo '-------------------<br>';
            echo 'id Projet : ' . $this->projects->id_project . '<br>';
            echo 'date fin de financement : ' . $this->projects->date_fin . '<br>';
            echo '-------------------<br>';

            // Liste des loans du projet
            $lLoans = $this->loans->select('id_project = ' . $this->projects->id_project);

            $iLoanNbTotal = count($lLoans);
            $iTreatedLoanNb = 0;
            $oLogger->addRecord(ULogger::INFO, 'project : ' . $id_project . ' : ' . $iLoanNbTotal . ' in total.');

            // on parcourt les loans du projet en remboursement
            foreach ($lLoans as $l) {
                //////////////////////////////
                // Echeancier remboursement //
                //////////////////////////////

                $this->lenders_accounts->get($l['id_lender'], 'id_lender_account');
                $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');

                $this->clients_adresses->get($this->lenders_accounts->id_client_owner, 'id_client');

                // 0 : fr/fr
                // 1 : fr/resident etranger
                // 2 : no fr/resident etranger
                $etranger = 0;
                // fr/resident etranger
                if ($this->clients->id_nationalite <= 1 && $this->clients_adresses->id_pays_fiscal > 1) {
                    $etranger = 1;
                } // no fr/resident etranger
                elseif ($this->clients->id_nationalite > 1 && $this->clients_adresses->id_pays_fiscal > 1) {
                    $etranger = 2;
                }

                $capital     = ($l['amount'] / 100);
                $nbecheances = $this->projects->period;
                $taux        = ($l['rate'] / 100);
                $commission  = $com;

                $tabl = $this->remb->echeancier($capital, $nbecheances, $taux, $commission, $tva);

                $donneesEcheances = $tabl[1];
                $lEcheanciers     = $tabl[2];

                // on crée les echeances de chaques preteurs
                foreach ($lEcheanciers as $k => $e) {
                    // Date d'echeance preteur
                    $dateEcheance = $this->dates->dateAddMoisJoursV3($this->projects->date_fin,$k);
                    $dateEcheance = date('Y-m-d H:i', $dateEcheance) . ':00';

                    // Date d'echeance emprunteur
                    $dateEcheance_emprunteur = $this->dates->dateAddMoisJoursV3($this->projects->date_fin,$k);
                    // on retire 6 jours ouvrés
                    $dateEcheance_emprunteur = $jo->display_jours_ouvres($dateEcheance_emprunteur, 6);
                    $dateEcheance_emprunteur = date('Y-m-d H:i', $dateEcheance_emprunteur) . ':00';

                    // particulier
                    if (in_array($this->clients->type, array(1, 3))) {
                        if ($etranger > 0) {
                            $montant_prelevements_obligatoires    = 0;
                            $montant_contributions_additionnelles = 0;
                            $montant_crds                         = 0;
                            $montant_csg                          = 0;
                            $montant_prelevements_solidarite      = 0;
                            $montant_prelevements_sociaux         = 0;
                            $montant_retenues_source              = round($retenues_source * $e['interets'], 2);
                        } else {
                            if ($this->lenders_accounts->exonere == 1) {

                                /// exo date debut et fin ///
                                if ($this->lenders_accounts->debut_exoneration != '0000-00-00' && $this->lenders_accounts->fin_exoneration != '0000-00-00') {
                                    if (strtotime($dateEcheance) >= strtotime($this->lenders_accounts->debut_exoneration) && strtotime($dateEcheance) <= strtotime($this->lenders_accounts->fin_exoneration)) {
                                        $montant_prelevements_obligatoires = 0;
                                    } else {
                                        $montant_prelevements_obligatoires = round($prelevements_obligatoires * $e['interets'], 2);
                                    }
                                } /////////////////////////////
                                else {
                                    $montant_prelevements_obligatoires = 0;
                                }
                            } else {
                                $montant_prelevements_obligatoires = round($prelevements_obligatoires * $e['interets'], 2);
                            }

                            $montant_contributions_additionnelles = round($contributions_additionnelles * $e['interets'], 2);
                            $montant_crds                         = round($crds * $e['interets'], 2);
                            $montant_csg                          = round($csg * $e['interets'], 2);
                            $montant_prelevements_solidarite      = round($prelevements_solidarite * $e['interets'], 2);
                            $montant_prelevements_sociaux         = round($prelevements_sociaux * $e['interets'], 2);
                            $montant_retenues_source              = 0;
                        }
                    } // entreprise
                    else {
                        $montant_prelevements_obligatoires    = 0;
                        $montant_contributions_additionnelles = 0;
                        $montant_crds                         = 0;
                        $montant_csg                          = 0;
                        $montant_prelevements_solidarite      = 0;
                        $montant_prelevements_sociaux         = 0;
                        $montant_retenues_source              = round($retenues_source * $e['interets'], 2);
                    }

                    $this->echeanciers->id_lender                    = $l['id_lender'];
                    $this->echeanciers->id_project                   = $this->projects->id_project;
                    $this->echeanciers->id_loan                      = $l['id_loan'];
                    $this->echeanciers->ordre                        = $k;
                    $this->echeanciers->montant                      = $e['echeance'] * 100;
                    $this->echeanciers->capital                      = $e['capital'] * 100;
                    $this->echeanciers->interets                     = $e['interets'] * 100;
                    $this->echeanciers->commission                   = $e['commission'] * 100;
                    $this->echeanciers->tva                          = $e['tva'] * 100;
                    $this->echeanciers->prelevements_obligatoires    = $montant_prelevements_obligatoires;
                    $this->echeanciers->contributions_additionnelles = $montant_contributions_additionnelles;
                    $this->echeanciers->crds                         = $montant_crds;
                    $this->echeanciers->csg                          = $montant_csg;
                    $this->echeanciers->prelevements_solidarite      = $montant_prelevements_solidarite;
                    $this->echeanciers->prelevements_sociaux         = $montant_prelevements_sociaux;
                    $this->echeanciers->retenues_source              = $montant_retenues_source;
                    $this->echeanciers->date_echeance                = $dateEcheance;
                    $this->echeanciers->date_echeance_emprunteur     = $dateEcheance_emprunteur;
                    $this->echeanciers->create();

                    $oLogger->addRecord(ULogger::INFO, 'project : ' . $id_project . ' : echeanciers (' . $this->echeanciers->id_echeancier . ') and order (' . $this->echeanciers->ordre .') has been created for loan (' .  $l['id_loan'] . ')');
                }
                $iTreatedLoanNb ++;
                $oLogger->addRecord(ULogger::INFO, 'project : ' . $id_project . ' : ' .  $iTreatedLoanNb . '/' . $iLoanNbTotal . ' lender loan treated.');
            }
        }
    }

    // fonction create echeances emprunteur
    public function createEcheancesEmprunteur($id_project)
    {
        ini_set('memory_limit', '512M');

        $oLogger = new ULogger('cron', $this->logPath, 'cron_check_projet_en_funding.log');

        $loans                  = $this->loadData('loans');
        $projects               = $this->loadData('projects');
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        $echeanciers            = $this->loadData('echeanciers');

        $remb = $this->loadLib('remb');
        $jo   = $this->loadLib('jours_ouvres');

        $this->settings->get('Commission remboursement', 'type');
        $com = $this->settings->value;

        // On definit le nombre de mois et de jours apres la date de fin pour commencer le remboursement
        $this->settings->get('Nombre de mois apres financement pour remboursement', 'type');
        $nb_mois = $this->settings->value;
        $this->settings->get('Nombre de jours apres financement pour remboursement', 'type');
        $nb_jours = $this->settings->value;

        // tva (0.196)
        $this->settings->get('TVA', 'type');
        $tva = $this->settings->value;

        $projects->get($id_project, 'id_project');

        $montantHaut = 0;
        $montantBas  = 0;
        foreach ($loans->select('id_project = ' . $projects->id_project) as $b) {
            $montantHaut += ($b['rate'] * ($b['amount'] / 100));
            $montantBas += ($b['amount'] / 100);
        }
        $tauxMoyen = ($montantHaut / $montantBas);

        $capital     = $projects->amount;
        $nbecheances = $projects->period;
        $taux        = ($tauxMoyen / 100);
        $commission  = $com;

        $tabl = $remb->echeancier($capital, $nbecheances, $taux, $commission, $tva);

        $donneesEcheances = $tabl[1];
        //$lEcheanciers = $tabl[2];

        $lEcheanciers = $echeanciers->getSumRembEmpruntByMonths($projects->id_project);

        $iEcheancierNbTotal = count($lEcheanciers);
        $iTreatedEcheancierNb = 0;
        $oLogger->addRecord(ULogger::INFO, 'project : ' . $id_project . ' : ' .$iEcheancierNbTotal . ' in total.');
        foreach ($lEcheanciers as $k => $e) {
            // Date d'echeance emprunteur
            $dateEcheance_emprunteur = $this->dates->dateAddMoisJoursV3($projects->date_fin, $k);
            // on retire 6 jours ouvrés
            $dateEcheance_emprunteur = $jo->display_jours_ouvres($dateEcheance_emprunteur, 6);

            $dateEcheance_emprunteur = date('Y-m-d H:i', $dateEcheance_emprunteur) . ':00';

            $echeanciers_emprunteur->id_project               = $projects->id_project;
            $echeanciers_emprunteur->ordre                    = $k;
            $echeanciers_emprunteur->montant                  = $e['montant'] * 100; // sum montant preteurs
            $echeanciers_emprunteur->capital                  = $e['capital'] * 100; // sum capital preteurs
            $echeanciers_emprunteur->interets                 = $e['interets'] * 100; // sum interets preteurs
            $echeanciers_emprunteur->commission               = $donneesEcheances['comParMois'] * 100; // on recup com du projet
            $echeanciers_emprunteur->tva                      = $donneesEcheances['tvaCom'] * 100; // et tva du projet
            $echeanciers_emprunteur->date_echeance_emprunteur = $dateEcheance_emprunteur;
            $echeanciers_emprunteur->create();

            $oLogger->addRecord(ULogger::INFO, 'project : ' . $id_project . ' :  echeance (' . $echeanciers_emprunteur->id_echeanciers_emprunteur . ') has been created.');

            $iTreatedEcheancierNb ++;
            $oLogger->addRecord(ULogger::INFO, 'project : ' . $id_project . ' : ' . $iTreatedEcheancierNb . '/' . $iEcheancierNbTotal . ' borrower echeance created.');
        }
    }

    // check les statuts remb
    public function _check_status()
    {
        // die temporaire pour eviter de changer le statut du prelevement en retard
        die;
        // chargement des datas
        $projects                = $this->loadData('projects');
        $projects_status         = $this->loadData('projects_status');
        $echeanciers             = $this->loadData('echeanciers');
        $echeanciers_emprunteur  = $this->loadData('echeanciers_emprunteur');
        $projects_status_history = $this->loadData('projects_status_history');
        $projects_status         = $this->loadData('projects_status');
        $loans                   = $this->loadData('loans');
        $preteur                 = $this->loadData('clients');
        $lender                  = $this->loadData('lenders_accounts');
        $companies               = $this->loadData('companies');

        // Cabinet de recouvrement
        $this->settings->get('Cabinet de recouvrement', 'type');
        $ca_recou = $this->settings->value;

        // FB
        $this->settings->get('Facebook', 'type');
        $lien_fb = $this->settings->value;

        // Twitter
        $this->settings->get('Twitter', 'type');
        $lien_tw = $this->settings->value;

        // Date du jour
        $today = date('Y-m-d');
        //$today = '2015-03-15';
        $time = strtotime($today . ' 00:00:00');

        // projets en remb ou en probleme
        $lProjects = $projects->selectProjectsByStatus('80,100');

        foreach ($lProjects as $p) {
            // On recupere le statut
            $projects_status->getLastStatut($p['id_project']);

            // On recup les echeances inferieur a la date du jour
            $lEcheancesEmp = $echeanciers_emprunteur->select('id_project = ' . $p['id_project'] . ' AND  	status_emprunteur = 0 AND date_echeance_emprunteur < "' . $today . ' 00:00:00"');

            foreach ($lEcheancesEmp as $e) {
                $dateRemb = strtotime($e['date_echeance_emprunteur']);

                // si statut remb
                if ($projects_status->status == 80) {
                    // date echeance emprunteur +5j (probleme)
                    $laDate = mktime(0, 0, 0, date("m", $dateRemb), date("d", $dateRemb) + 5, date("Y", $dateRemb));
                    $type   = 'probleme';
                } // statut probleme
                elseif ($projects_status->status == 100) {
                    // date echeance emprunteur +8j (recouvrement)
                    $laDate = mktime(0, 0, 0, date("m", $dateRemb), date("d", $dateRemb) + 8, date("Y", $dateRemb));
                    $type   = 'recouvrement';
                }

                // si la date +nJ est eqale ou depasse
                if ($laDate <= $time) {
                    // probleme
                    if ($type == 'probleme') {
                        echo 'probleme<br>';
                        $projects_status_history->addStatus(-1, 100, $p['id_project']);
                    } // recouvrement
                    else {
                        echo 'recouvrement<br>';
                        $projects_status_history->addStatus(-1, 110, $p['id_project']);

                        // date du probleme
                        $statusProbleme = $projects_status_history->select('id_project = ' . $p['id_project'] . ' AND  	id_project_status = 9', 'added DESC');

                        $timeAdd = strtotime($statusProbleme[0]['added']);
                        $month   = $this->dates->tableauMois['fr'][date('n', $timeAdd)];

                        $DateProbleme = date('d', $timeAdd) . ' ' . $month . ' ' . date('Y', $timeAdd);
                    }

                    // on recup les prets (donc leurs preteurs)
                    $lLoans = $loans->select('id_project = ' . $p['id_project']);

                    // On recup l'entreprise
                    $projects->get($p['id_project'], 'id_project');
                    $companies->get($projects->id_company, 'id_company');

                    // on fait le tour des prets
                    foreach ($lLoans as $l) {
                        // on recup le preteur
                        $lender->get($l['id_lender'], 'id_lender_account');
                        $preteur->get($lender->id_client_owner, 'id_client');

                        $rembNet = 0;

                        // Motif virement
                        $p         = substr($this->ficelle->stripAccents(utf8_decode(trim($preteur->prenom))), 0, 1);
                        $nom       = $this->ficelle->stripAccents(utf8_decode(trim($preteur->nom)));
                        $id_client = str_pad($preteur->id_client, 6, 0, STR_PAD_LEFT);
                        $motif     = mb_strtoupper($id_client . $p . $nom, 'UTF-8');

                        // probleme
                        if ($type == 'probleme') {
                            ////////////////////////////////////////////
                            // on recup la somme deja remb du preteur //
                            ////////////////////////////////////////////
                            $lEchea = $echeanciers->select('id_loan = ' . $l['id_loan'] . ' AND id_project = ' . $p['id_project'] . ' AND status = 1');

                            foreach ($lEchea as $e) {
                                // on fait la somme de tout
                                $rembNet += ($e['montant'] / 100) - $e['prelevements_obligatoires'] - $e['retenues_source'] - $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];
                            }
                            ////////////////////////////////////////////
                            // mail probleme preteur-erreur-remboursement
                            //**************************************//
                            //*** ENVOI DU MAIL PROBLEME PRETEUR ***//
                            //**************************************//
                            // Recuperation du modele de mail
                            $this->mails_text->get('preteur-erreur-remboursement', 'lang = "' . $this->language . '" AND type');

                            $varMail = array(
                                'surl'              => $this->surl,
                                'url'               => $this->furl,
                                'prenom_p'          => $preteur->prenom,
                                'valeur_bid'        => $this->ficelle->formatNumber($l['amount'] / 100),
                                'nom_entreprise'    => $companies->name,
                                'montant_rembourse' => $this->ficelle->formatNumber($rembNet),
                                'cab_recouvrement'  => $ca_recou,
                                'motif_virement'    => $motif,
                                'lien_fb'           => $lien_fb,
                                'lien_tw'           => $lien_tw
                            );
                        } // recouvrement
                        else {
                            // mail recouvrement preteur-dossier-recouvrement
                            //******************************************//
                            //*** ENVOI DU MAIL RECOUVREMENT PRETEUR ***//
                            //******************************************//
                            // Recuperation du modele de mail
                            $this->mails_text->get('preteur-dossier-recouvrement', 'lang = "' . $this->language . '" AND type');

                            $varMail = array(
                                'surl'             => $this->surl,
                                'url'              => $this->furl,
                                'prenom_p'         => $preteur->prenom,
                                'date_probleme'    => $DateProbleme,
                                'cab_recouvrement' => $ca_recou,
                                'nom_entreprise'   => $companies->name,
                                'motif_virement'   => $motif,
                                'lien_fb'          => $lien_fb,
                                'lien_tw'          => $lien_tw
                            );
                        }
                    }
                    break;
                }
            }
        }
    }

    // On check les virements a envoyer sur le sftp (une fois par jour)
    // les virements sont pour retirer largent du compte unilend vers le true compte client
    public function _virements()
    {
        if (true === $this->startCron('virements', 5)) {

            $this->virements           = $this->loadData('virements');
            $this->clients             = $this->loadData('clients');
            $this->lenders_accounts    = $this->loadData('lenders_accounts');
            $this->compteur_transferts = $this->loadData('compteur_transferts');
            $this->companies           = $this->loadData('companies');

            // Virement - BIC
            $this->settings->get('Virement - BIC', 'type');
            $bic = $this->settings->value;

            // Virement - domiciliation
            $this->settings->get('Virement - domiciliation', 'type');
            $domiciliation = $this->settings->value;

            // Virement - IBAN
            $this->settings->get('Virement - IBAN', 'type');
            $iban = $this->settings->value;
            $iban = str_replace(' ', '', $iban);

            // titulaire du compte
            $this->settings->get('titulaire du compte', 'type');
            $titulaire = utf8_decode($this->settings->value);

            // Retrait Unilend - BIC
            $this->settings->get('Retrait Unilend - BIC', 'type');
            $retraitBic = utf8_decode($this->settings->value);
            // Retrait Unilend - Domiciliation
            $this->settings->get('Retrait Unilend - Domiciliation', 'type');
            $retraitDom = utf8_decode($this->settings->value);
            // Retrait Unilend - IBAN
            $this->settings->get('Retrait Unilend - IBAN', 'type');
            $retraitIban = utf8_decode($this->settings->value);
            // Retrait Unilend - Titulaire du compte
            $this->settings->get('Retrait Unilend - Titulaire du compte', 'type');
            $retraitTitu = utf8_decode($this->settings->value);

            // On recupere la liste des virements en cours
            $lVirementsEnCours = $this->virements->select('status = 0 AND added_xml = "0000-00-00 00:00:00" ');

            // le nombre de virements
            $nbVirements = $this->virements->counter('status = 0 AND added_xml = "0000-00-00 00:00:00" ');

            // On recupere la liste des virements en cours
            //$lVirementsEnCours = $this->virements->select('status = 1 AND added_xml = "2014-01-15 11:01:00" ');
            // le nombre de virements
            //$nbVirements = $this->virements->counter('status = 1 AND added_xml = "2014-01-15 11:01:00" ');
            // On recupere le total
            $sum = $this->virements->sum('status = 0');

            //$sum = $this->virements->sum('status = 1 AND added_xml = "2014-01-15 11:01:00" ');
            $Totalmontants = round($sum / 100, 2);

            // Compteur pour avoir un id différent a chaque fois
            $nbCompteur = $this->compteur_transferts->counter('type = 1');

            // le id_compteur
            $id_compteur = $nbCompteur + 1;

            // on met a jour le compteur
            $this->compteur_transferts->type  = 1;
            $this->compteur_transferts->ordre = $id_compteur;
            $this->compteur_transferts->create();

            // date collée
            $dateColle = date('Ymd');

            // on recup le id_message
            $id_message = 'SFPMEI/' . $titulaire . '/' . $dateColle . '/' . $id_compteur;

            // date creation avec un T entre la date et l'heure
            $date_creation = date('Y-m-d\TH:i:s');

            // titulaire compte a debiter
            $compte = $titulaire . '-SFPMEI';

            // Date execution
            $date_execution = date('Y-m-d');

            $xml = '<?xml version="1.0" encoding="UTF-8"?>
	<Document xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
		<CstmrCdtTrfInitn>
			<GrpHdr>
				<MsgId>' . $id_message . '</MsgId>
				<CreDtTm>' . $date_creation . '</CreDtTm>
				<NbOfTxs>' . $nbVirements . '</NbOfTxs>
				<CtrlSum>' . $Totalmontants . '</CtrlSum>
				<InitgPty>
					<Nm>' . $compte . '</Nm>
				</InitgPty>
			</GrpHdr>
			<PmtInf>
				<PmtInfId>' . $titulaire . '/' . $dateColle . '/' . $id_compteur . '</PmtInfId>
				<PmtMtd>TRF</PmtMtd>
				<NbOfTxs>' . $nbVirements . '</NbOfTxs>
				<CtrlSum>' . $Totalmontants . '</CtrlSum>
				<PmtTpInf>
					<SvcLvl>
						<Cd>SEPA</Cd>
					</SvcLvl>
				</PmtTpInf>
				<ReqdExctnDt>' . $date_execution . '</ReqdExctnDt>
				<Dbtr>
					<Nm>SFPMEI</Nm>
					<PstlAdr>
						<Ctry>FR</Ctry>
					</PstlAdr>
				</Dbtr>
				<DbtrAcct>
					<Id>
						<IBAN>' . str_replace(' ', '', $iban) . '</IBAN>
					</Id>
				</DbtrAcct>
				<DbtrAgt>
					<FinInstnId>
						<BIC>' . str_replace(' ', '', $bic) . '</BIC>
					</FinInstnId>
				</DbtrAgt>';

            foreach ($lVirementsEnCours as $v) {
                $this->clients->get($v['id_client'], 'id_client');

                // Retrait sfmpei
                if ($v['type'] == 4) {
                    $ibanDestinataire = $retraitIban;
                    $bicDestinataire  = $retraitBic;
                    //$retraitDom;
                } // emprunteur
                elseif ($this->clients->status_pre_emp > 1) {
                    $this->companies->get($v['id_client'], 'id_client_owner');
                    $ibanDestinataire = $this->companies->iban;
                    $bicDestinataire  = $this->companies->bic;
                    $destinataire     = $this->companies->name;
                } // preteur
                else {
                    $this->lenders_accounts->get($v['id_client'], 'id_client_owner');
                    $ibanDestinataire = $this->lenders_accounts->iban;
                    $bicDestinataire  = $this->lenders_accounts->bic;

                    // morale
                    if (in_array($this->clients->type, array(2, 4))) {
                        $this->companies->get($v['id_client'], 'id_client_owner');
                        $destinataire = $this->companies->name;
                    } // physique
                    else {
                        $destinataire = $this->clients->nom . ' ' . $this->clients->prenom;
                    }
                }

                $this->virements->get($v['id_virement'], 'id_virement');
                $this->virements->status    = 1; // envoyé
                $this->virements->added_xml = date('Y-m-d H:i') . ':00';
                $this->virements->update();

                // variables
                $id_lot  = $titulaire . '/' . $dateColle . '/' . $v['id_virement'];
                $montant = round($v['montant'] / 100, 2);

                $xml .= '
				<CdtTrfTxInf>
					<PmtId>
						<EndToEndId>' . $id_lot . '</EndToEndId>
					</PmtId>
					<Amt>
						<InstdAmt Ccy="EUR">' . $montant . '</InstdAmt>
					</Amt>
					<CdtrAgt>
						<FinInstnId>
							<BIC>' . str_replace(' ', '', $bicDestinataire) . '</BIC>
						</FinInstnId>
					 </CdtrAgt>
					 <Cdtr>
						 <Nm>' . ($v['type'] == 4 ? $retraitTitu : $destinataire) . '</Nm>
						 <PstlAdr>
							 <Ctry>FR</Ctry>
						 </PstlAdr>
					 </Cdtr>
					 <CdtrAcct>
						 <Id>
							 <IBAN>' . str_replace(' ', '', $ibanDestinataire) . '</IBAN>
						 </Id>
					 </CdtrAcct>
					 <RmtInf>
						 <Ustrd>' . str_replace(' ', '', $v['motif']) . '</Ustrd>
					 </RmtInf>
				</CdtTrfTxInf>';
            }
            $xml .= '
			</PmtInf>
		</CstmrCdtTrfInitn>
	</Document>';

            echo $xml;

            $filename = 'Unilend_Virements_' . date('Ymd');

            if ($lVirementsEnCours != false) {

                if ($this->Config['env'] == 'prod') {
                    $connection = ssh2_connect('ssh.reagi.com', 22);
                    ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
                    $sftp       = ssh2_sftp($connection);
                    $sftpStream = @fopen('ssh2.sftp://' . $sftp . '/home/sfpmei/emissions/virements/' . $filename . '.xml', 'w');
                    fwrite($sftpStream, $xml);
                    fclose($sftpStream);
                }

                file_put_contents($this->path . 'protected/sftp/virements/' . $filename . '.xml', $xml);
            }

            $this->stopCron();
        }
    }

    // On check les prelevements a envoyer sur le sftp (une fois par jour)
    public function _prelevements()
    {
        if (true === $this->startCron('prelevements', 5)) {

            $this->prelevements            = $this->loadData('prelevements');
            $this->clients                 = $this->loadData('clients');
            $this->lenders_accounts        = $this->loadData('lenders_accounts');
            $this->compteur_transferts     = $this->loadData('compteur_transferts');
            $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
            $echeanciers                   = $this->loadData('echeanciers');
            $clients_mandats               = $this->loadData('clients_mandats');

            // Virement - BIC
            $this->settings->get('Virement - BIC', 'type');
            $bic = $this->settings->value;

            // Virement - domiciliation
            $this->settings->get('Virement - domiciliation', 'type');
            $domiciliation = $this->settings->value;

            // Virement - IBAN
            $this->settings->get('Virement - IBAN', 'type');
            $iban = $this->settings->value;
            $iban = str_replace(' ', '', $iban);

            // Virement - titulaire du compte
            $this->settings->get('titulaire du compte', 'type');
            $titulaire = utf8_decode($this->settings->value);

            // Nombre jours avant remboursement pour envoyer une demande de prelevement
            $this->settings->get('Nombre jours avant remboursement pour envoyer une demande de prelevement', 'type');
            $nbJoursAvant = $this->settings->value;

            // ICS
            $this->settings->get('ICS de SFPMEI', 'type');
            $ics = $this->settings->value;

            $today = date('Y-m-d');
            //// test ////
            //$today = '2015-06-06';
            //////////////
            ///////////////////////
            /// preteur ponctuel //
            ///////////////////////
            // On recupere la liste des prelevements en cours preteur ponctuel
            $lPrelevementsEnCoursPeteurPonctuel = $this->prelevements->select('status = 0 AND type = 1 AND type_prelevement = 2');
            //$lPrelevementsEnCoursPeteurPonctuel = $this->prelevements->select();
            // le nombre de prelevements preteur ponctuel
            $nbPrelevementsPeteurPonctuel = $this->prelevements->counter('status = 0 AND type = 1 AND type_prelevement = 2');
            // On recupere le total preteur ponctuel
            $sum                          = $this->prelevements->sum('status = 0 AND type = 1 AND type_prelevement = 2');
            $TotalmontantsPreteurPonctuel = round($sum / 100, 2);
            ////////////////////////
            ////////////////////////
            /// preteur recurrent //
            ////////////////////////
            // On recupere la liste des prelevements en cours preteur recurrent
            $lPrelevementsEnCoursPeteurRecu = $this->prelevements->select('type = 1 AND type_prelevement = 1 AND status <> 3');
            // le nombre de prelevements preteur recurrent
            $nbPrelevementsPeteurRecu = $this->prelevements->counter('type = 1 AND type_prelevement = 1 AND status <> 3');
            // On recupere le total preteur recurrent
            //$sum = $this->prelevements->sum('type = 1 AND type_prelevement = 1');
            //$TotalmontantsPreteurRecu = round($sum/100,2);

            $nbPermanent = 0;
            foreach ($lPrelevementsEnCoursPeteurRecu as $p) {
                //si jamais eu de prelevement avant
                if ($p['status'] == 0) {
                    $val = 'FRST'; // prelevement ponctuel
                } else {
                    $val = 'RCUR';

                    // date du xml généré au premier prelevement
                    $date_xml = strtotime($p['added_xml']);

                    // date xml + 1 mois
                    $dateXmlPlusUnMois = mktime(date("H", $date_xml), date("i", $date_xml), 0, date("m", $date_xml) + 1, date("d", $date_xml), date("Y", $date_xml));

                    $dateXmlPlusUnMois = date('Y-m-d', $dateXmlPlusUnMois);

                    ////////// test ////////////
                    //$dateXmlPlusUnMois = date('Y-m-d');
                    ///////////////////////////
                }

                // si status est a 0 (en cours) ou si le satut est supperieur et que la date du jour est égale a la date xml + 1 mois
                // 2 cas possible = 1 : premier prelevement | 2 : prelevement recurrent
                if ($p['status'] == 0 || $p['status'] > 0 && $dateXmlPlusUnMois == $today) {
                    $nbPermanent += 1;
                    $montantPermanent += $p['montant'];
                }
            }

            $nbPrelevementsPeteurRecu = $nbPermanent;
            $TotalmontantsPreteurRecu = $montantPermanent / 100;

            ////////////////////////
            ///////////////////////////
            /// emprunteur recurrent // <-------------|
            ///////////////////////////
            // On recupere la liste des prelevements en cours preteur recurrent
            $lPrelevementsEnCoursEmprunteur = $this->prelevements->select('type = 2 AND type_prelevement = 1 AND status = 0 AND date_execution_demande_prelevement = "' . $today . '"');
            // le nombre de prelevements preteur recurrent
            $nbPrelevementsEmprunteur = $this->prelevements->counter('type = 2 AND type_prelevement = 1 AND status = 0 AND date_execution_demande_prelevement = "' . $today . '"');
            // On recupere le total preteur recurrent
            $sum                     = $this->prelevements->sum('type = 2 AND type_prelevement = 1 AND status = 0 AND date_execution_demande_prelevement = "' . $today . '"');
            $TotalmontantsEmprunteur = round($sum / 100, 2);

            ///////////////////////////
            // Compteur pour avoir un id différent a chaque fois
            $nbCompteur = $this->compteur_transferts->counter('type = 2');

            // le id_compteur
            $id_compteur = $nbCompteur + 1;

            // on met a jour le compteur
            $this->compteur_transferts->type  = 2; // 2 : prelevement
            $this->compteur_transferts->ordre = $id_compteur;
            $this->compteur_transferts->create();

            // date collée
            $dateColle = date('Ymd');

            // on recup le id_message
            $id_message = 'SFPMEI/' . $titulaire . '/' . $dateColle . '/' . $id_compteur;

            // date creation avec un T entre la date et l'heure
            $date_creation = mktime(date("H"), date("i"), 0, date("m"), date("d") + 1, date("Y"));

            $date_creation = date('Y-m-d\TH:i:s', $date_creation);

            // titulaire compte a debiter
            $compte = $titulaire . '-SFPMEI';

            // Nombre de prelevements
            $nbPrelevements = $nbPrelevementsPeteurPonctuel + $nbPrelevementsPeteurRecu + $nbPrelevementsEmprunteur;
            // Montant total
            $Totalmontants = $TotalmontantsPreteurPonctuel + $TotalmontantsPreteurRecu + $TotalmontantsEmprunteur;

            $xml = '<?xml version="1.0" encoding="UTF-8"?>
	<Document xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
		<CstmrDrctDbtInitn>
			<GrpHdr>
				<MsgId>' . $id_message . '</MsgId>
				<CreDtTm>' . $date_creation . '</CreDtTm>
				<NbOfTxs>' . $nbPrelevements . '</NbOfTxs>
				<CtrlSum>' . $Totalmontants . '</CtrlSum>
				<InitgPty>
					<Nm>' . $compte . '</Nm>
				</InitgPty>
			</GrpHdr>';

            //////////////////////////////////////////
            /// lPrelevementsEnCoursPeteurPonctuel ///
            foreach ($lPrelevementsEnCoursPeteurPonctuel as $p) {

                $this->clients->get($p['id_client'], 'id_client');
                $this->lenders_accounts->get($p['id_client'], 'id_client_owner');

                // on met a jour le prelevement
                $this->prelevements->get($p['id_prelevement'], 'id_prelevement');
                $this->prelevements->status    = 1; // envoyé
                $this->prelevements->added_xml = date('Y-m-d H:i') . ':00';
                $this->prelevements->update();

                // variables
                $id_lot  = $titulaire . '/' . $dateColle . '/' . $p['id_prelevement'];
                $montant = round($p['montant'] / 100, 2);

                // Date execution
                // nb jour avant pour de prelevement
                $datePlusNbjour = mktime(date("H"), date("i"), 0, date("m"), date("d") - $nbJoursAvant, date("Y"));

                // si preteur
                if ($p['type'] == 1) {
                    // date d'execution du prelevement
                    $date_execution = mktime(date("H"), date("i"), 0, date("m"), $p['jour_prelevement'], date("Y"));

                    // si la date demandé est inferieur au nombre de jour min on rajoute 1 mois
                    if ($datePlusNbjour < $date_execution) {
                        $date_execution = mktime(date("H"), date("i"), 0, date("m") + 1, $p['jour_prelevement'], date("Y"));
                    }
                }

                $clients_mandats->get($p['id_client'], 'id_project = 0 AND id_client');

                $refmandat   = $p['motif'];
                $date_mandat = date('Y-m-d', strtotime($clients_mandats->updated));

                //si jamais eu de prelevement avant
                $val = 'FRST'; // prelevement ponctuel

                $table['id_lot']         = $id_lot;
                $table['montant']        = $montant;
                $table['val']            = $val;
                $table['date_execution'] = date('Y-m-d', $date_execution);
                $table['iban']           = $iban;
                $table['bic']            = $bic;
                $table['ics']            = $ics;
                $table['refmandat']      = $refmandat;
                $table['date_mandat']    = $date_mandat;
                $table['bicPreteur']     = $p['bic']; // bic
                $table['ibanPreteur']    = $p['iban'];
                $table['nomPreteur']     = $this->clients->nom;
                $table['prenomPreteur']  = $this->clients->prenom;
                $table['motif']          = $p['motif'];
                $table['id_prelevement'] = $p['id_prelevement'];

                $xml .= $this->xmPrelevement($table);
            }

            ///////////////////////////////////////
            /// $lPrelevementsEnCoursPeteurRecu ///
            foreach ($lPrelevementsEnCoursPeteurRecu as $p) {

                $this->clients->get($p['id_client'], 'id_client');
                $this->lenders_accounts->get($p['id_client'], 'id_client_owner');

                // variables
                $id_lot  = $titulaire . '/' . $dateColle . '/' . $p['id_prelevement'];
                $montant = round($p['montant'] / 100, 2);

                // Date execution
                // nb jour avant pour de prelevement
                $datePlusNbjour = mktime(date("H"), date("i"), 0, date("m"), date("d") - $nbJoursAvant, date("Y"));

                // si preteur
                if ($p['type'] == 1) {
                    // date d'execution du prelevement
                    $date_execution = mktime(date("H"), date("i"), 0, date("m"), $p['jour_prelevement'], date("Y"));

                    // si la date demandé est inferieur au nombre de jour min on rajoute 1 mois
                    if ($datePlusNbjour < $date_execution) {

                        $date_execution = mktime(date("H"), date("i"), 0, date("m") + 1, $p['jour_prelevement'], date("Y"));
                    }
                }

                //echo date('d/m/Y',$date_execution).'<bR>';
                // On recup le mandat
                $clients_mandats->get($p['id_client'], 'id_project = 0 AND id_client');

                $refmandat   = $p['motif'];
                $date_mandat = date('Y-m-d', strtotime($clients_mandats->updated));

                //si jamais eu de prelevement avant
                if ($p['status'] == 0) {
                    $val = 'FRST'; // prelevement ponctuel
                } else {
                    $val = 'RCUR';

                    // date du xml généré au premier prelevement
                    $date_xml = strtotime($p['added_xml']);

                    // date xml + 1 mois
                    $dateXmlPlusUnMois = mktime(date("H", $date_xml), date("i", $date_xml), 0, date("m", $date_xml) + 1, date("d", $date_xml), date("Y", $date_xml));

                    $dateXmlPlusUnMois = date('Y-m-d', $dateXmlPlusUnMois);

                    ////////// test ////////////
                    //$dateXmlPlusUnMois = date('Y-m-d');
                    ///////////////////////////
                }

                // si status est a 0 (en cours) ou si le satut est supperieur et que la date du jour est égale a la date xml + 1 mois
                // 2 cas possible = 1 : premier prelevement | 2 : prelevement recurrent
                if ($p['status'] == 0 || $p['status'] > 0 && $dateXmlPlusUnMois == $today) {
                    $table['id_lot']         = $id_lot;
                    $table['montant']        = $montant;
                    $table['val']            = $val;
                    $table['date_execution'] = date('Y-m-d', $date_execution);
                    $table['iban']           = $iban;
                    $table['bic']            = $bic;
                    $table['ics']            = $ics;
                    $table['refmandat']      = $refmandat;
                    $table['date_mandat']    = $date_mandat;
                    $table['bicPreteur']     = $p['bic']; // bic
                    $table['ibanPreteur']    = $p['iban'];
                    $table['nomPreteur']     = $this->clients->nom;
                    $table['prenomPreteur']  = $this->clients->prenom;
                    $table['motif']          = $p['motif'];
                    $table['id_prelevement'] = $p['id_prelevement'];

                    $xml .= $this->xmPrelevement($table);

                    // on met a jour le prelevement
                    $this->prelevements->get($p['id_prelevement'], 'id_prelevement');
                    $this->prelevements->status    = 1; // envoyé
                    $this->prelevements->added_xml = date('Y-m-d H:i') . ':00';
                    $this->prelevements->update();
                }
            }

            ///////////////////////////////////////
            /// $lPrelevementsEnCoursEmprunteur ///

            $old_iban = '';
            $old_bic  = '';
            foreach ($lPrelevementsEnCoursEmprunteur as $p) {
                // on recup le dernier prelevement effectué pour voir si c'est le meme iban ou bic
                $first = false;
                if ($p['num_prelevement'] > 1) {
                    $lastRembEmpr = $this->prelevements->select('type = 2 AND type_prelevement = 1 AND status = 1 AND id_project = ' . $p['id_project'], 'num_prelevement DESC', 0, 1);
                    $last_iban    = $lastRembEmpr[0]['iban'];
                    $last_bic     = $lastRembEmpr[0]['bic'];

                    if ($last_iban != $p['iban'] || $last_bic != $p['bic']) {
                        $first = true;
                    }
                }

                // variables
                $id_lot  = $titulaire . '/' . $dateColle . '/' . $p['id_prelevement'];
                $montant = round($p['montant'] / 100, 2);

                // On recup le mandat
                $clients_mandats->get($p['id_project'], 'id_project');

                $refmandat   = $p['motif'];
                $date_mandat = date('Y-m-d', strtotime($clients_mandats->updated));

                // si premier remb
                if ($p['num_prelevement'] == 1 || $first == true) //if($p['num_prelevement'] == 1)
                {
                    $val = 'FRST';
                } else {
                    $val = 'RCUR';
                }
                $old_iban = $p['iban'];
                $old_bic  = $p['bic'];

                ///////////////////////////////////////////////////////////
                // Temporaire pour régulariser le future prelevement du projet 374 qui passera le 2014-08-13
                //if($p['id_project'] == '374' && date('n') < 9){
                //$val = 'FRST';
                //}
                ///////////////////////////////////////////////////////////

                $this->clients->get($p['id_client'], 'id_client');

                $table['id_lot']         = $id_lot;
                $table['montant']        = $montant;
                $table['val']            = $val;
                $table['date_execution'] = $p['date_echeance_emprunteur'];
                $table['iban']           = $iban;
                $table['bic']            = $bic;
                $table['ics']            = $ics;
                $table['refmandat']      = $refmandat;
                $table['date_mandat']    = $date_mandat;
                $table['bicPreteur']     = $p['bic']; // bic
                $table['ibanPreteur']    = $p['iban'];
                $table['nomPreteur']     = $this->clients->nom;
                $table['prenomPreteur']  = $this->clients->prenom;
                $table['motif']          = $refmandat;
                $table['id_prelevement'] = $p['id_prelevement'];

                $xml .= $this->xmPrelevement($table);

                // on met a jour le prelevement
                $this->prelevements->get($p['id_prelevement'], 'id_prelevement');
                $this->prelevements->status    = 1; // envoyé
                $this->prelevements->added_xml = date('Y-m-d H:i') . ':00';
                $this->prelevements->update();
            }

            $xml .= '
		</CstmrDrctDbtInitn>
	</Document>';
            echo $xml;
            $filename = 'Unilend_Prelevements_' . date('Ymd');

            if ($nbPrelevements > 0) {
                if ($this->Config['env'] == 'prod') {
                    $connection = ssh2_connect('ssh.reagi.com', 22);
                    ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
                    $sftp       = ssh2_sftp($connection);
                    $sftpStream = @fopen('ssh2.sftp://' . $sftp . '/home/sfpmei/emissions/prelevements/' . $filename . '.xml', 'w');
                    fwrite($sftpStream, $xml);
                    fclose($sftpStream);
                }

                file_put_contents($this->path . 'protected/sftp/prelevements/' . $filename . '.xml', $xml);
            }

            $this->stopCron();
        }
    }

    // xml prelevement
    public function xmPrelevement($table)
    {
        $id_lot         = $table['id_lot'];
        $montant        = $table['montant'];
        $val            = $table['val'];
        $date_execution = date('Y-m-d', strtotime($table['date_execution']));;
        $iban          = $table['iban'];
        $bic           = $table['bic'];
        $ics           = $table['ics'];
        $refmandat     = $table['refmandat'];
        $date_mandat   = $table['date_mandat'];
        $bicPreteur    = $table['bicPreteur'];
        $ibanPreteur   = $table['ibanPreteur'];
        $nomPreteur    = $table['nomPreteur'];
        $prenomPreteur = $table['prenomPreteur'];
        $motif         = $table['motif'];

        $xml = '
			<PmtInf>
				<PmtInfId>' . $id_lot . '</PmtInfId>
				<PmtMtd>DD</PmtMtd>
				<NbOfTxs>1</NbOfTxs>
				<CtrlSum>' . $montant . '</CtrlSum>
				<PmtTpInf>
					<SvcLvl>
						<Cd>SEPA</Cd>
					</SvcLvl>
					<LclInstrm>
						<Cd>CORE</Cd>
					</LclInstrm>
					<SeqTp>' . $val . '</SeqTp>
				</PmtTpInf>
				<ReqdColltnDt>' . $date_execution . '</ReqdColltnDt>
				<Cdtr>
					<Nm>SFPMEI</Nm>
					<PstlAdr>
						<Ctry>FR</Ctry>
					</PstlAdr>
				</Cdtr>
				<CdtrAcct>
					<Id>
						<IBAN>' . $iban . '</IBAN>
					</Id>
					<Ccy>EUR</Ccy>
				</CdtrAcct>
				<CdtrAgt>
					<FinInstnId>
						<BIC>' . $bic . '</BIC>
					</FinInstnId>
				</CdtrAgt>
				<ChrgBr>SLEV</ChrgBr>
				<CdtrSchmeId>
					<Id>
						<PrvtId>
							<Othr>
								<Id>' . $ics . '</Id>
								<SchmeNm>
									<Prtry>SEPA</Prtry>
							   </SchmeNm>
						   </Othr>
					   </PrvtId>
					</Id>
				</CdtrSchmeId>
				<DrctDbtTxInf>
					<PmtId>
						<EndToEndId>' . $id_lot . '</EndToEndId>
					</PmtId>
					<InstdAmt Ccy="EUR">' . $montant . '</InstdAmt>
					<DrctDbtTx>
						<MndtRltdInf>
							<MndtId>' . $refmandat . '</MndtId>
							<DtOfSgntr>' . $date_mandat . '</DtOfSgntr>
							<AmdmntInd>false</AmdmntInd>
						</MndtRltdInf>
					</DrctDbtTx>
					<DbtrAgt>
						<FinInstnId>
							<BIC>' . $bicPreteur . '</BIC>
						</FinInstnId>
					 </DbtrAgt>
					 <Dbtr>
						 <Nm>' . $nomPreteur . ' ' . $prenomPreteur . '</Nm>
						 <PstlAdr>
							 <Ctry>FR</Ctry>
						 </PstlAdr>
					 </Dbtr>
					 <DbtrAcct>
						 <Id>
							 <IBAN>' . $ibanPreteur . '</IBAN>
						 </Id>
					 </DbtrAcct>
					 <RmtInf>
						<Ustrd>' . $motif . '</Ustrd>
					 </RmtInf>
				</DrctDbtTxInf>
			</PmtInf>';

        return $xml;
    }

    // cron toutes les heures
    // lors des virements si on a toujours pas recu on relance le client
    public function _relance_payment_preteur()
    {
        // relance retiré apres demande
        die;
        // chargement des datas
        $this->clients          = $this->loadData('clients');
        $this->lenders_accounts = $this->loadData('lenders_accounts');

        $lLenderNok = $this->lenders_accounts->select('status = 0');

        // date du jour
        $time = date('Y-m-d H');

        // test //
        //$time = '2013-10-11 00';
        //////////

        foreach ($lLenderNok as $l) {
            $this->clients->get($l['id_client_owner'], 'id_client');

            // ladate
            $ladate = strtotime($l['added']);

            // ladate +12h
            $ladatePlus12H = mktime(date("H", $ladate) + 12, date("i", $ladate), 0, date("m", $ladate), date("d", $ladate), date("Y", $ladate));

            // ladate +24h
            $ladatePlus24H = mktime(date("H", $ladate), date("i", $ladate), 0, date("m", $ladate), date("d", $ladate) + 1, date("Y", $ladate));

            // ladate +3j
            $ladatePlus3J = mktime(date("H", $ladate), date("i", $ladate), 0, date("m", $ladate), date("d", $ladate) + 3, date("Y", $ladate));

            // ladate +7j
            $ladatePlus7J = mktime(date("H", $ladate), date("i", $ladate), 0, date("m", $ladate), date("d", $ladate) + 7, date("Y", $ladate));

            $ladatePlus12H = date('Y-m-d H', $ladatePlus12H);
            $ladatePlus24H = date('Y-m-d H', $ladatePlus24H);
            $ladatePlus3J  = date('Y-m-d H', $ladatePlus3J);
            $ladatePlus7J  = date('Y-m-d H', $ladatePlus7J);

            echo 'Preteur : ' . $this->clients->id_client . ' - Nom : ' . $this->clients->prenom . ' ' . $this->clients->nom . '<br>';
            echo $l['added'] . '<br>';
            echo '+12h : ' . $ladatePlus12H . '<br>';
            echo '+24h : ' . $ladatePlus24H . '<br>';
            echo '+3j : ' . $ladatePlus3J . '<br>';
            echo '+7j : ' . $ladatePlus7J . '<br>';
            echo '---------------<br>';

            if ($ladatePlus12H == $time || $ladatePlus24H == $time || $ladatePlus3J == $time || $ladatePlus7J == $time) {
                // Motif virement
                $p         = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))), 0, 1);
                $nom       = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
                $id_client = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
                $motif     = mb_strtoupper($id_client . $p . $nom, 'UTF-8');

                //*********************************************************//
                //*** ENVOI DU MAIL RELANCE PAYMENT INSCRIPTION PRETEUR ***//
                //*********************************************************//
                // Recuperation du modele de mail
                $this->mails_text->get('preteur-relance-paiement-inscription', 'lang = "' . $this->language . '" AND type');

                $surl  = $this->surl;
                $url   = $this->lurl;
                $email = $this->clients->email;

                // FB
                $this->settings->get('Facebook', 'type');
                $lien_fb = $this->settings->value;

                // Twitter
                $this->settings->get('Twitter', 'type');
                $lien_tw = $this->settings->value;

                //'compte-p' => $this->lurl.'/alimentation',
                //'compte-p-virement' => $this->lurl.'/alimentation',
                $varMail = array(
                    'surl'              => $surl,
                    'url'               => $url,
                    'prenom_p'          => $this->clients->prenom,
                    'date_p'            => date('d/m/Y', strtotime($this->clients->added)),
                    'compte-p'          => $this->lurl . '/inscription_preteur/etape3/' . $this->clients->hash . '/2',
                    'compte-p-virement' => $this->lurl . '/inscription_preteur/etape3/' . $this->clients->hash,
                    'motif_virement'    => $motif,
                    'lien_fb'           => $lien_fb,
                    'lien_tw'           => $lien_tw
                );

                $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                $this->email = $this->loadLib('email');
                $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                $this->email->setSubject(stripslashes($sujetMail));
                $this->email->setHTMLBody(stripslashes($texteMail));

                // Pas de mail si le compte est desactivé
                if ($this->clients->status == 1) {
                    if ($this->Config['env'] == 'prod') {
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                        // Injection du mail NMP dans la queue
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($this->clients->email));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }
                }
            }
        }
    }

    // (cron passe toujours dessus chez oxeva  0 * * * * )
    public function _check_prelevement_remb()
    {
        // plus utilisé
        die;

        // Chargement des librairies
        $jo = $this->loadLib('jours_ouvres');

        // chargement des datas
        $this->projects     = $this->loadData('projects');
        $this->echeanciers  = $this->loadData('echeanciers');
        $this->prelevements = $this->loadData('prelevements');
        $this->companies    = $this->loadData('companies');
        $this->transactions = $this->loadData('transactions');

        // today
        $today = date('Y-m-d');
        // test //
        //$today = '2013-11-15';
        //////////
        // les projets en statut remboursement
        $this->lProjects = $this->projects->selectProjectsByStatus(80);

        foreach ($this->lProjects as $k => $p) {
            // on recup la companie
            $this->companies->get($p['id_company'], 'id_company');

            // les echeances non remboursé du projet
            $lEcheances = $this->echeanciers->getSumRembEmpruntByMonths($p['id_project'], '', '0');
            /* echo '<pre>';
              print_r($lEcheances);
              echo '</pre>'; */

            foreach ($lEcheances as $e) {
                $date = strtotime($e['date_echeance_emprunteur'] . ':00');
                // retourne la date - 5 jours ouvrés
                $result = $jo->getDateOuvre($date, 5, 1);
                echo 'echeance : ' . $e['ordre'] . ' -> ' . date('Y-m-d', strtotime($result)) . '<br>';

                // premier remb
                if ($e['ordre'] == 1) {
                    //retourne la date - 5 jours ouvrés
                    $result = $jo->getDateOuvre(strtotime($e['date_echeance_emprunteur'] . ':00'), 5, 1);
                } else {
                    //retourne la date - 2 jours ouvrés
                    $result = $jo->getDateOuvre(strtotime($e['date_echeance_emprunteur'] . ':00'), 2, 1);
                }

                $result = date('Y-m-d', strtotime($result));

                if ($result == $today) {
                    $lemontant = ($e['montant'] + $e['commission'] + $e['tva']);
                    // On enregistre la transaction
                    $this->transactions->id_client        = $this->lenders_accounts->id_client_owner;
                    $this->transactions->montant          = $lemontant * 100;
                    $this->transactions->id_langue        = 'fr';
                    $this->transactions->date_transaction = date('Y-m-d H:i:s');
                    $this->transactions->status           = '0'; // statut payement no ok
                    $this->transactions->etat             = '0'; // etat en attente
                    $this->transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                    $this->transactions->type_transaction = 6; // remb emprunteur
                    $this->transactions->transaction      = 1; // transaction virtuelle
                    $this->transactions->id_transaction   = $this->transactions->create();

                    $this->prelevements->id_client      = $this->companies->id_client_owner;
                    $this->prelevements->id_transaction = $this->transactions->id_transaction;
                    $this->prelevements->id_project     = $p['id_project'];
                    $this->prelevements->motif          = 'Remboursement projet ' . $p['id_project'];
                    $this->prelevements->montant        = $lemontant * 100;
                    $this->prelevements->bic            = $this->companies->bic;
                    $this->prelevements->iban           = $this->companies->iban;
                    if ($e['ordre'] == 1) {
                        $this->prelevements->type_prelevement = 2;
                    } // ponctuel
                    else {
                        $this->prelevements->type_prelevement = 1;
                    } // recurrent
                    $this->prelevements->type   = 2; // emprunteur
                    $this->prelevements->status = 0; // en cours
                    $this->prelevements->create();
                }
            }
        }
    }

    // transforme le fichier txt format truc en tableau
    public function recus2array($file)
    {
        $tablemontant = array(
            '{' => 0,
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            'E' => 5,
            'F' => 6,
            'G' => 7,
            'H' => 8,
            'I' => 9,
            '}' => 0,
            'J' => 1,
            'K' => 2,
            'L' => 3,
            'M' => 4,
            'N' => 5,
            'O' => 6,
            'P' => 7,
            'Q' => 8,
            'R' => 9
        );

        $url = $file;

        $array  = array();
        $handle = @fopen($url, "r"); //lecture du fichier
        if ($handle) {

            $i = 0;
            while (($ligne = fgets($handle)) !== false) {
                if (strpos($ligne, 'CANTONNEMENT') == true || strpos($ligne, 'DECANTON') == true || strpos($ligne, 'REGULARISATION DIGITAL') == true || strpos($ligne, '00374 REGULARISATION DIGITAL') == true || strpos($ligne, 'REGULARISATION') == true || strpos($ligne, 'régularisation') == true || strpos($ligne, '00374 régularisation') == true || strpos($ligne, 'REGULARISAT') == true) {
                    $codeEnregi = substr($ligne, 0, 2);
                    if ($codeEnregi == 04) {
                        $i++;
                    }
                    //echo $i.' '.$ligne.'<br>';
                    $tabRestriction[$i] = $i;
                } else {

                    $codeEnregi = substr($ligne, 0, 2);

                    if ($codeEnregi == 04) {
                        $i++;
                        $laligne = 1;

                        // On check si on a la restriction "BIENVENUE"
                        if (strpos($ligne, 'BIENVENUE') == true) {
                            //echo $i.' '.$ligne.'<br>';
                            $array[$i]['unilend_bienvenue'] = true;
                        }

                        $array[$i]['codeEnregi']          = substr($ligne, 0, 2);
                        $array[$i]['codeBanque']          = substr($ligne, 2, 5);
                        $array[$i]['codeOpBNPP']          = substr($ligne, 7, 4);
                        $array[$i]['codeGuichet']         = substr($ligne, 11, 5);
                        $array[$i]['codeDevises']         = substr($ligne, 16, 3);
                        $array[$i]['nbDecimales']         = substr($ligne, 19, 1);
                        $array[$i]['zoneReserv1']         = substr($ligne, 20, 1);
                        $array[$i]['numCompte']           = substr($ligne, 21, 11);
                        $array[$i]['codeOpInterbancaire'] = substr($ligne, 32, 2);
                        $array[$i]['dateEcriture']        = substr($ligne, 34, 6);
                        $array[$i]['codeMotifRejet']      = substr($ligne, 40, 2);
                        $array[$i]['dateValeur']          = substr($ligne, 42, 6);
                        //$array[$i]['libelleOpe1'] = substr($ligne,48,31);
                        $array[$i]['zoneReserv2']     = substr($ligne, 79, 2);
                        $array[$i]['numEcriture']     = substr($ligne, 81, 7);
                        $array[$i]['codeExoneration'] = substr($ligne, 88, 1);
                        $array[$i]['zoneReserv3']     = substr($ligne, 89, 1);

                        $array[$i]['refOp']  = substr($ligne, 104, 16);
                        $array[$i]['ligne1'] = $ligne;

                        // On affiche la ligne seulement si c'est un virement
                        if (!in_array(substr($ligne, 32, 2), array(23, 25, 'A1', 'B1'))) {
                            $array[$i]['libelleOpe1'] = substr($ligne, 48, 31);
                        }

                        // on recup le champ montant
                        $montant = substr($ligne, 90, 14);
                        // on retire les zeros du debut et le dernier caractere
                        $Debutmontant = ltrim(substr($montant, 0, 13), '0');
                        // On recup le dernier caractere
                        $dernier              = substr($montant, -1, 1);
                        $array[$i]['montant'] = $Debutmontant . $tablemontant[$dernier];
                    }

                    if ($codeEnregi == 05) {

                        // On check si on a la restriction "BIENVENUE"
                        if (strpos($ligne, 'BIENVENUE') == true) {
                            //echo $i.' '.$ligne.'<br>';
                            $array[$i]['unilend_bienvenue'] = true;
                        }

                        // si prelevement
                        if (in_array(substr($ligne, 32, 2), array(23, 25, 'A1', 'B1'))) {
                            // On veut recuperer ques ces 2 lignes
                            if (in_array(trim(substr($ligne, 45, 3)), array('LCC', 'LC2'))) {

                                $laligne += 1;
                                //$array[$i]['ligne'.$laligne] = $ligne;
                                $array[$i]['libelleOpe' . $laligne] = trim(substr($ligne, 45));
                            }
                        } // virement
                        else {
                            $laligne += 1;
                            //$array[$i]['ligne'.$laligne] = $ligne;
                            $array[$i]['libelleOpe' . $laligne] = trim(substr($ligne, 45));
                        }
                    }
                }
            }
            if (!feof($handle)) {
                $this->stopCron();
                return "Erreur: fgets() a échoué\n";
            }
            fclose($handle);

            // on retire les indésirables
            if ($tabRestriction != false) {
                foreach ($tabRestriction as $r) {
                    unset($array[$r]);
                }
            }
            $this->stopCron();
            return $array;
        }
    }

    // reception virements/prelevements (toutes les 30 min)
    public function _reception()
    {
        if (true === $this->startCron('reception', 5)) {

            $receptions = $this->loadData('receptions');

            $clients      = $this->loadData('clients');
            $lenders      = $this->loadData('lenders_accounts');
            $transactions = $this->loadData('transactions');
            $wallets      = $this->loadData('wallets_lines');
            $bank         = $this->loadData('bank_lines');

            $projects               = $this->loadData('projects');
            $companies              = $this->loadData('companies');
            $prelevements           = $this->loadData('prelevements');
            $echeanciers            = $this->loadData('echeanciers');
            $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
            $bank_unilend           = $this->loadData('bank_unilend');

            $projects_remb = $this->loadData('projects_remb');

            $this->notifications = $this->loadData('notifications');

            $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications'); // add gestion alertes
            $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif'); // add gestion alertes
            // Statuts virements
            $statusVirementRecu  = array(05, 18, 45, 13);
            $statusVirementEmis  = array(06, 21);
            $statusVirementRejet = array(12);

            //Statuts prelevements
            $statusPrelevementEmi    = array(23, 25, 'A1', 'B1');
            $statusPrelevementRejete = array(10, 27, 'A3', 'B3');

            if ($this->Config['env'] == 'prod') {
                // connexion
                $connection = ssh2_connect('ssh.reagi.com', 22);
                ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
                $sftp = ssh2_sftp($connection);

                // on verifie si le dossier existe. Si c'est pas le cas on suppose que la connextion fonctionne pas
                $dossier = 'ssh2.sftp://' . $sftp . '/home/sfpmei/receptions';
                if (!file_exists($dossier)) {
                    if ($this->Config['env'] != "dev") {
                        $this->oLogger->addRecord(ULogger::ERROR,
                            'Cron : ' . __METHOD__ . ' Unilend error connexion ssh',
                            array($this->Config['env']));
                        mail($this->sDestinatairesDebug, '[Alert] Unilend error connexion ssh ' . $this->Config['env'], '[Alert] Unilend error connexion ssh ' . $this->Config['env'] . ' cron reception', $this->sHeadersDebug);
                    }
                    $this->stopCron();
                    die;
                }
            }

            // Lien
            $lien = 'ssh2.sftp://' . $sftp . '/home/sfpmei/receptions/UNILEND-00040631007-' . date('Ymd') . '.txt';

            // enregistrement chez nous
            $file = @file_get_contents($lien);
            if ($file === false) {
                //die; // pour le test
                //echo 'pas de fichier';

                $ladate = time();

                // le cron passe a 15 et 45, nous on va check a 15
                $NotifHeure = mktime(10, 0, 0, date('m'), date('d'), date('Y'));

                $NotifHeurefin = mktime(10, 20, 0, date('m'), date('d'), date('Y'));

                // Si a 10h on a pas encore de fichier bah on lance un mail notif
                if ($ladate >= $NotifHeure && $ladate <= $NotifHeurefin) {

                    //************************************//
                    //*** ENVOI DU MAIL ETAT QUOTIDIEN ***//
                    //************************************//
                    // destinataire
                    $this->settings->get('Adresse notification aucun virement', 'type');
                    $destinataire = $this->settings->value;
                    //$destinataire = 'd.courtier@equinoa.com';
                    // Recuperation du modele de mail
                    $this->mails_text->get('notification-aucun-virement', 'lang = "' . $this->language . '" AND type');

                    $surl = $this->surl;
                    $url  = $this->lurl;

                    $sujetMail = $this->mails_text->subject;
                    eval("\$sujetMail = \"$sujetMail\";");

                    $texteMail = $this->mails_text->content;
                    eval("\$texteMail = \"$texteMail\";");

                    $exp_name = $this->mails_text->exp_name;
                    eval("\$exp_name = \"$exp_name\";");

                    // Nettoyage de printemps
                    $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
                    $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

                    $this->email = $this->loadLib('email');
                    $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                    $this->email->addRecipient(trim($destinataire));

                    $this->email->setSubject('=?UTF-8?B?' . base64_encode($sujetMail) . '?=');
                    $this->email->setHTMLBody($texteMail);
                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                }
            } else {
                // lecture du fichier
                $lrecus = $this->recus2array($lien);

                /* EX :

                  0430004056802118EUR2 0004063100718230615  230615DELERY HELENE                    0000000  0000000000400{ZZ0X4VY7PFE69K8V
                  0530004056802118EUR2 0004063100718230615     NPYDELERY HELENE
                  0530004056802118EUR2 0004063100718230615     LCC004927RA-610
                  0530004056802118EUR2 0004063100718230615     RCNZZ0X4VY7PFE69K8VD

                  [codeEnregi] => 04
                  [codeBanque] => 30004
                  [codeOpBNPP] => 0568
                  [codeGuichet] => 02118
                  [codeDevises] => EUR
                  [nbDecimales] => 2
                  [zoneReserv1] =>
                  [numCompte] => 00040631007
                  [codeOpInterbancaire] => 18
                  [dateEcriture] => 230615
                  [codeMotifRejet] =>
                  [dateValeur] => 230615
                  [zoneReserv2] =>
                  [numEcriture] => 0000000
                  [codeExoneration] =>
                  [zoneReserv3] =>
                  [ligne1] => 0430004056802118EUR2 0004063100718230615  230615DELERY HELENE                    0000000  0000000000400{ZZ0X4VY7PFE69K8V

                  [refOp] => ZZ0X4VY7PFE69K8V
                  [libelleOpe1] => DELERY HELENE
                  [montant] => 4000
                  [libelleOpe2] => NPYDELERY HELENE
                  [libelleOpe3] => LCC004927HDELERY
                  [libelleOpe4] => RCNZZ0X4VY7PFE69K8VD

                 */
                // on regarde si on a deja des truc d'aujourd'hui
                $recep = $receptions->select('LEFT(added,10) = "' . date('Y-m-d') . '"'); // <------------------------------------------------------------ a remettre
                // si on a un fichier et qu'il n'est pas deja present en bdd
                // on enregistre qu'une fois par jour
                if ($lrecus != false && $recep == false) {
                    file_put_contents($this->path . 'protected/sftp/reception/UNILEND-00040631007-' . date('Ymd') . '.txt', $file); // <------------------ a remettre

                    foreach ($lrecus as $r) {
                        $code = $r['codeOpInterbancaire'];

                        // Status virement/prelevement
                        if (in_array($code, $statusVirementRecu)) {
                            $type               = 2; // virement
                            $status_virement    = 1; // recu
                            $status_prelevement = 0;
                        } elseif (in_array($code, $statusVirementEmis)) {
                            $type               = 2; // virement
                            $status_virement    = 2; // emis
                            $status_prelevement = 0;
                        } elseif (in_array($code, $statusVirementRejet)) {
                            $type               = 2; // virement
                            $status_virement    = 3; // rejet
                            $status_prelevement = 0;
                        } elseif (in_array($code, $statusPrelevementEmi)) {
                            $type               = 1; // prelevement
                            $status_virement    = 0;
                            $status_prelevement = 2; // emis
                        } elseif (in_array($code, $statusPrelevementRejete)) {
                            $type               = 1; // prelevement
                            $status_virement    = 0;
                            $status_prelevement = 3; // rejete/impaye
                        } // Si pas dans les criteres
                        else {
                            $type               = 4; // recap payline
                            $status_virement    = 0;
                            $status_prelevement = 0;
                        }

                        $motif = '';
                        for ($i = 1; $i <= 5; $i++) {
                            if ($r['libelleOpe' . $i] != false) {
                                $motif .= trim($r['libelleOpe' . $i]) . '<br>';
                            }
                        }

                        // Si on a un virement unilend offre de bienvenue
                        if ($r['unilend_bienvenue'] == true) {
                            //if(5 == 6){

                            if ($this->Config['env'] != "dev") {
                                $this->oLogger->addRecord(ULogger::INFO,
                                    'Cron : ' . __METHOD__ . ' virement offre de bienvenue',
                                    array($this->Config['env']));
                            }

                            // transact
                            $transactions->id_prelevement   = 0;
                            $transactions->id_client        = 0;
                            $transactions->montant          = $r['montant'];
                            $transactions->id_langue        = 'fr';
                            $transactions->date_transaction = date('Y-m-d H:i:s');
                            $transactions->status           = 1;
                            $transactions->etat             = 1;
                            $transactions->transaction      = 1;
                            $transactions->type_transaction = 18; // Unilend virement offre de bienvenue
                            $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                            $transactions->id_transaction   = $transactions->create();

                            // bank unilend
                            $bank_unilend->id_transaction = $transactions->id_transaction;
                            $bank_unilend->id_project     = 0;
                            $bank_unilend->montant        = $receptions->montant;
                            $bank_unilend->type           = 4; // Unilend offre de bienvenue
                            $bank_unilend->create();
                        } // Sinon comme d'hab
                        else {
                            $receptions->id_client          = 0;
                            $receptions->id_project         = 0;
                            $receptions->status_bo          = 0;
                            $receptions->remb               = 0;
                            $receptions->motif              = $motif;
                            $receptions->montant            = $r['montant'];
                            $receptions->type               = $type;
                            $receptions->status_virement    = $status_virement;
                            $receptions->status_prelevement = $status_prelevement;
                            $receptions->ligne              = $r['ligne1'];
                            $receptions->id_reception       = $receptions->create();

                            /////////////////////////////// ATTRIBUTION AUTO PRELEVEMENT (VIREMENTS EMPRUNTEUR) /////////////////////////////
                            if ($type == 1 && $status_prelevement == 2) {

                                // On cherche une suite de chiffres
                                preg_match_all('#[0-9]+#', $motif, $extract);
                                $nombre = (int)$extract[0][0]; // on retourne un int pour retirer les zeros devant

                                $listPrel = $prelevements->select('id_project = ' . $nombre . ' AND status = 0');

                                // on regarde si on a une corespondance
                                if (count($listPrel) > 0) {

                                    // on compare les 2 motif
                                    $mystring = trim($motif);
                                    $findme   = $listPrel[0]['motif'];
                                    $pos      = strpos($mystring, $findme);

                                    // on laisse en manuel
                                    if ($pos === false) {
                                        //echo 'Recu';
                                    } // Automatique (on attribue le prelevement au preteur)
                                    else {
                                        //echo 'Auto';
                                        if ($transactions->get($receptions->id_reception, 'status = 1 AND etat = 1 AND type_transaction = 6 AND id_prelevement') == false) {

                                            $projects->get($nombre, 'id_project');
                                            // On recup l'entreprise
                                            $companies->get($projects->id_company, 'id_company');
                                            // On recup le client
                                            $clients->get($companies->id_client_owner, 'id_client');

                                            // reception
                                            $receptions->get($receptions->id_reception, 'id_reception');
                                            $receptions->id_client  = $clients->id_client;
                                            $receptions->id_project = $projects->id_project;
                                            $receptions->status_bo  = 2;
                                            $receptions->remb       = 1;
                                            $receptions->update();

                                            // transact
                                            $transactions->id_prelevement   = $receptions->id_reception;
                                            $transactions->id_client        = $clients->id_client;
                                            $transactions->montant          = $receptions->montant;
                                            $transactions->id_langue        = 'fr';
                                            $transactions->date_transaction = date('Y-m-d H:i:s');
                                            $transactions->status           = 1;
                                            $transactions->etat             = 1;
                                            $transactions->transaction      = 1;
                                            $transactions->type_transaction = 6; // remb emprunteur
                                            $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                            $transactions->id_transaction   = $transactions->create();

                                            // bank unilend
                                            $bank_unilend->id_transaction = $transactions->id_transaction;
                                            $bank_unilend->id_project     = $projects->id_project;
                                            $bank_unilend->montant        = $receptions->montant;
                                            $bank_unilend->type           = 1;
                                            $bank_unilend->create();

                                            // on parcourt les echeances
                                            $eche    = $echeanciers_emprunteur->select('status_emprunteur = 0 AND id_project = ' . $projects->id_project, 'ordre ASC');
                                            $sumRemb = ($receptions->montant / 100);

                                            $newsum = $sumRemb;
                                            foreach ($eche as $e) {
                                                $ordre = $e['ordre'];

                                                // on récup le montant que l'emprunteur doit rembourser
                                                $montantDuMois = $echeanciers->getMontantRembEmprunteur($e['montant'] / 100, $e['commission'] / 100, $e['tva'] / 100);
                                                // On verifie si le montant a remb est inferieur ou égale a la somme récupéré
                                                if ($montantDuMois <= $newsum) {
                                                    // On met a jour les echeances du mois
                                                    $echeanciers->updateStatusEmprunteur($projects->id_project, $ordre);

                                                    $echeanciers_emprunteur->get($projects->id_project, 'ordre = ' . $ordre . ' AND id_project');
                                                    $echeanciers_emprunteur->status_emprunteur             = 1;
                                                    $echeanciers_emprunteur->date_echeance_emprunteur_reel = date('Y-m-d H:i:s');
                                                    $echeanciers_emprunteur->update();

                                                    // et on retire du wallet unilend
                                                    $newsum = $newsum - $montantDuMois;

                                                    if ($projects_remb->counter('id_project = "' . $projects->id_project . '" AND ordre = "' . $ordre . '" AND status IN(0,1)') <= 0) {

                                                        $date_echeance_preteur = $echeanciers->select('id_project = "' . $projects->id_project . '" AND ordre = "' . $ordre . '"', '', 0, 1);
                                                        // On regarde si le remb preteur auto est autorisé (eclatement preteur auto)
                                                        if ($projects->remb_auto == 0) {
                                                            // file d'attente pour les remb auto preteurs
                                                            $projects_remb->id_project                = $projects->id_project;
                                                            $projects_remb->ordre                     = $ordre;
                                                            $projects_remb->date_remb_emprunteur_reel = date('Y-m-d H:i:s');
                                                            $projects_remb->date_remb_preteurs        = $date_echeance_preteur[0]['date_echeance'];
                                                            $projects_remb->date_remb_preteurs_reel   = '0000-00-00 00:00:00';
                                                            $projects_remb->status                    = 0; // nom remb aux preteurs
                                                            $projects_remb->create();
                                                        }
                                                    }
                                                } else {
                                                    break;
                                                }
                                            }// fin boucle
                                        } // fin check transaction
                                    }// fin auto
                                }// fin check id projet
                            }

                            ////////////////////////// VIREMENT AUTOMATIQUE PRETEUR //////////////////////////////////////
                            // on fait ca que pour les virements recu
                            elseif ($type == 2 && $status_virement == 1) {
                                $is_remboursement_anticipe = false;

                                // On gère ici le Remboursement anticipé
                                if (strstr($r['libelleOpe3'], 'RA-')) {
                                    // on récupère l'id_projet
                                    $tab_id_projet = explode('RA-', $r['libelleOpe3']);
                                    $id_projet     = $tab_id_projet[1];

                                    // on check si on trouve le projet
                                    $this->projects = $this->loadData('projects');
                                    if ($this->projects->get($id_projet)) {
                                        $retour_auto = true;
                                    }
                                    $is_remboursement_anticipe = true;
                                } else {

                                    // DEBUT RECHERCHE DU MOTIF EN BDD //
                                    // On cherche une suite de chiffres
                                    preg_match_all('#[0-9]+#', $motif, $extract);
                                    //$nombre = (int)$extract[0][0]; // on retourne un int pour retirer les zeros devant

                                    $retour_auto = false;
                                    foreach ($extract[0] as $nombre) {
                                        // ajout de la condition pour ne pas rerentrer dedans une fois qu'on a déjà trouvé
                                        if ($retour_auto != true) {
                                            // si existe en bdd
                                            if ($clients->get($nombre, 'id_client')) {
                                                // on créer le motif qu'on devrait avoir
                                                $p           = substr($this->ficelle->stripAccents(utf8_decode(trim($clients->prenom))), 0, 1);
                                                $nom         = $this->ficelle->stripAccents(utf8_decode(trim($clients->nom)));
                                                $id_client   = str_pad($clients->id_client, 6, 0, STR_PAD_LEFT);
                                                $returnMotif = mb_strtoupper($id_client . $p . $nom, 'UTF-8');

                                                $mystring = str_replace(' ', '', $motif); // retire les espaces au cas ou le motif soit mal ecrit
                                                $findme   = str_replace(' ', '', $returnMotif);
                                                $pos      = strpos($mystring, $findme);

                                                // on laisse en manuel
                                                if ($pos === false) {
                                                    $retour_auto = false; //echo 'Recu';
                                                } // Automatique (on attribue le virement au preteur)
                                                else {
                                                    $retour_auto = true; //echo 'Auto';
                                                }
                                            }
                                        }
                                    }
                                } //end else
                                // FIN RECHERCHE MOTIF EN BDD //

                                if ($retour_auto == true) {

                                    if ($transactions->get($receptions->id_reception, 'status = 1 AND etat = 1 AND id_virement') == false) {

                                        if ($is_remboursement_anticipe) {
                                            // reception
                                            $receptions->get($receptions->id_reception, 'id_reception');
                                            $receptions->id_project    = $this->projects->id_project;
                                            $receptions->remb_anticipe = 1;
                                            $receptions->status_bo     = 2; // // attri auto
                                            $receptions->update();

                                            // Ajouter nouveau type transaction
                                            //
                                            //
                                            //ALTER TABLE  `receptions` ADD  `remb_anticipe` INT NOT NULL COMMENT  '0 : non / 1 : oui' AFTER  `type`
                                            //
                                            //
                                            // transact
                                            $transactions->id_virement      = $receptions->id_reception;
                                            $transactions->id_project       = $this->projects->id_project;
                                            $transactions->montant          = $receptions->montant;
                                            $transactions->id_langue        = 'fr';
                                            $transactions->date_transaction = date('Y-m-d H:i:s');
                                            $transactions->status           = 1;
                                            $transactions->etat             = 1;
                                            $transactions->transaction      = 1;
                                            $transactions->type_transaction = 22; // remboursement anticipe
                                            $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                            $transactions->id_transaction   = $transactions->create();

                                            // bank unilend
                                            $bank_unilend                 = $this->loadData('bank_unilend');
                                            $bank_unilend->id_transaction = $transactions->id_transaction;
                                            $bank_unilend->id_project     = $this->projects->id_project;
                                            $bank_unilend->montant        = $receptions->montant;
                                            $bank_unilend->type           = 1; // remb emprunteur
                                            $bank_unilend->status         = 0; // chez unilend
                                            $bank_unilend->create();

                                            // on pousse un mail à Unilend pour les prévenir du virement
                                            //************************************//
                                            //*** ENVOI DU MAIL Remboursement-anticipe ***//
                                            //************************************//
                                            // destinataire
                                            $this->settings->get('Adresse notification nouveau remboursement anticipe', 'type');
                                            $destinataire = $this->settings->value;
                                            //$destinataire = 'd.courtier@equinoa.com';

                                            // Recuperation du modele de mail
                                            $this->mails_text->get('notification-nouveau-remboursement-anticipe', 'lang = "' . $this->language . '" AND type');

                                            $surl = $this->surl;
                                            $url = $this->lurl;
                                            $id_projet = $this->projects->id_project;
                                            $montant = ($transactions->montant / 100);
                                            $nom_projet = $this->projects->title;

                                            $sujetMail = $this->mails_text->subject;
                                            eval("\$sujetMail = \"$sujetMail\";");

                                            $texteMail = $this->mails_text->content;
                                            eval("\$texteMail = \"$texteMail\";");

                                            $exp_name = $this->mails_text->exp_name;
                                            eval("\$exp_name = \"$exp_name\";");

                                            // Nettoyage de printemps
                                            $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
                                            $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

                                            $this->email = $this->loadLib('email');
                                            $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                                            $this->email->addRecipient(trim($destinataire));

                                            $this->email->setSubject('=?UTF-8?B?' . base64_encode($sujetMail) . '?=');
                                            $this->email->setHTMLBody($texteMail);
                                            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                                        } else {
                                            // reception
                                            $receptions->get($receptions->id_reception, 'id_reception');
                                            $receptions->id_client = $clients->id_client;
                                            $receptions->status_bo = 2;
                                            $receptions->remb      = 1;
                                            $receptions->update();

                                            // lender
                                            $lenders->get($clients->id_client, 'id_client_owner');
                                            $lenders->status = 1;
                                            $lenders->update();

                                            // transact
                                            $transactions->id_virement      = $receptions->id_reception;
                                            $transactions->id_client        = $lenders->id_client_owner;
                                            $transactions->montant          = $receptions->montant;
                                            $transactions->id_langue        = 'fr';
                                            $transactions->date_transaction = date('Y-m-d H:i:s');
                                            $transactions->status           = 1;
                                            $transactions->etat             = 1;
                                            $transactions->transaction      = 1;
                                            $transactions->type_transaction = 4; // alimentation virement
                                            $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                            $transactions->id_transaction   = $transactions->create();

                                            // wallet
                                            $wallets->id_lender                = $lenders->id_lender_account;
                                            $wallets->type_financial_operation = 30; // alimenation
                                            $wallets->id_transaction           = $transactions->id_transaction;
                                            $wallets->type                     = 1; // physique
                                            $wallets->amount                   = $receptions->montant;
                                            $wallets->status                   = 1;
                                            $wallets->id_wallet_line           = $wallets->create();

                                            // bank line
                                            $bank->id_wallet_line    = $wallets->id_wallet_line;
                                            $bank->id_lender_account = $lenders->id_lender_account;
                                            $bank->status            = 1;
                                            $bank->amount            = $receptions->montant;
                                            $bank->create();

                                            $this->notifications->type            = 5; // alim virement
                                            $this->notifications->id_lender       = $lenders->id_lender_account;
                                            $this->notifications->amount          = $receptions->montant;
                                            $this->notifications->id_notification = $this->notifications->create();

                                            //////// GESTION ALERTES //////////
                                            $this->clients_gestion_mails_notif->id_client                      = $lenders->id_client_owner;
                                            $this->clients_gestion_mails_notif->id_notif                       = 6; // alim virement
                                            $this->clients_gestion_mails_notif->date_notif                     = date('Y-m-d H:i:s');
                                            $this->clients_gestion_mails_notif->id_notification                = $this->notifications->id_notification;
                                            $this->clients_gestion_mails_notif->id_transaction                 = $transactions->id_transaction;
                                            $this->clients_gestion_mails_notif->id_clients_gestion_mails_notif = $this->clients_gestion_mails_notif->create();
                                            //////// FIN GESTION ALERTES //////////
                                            // on met l'etape inscription a 3
                                            if ($clients->etape_inscription_preteur < 3) {
                                                $clients->etape_inscription_preteur = 3; // etape 3 ok
                                                $clients->update();
                                            }

                                            // envoi email virement maintenant ou non
                                            if ($this->clients_gestion_notifications->getNotif($lenders->id_client_owner, 6, 'immediatement') == true) {

                                                //////// GESTION ALERTES //////////
                                                $this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                                                $this->clients_gestion_mails_notif->immediatement = 1; // on met a jour le statut immediatement
                                                $this->clients_gestion_mails_notif->update();
                                                //////// FIN GESTION ALERTES //////////
                                                // email
                                                //******************************//
                                                //*** ENVOI DU MAIL preteur-alimentation ***//
                                                //******************************//
                                                // Recuperation du modele de mail
                                                $this->mails_text->get('preteur-alimentation', 'lang = "' . $this->language . '" AND type');

                                                // FB
                                                $this->settings->get('Facebook', 'type');
                                                $lien_fb = $this->settings->value;

                                                // Twitter
                                                $this->settings->get('Twitter', 'type');
                                                $lien_tw = $this->settings->value;

                                                // Solde du compte preteur
                                                $solde = $transactions->getSolde($receptions->id_client);

                                                $varMail = array(
                                                    'surl'            => $this->surl,
                                                    'url'             => $this->lurl,
                                                    'prenom_p'        => utf8_decode($clients->prenom),
                                                    'fonds_depot'     => $this->ficelle->formatNumber($receptions->montant / 100),
                                                    'solde_p'         => $this->ficelle->formatNumber($solde),
                                                    'motif_virement'  => $returnMotif,
                                                    'projets'         => $this->lurl . '/projets-a-financer',
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

                                                // Pas de mail si le compte est desactivé
                                                if ($clients->status == 1) {
                                                    if ($this->Config['env'] == 'prod') {

                                                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $clients->email, $tabFiler);
                                                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                                                    } else {
                                                        $this->email->addRecipient(trim($clients->email));
                                                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                                                    }
                                                }
                                            }
                                        }//fin else $is_remboursement_anticipe
                                    } // fin check transaction
                                }
                            }
                            ////////////////////////// FIN VIREMENT AUTOMATIQUE PRETEUR //////////////////////////////////////
                        }
                    }
                }
            }

            $this->stopCron();
        }
    }

    // 1 fois pr jour a  1h du matin
    public function _etat_quotidien()
    {
        if (true === $this->startCron('etat_quotidien', 10)) {

            $jour = date('d');

            // si on veut mettre a jour une date on met le jour ici mais attention ca va sauvegarder enbdd et sur l'etat quotidien fait ce matin a 1h du mat
            if ($jour == 1) {
                // On recup le nombre de jour dans le mois
                $mois = mktime(0, 0, 0, date('m') - 1, 1, date('Y'));

                //$mois = mktime( 0, 0, 0, 9, $num,date('Y'));
                //$jour = $num;

                $nbJours = date("t", $mois);

                $leMois = date('m', $mois);
                $lannee = date('Y', $mois);
                $leJour = $nbJours;

                // affiche les données avant cette date
                $InfeA = mktime(0, 0, 0, date('m'), 1, date('Y'));
                //$InfeA = mktime( 0, 0, 0, 9, $num,date('Y'));

                $lanneeLemois = $lannee . '-' . $leMois;

                // affichage de la date du fichier
                $laDate = $jour . '-' . date('m') . '-' . date('Y');
                //$laDate = $jour.'-09-'.$lannee;

                $lemoisLannee2 = $leMois . '/' . $lannee;
            } else {
                // On recup le nombre de jour dans le mois
                $mois    = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $nbJours = date("t", $mois);

                $leMois = date('m');
                $lannee = date('Y');
                $leJour = $nbJours;

                $InfeA = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

                // pour regeneration à la mano
                //$InfeA = mktime( 0, 0, 0, 07, 26,2015);

                $lanneeLemois = date('Y-m');

                $laDate = date('d-m-Y');

                $lemoisLannee2 = date('m/Y');
            }

            // chargement des datas
            $transac                = $this->loadData('transactions');
            $echeanciers            = $this->loadData('echeanciers');
            $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
            $virements              = $this->loadData('virements');
            $prelevements           = $this->loadData('prelevements');
            $etat_quotidien         = $this->loadData('etat_quotidien');
            $bank_unilend           = $this->loadData('bank_unilend');

            // Les remboursements preteurs
            $lrembPreteurs = $bank_unilend->sumMontantByDayMonths('type = 2 AND status = 1', $leMois, $lannee);

            // On recup les echeances le jour où ils ont été remb aux preteurs
            $listEcheances = $bank_unilend->ListEcheancesByDayMonths('type = 2 AND status = 1', $leMois, $lannee);

            // alimentations CB
            $alimCB = $transac->sumByday(3, $leMois, $lannee);

            // 2 : alimentations virements
            $alimVirement = $transac->sumByday(4, $leMois, $lannee);

            // 7 : alimentations prelevements
            $alimPrelevement = $transac->sumByday(7, $leMois, $lannee);

            // 6 : remb Emprunteur (prelevement)
            $rembEmprunteur = $transac->sumByday(6, $leMois, $lannee);

            // 15 : rejet remb emprunteur
            $rejetrembEmprunteur = $transac->sumByday(15, $leMois, $lannee);

            // 9 : virement emprunteur (octroi prêt : montant | commissions octoi pret : unilend_montant)
            $virementEmprunteur = $transac->sumByday(9, $leMois, $lannee);

            // 11 : virement unilend (argent gagné envoyé sur le compte)
            $virementUnilend = $transac->sumByday(11, $leMois, $lannee);

            // 12 virerment pour l'etat
            $virementEtat = $transac->sumByday(12, $leMois, $lannee);

            // 8 : retrait preteur
            $retraitPreteur = $transac->sumByday(8, $leMois, $lannee);

            // 13 regul commission
            $regulCom = $transac->sumByday(13, $leMois, $lannee);

            // 16 unilend offre bienvenue
            $offres_bienvenue = $transac->sumByday(16, $leMois, $lannee);

            // 17 unilend offre bienvenue retrait
            $offres_bienvenue_retrait = $transac->sumByday(17, $leMois, $lannee);

            // 18 unilend offre bienvenue
            $unilend_bienvenue = $transac->sumByday(18, $leMois, $lannee);

            $listDates = array();
            for ($i = 1; $i <= $nbJours; $i++) {
                $listDates[$i] = $lanneeLemois . '-' . (strlen($i) < 2 ? '0' : '') . $i;
            }

            // recup des prelevements permanent

            $listPrel = array();
            foreach ($prelevements->select('type_prelevement = 1 AND status > 0 AND type = 1') as $prelev) {
                $addedXml = strtotime($prelev['added_xml']);
                $added    = strtotime($prelev['added']);

                $dateaddedXml = date('Y-m', $addedXml);
                $date         = date('Y-m', $added);
                $i            = 1;

                // on enregistre dans la table la premier prelevement
                $listPrel[date('Y-m-d', $added)] += $prelev['montant'];

                // tant que la date de creation n'est pas egale on rajoute les mois entre
                while ($date != $dateaddedXml) {
                    $newdate = mktime(0, 0, 0, date('m', $added) + $i, date('d', $addedXml), date('Y', $added));

                    $date  = date('Y-m', $newdate);
                    $added = date('Y-m-d', $newdate) . ' 00:00:00';

                    $listPrel[date('Y-m-d', $newdate)] += $prelev['montant'];

                    $i++;
                }
            }

            // on recup totaux du mois dernier
            $oldDate           = mktime(0, 0, 0, $leMois - 1, 1, $lannee);
            $oldDate           = date('Y-m', $oldDate);
            $etat_quotidienOld = $etat_quotidien->getTotauxbyMonth($oldDate);

            if ($etat_quotidienOld != false) {
                $soldeDeLaVeille = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
                $soldeReel       = $etat_quotidienOld['totalNewSoldeReel'];

                $soldeReel_old = $soldeReel;

                $soldeSFFPME_old = $etat_quotidienOld['totalSoldeSFFPME'];

                $soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];

                $soldePromotion_old = $etat_quotidienOld['totalSoldePromotion'];
            } else {
                // Solde theorique
                $soldeDeLaVeille = 0;

                // solde reel
                $soldeReel     = 0;
                $soldeReel_old = 0;

                $soldeSFFPME_old = 0;

                $soldeAdminFiscal_old = 0;

                // soldePromotion
                $soldePromotion_old = 0;
            }

            $newsoldeDeLaVeille = $soldeDeLaVeille;

            $soldePromotion = $soldePromotion_old;

            // ecart
            $oldecart = $soldeDeLaVeille - $soldeReel;

            // Solde SFF PME
            $soldeSFFPME = $soldeSFFPME_old;

            // Solde Admin. Fiscale
            $soldeAdminFiscal = $soldeAdminFiscal_old;

            //$bank_unilend->sumMontant('type ')
            // -- totaux -- //
            $totalAlimCB                              = 0;
            $totalAlimVirement                        = 0;
            $totalAlimPrelevement                     = 0;
            $totalRembEmprunteur                      = 0;
            $totalVirementEmprunteur                  = 0;
            $totalVirementCommissionUnilendEmprunteur = 0;
            $totalCommission                          = 0;

            // Retenues fiscales
            $totalPrelevements_obligatoires    = 0;
            $totalRetenues_source              = 0;
            $totalCsg                          = 0;
            $totalPrelevements_sociaux         = 0;
            $totalContributions_additionnelles = 0;
            $totalPrelevements_solidarite      = 0;
            $totalCrds                         = 0;

            $totalRetraitPreteur  = 0;
            $totalSommeMouvements = 0;

            $totalNewSoldeReel = 0;

            $totalEcartSoldes = 0;

            // Solde SFF PME
            $totalSoldeSFFPME = $soldeSFFPME_old;

            // Solde Admin. Fiscale
            $totalSoldeAdminFiscal = $soldeAdminFiscal_old;

            $tableau = '
		<style>
			table th,table td{width:80px;height:20px;border:1px solid black;}
			table td.dates{text-align:center;}
			.right{text-align:right;}
			.center{text-align:center;}
			.boder-top{border-top:1px solid black;}
			.boder-bottom{border-bottom:1px solid black;}
			.boder-left{border-left:1px solid black;}
			.boder-right{border-right:1px solid black;}
		</style>

		<table border="0" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 2500px;">
			<tr>
				<th colspan="34" style="height:35px;font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">UNILEND</th>
			</tr>
			<tr>
				<th rowspan="2">' . $laDate . '</th>
				<th colspan="3">Chargements compte prêteurs</th>
				<th>Chargements offres</th>
				<th>Echeances<br />Emprunteur</th>
                <th>Octroi prêt</th>
                <th>Commissions<br />octroi prêt</th>
                <th>Commissions<br />restant dû</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux prêteurs</th>
                <th>&nbsp;</th>
                <th colspan="6">Soldes</th>
                <th colspan="6">Mouvements internes</th>
                <th colspan="3">Virements</th>
                <th>Prélèvements</th>

			</tr>
			<tr>

				<td class="center">Carte<br>bancaire</td>
				<td class="center">Virement</td>
				<td class="center">Prélèvement</td>
				<td class="center">Virement</td>
				<td class="center">Prélèvement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>

                <td class="center">Prélèvements<br />obligatoires</td>
                <td class="center">Retenues à la<br />source</td>
                <td class="center">CSG</td>
                <td class="center">Prélèvements<br />sociaux</td>
                <td class="center">Contributions<br />additionnelles</td>
                <td class="center">Prélèvements<br />solidarité</td>
                <td class="center">CRDS</td>
                <td class="center">Virement</td>
                <td class="center">Total<br />mouvements</td>
                <td class="center">Solde<br />théorique</td>
                <td class="center">Solde<br />réel</td>
                <td class="center">Ecart<br />global</td>
				<td class="center">Solde<br />Promotions</td>
				<td class="center">Solde<br />SFF PME</td>
				<td class="center">Solde Admin.<br>Fiscale</td>

				<td class="center">Offre promo</td>
                <td class="center">Octroi prêt</td>
                <td class="center">Retour prêteur<br />(Capital)</td>
                <td class="center">Retour prêteur<br />(Intérêts nets)</td>
				<td class="center">Affectation<br />Ech. Empr.</td>
                <td class="center">Ecart<br />fiscal</td>

                <td class="center">Fichier<br />virements</td>
                <td class="center">Dont<br />SFF PME</td>
				<td class="center">Administration<br />Fiscale</td>
                <td class="center">Fichier<br />prélèvements</td>
			</tr>
			<tr>
				<td colspan="18">Début du mois</td>
                <td class="right">' . $this->ficelle->formatNumber($soldeDeLaVeille) . '</td>
                <td class="right">' . $this->ficelle->formatNumber($soldeReel) . '</td>
                <td class="right">' . $this->ficelle->formatNumber($oldecart) . '</td>
                <td class="right">' . $this->ficelle->formatNumber($soldePromotion_old) . '</td>
                <td class="right">' . $this->ficelle->formatNumber($soldeSFFPME_old) . '</td>
                <td class="right">' . $this->ficelle->formatNumber($soldeAdminFiscal_old) . '</td>
                <td colspan="10">&nbsp;</td>
            </tr>';

            foreach ($listDates as $key => $date) {

                if (strtotime($date . ' 00:00:00') < $InfeA) {

                    // sommes des echeance par jour (sans RA)
                    $echangeDate = $echeanciers->getEcheanceByDayAll($date, '1 AND status_ra = 0');

                    // sommes des echeance par jour (que RA)
                    $echangeDateRA = $echeanciers->getEcheanceByDayAll($date, '1 AND status_ra = 1');

                    // on recup com de lecheance emprunteur a la date de mise a jour de la ligne (ddonc au changement de statut remboursé)
                    //$commission = $echeanciers_emprunteur->sum('commission','LEFT(date_echeance_emprunteur_reel,10) = "'.$date.'" AND status_emprunteur = 1');
                    // on met la commission au moment du remb preteurs
                    $commission = $echeanciers_emprunteur->sum('commission', 'id_echeancier_emprunteur IN(' . $listEcheances[$date] . ')');

                    // commission sommes remboursé
                    $commission = ($commission / 100);

                    //$latva = $echeanciers_emprunteur->sum('tva','LEFT(date_echeance_emprunteur_reel,10) = "'.$date.'" AND status_emprunteur = 1');
                    // On met la TVA au moment du remb preteurs
                    $latva = $echeanciers_emprunteur->sum('tva', 'id_echeancier_emprunteur IN(' . $listEcheances[$date] . ')');

                    // la tva
                    $latva = ($latva / 100);

                    $commission += $latva;

                    ////////////////////////////
                    /// add regul commission ///

                    $commission += $regulCom[$date]['montant'];

                    ///////////////////////////
                    //prelevements_obligatoires
                    $prelevements_obligatoires = $echangeDate['prelevements_obligatoires'];
                    //retenues_source
                    $retenues_source = $echangeDate['retenues_source'];
                    //csg
                    $csg = $echangeDate['csg'];
                    //prelevements_sociaux
                    $prelevements_sociaux = $echangeDate['prelevements_sociaux'];
                    //contributions_additionnelles
                    $contributions_additionnelles = $echangeDate['contributions_additionnelles'];
                    //prelevements_solidarite
                    $prelevements_solidarite = $echangeDate['prelevements_solidarite'];
                    //crds
                    $crds = $echangeDate['crds'];

                    // Retenues Fiscales
                    $retenuesFiscales = $prelevements_obligatoires + $retenues_source + $csg + $prelevements_sociaux + $contributions_additionnelles + $prelevements_solidarite + $crds;

                    // Solde promotion
                    $soldePromotion += $unilend_bienvenue[$date]['montant'];  // ajouté le 19/11/2014
                    $soldePromotion -= $offres_bienvenue[$date]['montant'];  // ajouté le 19/11/2014

                    $soldePromotion += $offres_bienvenue_retrait[$date]['montant']; // (on ajoute le offres retirées d'un compte) ajouté le 19/11/2014

                    $offrePromo = $offres_bienvenue[$date]['montant'] + $offres_bienvenue_retrait[$date]['montant'];
                    //$offrePromo -= ;
                    // ADD $rejetrembEmprunteur[$date]['montant'] // 22/01/2015
                    // total Mouvements
                    $entrees = ($alimCB[$date]['montant'] + $alimVirement[$date]['montant'] + $alimPrelevement[$date]['montant'] + $rembEmprunteur[$date]['montant'] + $unilend_bienvenue[$date]['montant'] + $rejetrembEmprunteur[$date]['montant']);
                    $sorties = (str_replace('-', '', $virementEmprunteur[$date]['montant']) + $virementEmprunteur[$date]['montant_unilend'] + $commission + $retenuesFiscales + str_replace('-', '', $retraitPreteur[$date]['montant']));

                    // Total mouvementsc de la journée
                    $sommeMouvements = ($entrees - $sorties);;    // solde De La Veille (solde theorique)
                    // addition du solde theorique et des mouvements
                    $newsoldeDeLaVeille += $sommeMouvements;

                    // Solde reel de base
                    $soldeReel += $transac->getSoldeReelDay($date);

                    // on rajoute les virements des emprunteurs
                    $soldeReelUnilend = $transac->getSoldeReelUnilendDay($date);

                    // solde pour l'etat
                    $soldeReelEtat = $transac->getSoldeReelEtatDay($date);

                    // la partie pour l'etat des remb unilend + la commission qu'on retire a chaque fois du solde
                    $laComPlusLetat = $commission + $soldeReelEtat;

                    // Solde réel  = solde reel unilend
                    $soldeReel += $soldeReelUnilend - $laComPlusLetat;

                    // on addition les solde precedant
                    $newSoldeReel = $soldeReel; // on retire la commission des echeances du jour ainsi que la partie pour l'etat
                    // On recupere le solde dans une autre variable
                    $soldeTheorique = $newsoldeDeLaVeille;

                    $leSoldeReel = $newSoldeReel;

                    if (strtotime($date . ' 00:00:00') > time()) {
                        $soldeTheorique = 0;
                        $leSoldeReel    = 0;
                    }

                    // ecart global soldes
                    $ecartSoldes = ($soldeTheorique - $leSoldeReel);

                    // Solde SFF PME
                    $soldeSFFPME += $virementEmprunteur[$date]['montant_unilend'] - $virementUnilend[$date]['montant'] + $commission;

                    // Solde Admin. Fiscale
                    $soldeAdminFiscal += $retenuesFiscales - $virementEtat[$date]['montant'];

                    ////////////////////////////
                    /// add regul partie etat fiscal ///

                    $soldeAdminFiscal += $regulCom[$date]['montant_unilend'];

                    ///////////////////////////
                    // somme capital preteurs par jour
                    $capitalPreteur = $echangeDate['capital'];
                    $capitalPreteur += $echangeDateRA['capital'];
                    $capitalPreteur = ($capitalPreteur / 100);

                    // somme net net preteurs par jour
                    $interetNetPreteur = ($echangeDate['interets'] / 100) - $retenuesFiscales;

                    // Montant preteur
                    $montantPreteur = ($interetNetPreteur + $capitalPreteur);

                    // Affectation Ech. Empr.
                    $affectationEchEmpr = $lrembPreteurs[$date]['montant'] + $lrembPreteurs[$date]['etat'] + $commission;

                    // ecart Mouv Internes
                    $ecartMouvInternes = round(($affectationEchEmpr) - $commission - $retenuesFiscales - $capitalPreteur - $interetNetPreteur, 2);

                    // solde bids validés
                    $octroi_pret = (str_replace('-', '', $virementEmprunteur[$date]['montant']) + $virementEmprunteur[$date]['montant_unilend']);

                    // Virements ok (fichier virements)
                    $virementsOK = $virements->sumVirementsbyDay($date, 'status > 0');

                    //dont sffpme virements (argent gagné a donner a sffpme)
                    $virementsAttente = $virementUnilend[$date]['montant'];

                    // Administration Fiscale
                    $adminFiscalVir = $virementEtat[$date]['montant'];

                    // prelevements
                    $prelevPonctuel = $prelevements->sum('LEFT(added_xml,10) = "' . $date . '" AND status > 0');

                    if ($listPrel[$date] != false) {
                        $sommePrelev = $prelevPonctuel + $listPrel[$date];
                        //echo $prelevPonctuel .'<br>';
                    } else {
                        $sommePrelev = $prelevPonctuel;
                    }

                    $sommePrelev = $sommePrelev / 100;

                    $leRembEmprunteur = $rembEmprunteur[$date]['montant'] + $rejetrembEmprunteur[$date]['montant']; // update le 22/01/2015
                    // additions //

                    $totalAlimCB += $alimCB[$date]['montant'];
                    $totalAlimVirement += $alimVirement[$date]['montant'];
                    $totalAlimPrelevement += $alimPrelevement[$date]['montant'];
                    $totalRembEmprunteur += $leRembEmprunteur; // update le 22/01/2015
                    $totalVirementEmprunteur += str_replace('-', '', $virementEmprunteur[$date]['montant']);
                    $totalVirementCommissionUnilendEmprunteur += $virementEmprunteur[$date]['montant_unilend'];

                    $totalVirementUnilend_bienvenue += $unilend_bienvenue[$date]['montant'];

                    $totalCommission += $commission;

                    $totalPrelevements_obligatoires += $prelevements_obligatoires;
                    $totalRetenues_source += $retenues_source;
                    $totalCsg += $csg;
                    $totalPrelevements_sociaux += $prelevements_sociaux;
                    $totalContributions_additionnelles += $contributions_additionnelles;
                    $totalPrelevements_solidarite += $prelevements_solidarite;
                    $totalCrds += $crds;

                    $totalRetraitPreteur += $retraitPreteur[$date]['montant'];
                    $totalSommeMouvements += $sommeMouvements;
                    $totalNewsoldeDeLaVeille = $newsoldeDeLaVeille; // Solde théorique
                    $totalNewSoldeReel       = $newSoldeReel;
                    $totalEcartSoldes        = $ecartSoldes;
                    $totalAffectationEchEmpr += $affectationEchEmpr;

                    // total solde promotion
                    $totalSoldePromotion = $soldePromotion;

                    // total des offre promo retiré d'un compte prêteur
                    $totalOffrePromo += $offrePromo;

                    // Solde SFF PME
                    $totalSoldeSFFPME = $soldeSFFPME;
                    // Solde Admin. Fiscale
                    $totalSoldeAdminFiscal = $soldeAdminFiscal;

                    $totalOctroi_pret += $octroi_pret;
                    $totalCapitalPreteur += $capitalPreteur;
                    $totalInteretNetPreteur += $interetNetPreteur;

                    $totalEcartMouvInternes += $ecartMouvInternes;

                    $totalVirementsOK += $virementsOK;

                    // dont sff pme
                    $totalVirementsAttente += $virementsAttente;

                    $totaladdsommePrelev += $sommePrelev;

                    $totalAdminFiscalVir += $adminFiscalVir;

                    $tableau .= '
                <tr>
                    <td class="dates">' . (strlen($key) < 2 ? '0' : '') . $key . '/' . $lemoisLannee2 . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($alimCB[$date]['montant']) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($alimVirement[$date]['montant']) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($alimPrelevement[$date]['montant']) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($unilend_bienvenue[$date]['montant']) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($leRembEmprunteur) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber(str_replace('-', '', $virementEmprunteur[$date]['montant'])) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($virementEmprunteur[$date]['montant_unilend']) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($commission) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($prelevements_obligatoires) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($retenues_source) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($csg) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($prelevements_sociaux) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($contributions_additionnelles) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($prelevements_solidarite) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($crds) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber(str_replace('-', '', $retraitPreteur[$date]['montant'])) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($sommeMouvements) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($soldeTheorique) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($leSoldeReel) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber(round($ecartSoldes, 2)) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($soldePromotion) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($soldeSFFPME) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($soldeAdminFiscal) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($offrePromo) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($octroi_pret) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($capitalPreteur) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($interetNetPreteur) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($affectationEchEmpr) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($ecartMouvInternes) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($virementsOK) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($virementsAttente) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($adminFiscalVir) . '</td>
                    <td class="right">' . $this->ficelle->formatNumber($sommePrelev) . '</td>
                </tr>';
                } else {
                    $tableau .= '
                <tr>
                    <td class="dates">' . (strlen($key) < 2 ? '0' : '') . $key . '/' . $lemoisLannee2 . '</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>';
                }
            }

            $tableau .= '
            <tr>
                <td colspan="33">&nbsp;</td>
            </tr>
            <tr>
                <th>Total mois</th>
                <th class="right">' . $this->ficelle->formatNumber($totalAlimCB) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalAlimVirement) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalAlimPrelevement) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalVirementUnilend_bienvenue) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalRembEmprunteur) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalVirementEmprunteur) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalVirementCommissionUnilendEmprunteur) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalCommission) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalPrelevements_obligatoires) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalRetenues_source) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalCsg) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalPrelevements_sociaux) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalContributions_additionnelles) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalPrelevements_solidarite) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalCrds) . '</th>
                <th class="right">' . $this->ficelle->formatNumber(str_replace('-', '', $totalRetraitPreteur)) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalSommeMouvements) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalNewsoldeDeLaVeille) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalNewSoldeReel) . '</th>
                <th class="right">' . $this->ficelle->formatNumber(round($totalEcartSoldes, 2)) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalSoldePromotion) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalSoldeSFFPME) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalSoldeAdminFiscal) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalOffrePromo) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalOctroi_pret) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalCapitalPreteur) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalInteretNetPreteur) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalAffectationEchEmpr) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalEcartMouvInternes) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalVirementsOK) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalVirementsAttente) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totalAdminFiscalVir) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($totaladdsommePrelev) . '</th>
            </tr>
        </table>';

            $table[1]['name'] = 'totalAlimCB';
            $table[1]['val']  = $totalAlimCB;
            $table[2]['name'] = 'totalAlimVirement';
            $table[2]['val']  = $totalAlimVirement;
            $table[3]['name'] = 'totalAlimPrelevement';
            $table[3]['val']  = $totalAlimPrelevement;
            $table[4]['name'] = 'totalRembEmprunteur';
            $table[4]['val']  = $totalRembEmprunteur;
            $table[5]['name'] = 'totalVirementEmprunteur';
            $table[5]['val']  = $totalVirementEmprunteur;
            $table[6]['name'] = 'totalVirementCommissionUnilendEmprunteur';
            $table[6]['val']  = $totalVirementCommissionUnilendEmprunteur;
            $table[7]['name'] = 'totalCommission';
            $table[7]['val']  = $totalCommission;

            $table[8]['name']  = 'totalPrelevements_obligatoires';
            $table[8]['val']   = $totalPrelevements_obligatoires;
            $table[9]['name']  = 'totalRetenues_source';
            $table[9]['val']   = $totalRetenues_source;
            $table[10]['name'] = 'totalCsg';
            $table[10]['val']  = $totalCsg;
            $table[11]['name'] = 'totalPrelevements_sociaux';
            $table[11]['val']  = $totalPrelevements_sociaux;
            $table[12]['name'] = 'totalContributions_additionnelles';
            $table[12]['val']  = $totalContributions_additionnelles;
            $table[13]['name'] = 'totalPrelevements_solidarite';
            $table[13]['val']  = $totalPrelevements_solidarite;
            $table[14]['name'] = 'totalCrds';
            $table[14]['val']  = $totalCrds;

            $table[15]['name'] = 'totalRetraitPreteur';
            $table[15]['val']  = $totalRetraitPreteur;
            $table[16]['name'] = 'totalSommeMouvements';
            $table[16]['val']  = $totalSommeMouvements;
            $table[17]['name'] = 'totalNewsoldeDeLaVeille';
            //$table[17]['val'] = $totalNewsoldeDeLaVeille-$soldeDeLaVeille;
            $table[17]['val']  = $totalNewsoldeDeLaVeille;
            $table[18]['name'] = 'totalNewSoldeReel';
            //$table[18]['val'] = $totalNewSoldeReel-$soldeReel_old;
            $table[18]['val']  = $totalNewSoldeReel;
            $table[19]['name'] = 'totalEcartSoldes';
            $table[19]['val']  = $totalEcartSoldes;

            $table[20]['name'] = 'totalOctroi_pret';
            $table[20]['val']  = $totalOctroi_pret;

            $table[21]['name'] = 'totalCapitalPreteur';
            $table[21]['val']  = $totalCapitalPreteur;
            $table[22]['name'] = 'totalInteretNetPreteur';
            $table[22]['val']  = $totalInteretNetPreteur;
            $table[23]['name'] = 'totalEcartMouvInternes';
            $table[23]['val']  = $totalEcartMouvInternes;

            $table[24]['name'] = 'totalVirementsOK';
            $table[24]['val']  = $totalVirementsOK;
            $table[25]['name'] = 'totalVirementsAttente';
            $table[25]['val']  = $totalVirementsAttente;
            $table[26]['name'] = 'totaladdsommePrelev';
            $table[26]['val']  = $totaladdsommePrelev;

            // Solde SFF PME
            $table[27]['name'] = 'totalSoldeSFFPME';
            $table[27]['val']  = $totalSoldeSFFPME;
            //$table[27]['val'] = $totalSoldeSFFPME-$soldeSFFPME_old;
            // Solde Admin. Fiscale
            $table[28]['name'] = 'totalSoldeAdminFiscal';
            //$table[28]['val'] = $totalSoldeAdminFiscal-$soldeAdminFiscal_old;
            $table[28]['val'] = $totalSoldeAdminFiscal;

            // Solde Admin. Fiscale (virement)
            $table[29]['name'] = 'totalAdminFiscalVir';
            $table[29]['val']  = $totalAdminFiscalVir;

            $table[30]['name'] = 'totalAffectationEchEmpr';
            $table[30]['val']  = $totalAffectationEchEmpr;

            $table[31]['name'] = 'totalVirementUnilend_bienvenue';
            $table[31]['val']  = $totalVirementUnilend_bienvenue;

            $table[32]['name'] = 'totalSoldePromotion';
            $table[32]['val']  = $totalSoldePromotion;

            $table[33]['name'] = 'totalOffrePromo';
            $table[33]['val']  = $totalOffrePromo;

            // create sav solde
            $etat_quotidien->createEtat_quotidient($table, $leMois, $lannee);

            // on recup toataux du mois de decembre de l'année precedente
            $oldDate           = mktime(0, 0, 0, 12, $jour, $lannee - 1);
            $oldDate           = date('Y-m', $oldDate);
            $etat_quotidienOld = $etat_quotidien->getTotauxbyMonth($oldDate);

            if ($etat_quotidienOld != false) {
                $soldeDeLaVeille = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
                $soldeReel       = $etat_quotidienOld['totalNewSoldeReel'];

                $soldeSFFPME_old = $etat_quotidienOld['totalSoldeSFFPME'];

                $soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];

                $soldePromotion_old = $etat_quotidienOld['totalSoldePromotion'];
            } else {
                // Solde theorique
                $soldeDeLaVeille = 0;

                // solde reel
                $soldeReel = 0;

                $soldeSFFPME_old = 0;

                $soldeAdminFiscal_old = 0;

                $soldePromotion_old = 0;
            }

            // ecart
            $oldecart = $soldeDeLaVeille - $soldeReel;

            $tableau .= '
        <table border="0" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 2500px;">
            <tr>
                <th colspan="34" style="font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">&nbsp;</th>
            </tr>
            <tr>
                <th colspan="34" style="height:35px;font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">UNILEND</th>
            </tr>
            <tr>
                <th rowspan="2">' . $lannee . '</th>
                <th colspan="3">Chargements compte prêteurs</th>
                <th>Chargements offres</th>
                <th>Echeances<br />Emprunteur</th>
                <th>Octroi prêt</th>
                <th>Commissions<br />octroi prêt</th>
                <th>Commissions<br />restant dû</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux prêteurs</th>
                <th>&nbsp;</th>
                <th colspan="6">Soldes</th>
                <th colspan="6">Mouvements internes</th>
                <th colspan="3">Virements</th>
                <th>Prélèvements</th>
            </tr>
            <tr>
                <td class="center">Carte<br />bancaire</td>
                <td class="center">Virement</td>
                <td class="center">Prélèvement</td>
                <td class="center">Virement</td>
                <td class="center">Prélèvement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                <td class="center">Prélèvements<br />obligatoires</td>
                <td class="center">Retenues à la<br />source</td>
                <td class="center">CSG</td>
                <td class="center">Prélèvements<br />sociaux</td>
                <td class="center">Contributions<br />additionnelles</td>
                <td class="center">Prélèvements<br />solidarité</td>
                <td class="center">CRDS</td>
                <td class="center">Virement</td>
                <td class="center">Total<br />mouvements</td>
                <td class="center">Solde<br />théorique</td>
                <td class="center">Solde<br />réel</td>
                <td class="center">Ecart<br />global</td>
                <td class="center">Solde<br />Promotions</td>
                <td class="center">Solde<br />SFF PME</td>
                <td class="center">Solde Admin.<br>Fiscale</td>
                <td class="center">Offre promo</td>
                <td class="center">Octroi prêt</td>
                <td class="center">Retour prêteur<br />(Capital)</td>
                <td class="center">Retour prêteur<br />(Intérêts nets)</td>
                <td class="center">Affectation<br />Ech. Empr.</td>
                <td class="center">Ecart<br />fiscal</td>
                <td class="center">Fichier<br />virements</td>
                <td class="center">Dont<br />SFF PME</td>
                <td class="center">Administration<br />Fiscale</td>
                <td class="center">Fichier<br />prélèvements</td>
            </tr>
            <tr>
                <td colspan="18">Début d\'année</td>
                <td class="right">' . $this->ficelle->formatNumber($soldeDeLaVeille) . '</td>
                <td class="right">' . $this->ficelle->formatNumber($soldeReel) . '</td>
                <td class="right">' . $this->ficelle->formatNumber($oldecart) . '</td>
                <td class="right">' . $this->ficelle->formatNumber($soldePromotion_old) . '</td>
                <td class="right">' . $this->ficelle->formatNumber($soldeSFFPME_old) . '</td>
                <td class="right">' . $this->ficelle->formatNumber($soldeAdminFiscal_old) . '</td>
                <td colspan="10">&nbsp;</td>
            </tr>';

            $sommetotalAlimCB                              = 0;
            $sommetotalAlimVirement                        = 0;
            $sommetotalAlimPrelevement                     = 0;
            $sommetotalRembEmprunteur                      = 0;
            $sommetotalVirementEmprunteur                  = 0;
            $sommetotalVirementCommissionUnilendEmprunteur = 0;
            $sommetotalCommission                          = 0;

            // Retenues fiscales
            $sommetotalPrelevements_obligatoires    = 0;
            $sommetotalRetenues_source              = 0;
            $sommetotalCsg                          = 0;
            $sommetotalPrelevements_sociaux         = 0;
            $sommetotalContributions_additionnelles = 0;
            $sommetotalPrelevements_solidarite      = 0;
            $sommetotalCrds                         = 0;
            $sommetotalAffectationEchEmpr           = 0;

            // Remboursements aux prêteurs
            $sommetotalRetraitPreteur = 0;

            $sommetotalSommeMouvements = 0;

            $sommetotalNewsoldeDeLaVeille = 0;
            $sommetotalNewSoldeReel       = 0;
            $sommetotalEcartSoldes        = 0;
            $sommetotalSoldeSFFPME        = 0;
            $sommetotalSoldeAdminFiscal   = 0;
            $sommetotalSoldePromotion     = 0;

            // Mouvements internes
            $sommetotalOctroi_pret       = 0;
            $sommetotalCapitalPreteur    = 0;
            $sommetotalInteretNetPreteur = 0;
            $sommetotalEcartMouvInternes = 0;

            // Virements
            $sommetotalVirementsOK      = 0;
            $sommetotalVirementsAttente = 0;
            $sommetotalAdminFiscalVir   = 0;

            // Prélèvements
            $sommetotaladdsommePrelev = 0;

            $sommetotalVirementUnilend_bienvenue = 0;

            $sommetotalOffrePromo = 0;

            for ($i = 1; $i <= 12; $i++) {
                if (strlen($i) < 2) {
                    $numMois = '0' . $i;
                } else {
                    $numMois = $i;
                }

                $lemois = $etat_quotidien->getTotauxbyMonth($lannee . '-' . $numMois);

                $sommetotalAlimCB += $lemois['totalAlimCB'];
                $sommetotalAlimVirement += $lemois['totalAlimVirement'];
                $sommetotalAlimPrelevement += $lemois['totalAlimPrelevement'];
                $sommetotalRembEmprunteur += $lemois['totalRembEmprunteur'];
                $sommetotalVirementEmprunteur += $lemois['totalVirementEmprunteur'];
                $sommetotalVirementCommissionUnilendEmprunteur += $lemois['totalVirementCommissionUnilendEmprunteur'];
                $sommetotalCommission += $lemois['totalCommission'];

                $sommetotalVirementUnilend_bienvenue += $lemois['totalVirementUnilend_bienvenue'];

                $sommetotalOffrePromo += $lemois['totalOffrePromo'];

                // Retenues fiscales
                $sommetotalPrelevements_obligatoires += $lemois['totalPrelevements_obligatoires'];
                $sommetotalRetenues_source += $lemois['totalRetenues_source'];
                $sommetotalCsg += $lemois['totalCsg'];
                $sommetotalPrelevements_sociaux += $lemois['totalPrelevements_sociaux'];
                $sommetotalContributions_additionnelles += $lemois['totalContributions_additionnelles'];
                $sommetotalPrelevements_solidarite += $lemois['totalPrelevements_solidarite'];
                $sommetotalCrds += $lemois['totalCrds'];

                // Remboursements aux prêteurs
                $sommetotalRetraitPreteur += $lemois['totalRetraitPreteur'];

                $sommetotalSommeMouvements += $lemois['totalSommeMouvements'];

                // Soldes
                if ($lemois != false) {
                    $sommetotalNewsoldeDeLaVeille = $lemois['totalNewsoldeDeLaVeille'];
                    $sommetotalNewSoldeReel       = $lemois['totalNewSoldeReel'];
                    $sommetotalEcartSoldes        = $lemois['totalEcartSoldes'];
                    $sommetotalSoldeSFFPME        = $lemois['totalSoldeSFFPME'];
                    $sommetotalSoldeAdminFiscal   = $lemois['totalSoldeAdminFiscal'];
                    $sommetotalSoldePromotion     = $lemois['totalSoldePromotion'];
                }

                // Mouvements internes
                $sommetotalOctroi_pret += $lemois['totalOctroi_pret'];
                $sommetotalCapitalPreteur += $lemois['totalCapitalPreteur'];
                $sommetotalInteretNetPreteur += $lemois['totalInteretNetPreteur'];
                $sommetotalEcartMouvInternes += $lemois['totalEcartMouvInternes'];

                // Virements
                $sommetotalVirementsOK += $lemois['totalVirementsOK'];
                $sommetotalVirementsAttente += $lemois['totalVirementsAttente'];
                $sommetotalAdminFiscalVir += $lemois['totalAdminFiscalVir'];

                // Prélèvements
                $sommetotaladdsommePrelev += $lemois['totaladdsommePrelev'];

                $sommetotalAffectationEchEmpr += $lemois['totalAffectationEchEmpr'];

                $tableau .= '
                <tr>
                    <th>' . $this->dates->tableauMois['fr'][$i] . '</th>';

                if ($lemois != false) {
                    $tableau .= '
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalAlimCB']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalAlimVirement']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalAlimPrelevement']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalVirementUnilend_bienvenue']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalRembEmprunteur']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalVirementEmprunteur']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalVirementCommissionUnilendEmprunteur']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalCommission']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalPrelevements_obligatoires']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalRetenues_source']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalCsg']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalPrelevements_sociaux']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalContributions_additionnelles']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalPrelevements_solidarite']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalCrds']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber(str_replace('-', '', $lemois['totalRetraitPreteur'])) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalSommeMouvements']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalNewsoldeDeLaVeille']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalNewSoldeReel']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalEcartSoldes']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalSoldePromotion']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalSoldeSFFPME']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalSoldeAdminFiscal']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalOffrePromo']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalOctroi_pret']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalCapitalPreteur']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalInteretNetPreteur']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalAffectationEchEmpr']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalEcartMouvInternes']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalVirementsOK']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalVirementsAttente']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totalAdminFiscalVir']) . '</td>
                        <td class="right">' . $this->ficelle->formatNumber($lemois['totaladdsommePrelev']) . '</td>';
                } else {
                    $tableau .= '
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>';
                }

                $tableau .= '</tr>';
            }

            $tableau .= '
            <tr>
                <th>Total année</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalAlimCB) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalAlimVirement) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalAlimPrelevement) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalVirementUnilend_bienvenue) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalRembEmprunteur) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalVirementEmprunteur) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalVirementCommissionUnilendEmprunteur) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalCommission) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalPrelevements_obligatoires) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalRetenues_source) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalCsg) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalPrelevements_sociaux) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalContributions_additionnelles) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalPrelevements_solidarite) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalCrds) . '</th>
                <th class="right">' . $this->ficelle->formatNumber(str_replace('-', '', $sommetotalRetraitPreteur)) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalSommeMouvements) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalNewsoldeDeLaVeille) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalNewSoldeReel) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalEcartSoldes) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalSoldePromotion) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalSoldeSFFPME) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalSoldeAdminFiscal) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalOffrePromo) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalOctroi_pret) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalCapitalPreteur) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalInteretNetPreteur) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalAffectationEchEmpr) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalEcartMouvInternes) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalVirementsOK) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalVirementsAttente) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotalAdminFiscalVir) . '</th>
                <th class="right">' . $this->ficelle->formatNumber($sommetotaladdsommePrelev) . '</th>
            </tr>
        </table>';

            if ($this->Config['env'] == 'prod') {
                echo utf8_decode($tableau);
            } else {
                echo($tableau);
            }
            // si on met un param on peut regarder sans enregister de fichier ou d'envoie de mail
            if (isset($this->params[0])) {
                $this->stopCron();
                die;
            }

            $filename = 'Unilend_etat_' . date('Ymd');
            //$filename = 'Unilend_etat_'.$ladatedetest;
            //$filename = 'Unilend_etat_20150726';

            if ($this->Config['env'] == 'prod') {
                $connection = ssh2_connect('ssh.reagi.com', 22);
                ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
                $sftp       = ssh2_sftp($connection);
                $sftpStream = @fopen('ssh2.sftp://' . $sftp . '/home/sfpmei/emissions/etat_quotidien/' . $filename . '.xls', 'w');
                fwrite($sftpStream, $tableau);
                fclose($sftpStream);
            }

            file_put_contents($this->path . 'protected/sftp/etat_quotidien/' . $filename . '.xls', $tableau);

            // Pour regeneration on die avant l'envoie du mail

            //
            //************************************//
            //*** ENVOI DU MAIL ETAT QUOTIDIEN ***//
            //************************************//
            // destinataire
            $this->settings->get('Adresse notification etat quotidien', 'type');
            $destinataire = $this->settings->value;
            //$destinataire = 'd.courtier@equinoa.com';
            // Recuperation du modele de mail
            $this->mails_text->get('notification-etat-quotidien', 'lang = "' . $this->language . '" AND type');

            $surl = $this->surl;
            $url = $this->lurl;

            $sujetMail = $this->mails_text->subject;
            eval("\$sujetMail = \"$sujetMail\";");

            $texteMail = $this->mails_text->content;
            eval("\$texteMail = \"$texteMail\";");

            $exp_name = $this->mails_text->exp_name;
            eval("\$exp_name = \"$exp_name\";");

            // Nettoyage de printemps
            $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
            $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

            $this->email = $this->loadLib('email');
            $this->email->setFrom($this->mails_text->exp_email, $exp_name);
            $this->email->attachFromString($tableau, $filename . '.xls');
            $this->email->addRecipient(trim($destinataire));
            $this->email->setSubject('=?UTF-8?B?' . base64_encode($sujetMail) . '?=');
            $this->email->setHTMLBody($texteMail);

            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);

            $this->stopCron();
        }
    }

    // check  le 1 er et le 15 du mois si y a un virement a faire  (1h du matin)
    public function _retraitUnilend()
    {
        if (true === $this->startCron('retraitUnilend', 5)) {

            $jour = date('d');

            $datesVirements = array(1, 15);

            if (in_array($jour, $datesVirements)) {

                // chargement des datas
                $virements    = $this->loadData('virements');
                $bank_unilend = $this->loadData('bank_unilend');
                $transactions = $this->loadData('transactions');

                // 3%+tva  + les retraits Unilend
                $comProjet = $bank_unilend->sumMontant('status IN(0,3) AND type IN(0,3) AND retrait_fiscale = 0');
                // com sur remb
                $comRemb = $bank_unilend->sumMontant('status = 1 AND type IN(1,2)');

                $etatRemb = $bank_unilend->sumMontantEtat('status = 1 AND type IN(2)');

                // On prend la com projet + la com sur les remb et on retire la partie pour l'etat
                echo $total = $comRemb + $comProjet - $etatRemb;

                if ($total > 0) {
                    // On enregistre la transaction
                    $transactions->id_client        = 0;
                    $transactions->montant          = $total;
                    $transactions->id_langue        = 'fr';
                    $transactions->date_transaction = date('Y-m-d H:i:s');
                    $transactions->status           = '1';
                    $transactions->etat             = '1';
                    $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                    $transactions->type_transaction = 11; // virement Unilend (retrait)
                    $transactions->transaction      = 1; // transaction virtuelle
                    $transactions->id_transaction   = $transactions->create();

                    // on créer le virement
                    $virements->id_client      = 0;
                    $virements->id_project     = 0;
                    $virements->id_transaction = $transactions->id_transaction;
                    $virements->montant        = $total;
                    $virements->motif          = 'UNILEND_' . date('dmY');
                    $virements->type           = 4; // Unilend
                    $virements->status         = 0;
                    $virements->id_virement    = $virements->create();

                    // on enregistre le mouvement
                    $bank_unilend->id_transaction         = $transactions->id_transaction;
                    $bank_unilend->id_echeance_emprunteur = 0;
                    $bank_unilend->id_project             = 0;
                    $bank_unilend->montant                = '-' . $total;
                    $bank_unilend->type                   = 3;
                    $bank_unilend->status                 = 3;
                    $bank_unilend->create();
                }
            }

            $this->stopCron();
        }
    }

    // passe a 1h30 (pour decaler avec l'etat fiscal) du matin le 1er du mois
    public function _echeances_par_mois()
    {
        if (true === $this->startCron('echeances_par_mois', 5)) {

            $dateMoins1Mois = mktime(date("H"), date("i"), 0, date("m") - 1, date("d"), date("Y"));
            $dateMoins1Mois = date('Y-m', $dateMoins1Mois);

            $csv = "id_client;id_lender_account;type;iso_pays;exonere;debut_exoneration;fin_exoneration;id_project;id_loan;ordre;montant;capital;interets;prelevements_obligatoires;retenues_source;csg;prelevements_sociaux;contributions_additionnelles;prelevements_solidarite;crds;date_echeance;date_echeance_reel;status_remb_preteur;date_echeance_emprunteur;date_echeance_emprunteur_reel;\n";

            $sql = '
		SELECT
			c.id_client,
		   la.id_lender_account,
		   c.type,
		   (IFNULL
		   		(
				   (IFNULL	(
								(
									SELECT p.iso
									FROM lenders_imposition_history lih
										JOIN pays_v2 p ON p.id_pays = lih.id_pays
									WHERE lih.added <= e.date_echeance_reel
									AND lih.id_lender = e.id_lender
									ORDER BY lih.added DESC
									LIMIT 1
								)

								,p.iso
							)
					), "FR"
				)
			)as iso_pays,
		   la.exonere,
		   la.debut_exoneration,
		   la.fin_exoneration,
		   e.id_project,
		   e.id_loan,
		   e.ordre,
		   e.montant,
		   e.capital,
		   e.interets,
		   e.prelevements_obligatoires,
		   e.retenues_source,
		   e.csg,
		   e.prelevements_sociaux,
		   e.contributions_additionnelles,
		   e.prelevements_solidarite,
		   e.crds,
		   e.date_echeance,
		   e.date_echeance_reel,
		   e.status,
		   e.date_echeance_emprunteur,
		   e.date_echeance_emprunteur_reel
		FROM echeanciers e
		LEFT JOIN lenders_accounts la  ON la.id_lender_account = e.id_lender
		LEFT JOIN clients c ON c.id_client = la.id_client_owner
		LEFT JOIN clients_adresses ca ON ca.id_client = c.id_client

		LEFT JOIN pays_v2 p ON p.id_pays = ca.id_pays_fiscal
		WHERE LEFT(e.date_echeance_reel,7) = "' . $dateMoins1Mois . '"
                AND e.status = 1
                AND e.status_ra = 0 /*on ne veut pas de remb anticipe */
                ORDER BY e.date_echeance ASC';

            $resultat = $this->bdd->query($sql);
            while ($record = $this->bdd->fetch_array($resultat)) {
                for ($i = 0; $i <= 23; $i++) {
                    $csv .= str_replace('.', ',', $record[$i]) . ";";
                }
                $csv .= "\n";
            }

            $filename = 'echeances_' . date('Ymd');

            file_put_contents($this->path . 'protected/sftp/etat_fiscal/' . $filename . '.csv', $csv);
            // Enregistrement sur le sftp
            $connection = ssh2_connect('ssh.reagi.com', 22);
            ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
            $sftp       = ssh2_sftp($connection);
            $sftpStream = @fopen('ssh2.sftp://' . $sftp . '/home/sfpmei/emissions/etat_fiscal/' . $filename . '.csv', 'w');
            fwrite($sftpStream, $csv);
            fclose($sftpStream);

            $this->stopCron();
        }
        die;
    }

    // passe a 1h du matin le 1er du mois
    public function _etat_fiscal()
    {
        if (true === $this->startCron('etat_fiscal', 5)) {

            $echeanciers  = $this->loadData('echeanciers');
            $bank_unilend = $this->loadData('bank_unilend');
            $transactions = $this->loadData('transactions');

            // EQ-Acompte d'impôt sur le revenu
            $this->settings->get("EQ-Acompte d'impôt sur le revenu", 'type');
            $prelevements_obligatoires = $this->settings->value * 100;

            // EQ-Contribution additionnelle au Prélèvement Social
            $this->settings->get('EQ-Contribution additionnelle au Prélèvement Social', 'type');
            $txcontributions_additionnelles = $this->settings->value * 100;

            // EQ-CRDS
            $this->settings->get('EQ-CRDS', 'type');
            $txcrds = $this->settings->value * 100;

            // EQ-CSG
            $this->settings->get('EQ-CSG', 'type');
            $txcsg = $this->settings->value * 100;

            // EQ-Prélèvement de Solidarité
            $this->settings->get('EQ-Prélèvement de Solidarité', 'type');
            $txprelevements_solidarite = $this->settings->value * 100;

            // EQ-Prélèvement social
            $this->settings->get('EQ-Prélèvement social', 'type');
            $txprelevements_sociaux = $this->settings->value * 100;

            // EQ-Retenue à la source
            $this->settings->get('EQ-Retenue à la source', 'type');
            $tauxRetenuSoucre = $this->settings->value * 100;

            $jour = date("d");
            $mois = date("m");
            // test ////// - Ne pas mettre de "0" pour 08 par exemple
            //$mois = 8;
            // fin test //
            $annee = date("Y");

            $dateDebutTime = mktime(0, 0, 0, $mois - 1, 1, $annee);

            $dateDebutSql = date('Y-m-d', $dateDebutTime);
            $dateDebut    = date('d/m/Y', $dateDebutTime);

            $dateFinTime = mktime(0, 0, 0, $mois, 0, $annee);

            $dateFinSql = date('Y-m-d', $dateFinTime);
            $dateFin    = date('d/m/Y', $dateFinTime);

            //////////////////////
            // personnes morale //

            $Morale1  = $echeanciers->getEcheanceBetweenDates($dateDebutSql, $dateFinSql, '0', '2'); // entreprises
            $etranger = $echeanciers->getEcheanceBetweenDatesEtranger($dateDebutSql, $dateFinSql); // etrangers

            $MoraleInte = ($Morale1['interets'] / 100) + ($etranger['interets'] / 100);

            // on recup les personnes morales et les personnes physique exonéré
            //$InteRetenuSoucre = $PhysiqueExoInte + $MoraleInte;
            $InteRetenuSoucre = $MoraleInte;

            //$prelevementRetenuSoucre = $PhysiqueExo['retenues_source'] + $Morale['retenues_source'];
            $prelevementRetenuSoucre = $Morale1['retenues_source'] + $etranger['retenues_source'];

            /////////////////////
            //////////////////////////
            // Physique non exoneré //
            $PhysiqueNoExo     = $echeanciers->getEcheanceBetweenDates($dateDebutSql, $dateFinSql, '0', '1');
            $PhysiqueNoExoInte = ($PhysiqueNoExo['interets'] / 100) - ($etranger['interets'] / 100);

            // prelevements pour physiques non exonéré
            $lesPrelevSurPhysiqueNoExo = $PhysiqueNoExo['prelevements_obligatoires'] - $etranger['prelevements_obligatoires'];

            ////////////////////////
            /////////////////////////////////////////
            // Physique non exoneré dans la peride //
            $PhysiqueNonExoPourLaPeriode = $echeanciers->getEcheanceBetweenDates_exonere_mais_pas_dans_les_dates($dateDebutSql, $dateFinSql, '1', '1');
            $PhysiqueNoExoInte += ($PhysiqueNonExoPourLaPeriode['interets'] / 100);

            // prelevements pour physiques non exonéré
            $lesPrelevSurPhysiqueNoExo += $PhysiqueNonExoPourLaPeriode['prelevements_obligatoires'];

            ////////////////////////
            //////////////////////
            // Physique exoneré //
            $PhysiqueExo     = $echeanciers->getEcheanceBetweenDates($dateDebutSql, $dateFinSql, '1', '1');
            $PhysiqueExoInte = ($PhysiqueExo['interets'] / 100);

            // prelevements pour physiques exonéré
            $lesPrelevSurPhysiqueExo = $PhysiqueExo['prelevements_obligatoires'];

            //////////////////////
            //////////////
            // Physique //
            $Physique     = $echeanciers->getEcheanceBetweenDates($dateDebutSql, $dateFinSql, '', '1');
            $PhysiqueInte = ($Physique['interets'] / 100) - ($etranger['interets'] / 100);

            // prelevements pour physiques
            $lesPrelevSurPhysique = $Physique['prelevements_obligatoires'] - $etranger['prelevements_obligatoires'];

            $csg                          = $Physique['csg'] - $etranger['csg'];
            $prelevements_sociaux         = $Physique['prelevements_sociaux'] - $etranger['prelevements_sociaux'];
            $contributions_additionnelles = $Physique['contributions_additionnelles'] - $etranger['contributions_additionnelles'];
            $prelevements_solidarite      = $Physique['prelevements_solidarite'] - $etranger['prelevements_solidarite'];
            $crds                         = $Physique['crds'] - $etranger['crds'];

            $table = '

		<style>
			table th,table td{width:80px;height:20px;border:1px solid black;}
			table td.dates{text-align:center;}
			.right{text-align:right;}
			.center{text-align:center;}
			.boder-top{border-top:1px solid black;}
			.boder-bottom{border-bottom:1px solid black;}
			.boder-left{border-left:1px solid black;}
			.boder-right{border-right:1px solid black;}
		</style>

        <table border="1" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 650px;">
        	<tr>
            	<th colspan="4">UNILEND</th>
            </tr>
            <tr>
            	<th style="background-color:#C9DAF2;">Période :</th>
                <th style="background-color:#C9DAF2;">' . $dateDebut . '</th>
                <th style="background-color:#C9DAF2;">au</th>
                <th style="background-color:#C9DAF2;">' . $dateFin . '</th>
            </tr>

			<tr>
            	<th style="background-color:#ECAEAE;" colspan="4">Prélèvements obligatoires</th>
            </tr>
			<tr>
            	<th>&nbsp;</th>
                <th style="background-color:#F4F3DA;">Base (Intérêts bruts)</th>
                <th style="background-color:#F4F3DA;">Montant prélèvements</th>
                <th style="background-color:#F4F3DA;">Taux</th>
            </tr>
			<tr>
				<th style="background-color:#E6F4DA;">Soumis au prélèvement</th>
				<td class="right">' . $this->ficelle->formatNumber($PhysiqueNoExoInte) . '</td>
				<td class="right">' . $this->ficelle->formatNumber($lesPrelevSurPhysiqueNoExo) . '</td>
				<td style="background-color:#DDDAF4;" class="right">' . $this->ficelle->formatNumber($prelevements_obligatoires) . '%</td>
			</tr>
			<tr>
				<th style="background-color:#E6F4DA;">Dispensé</th>
				<td class="right">' . $this->ficelle->formatNumber($PhysiqueExoInte) . '</td>
				<td class="right">' . $this->ficelle->formatNumber($lesPrelevSurPhysiqueExo) . '</td>
				<td style="background-color:#DDDAF4;" class="right">' . $this->ficelle->formatNumber(0) . '%</td>
			</tr>
			<tr>
				<th style="background-color:#E6F4DA;">Total</th>
				<td class="right">' . $this->ficelle->formatNumber($PhysiqueInte) . '</td>
				<td class="right">' . $this->ficelle->formatNumber($lesPrelevSurPhysique) . '</td>
				<td style="background-color:#DDDAF4;" class="right">' . $this->ficelle->formatNumber($prelevements_obligatoires) . '%</td>
			</tr>

			<tr>
            	<th style="background-color:#ECAEAE;" colspan="4">Retenue à la source</th>
            </tr>
			<tr>
				<th style="background-color:#E6F4DA;">Retenue à la source</th>
				<td class="right">' . $this->ficelle->formatNumber($InteRetenuSoucre) . '</td>
				<td class="right">' . $this->ficelle->formatNumber($prelevementRetenuSoucre) . '</td>
				<td style="background-color:#DDDAF4;" class="right">' . $this->ficelle->formatNumber($tauxRetenuSoucre) . '%</td>
			</tr>

			<tr>
            	<th style="background-color:#ECAEAE;" colspan="4">Prélèvements sociaux</th>
            </tr>
			<tr>
				<th style="background-color:#E6F4DA;">CSG</th>
				<td class="right">' . $this->ficelle->formatNumber($PhysiqueInte) . '</td>
				<td class="right">' . $this->ficelle->formatNumber($csg) . '</td>
				<td style="background-color:#DDDAF4;" class="right">' . $this->ficelle->formatNumber($txcsg) . '%</td>
			</tr>
			<tr>
				<th style="background-color:#E6F4DA;">Prélèvement social</th>
				<td class="right">' . $this->ficelle->formatNumber($PhysiqueInte) . '</td>
				<td class="right">' . $this->ficelle->formatNumber($prelevements_sociaux) . '</td>
				<td style="background-color:#DDDAF4;" class="right">' . $this->ficelle->formatNumber($txprelevements_sociaux) . '%</td>
			</tr>
			<tr>
				<th style="background-color:#E6F4DA;">Contribution additionnelle</th>
				<td class="right">' . $this->ficelle->formatNumber($PhysiqueInte) . '</td>
				<td class="right">' . $this->ficelle->formatNumber($contributions_additionnelles) . '</td>
				<td style="background-color:#DDDAF4;" class="right">' . $this->ficelle->formatNumber($txcontributions_additionnelles) . '%</td>
			</tr>
			<tr>
				<th style="background-color:#E6F4DA;">Prélèvement de solidarité</th>
				<td class="right">' . $this->ficelle->formatNumber($PhysiqueInte) . '</td>
				<td class="right">' . $this->ficelle->formatNumber($prelevements_solidarite) . '</td>
				<td style="background-color:#DDDAF4;" class="right">' . $this->ficelle->formatNumber($txprelevements_solidarite) . '%</td>
			</tr>
			<tr>
				<th style="background-color:#E6F4DA;">CRDS</th>
				<td class="right">' . $this->ficelle->formatNumber($PhysiqueInte) . '</td>
				<td class="right">' . $this->ficelle->formatNumber($crds) . '</td>
				<td style="background-color:#DDDAF4;" class="right">' . $this->ficelle->formatNumber($txcrds) . '%</td>
			</tr>
        </table>
		';

            echo utf8_decode($table);

            $filename = 'Unilend_etat_fiscal_' . date('Ymd');
            file_put_contents($this->path . 'protected/sftp/etat_fiscal/' . $filename . '.xls', $table);

            // Enregistrement sur le sftp
            $connection = ssh2_connect('ssh.reagi.com', 22);
            ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
            $sftp       = ssh2_sftp($connection);
            $sftpStream = @fopen('ssh2.sftp://' . $sftp . '/home/sfpmei/emissions/etat_fiscal/' . $filename . '.xls', 'w');
            fwrite($sftpStream, $table);
            fclose($sftpStream);

            //************************************//
            //*** ENVOI DU MAIL ETAT FISCAL + echeances mois ***//
            //************************************//
            // destinataire
            //$destinataire = 'd.courtier@equinoa.com';
            // Recuperation du modele de mail
            $this->mails_text->get('notification-etat-fiscal', 'lang = "' . $this->language . '" AND type');

            $surl = $this->surl;
            $url  = $this->lurl;

            $sujetMail = $this->mails_text->subject;
            eval("\$sujetMail = \"$sujetMail\";");

            $texteMail = $this->mails_text->content;
            eval("\$texteMail = \"$texteMail\";");

            $exp_name = $this->mails_text->exp_name;
            eval("\$exp_name = \"$exp_name\";");

            // Nettoyage de printemps
            $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
            $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

            $this->email = $this->loadLib('email');
            $this->email->setFrom($this->mails_text->exp_email, $exp_name);
            $this->email->attachFromString($table, $filename . '.xls');
            //$this->email->attachFromString($csv,'echeances_'.date('Y-m-d').'.csv');

            if ($this->Config['env'] == 'prod') {
                //$this->email->addRecipient('d.courtier@equinoa.com');
                $this->settings->get('Adresse notification etat fiscal', 'type');
                $this->email->addRecipient($this->settings->value);
            } else {
                foreach ($this->Config['DebugMailIt'] as $sEmailDebug) {
                    $this->email->addRecipient($sEmailDebug);
                }
            }

            $this->email->setSubject('=?UTF-8?B?' . base64_encode($sujetMail) . '?=');
            $this->email->setHTMLBody($texteMail);
            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
            /////////////////////////////////////////////////////
            // On retire de bank unilend la partie  pour letat //
            /////////////////////////////////////////////////////

            $dateRembtemp = mktime(date("H"), date("i"), date("s"), date("m") - 1, date("d"), date("Y"));
            $dateRemb     = date("Y-m", $dateRembtemp);
            $dateRembM    = date("m", $dateRembtemp);
            $dateRembY    = date("Y", $dateRembtemp);
            $etatRemb     = $bank_unilend->sumMontantEtat('status = 1 AND type IN(2) AND LEFT(added,7) = "' . $dateRemb . '"');

            // 13 regul commission
            $regulCom = $transactions->sumByday(13, $dateRembM, $dateRembY);

            $sommeRegulDuMois = 0;
            foreach ($regulCom as $r) {
                $sommeRegulDuMois += $r['montant_unilend'] * 100;
            }

            $etatRemb += $sommeRegulDuMois;

            if ($etatRemb > 0) {
                // on créer un transaction sortante
                // On enregistre la transaction
                $transactions->id_client        = 0;
                $transactions->montant          = $etatRemb;
                $transactions->id_langue        = 'fr';
                $transactions->date_transaction = date('Y-m-d H:i:s');
                $transactions->status           = '1';
                $transactions->etat             = '1';
                $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                $transactions->type_transaction = 12; // virement etat (retrait)
                $transactions->transaction      = 1; // transaction virtuelle
                $transactions->id_transaction   = $transactions->create();

                // on enregistre le mouvement
                $bank_unilend->id_transaction         = $transactions->id_transaction;
                $bank_unilend->id_echeance_emprunteur = 0;
                $bank_unilend->id_project             = 0;
                $bank_unilend->montant                = '-' . $etatRemb;
                $bank_unilend->type                   = 3;
                $bank_unilend->status                 = 3;
                $bank_unilend->retrait_fiscale        = 1;
                $bank_unilend->create();
            }

            $this->stopCron();
        }
    }

    // part une fois par jour a 1h du matin afin de checker les mail de la veille
    public function _checkMailNoDestinataire()
    {
        if (true === $this->startCron('checkMailNoDestinataire', 5)) {

            $nmp  = $this->loadData('nmp');
            $date = mktime(0, 0, 0, date("m"), date("d") - 1, date("Y"));
            $date = date('Y-m-d', $date);

            $lNoMail = $nmp->select('mailto = "" AND added LIKE "' . $date . '%"');

            if ($lNoMail != false) {
                foreach ($lNoMail as $m) {
                    $to      = 'unilend@equinoa.fr';
                    $subject = '[Alerte] Mail Sans destinataire';

                    $message = '
				<html>
				<head>
				  <title>[Alerte] Mail Sans destinataire</title>
				</head>
				<body>
					<p>Un mail a ete envoye sans destinataire</p>
					<table>
						<tr>
							<th>id_nmp : </th><td>' . $m['id_nmp'] . '</td>
						</tr>
					</table>
				</body>
				</html>
				';

                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                    $headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
                    if ($this->Config['env'] != "dev") {
                        mail($to, $subject, $message, $headers);
                    } else {
                        mail($this->sDestinatairesDebug, $subject, $message, $this->sHeadersDebug);
                    }
                }
            }

            $this->stopCron();
        }
    }

    // Toutes les minutes de 21h à 7h
    public function _declarationContratPret()
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300); //300 seconds = 5 minutes

        if (true === $this->startCron('declarationContratPret', 5)) {

            $loans    = $this->loadData('loans');
            $projects = $this->loadData('projects');

            $lProjects = $projects->selectProjectsByStatus('80,90,100,110,120');

            if (count($lProjects) > 0) {
                $a          = 0;
                $lesProjets = '';
                foreach ($lProjects as $p) {
                    $lesProjets .= ($a == 0 ? '' : ',') . $p['id_project'];
                    $a++;
                }

                // On recupere que le premier loan
                $lLoans = $loans->select('status = "0" AND fichier_declarationContratPret = "" AND id_project IN(' . $lesProjets . ')', 'id_loan ASC', 0, 10);
                if (count($lLoans) > 0) {
                    foreach ($lLoans as $l) {
                        $projects->get($l['id_project'], 'id_project');

                        $path = $this->path . 'protected/declarationContratPret/' . substr($l['added'], 0, 4) . '/' . $projects->slug . '/';
                        $nom = 'Unilend_declarationContratPret_' . $l['id_loan'] . '.pdf';

                        $oCommandPdf = new Command('pdf', 'declarationContratPret_html', array(
                            $l['id_loan'], $path
                        ), $this->language);
                        $oPdf        = new pdfController($oCommandPdf, $this->Config, 'default');
                        $oPdf->_declarationContratPret_html($l['id_loan'], $path);

                        $loans->get($l['id_loan'], 'id_loan');
                        $loans->fichier_declarationContratPret = $nom;
                        $loans->update();
                    }
                }
                echo "Toutes les d&eacute;clarations sont g&eacute;n&eacute;r&eacute;es <br />";
            }

            $this->stopCron();
        }
    }

    /////////////////////////
    /// POUR LA DEMO ONLY ///
    /////////////////////////
    // On copie le backup recu pas oxeva
    public function copyBackup()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;
        $this->autoFireView   = false;

        if ($this->Config['env'] != 'demo') {
            die;
        }

        // Dossier backup
        $backup = $this->path . 'backup/';

        $backup2 = $this->path . 'backup2/';

        /////////////////////////////////////////////////
        // On parcour le dossier backup2 pour le vider //
        /////////////////////////////////////////////////

        $dir = opendir($backup2);

        while ($file = readdir($dir)) {
            // On retire les dossiers et les "." ".." ainsi que le fichier schemas.sql
            if ($file != '.' && $file != '..' && !is_dir($backup2 . $file)) {
                // On reverifie si on a bien le fichier GZ
                if (file_exists($backup2 . $file)) {
                    unlink($backup2 . $file);
                }
                // Fin fichier sql.gz
            }
        }

        closedir($dir);

        // On parcours le dossier backup rempli par oxeva //
        $dir = opendir($backup);

        while ($file = readdir($dir)) {
            // On retire les dossiers et les "." ".." ainsi que le fichier schemas.sql
            if ($file != '.' && $file != '..' && !is_dir($backup . $file)) {
                // On reverifie si on a bien le fichier
                if (file_exists($backup . $file)) {
                    // On le copie dans backup2
                    copy($backup . $file, $backup2 . $file);
                }
            }
        }
        closedir($dir);
    }

    // Mise a jour de la bdd demo tous les jours a 2h du matin
    public function _updateDemoBDD()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;
        $this->autoFireView   = false;

        if ($this->Config['env'] != 'demo') {
            $this->stopCron();
            die;
        }

        // On copie le backup oxeva dans backup2
        $this->copyBackup();

        // Dossier backup
        $dirname = $this->path . 'backup2/';

        // Informations pour la connexion à la BDD
        $mysqlDatabaseName = $this->Config['bdd_config'][$this->Config['env']]['BDD'];
        $mysqlUserName     = $this->Config['bdd_config'][$this->Config['env']]['USER'];
        $mysqlPassword     = $this->Config['bdd_config'][$this->Config['env']]['PASSWORD'];
        $mysqlHostName     = $this->Config['bdd_config'][$this->Config['env']]['HOST'];

        // Si on a un fichier schemas.sql (permet de supprimer et de recrer les tables)
        if (file_exists($dirname . 'schemas.sql')) {
            // chemin fichier
            $mysqlImportFilename = $dirname . 'schemas.sql';

            // Commande
            $command = 'mysql -h' . $mysqlHostName . ' -u' . $mysqlUserName . ' -p' . $mysqlPassword . ' ' . $mysqlDatabaseName . ' < ' . $mysqlImportFilename;

            // Exec commande
            exec($command, $output = array(), $worked);

            switch ($worked) {
                case 0:
                    echo 'IMPORT SCHEMAS.SQL OK<br>';
                    break;
                case 1:
                    echo 'IMPORT SCHEMAS.SQL NOK<br>';
                    break;
            }
        }
        echo '---------------<br>';

        $dir = opendir($dirname);

        while ($fileGZ = readdir($dir)) {
            // On retire les dossiers et les "." ".." ainsi que le fichier schemas.sql
            if ($fileGZ != '.' && $fileGZ != '..' && !is_dir($dirname . $fileGZ) && $fileGZ != 'schemas.sql') {
                // On reverifie si on a bien le fichier GZ
                if (file_exists($dirname . $fileGZ)) {
                    $fichierGZ = $dirname . $fileGZ;
                    $file      = str_replace('.gz', '', $fileGZ);
                    $fichier   = $dirname . $file;

                    $command = "gunzip " . $fichierGZ;
                    exec($command, $output = array(), $worked);
                    switch ($worked) {
                        case 0:
                            echo 'GUNZIP ' . $fileGZ . ' OK<br>';
                            break;
                        case 1:
                            echo 'GUNZIP ' . $fileGZ . ' NOK<br>';
                            break;
                    }

                    if (file_exists($fichier)) {
                        $mysqlImportFilename = $fichier;

                        $command = 'mysql -h' . $mysqlHostName . ' -u' . $mysqlUserName . ' -p' . $mysqlPassword . ' ' . $mysqlDatabaseName . ' < ' . $mysqlImportFilename;
                        exec($command, $output = array(), $worked);

                        switch ($worked) {
                            case 0:
                                echo 'IMPORT ' . $file . ' OK<br>';
                                break;
                            case 1:
                                echo 'IMPORT ' . $file . ' NOK<br>';
                                break;
                        }
                    }
                }
            }
            echo '----------------<br>';
        }

        closedir($dir);

        // Mise a jour des données //
        // On change l'adresse mail de tout les clients
        $this->bdd->query("UPDATE clients SET email = 'DCourtier.Auto@equinoa.fr'");
        // Et on change l'adresse de notifiaction
        $this->bdd->query("UPDATE settings SET value = 'DCourtier.Auto@equinoa.fr' WHERE id_setting = 44");
        // email facture
        $this->bdd->query("UPDATE companies SET email_facture = 'DCourtier.Auto@equinoa.fr'");

        // Email pour prevenir de la mise a jour //
        $to      = 'unilend@equinoa.fr';
        $subject = '[UNILEND DEMO] La BDD a ete mise à jour';
        $message = '
		<html>
		<head>
		  <title>[UNILEND DEMO] La BDD a ete mise à jour</title>
		</head>
		<body>
		  <p>[UNILEND DEMO] La BDD a ete mise à jour</p>
		</body>
		</html>
		';

        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'To: equinoa <unilend@equinoa.fr>' . "\r\n";
        $headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";

        if ($this->Config['env'] != "dev") {
            mail($to, $subject, $message, $headers);
        } else {
            mail($this->sDestinatairesDebug, $subject, $message, $this->sHeadersDebug);
        }
    }

    // Toutes les minutes on check les bids pour les passer en ENCOURS/OK/NOK (check toutes les 5 min et toutes les minutes de 15h30 à 16h00)
    public function _checkBids()
    {
        if (true === $this->startCron('checkBids', 5)) {
            ini_set('max_execution_time', '300');
            ini_set('memory_limit', '1G');

            $this->projects                      = $this->loadData('projects');
            $this->projects_status               = $this->loadData('projects_status');
            $this->emprunteur                    = $this->loadData('clients');
            $this->companies                     = $this->loadData('companies');
            $this->bids                          = $this->loadData('bids');
            $this->lenders_accounts              = $this->loadData('lenders_accounts');
            $this->preteur                       = $this->loadData('clients');
            $this->notifications                 = $this->loadData('notifications');
            $this->wallets_lines                 = $this->loadData('wallets_lines');
            $this->bids_logs                     = $this->loadData('bids_logs');
            $this->offres_bienvenues_details     = $this->loadData('offres_bienvenues_details');
            $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications'); // add gestion alertes
            $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif'); // add gestion alertes

            foreach ($this->projects->selectProjectsByStatus(\projects_status::EN_FUNDING, ' AND p.status = 0') as $p) {
                $aLogContext     = array();
                $bids_logs       = false;
                $nb_bids_ko      = 0;
                $leSoldeE        = 0;
                $montantEmprunt  = $p['amount'];
                $nb_bids_encours = $this->bids->counter('id_project = ' . $p['id_project'] . ' AND status = 0');
                $total_bids      = $this->bids->counter('id_project = ' . $p['id_project']);
                $soldeBid        = $this->bids->getSoldeBid($p['id_project']);

                $this->bids_logs->debut      = date('Y-m-d H:i:s');
                $this->bids_logs->id_project = $p['id_project'];

                if ($soldeBid >= $montantEmprunt) {
                    foreach ($this->bids->select('id_project = ' . $p['id_project'] . ' AND status = 0', 'rate ASC, added ASC') as $e) {
                        if ($leSoldeE < $montantEmprunt) {
                            $leSoldeE += ($e['amount'] / 100);
                        } else { // Les bid qui depassent on leurs redonne leur argent et on met en ko
                            $bids_logs = true;
                            $this->bids->get($e['id_bid'], 'id_bid');

                            $this->bids->status = 2; // statut bid ko
                            $this->bids->update();

                            $this->lenders_accounts->get($e['id_lender_account'], 'id_lender_account');
                            $this->preteur->get($this->lenders_accounts->id_client_owner, 'id_client');

                            $this->transactions->id_client        = $this->lenders_accounts->id_client_owner;
                            $this->transactions->montant          = $e['amount'];
                            $this->transactions->id_langue        = 'fr';
                            $this->transactions->date_transaction = date('Y-m-d H:i:s');
                            $this->transactions->status           = '1';
                            $this->transactions->etat             = '1';
                            $this->transactions->id_project       = $p['id_project'];
                            $this->transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                            $this->transactions->type_transaction = 2;
                            $this->transactions->id_bid_remb      = $e['id_bid'];
                            $this->transactions->transaction      = 2; // transaction virtuelle
                            $this->transactions->create();

                            $this->wallets_lines->id_lender                = $e['id_lender_account'];
                            $this->wallets_lines->type_financial_operation = 20;
                            $this->wallets_lines->id_transaction           = $this->transactions->id_transaction;
                            $this->wallets_lines->status                   = 1;
                            $this->wallets_lines->type                     = 2;
                            $this->wallets_lines->id_bid_remb              = $e['id_bid'];
                            $this->wallets_lines->amount                   = $e['amount'];
                            $this->wallets_lines->id_project               = $p['id_project'];
                            $this->wallets_lines->create();

                            $this->notifications->type       = 1; // rejet
                            $this->notifications->id_lender  = $e['id_lender_account'];
                            $this->notifications->id_project = $p['id_project'];
                            $this->notifications->amount     = $e['amount'];
                            $this->notifications->id_bid     = $e['id_bid'];
                            $this->notifications->create();

                            $this->clients_gestion_mails_notif->id_client       = $this->lenders_accounts->id_client_owner;
                            $this->clients_gestion_mails_notif->id_notif        = 3; // rejet
                            $this->clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
                            $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                            $this->clients_gestion_mails_notif->id_transaction  = $this->transactions->id_transaction;
                            $this->clients_gestion_mails_notif->create();

                            $sumOffres = $this->offres_bienvenues_details->sum('id_client = ' . $this->lenders_accounts->id_client_owner . ' AND id_bid = ' . $e['id_bid'], 'montant');
                            if ($sumOffres > 0) {
                                // sum des offres inferieur au montant a remb
                                if ($sumOffres <= $e['amount']) {
                                    $this->offres_bienvenues_details->montant = $sumOffres;
                                } else {// Si montant des offres superieur au remb on remb le montant a crediter
                                    $this->offres_bienvenues_details->montant = $e['amount'];
                                }

                                $this->offres_bienvenues_details->id_offre_bienvenue = 0;
                                $this->offres_bienvenues_details->id_client          = $this->lenders_accounts->id_client_owner;
                                $this->offres_bienvenues_details->id_bid             = 0;
                                $this->offres_bienvenues_details->id_bid_remb        = $e['id_bid'];
                                $this->offres_bienvenues_details->status             = 0;
                                $this->offres_bienvenues_details->type               = 2;
                                $this->offres_bienvenues_details->create();
                            }
                            $nb_bids_ko++;
                        }

                        if (1 != $this->bids->checked) {
                            $this->bids->checked = 1;
                            $this->bids->update();
                        }
                    }

                    $aLogContext['Project ID']    = $p['id_project'];
                    $aLogContext['Balance']       = $soldeBid;
                    $aLogContext['Rejected bids'] = $nb_bids_ko;

                    // EMAIL EMPRUNTEUR FUNDE //
                    if ($p['status_solde'] == 0) {
                        $this->oLogger->addRecord(ULogger::INFO, 'Project funded - send email to borrower', array('Project ID' => $p['id_project']));

                        // Mise a jour du statut pour envoyer qu'une seule fois le mail a l'emprunteur
                        $this->projects->get($p['id_project'], 'id_project');
                        $this->projects->status_solde = 1;
                        $this->projects->update();

                        $this->settings->get('Facebook', 'type');
                        $lien_fb = $this->settings->value;

                        $this->settings->get('Twitter', 'type');
                        $lien_tw = $this->settings->value;

                        $this->settings->get('Heure fin periode funding', 'type');
                        $this->heureFinFunding = $this->settings->value;

                        $this->companies->get($p['id_company'], 'id_company');
                        $this->emprunteur->get($this->companies->id_client_owner, 'id_client');

                        $tab_date_retrait = explode(' ', $p['date_retrait_full']);
                        $tab_date_retrait = explode(':', $tab_date_retrait[1]);
                        $heure_retrait    = $tab_date_retrait[0] . ':' . $tab_date_retrait[1];

                        if ($heure_retrait == '00:00') {
                            $heure_retrait = $this->heureFinFunding;
                        }

                        $inter = $this->dates->intervalDates(date('Y-m-d H:i:s'), $p['date_retrait'] . ' ' . $heure_retrait . ':00');

                        if ($inter['mois'] > 0) {
                            $tempsRest = $inter['mois'] . ' mois';
                        } elseif ($inter['jours'] > 0) {
                            $tempsRest = $inter['jours'] . ' jours';
                        } elseif ($inter['heures'] > 0 && $inter['minutes'] >= 120) {
                            $tempsRest = $inter['heures'] . ' heures';
                        } elseif ($inter['minutes'] > 0 && $inter['minutes'] < 120) {
                            $tempsRest = $inter['minutes'] . ' min';
                        } else {
                            $tempsRest = $inter['secondes'] . ' secondes';
                        }

                        //*** ENVOI DU MAIL FUNDE EMPRUNTEUR ***//

                        $this->mails_text->get('emprunteur-dossier-funde', 'lang = "' . $this->language . '" AND type');

                        // Taux moyen pondéré
                        $montantHaut = 0;
                        $montantBas  = 0;
                        foreach ($this->bids->select('id_project = ' . $p['id_project'] . ' AND status = 0') as $b) {
                            $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                            $montantBas += ($b['amount'] / 100);
                        }
                        $taux_moyen = ($montantHaut / $montantBas);
                        $taux_moyen = $this->ficelle->formatNumber($taux_moyen);

                        $varMail = array(
                            'surl'                   => $this->surl,
                            'url'                    => $this->lurl,
                            'prenom_e'               => utf8_decode($this->emprunteur->prenom),
                            'taux_moyen'             => $taux_moyen,
                            'link_compte_emprunteur' => $this->lurl . '/synthese_emprunteur',
                            'temps_restant'          => $tempsRest,
                            'projet'                 => $p['title'],
                            'lien_fb'                => $lien_fb,
                            'lien_tw'                => $lien_tw
                        );

                        $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                        $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                        $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                        $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                        $this->email = $this->loadLib('email');
                        $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                        $this->email->setSubject(stripslashes($sujetMail));
                        $this->email->setHTMLBody(stripslashes($texteMail));

                        // Pas de mail si le compte est desactivé
                        if ($this->emprunteur->status == 1) {
                            if ($this->Config['env'] == 'prod') {
                                Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->emprunteur->email, $tabFiler);
                                $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                            } else {
                                $this->email->addRecipient(trim($this->emprunteur->email));
                                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                            }
                        }
                        //*** ENVOI DU MAIL NOTIFICATION FUNDE 100% ***//

                        $this->settings->get('Adresse notification projet funde a 100', 'type');
                        $destinataire = $this->settings->value;

                        $nbPeteurs = $this->bids->getNbPreteurs($p['id_project']);

                        $this->mails_text->get('notification-projet-funde-a-100', 'lang = "' . $this->language . '" AND type');

                        $surl = $this->surl;
                        $url = $this->lurl;
                        $id_projet = $p['id_project'];
                        $title_projet = utf8_decode($p['title']);
                        $nbPeteurs = $nbPeteurs;
                        $tx = $taux_moyen;
                        $periode = $tempsRest;

                        $sujetMail = htmlentities($this->mails_text->subject);
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

                        $this->email->setSubject('=?UTF-8?B?' . base64_encode(html_entity_decode($sujetMail)) . '?=');
                        $this->email->setHTMLBody($texteMail);
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }
                }

                if ($bids_logs == true) {
                    $this->bids_logs->nb_bids_encours = $nb_bids_encours;
                    $this->bids_logs->nb_bids_ko      = $nb_bids_ko;
                    $this->bids_logs->total_bids      = $total_bids;
                    $this->bids_logs->total_bids_ko   = $this->bids->counter('id_project = ' . $p['id_project'] . ' AND status = 2');
                    $this->bids_logs->fin             = date('Y-m-d H:i:s');
                    $this->bids_logs->create();
                }

                $this->oLogger->addRecord(ULogger::INFO, 'Project ID: ' . $p['id_project'], $aLogContext);
            }

            $this->stopCron();
        }
    }

    // On check bid ko si oui ou non un mail de degel est parti. Si c'est non on envoie un mail
    public function _checkEmailBidKO()
    {
        if (true === $this->startCron('checkEmailBidKO', 1)) {

            // On fait notre cron toutes les  5 minutes et toutes les minutes entre 15h30 et 16h00
            $les5    = array(0, 05, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55);
            $minutes = date('i');
            $dateDeb = mktime(15, 30, 0, date('m'), date('d'), date('Y'));
            $dateFin = mktime(16, 00, 0, date('m'), date('d'), date('Y'));

            if (in_array($minutes, $les5) || time() >= $dateDeb && time() <= $dateFin) {
                $this->projects                      = $this->loadData('projects');
                $this->projects_status               = $this->loadData('projects_status');
                $this->emprunteur                    = $this->loadData('clients');
                $this->companies                     = $this->loadData('companies');
                $this->bids                          = $this->loadData('bids');
                $this->lenders_accounts              = $this->loadData('lenders_accounts');
                $this->preteur                       = $this->loadData('clients');
                $this->notifications                 = $this->loadData('notifications');
                $this->wallets_lines                 = $this->loadData('wallets_lines');
                $this->bids_logs                     = $this->loadData('bids_logs');
                $this->current_projects_status       = $this->loadData('projects_status');
                $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
                $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
                $this->transactions                  = $this->loadData('transactions');

                $this->settings->get('Facebook', 'type');
                $lien_fb = $this->settings->value;

                $this->settings->get('Twitter', 'type');
                $lien_tw = $this->settings->value;

                $this->settings->get('Heure fin periode funding', 'type');
                $this->heureFinFunding = $this->settings->value;

                $iEmailsSent  = 0;
                $iBidsUpdated = 0;
                $lBidsKO = $this->bids->select('status = 2 AND status_email_bid_ko = 0');

                foreach ($lBidsKO as $e) {
                    // On check si on a pas de changement en cours de route
                    $this->bids->get($e['id_bid'], 'id_bid');
                    $this->current_projects_status->getLastStatut($e['id_project']);

                    // si pas de mail est que le projet est statut "enfunding", "fundé", "rembourssement"
                    if (
                        $this->bids->status_email_bid_ko == '0'
                        && in_array($this->current_projects_status->status, array(\projects_status::EN_FUNDING, \projects_status::FUNDE, \projects_status::REMBOURSEMENT))
                    ) {

                        $this->lenders_accounts->get($e['id_lender_account'], 'id_lender_account');
                        $this->preteur->get($this->lenders_accounts->id_client_owner, 'id_client');

                        ++$iBidsUpdated;

                        if ($this->clients_gestion_notifications->getNotif($this->preteur->id_client, 3, 'immediatement') == true) {
                            $this->transactions->get($e['id_bid'], 'id_bid_remb');
                            $this->clients_gestion_mails_notif->get($this->transactions->id_transaction, 'id_client = ' . $this->preteur->id_client . ' AND id_transaction');
                            $this->clients_gestion_mails_notif->immediatement = 1; // on met a jour le statut immediatement
                            $this->clients_gestion_mails_notif->update();

                            $this->bids->status_email_bid_ko = 1;
                            $this->bids->update();

                            $this->projects->get($e['id_project'], 'id_project');
                            $this->companies->get($this->projects->id_company, 'id_company');

                            $tab_date_retrait = explode(' ', $this->projects->date_retrait_full);
                            $tab_date_retrait = explode(':', $tab_date_retrait[1]);
                            $heure_retrait    = $tab_date_retrait[0] . ':' . $tab_date_retrait[1];

                            if ($heure_retrait == '00:00') {
                                $heure_retrait = $this->heureFinFunding;
                            }

                            $inter = $this->dates->intervalDates(date('Y-m-d H:i:s'), $this->projects->date_retrait . ' ' . $heure_retrait . ':00');
                            if ($inter['mois'] > 0) {
                                $tempsRest = $inter['mois'] . ' mois';
                            } elseif ($inter['jours'] > 0) {
                                $tempsRest = $inter['jours'] . ' jours';
                                if ($inter['jours'] == 1) {
                                    $tempsRest = $inter['jours'] . ' jour';
                                }
                            } elseif ($inter['heures'] > 0 && $inter['minutes'] >= 120) {
                                $tempsRest = $inter['heures'] . ' heures';
                            } elseif ($inter['minutes'] > 0 && $inter['minutes'] < 120) {
                                $tempsRest = $inter['minutes'] . ' min';
                            } else {
                                $tempsRest = $inter['secondes'] . ' secondes';
                            }

                            $p         = substr($this->ficelle->stripAccents(utf8_decode(trim($this->preteur->prenom))), 0, 1);
                            $nom       = $this->ficelle->stripAccents(utf8_decode(trim($this->preteur->nom)));
                            $id_client = str_pad($this->preteur->id_client, 6, 0, STR_PAD_LEFT);
                            $motif     = mb_strtoupper($id_client . $p . $nom, 'UTF-8');

                            $retrait = strtotime($this->projects->date_retrait . ' ' . $heure_retrait . ':00');

                            if ($retrait <= time()) {
                                $this->mails_text->get('preteur-bid-ko-apres-fin-de-periode-projet', 'lang = "' . $this->language . '" AND type');
                            } else {
                                $this->mails_text->get('preteur-bid-ko', 'lang = "' . $this->language . '" AND type');
                            }

                            $timedate_bid = strtotime($e['added']);
                            $month        = $this->dates->tableauMois['fr'][date('n', $timedate_bid)];

                            $varMail = array(
                                'surl'           => $this->surl,
                                'url'            => $this->lurl,
                                'prenom_p'       => $this->preteur->prenom,
                                'valeur_bid'     => $this->ficelle->formatNumber($e['amount'] / 100),
                                'taux_bid'       => $this->ficelle->formatNumber($e['rate']),
                                'nom_entreprise' => $this->companies->name,
                                'projet-p'       => $this->lurl . '/projects/detail/' . $this->projects->slug,
                                'date_bid'       => date('d', $timedate_bid) . ' ' . $month . ' ' . date('Y', $timedate_bid),
                                'heure_bid'      => $this->dates->formatDate($e['added'], 'H\hi'),
                                'fin_chrono'     => $tempsRest,
                                'projet-bid'     => $this->lurl . '/projects/detail/' . $this->projects->slug,
                                'motif_virement' => $motif,
                                'lien_fb'        => $lien_fb,
                                'lien_tw'        => $lien_tw
                            );

                            $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                            $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                            $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                            $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                            $this->email = $this->loadLib('email');
                            $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                            $this->email->setSubject(stripslashes($sujetMail));
                            $this->email->setHTMLBody(stripslashes($texteMail));

                            if ($this->preteur->status == 1) {
                                ++$iEmailsSent;

                                if ($this->Config['env'] == 'prod') {
                                    Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->preteur->email, $tabFiler);
                                    $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                                } else {
                                    $this->email->addRecipient(trim($this->preteur->email));
                                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                                }
                            }
                        } else {
                            $this->bids->status_email_bid_ko = 3; // On met un statut 3 pour eviter que le mail parte lorsque le preteur rechangera dans gestion alertes
                            $this->bids->update();
                        }
                    }
                }
            }

            $this->oLogger->addRecord(ULogger::INFO, 'Emails sent: ' . $iEmailsSent, array('ID' => $this->iStartTime));
            $this->oLogger->addRecord(ULogger::INFO, 'Bids updated: ' . $iBidsUpdated, array('ID' => $this->iStartTime));

            $this->stopCron();
        }
    }

    // a 16 h 10 (10 16 * * *)
    public function _checkFinProjet()
    {
        if (true === $this->startCron('checkFinProjet', 5)) {

            $projects       = $this->loadData('projects');
            $bids           = $this->loadData('bids');
            $loans          = $this->loadData('loans');
            $transactions   = $this->loadData('transactions');
            $projects_check = $this->loadData('projects_check');

            // 60 : fundé | on recup que ceux qui se sont terminé le jour meme
            $lProjets = $projects->selectProjectsByStatus('60', ' AND LEFT(p.date_fin,10) = "' . date('Y-m-d') . '"');

            foreach ($lProjets as $p) {
                if ($projects_check->get($p['id_project'], 'id_project')) {

                } else {
                    $montantBidsTotal = $bids->getSoldeBid($p['id_project']);
                    $montantBidsOK    = $bids->sum('id_project = ' . $p['id_project'] . ' AND status = 1', 'amount');
                    $montantBidsOK    = ($montantBidsOK / 100);
                    $montantBidsKO    = $bids->sum('id_project = ' . $p['id_project'] . ' AND status = 2', 'amount');
                    $montantBidsKO    = ($montantBidsKO / 100);

                    $montantLoans = $loans->sum('id_project = ' . $p['id_project'], 'amount');
                    $montantLoans = ($montantLoans / 100);

                    $montantTransTotal = $transactions->sum('id_project = ' . $p['id_project'] . ' AND type_transaction = 2', 'montant');
                    $montantTransTotal = str_replace('-', '', ($montantTransTotal / 100));
                    $montantTransDegel = $transactions->sum('id_project = ' . $p['id_project'] . ' AND type_transaction = 2 AND id_bid_remb != 0', 'montant');
                    $montantTransDegel = ($montantTransDegel / 100);

                    $montantTransEnchere = $transactions->sum('id_project = ' . $p['id_project'] . ' AND type_transaction = 2 AND id_bid_remb != 0', 'montant');
                    $montantTransEnchere = ($montantTransEnchere / 100);

                    $diffMontantBidsEtProjet = str_replace('-', '', ($montantBidsOK - $p['amount']));
                    $diffEntreBidsKoEtDegel  = ($montantTransEnchere - $montantBidsKO);

                    $contenu = '';
                    $contenu .= '<br>-------- PROJET ' . $p['id_project'] . ' --------<br><br>';
                    $contenu .= 'Montant projet : ' . $p['amount'] . '<br>';
                    $contenu .= '<br>--------BIDS--------<br>';
                    $contenu .= 'montantBids : ' . $montantBidsTotal . '<br>';
                    $contenu .= 'montantBidsOK : ' . $montantBidsOK . '<br>';
                    $contenu .= 'montantBidsKO : ' . $montantBidsKO . '<br>';
                    $contenu .= '<br>--------LOANS--------<br>';
                    $contenu .= 'montantLoans : ' . $montantLoans . '<br>';
                    $contenu .= '<br>--------TRANSACTIONS--------<br>';
                    $contenu .= 'montantTransTotal : ' . $montantTransTotal . '<br>';
                    $contenu .= 'montantTransDegel : ' . $montantTransDegel . '<br>';
                    $contenu .= 'montantTransEnchere : ' . $montantTransEnchere . '<br>';
                    $contenu .= '<br>--------PLUS--------<br>';
                    $contenu .= 'diffMontantBidsEtProjet : ' . $diffMontantBidsEtProjet . '<br>';
                    $contenu .= 'diffEntreBidsKoEtDegel : ' . $diffEntreBidsKoEtDegel . '<br>';
                    $contenu .= '<br>-------- FIN PROJET ' . $p['id_project'] . ' --------<br>';

                    $verif_no_good = false;

                    if ($montantTransTotal != $p['amount']) {
                        $verif_no_good = true;
                    }
                    if ($montantLoans != $p['amount']) {
                        $verif_no_good = true;
                    }
                    if ($diffEntreBidsKoEtDegel != $diffMontantBidsEtProjet) {
                        $verif_no_good = true;
                    }

                    if ($verif_no_good == true) {
                        $to      = 'unilend@equinoa.fr';
                        $subject = '[ALERTE] Une incoherence est présente dans le projet ' . $p['id_project'];
                        $message = '
					<html>
					<head>
					  <title>[ALERTE] Une incoherence est présente dans le projet ' . $p['id_project'] . '</title>
					</head>
					<body>
						<p>[ALERTE] Une incoherence est présente dans le projet ' . $p['id_project'] . '</p>
						<p>' . $contenu . '</p>
					</body>
					</html>
					';
                        $headers = 'MIME-Version: 1.0' . "\r\n";
                        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                        $headers .= 'To: equinoa <unilend@equinoa.fr>' . "\r\n";
                        $headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
                        if ($this->Config['env'] != "dev") {
                            mail($to, $subject, $message, $headers);
                        } else {
                            mail($this->sDestinatairesDebug, $subject, $message, $this->sHeadersDebug);
                        }

                        $projects_check->status = 2;
                    } else {// pas d'erreur
                        $projects_check->status = 1;
                    }
                    $projects_check->id_project = $p['id_project'];

                    $projects_check->create();
                }
            }

            $this->stopCron();
        }
    }

    // relance une completude a j+8 (add le 22/07/2014)
    // Passe tous les jours (tous les matin à 6h du matin) 0  6  *  *  *
    public function _relance_completude()
    {
        if (true === $this->startCron('relanceCompletude', 5)) {

            $this->clients                = $this->loadData('clients');
            $this->clients_status         = $this->loadData('clients_status');
            $this->clients_status_history = $this->loadData('clients_status_history');

            $timeMoins8 = mktime(0, 0, 0, date("m"), date("d") - 8, date("Y"));
            $lPreteurs  = $this->clients->selectPreteursByStatus('20', '', 'added_status DESC');

            $surl = $this->surl;
            $url  = $this->lurl;

            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;
            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            foreach ($lPreteurs as $p) {
                $timestamp_date = $this->dates->formatDateMySqlToTimeStamp($p['added_status']);

                // on ajoute une restriction. Plus de 7j et le premier samedi qui suit.
                if ($timestamp_date <= $timeMoins8 && date('w') == 6) {
                    $this->clients_status_history->get($p['id_client_status_history'], 'id_client_status_history');

                    $this->mails_text->get('completude', 'lang = "' . $this->language . '" AND type');

                    $timeCreate = strtotime($p['added_status']);
                    $month      = $this->dates->tableauMois['fr'][date('n', $timeCreate)];

                    $varMail = array(
                        'furl'          => $url,
                        'surl'          => $surl,
                        'url'           => $url,
                        'prenom_p'      => $p['prenom'],
                        'date_creation' => date('d', $timeCreate) . ' ' . $month . ' ' . date('Y', $timeCreate),
                        'content'       => $this->clients_status_history->content,
                        'lien_fb'       => $lien_fb,
                        'lien_tw'       => $lien_tw
                    );
                    $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                    $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                    $sujetMail = 'RAPPEL : ' . $sujetMail;
                    $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                    $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                    $this->email = $this->loadLib('email');
                    $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                    $this->email->setSubject(stripslashes($sujetMail));
                    $this->email->setHTMLBody(stripslashes($texteMail));

                    if ($p['status'] == 1) {
                        if ($this->Config['env'] == 'prod') {
                            Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $p['email'], $tabFiler);
                            // Injection du mail NMP dans la queue
                            $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                        } else {
                            $this->email->addRecipient(trim($p['email']));
                            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        }
                    }
                    $this->clients_status_history->addStatus('-1', 30, $p['id_client'], $this->clients_status_history->content);
                }
            }

            //DEUXIEME ETAPE - On relance les comptes en completude relance
            $lPreteurs = $this->clients->selectPreteursByStatus('30', '', 'added_status DESC');

            $timeMoins8  = mktime(0, 0, 0, date("m"), date("d") - 8, date("Y"));
            $timeMoins30 = mktime(0, 0, 0, date("m"), date("d") - 30, date("Y"));

            foreach ($lPreteurs as $p) {
                $op_pour_relance             = false;
                $clients_status_history      = $this->loadData('clients_status_history');
                $data_clients_status_history = $clients_status_history->get_last_statut($p['id_client'], 'id_client');
                $numero_relance              = $data_clients_status_history['numero_relance'];
                $timestamp_date              = $this->dates->formatDateMySqlToTimeStamp($p['added_status']);
                if ($timestamp_date <= $timeMoins8 && $numero_relance == 0 && date('w') == 6) {// Relance J+15 && samedi
                    $op_pour_relance = true;
                    $this->clients_status_history->addStatus('-1', 30, $p['id_client'], $data_clients_status_history['content'], 2);
                } elseif ($timestamp_date <= $timeMoins8 && $numero_relance == 2 && date('w') == 6) {// Relance J+30
                    $op_pour_relance = true;
                    $this->clients_status_history->addStatus('-1', 30, $p['id_client'], $data_clients_status_history['content'], 3);
                } elseif ($timestamp_date <= $timeMoins30 && $numero_relance == 3 && date('w') == 6) {// Relance J+60
                    $op_pour_relance = true;
                    $this->clients_status_history->addStatus('-1', 30, $p['id_client'], $data_clients_status_history['content'], 4);
                }

                if ($op_pour_relance) {
                    $this->mails_text->get('completude', 'lang = "' . $this->language . '" AND type');
                    $timeCreate = strtotime($p['added_status']);
                    $month      = $this->dates->tableauMois['fr'][date('n', $timeCreate)];

                    $varMail = array(
                        'furl'          => $url,
                        'surl'          => $surl,
                        'url'           => $url,
                        'prenom_p'      => $p['prenom'],
                        'date_creation' => date('d', $timeCreate) . ' ' . $month . ' ' . date('Y', $timeCreate),
                        'content'       => $data_clients_status_history['content'],
                        'lien_fb'       => $lien_fb,
                        'lien_tw'       => $lien_tw
                    );
                    $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                    $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                    $sujetMail = 'RAPPEL : ' . $sujetMail;
                    $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                    $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                    $this->email = $this->loadLib('email');
                    $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                    $this->email->setSubject(stripslashes($sujetMail));
                    $this->email->setHTMLBody(stripslashes($texteMail));

                    if ($p['status'] == 1) {
                        if ($this->Config['env'] == 'prod') {
                            Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $p['email'], $tabFiler);
                            $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                        } else {
                            $this->email->addRecipient(trim($p['email']));
                            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        }
                    }
                }
            }

            $this->stopCron();
        }
    }

    // généré à 1h du matin
    public function _xmlProjects()
    {
        if (true === $this->startCron('xmlProjects', 5)) {

            $projects  = $this->loadData('projects');
            $companies = $this->loadData('companies');
            $bids      = $this->loadData('bids');

            $lProjets = $projects->selectProjectsByStatus('50');

            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<partenaire>';

            foreach ($lProjets as $p) {
                $companies->get($p['id_company'], 'id_company');

                $monantRecolt = $bids->sum('id_project = ' . $p['id_project'] . ' AND status = 0', 'amount');
                $monantRecolt = ($monantRecolt / 100);

                if ($monantRecolt > $p['amount']) {
                    $monantRecolt = $p['amount'];
                }

                $xml .= '<projet>';
                $xml .= '<reference_partenaire>045</reference_partenaire>';
                $xml .= '<date_export>' . date('Y-m-d') . '</date_export>';
                $xml .= '<reference_projet>' . $p['id_project'] . '</reference_projet>';
                $xml .= '<impact_social>NON</impact_social>';
                $xml .= '<impact_environnemental>NON</impact_environnemental>';
                $xml .= '<impact_culturel>NON</impact_culturel>';
                $xml .= '<impact_eco>OUI</impact_eco>';
                $xml .= '<mots_cles_nomenclature_operateur></mots_cles_nomenclature_operateur>';
                $xml .= '<mode_financement>PRR</mode_financement>';
                $xml .= '<type_porteur_projet>ENT</type_porteur_projet>';
                $xml .= '<qualif_ESS>NON</qualif_ESS>';
                $xml .= '<code_postal>' . $companies->zip . '</code_postal>'; ////////////////////////////////////////
                $xml .= '<ville><![CDATA["' . utf8_encode($companies->city) . '"]]></ville>';
                $xml .= '<titre><![CDATA["' . $p['title'] . '"]]></titre>';
                $xml .= '<description><![CDATA["' . $p['nature_project'] . '"]]></description>';
                $xml .= '<url><![CDATA["' . $this->lurl . '/projects/detail/' . $p['slug'] . '/?utm_source=TNProjets&utm_medium=Part&utm_campaign=Permanent"]]></url>';
                $xml .= '<url_photo><![CDATA["' . $this->surl . '/images/dyn/projets/169/' . $p['photo_projet'] . '"]]></url_photo>';
                $xml .= '<date_debut_collecte>' . $p['date_publication'] . '</date_debut_collecte>';
                $xml .= '<date_fin_collecte>' . $p['date_retrait'] . '</date_fin_collecte>';
                $xml .= '<montant_recherche>' . $p['amount'] . '</montant_recherche>';
                $xml .= '<montant_collecte>' . $this->ficelle->formatNumber($monantRecolt, 0) . '</montant_collecte>';
                $xml .= '</projet>';
            }
            $xml .= '</partenaire>';
            file_put_contents($this->spath . 'fichiers/045.xml', $xml);
            $this->stopCron();
        }
        die;
    }

    // passe a 1h du matin tous les jours
    // check les remb qui n'ont pas de factures
    public function _genere_factures()
    {
        if (true === $this->startCron('genereFacture', 5)) {

            $projects    = $this->loadData('projects');
            $factures    = $this->loadData('factures');
            $companies   = $this->loadData('companies');
            $emprunteurs = $this->loadData('clients');

            foreach ($factures->selectEcheancesRembAndNoFacture() as $r) {
                $oCommandPdf = new Command('pdf', 'facture_ER', array($r['hash'], $r['id_project'], $r['ordre']), $this->language);
                    $oPdf        = new pdfController($oCommandPdf, $this->Config, 'default');
                    $oPdf->_facture_ER($r['hash'], $r['id_project'], $r['ordre']);
                }

            foreach ($projects->selectProjectsByStatus(\projects_status::REMBOURSEMENT) as $projet) {
                if (false === $factures->get($projet['id_project'], 'type_commission = 1 AND id_project')) {
                    $companies->get($projet['id_company'], 'id_company');
                    $emprunteurs->get($companies->id_client_owner, 'id_client');
                    $oCommandPdf = new Command('pdf', 'facture_EF', array($emprunteurs->hash, $r['id_project']), $this->language);
                    $oPdf        = new pdfController($oCommandPdf, $this->Config, 'default');
                    $oPdf->_facture_EF($emprunteurs->hash, $r['id_project']);
                }
            }

            $this->stopCron();
        }
        die;
    }

    // 1 fois par jour et on check les transactions non validés sur une journée (00:30)
    public function _check_alim_cb()
    {
        if (true === $this->startCron('checkAlimCb', 5)) {

            $this->autoFireHeader = false;
            $this->autoFireHead   = false;
            $this->autoFireView   = false;
            $this->autoFireFooter = false;

            $this->transactions     = $this->loadData('transactions');
            $this->backpayline      = $this->loadData('backpayline');
            $this->lenders_accounts = $this->loadData('lenders_accounts');
            $this->wallets_lines    = $this->loadData('wallets_lines');
            $this->bank_lines       = $this->loadData('bank_lines');

            // On recup la lib et le reste payline
            require_once($this->path . 'protected/payline/include.php');

            $date = mktime(0, 0, 0, date("m"), date("d") - 1, date("Y"));
            $date = date('Y-m-d', $date);

            $listTran = $this->transactions->select('type_transaction = 3 AND status = 0 AND etat = 0 AND LEFT(date_transaction,10) = "' . $date . '"');

            $payline = new paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);

            foreach ($listTran as $t) {
                $array_payline = unserialize($t['serialize_payline']);
                $token         = $array_payline['token'];
                $array         = array();

                $array['token']   = $token;
                $array['version'] = '3';
                $response         = $payline->getWebPaymentDetails($array);

                if (isset($response)) {
                    // si on retourne une transaction accpetée
                    if ($response['result']['code'] == '00000') {
                        if ($this->transactions->get($response['order']['ref'], 'status = 0 AND etat = 0 AND id_transaction')) {
                            // On enregistre le resultat payline
                            $this->backpayline->code           = $response['result']['code'];
                            $this->backpayline->token          = $array['token'];
                            $this->backpayline->id             = $response['transaction']['id'];
                            $this->backpayline->date           = $response['transaction']['date'];
                            $this->backpayline->amount         = $response['payment']['amount'];
                            $this->backpayline->serialize      = serialize($response);
                            $this->backpayline->id_backpayline = $this->backpayline->create();

                            $this->transactions->id_backpayline   = $this->backpayline->id_backpayline;
                            $this->transactions->montant          = $response['payment']['amount'];
                            $this->transactions->id_langue        = 'fr';
                            $this->transactions->date_transaction = date('Y-m-d H:i:s');
                            $this->transactions->status           = '1';
                            $this->transactions->etat             = '1';
                            $this->transactions->type_paiement    = ($response['extendedCard']['type'] == 'VISA' ? '0' : ($response['extendedCard']['type'] == 'MASTERCARD' ? '3' : ''));
                            $this->transactions->update();

                            $this->lenders_accounts->get($this->transactions->id_client, 'id_client_owner');
                            $this->lenders_accounts->status = 1;
                            $this->lenders_accounts->update();

                            $this->wallets_lines->id_lender                = $this->lenders_accounts->id_lender_account;
                            $this->wallets_lines->type_financial_operation = 30; // alimentation preteur
                            $this->wallets_lines->id_transaction           = $this->transactions->id_transaction;
                            $this->wallets_lines->status                   = 1;
                            $this->wallets_lines->type                     = 1;
                            $this->wallets_lines->amount                   = $response['payment']['amount'];
                            $this->wallets_lines->id_wallet_line           = $this->wallets_lines->create();

                            $this->bank_lines->id_wallet_line    = $this->wallets_lines->id_wallet_line;
                            $this->bank_lines->id_lender_account = $this->lenders_accounts->id_lender_account;
                            $this->bank_lines->status            = 1;
                            $this->bank_lines->amount            = $response['payment']['amount'];
                            $this->bank_lines->create();

                            $to      = 'd.courtier@relance.fr';
                            $subject = '[Alerte] BACK PAYLINE Transaction approved';
                            $message = '
						<html>
						<head>
						  <title>[Alerte] BACK PAYLINE Transaction approved</title>
						</head>
						<body>
						  <h3>[Alerte] BACK PAYLINE Transaction approved</h3>
						  <p>Un payement payline accepet&eacute; n\'a pas &eacute;t&eacute; mis &agrave; jour dans la BDD Unilend.</p>
						  <table>
							<tr>
							  <th>Id client : </th><td>' . $this->transactions->id_client . '</td>
							</tr>
							<tr>
							  <th>montant : </th><td>' . ($this->transactions->montant / 100) . '</td>
							</tr>
							<tr>
							  <th>serialize donnees payline : </th><td>' . serialize($response) . '</td>
							</tr>
						  </table>
						</body>
						</html>
						';

                            $headers = 'MIME-Version: 1.0' . "\r\n";
                            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                            $headers .= 'From: Unilend <unilend@equinoa.fr>' . "\r\n";
                            if ($this->Config['env'] != "dev") {
                                mail($to, $subject, $message, $headers);
                            } else {
                                mail($this->sDestinatairesDebug, $subject, $message, $this->sHeadersDebug);
                            }
                        }
                    }
                }
            }

            $this->stopCron();
        }
    }

    // Une fois par jour (crée le 27/04/2015)
    public function check_remboursement_preteurs()
    {
        $echeanciers = $this->loadData('echeanciers');
        $projects    = $this->loadData('projects');
        $surl = $this->surl; //Variable for eval($texteMail); Do not delete.
        $liste   = $echeanciers->selectEcheanciersByprojetEtOrdre(); // <--- a rajouter en prod
        $liste_remb = '';
        foreach ($liste as $l) {
            $projects->get($l['id_project'], 'id_project');
            $liste_remb .= '
				<tr>
					<td>' . $l['id_project'] . '</td>
					<td>' . $projects->title_bo . '</td>
					<td>' . $l['ordre'] . '</td>
					<td>' . $l['date_echeance'] . '</td>

					<td>' . $l['date_echeance_emprunteur'] . '</td>
					<td>' . $l['date_echeance_emprunteur_reel'] . '</td>
					<td>' . ((int)$l['status_emprunteur'] === 1 ? 'Oui' : 'Non') . '</td>
				</tr>';
        }

        $this->settings->get('Adresse notification check remb preteurs', 'type');
        $destinataire = $this->settings->value;
        $this->mails_text->get('notification-check-remboursements-preteurs', 'lang = "' . $this->language . '" AND type');

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
        if('prod' === $this->Config['env']) {
            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
        }
    }

    // Cron une fois par jour a 19h30 (* 18-20 * * *)
    public function _alertes_quotidienne()
    {
        ini_set('max_execution_time', 3600); // hotbug 07/09/2015
        ini_set('memory_limit', '4096M'); // hotbug 07/09/2015

        if (true === $this->startCron('notification quotidienne', 5)) {
            $clients                       = $this->loadData('clients');
            $clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
            $clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
            $notifications                 = $this->loadData('notifications');
            $projects                      = $this->loadData('projects');

            $this->lng['email-synthese'] = $this->ln->selectFront('email-synthese', $this->language, $this->App);

            $dateDebutRemboursement = mktime(18, 0, 0, date('m'), date('d'), date('Y'));
            $dateFinRemboursement   = mktime(19, 30, 0, date('m'), date('d'), date('Y'));

            $dateDebutNewProject = mktime(19, 30, 0, date('m'), date('d'), date('Y'));
            $dateFinNewProject   = mktime(20, 0, 0, date('m'), date('d'), date('Y'));

            $dateDebutOffreRealisee = mktime(20, 0, 0, date('m'), date('d'), date('Y'));
            $dateFinOffreRealisee   = mktime(20, 15, 0, date('m'), date('d'), date('Y'));

            $dateDebutOffreRefusee = mktime(20, 15, 0, date('m'), date('d'), date('Y'));
            $dateFinOffreRefusee   = mktime(20, 30, 0, date('m'), date('d'), date('Y'));

            $dateDebutOffreAcceptee = mktime(20, 30, 0, date('m'), date('d'), date('Y'));
            $dateFinOffreAcceptee   = mktime(21, 0, 0, date('m'), date('d'), date('Y'));

            if (time() >= $dateDebutNewProject && time() < $dateFinNewProject) {
                $id_notif = 1;

                //////// on va checker que tous les preteurs ont leur ligne de notif nouveau projet ///////////
                $lPreteurs = $clients->selectPreteursByStatusSlim(60);
                // Liste des projets
                $lProjects = $projects->selectProjectsByStatusSlim(50);

                foreach ($lPreteurs as $preteur) {
                    foreach ($lProjects as $projet) {
                        if ($clients_gestion_mails_notif->counter('id_client = ' . $preteur['id_client'] . ' AND id_project = ' . $projet['id_project']) <= 0) {
                            $notifications->type            = 8; // nouveau projet
                            $notifications->id_lender       = $preteur['id_lender'];
                            $notifications->id_project      = $projet['id_project'];
                            $notifications->status          = 1; // on le fait passé en deja lu car pas forcement du jour meme
                            $notifications->id_notification = $notifications->create();

                            $clients_gestion_mails_notif->id_client                      = $preteur['id_client'];
                            $clients_gestion_mails_notif->id_notif                       = 1; // type nouveau projet
                            $clients_gestion_mails_notif->id_notification                = $notifications->id_notification;
                            $clients_gestion_mails_notif->id_project                     = $projet['id_project'];
                            $clients_gestion_mails_notif->date_notif                     = $projet['date_publication_full'];
                            $clients_gestion_mails_notif->id_clients_gestion_mails_notif = $clients_gestion_mails_notif->create();
                        }
                    }
                }
            } elseif (time() >= $dateDebutOffreRealisee && time() < $dateFinOffreRealisee) {// Offre realisée
                $id_notif = 2;
            } elseif (time() >= $dateDebutOffreRefusee && time() < $dateFinOffreRefusee) {// Offre refusée
                $id_notif = 3;
            } elseif (time() >= $dateDebutOffreAcceptee && time() < $dateFinOffreAcceptee) {// Offre Acceptée
                $id_notif = 4;
            } elseif (time() >= $dateDebutRemboursement && time() < $dateFinRemboursement) {// Remboursement
                $id_notif = 5;
            } else {
                $this->stopCron();
                die;
            }
            $list_id_client = $clients_gestion_notifications->selectIdclientNotifs('quotidienne', $id_notif, 0, 50);

            $array_mail_nouveaux_projects = false;
            $array_offres_placees         = false;
            $array_offres_refusees        = false;
            $array_offres_acceptees       = false;
            $array_remb                   = false;

            foreach ($list_id_client as $id_client) {
                $mails_notif = $clients_gestion_notifications->selectNotifsByClient($id_client, 'quotidienne', $id_notif);
                foreach ($mails_notif as $mail) {
                    // Nouveaux projets
                    if ($id_notif == 1) {
                        $array_mail_nouveaux_projects[$id_client][$mail['id_clients_gestion_mails_notif']] = $mail;
                    } elseif ($id_notif == 2) {// Offres placées
                        $array_offres_placees[$id_client][$mail['id_clients_gestion_mails_notif']] = $mail;
                    } elseif ($id_notif == 3) {// Offres refusées
                        $array_offres_refusees[$id_client][$mail['id_clients_gestion_mails_notif']] = $mail;
                    } elseif ($id_notif == 4) {// Offres accpectées
                        $array_offres_acceptees[$id_client][$mail['id_clients_gestion_mails_notif']] = $mail;
                    } elseif ($id_notif == 5) {// remb
                        $array_remb[$id_client][$mail['id_clients_gestion_mails_notif']] = $mail;
                    }
                }
            }

            if ($array_mail_nouveaux_projects != false) {
                $this->nouveaux_projets_synthese($array_mail_nouveaux_projects, 'quotidienne');
            }
            if ($array_offres_placees != false) {
                $this->offres_placees_synthese($array_offres_placees, 'quotidienne');
            }
            if ($array_offres_refusees != false) {
                $this->offres_refusees_synthese($array_offres_refusees, 'quotidienne');
            }
            if ($array_offres_acceptees != false) {
                $this->offres_acceptees_synthese($array_offres_acceptees, 'quotidienne');
            }
            if ($array_remb != false) {
                $this->remb_synthese($array_remb, 'quotidienne');
            }

            $this->stopCron();
        }
        die;
    }

    // chaque samedi matin à 9h00  (0 9 * * 6 )
    public function _alertes_hebdomadaire()
    {
        if (true === $this->startCron('notification hebomadaire', 5)) {
            $clients                       = $this->loadData('clients');
            $clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
            $clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
            $notifications                 = $this->loadData('notifications');
            $projects                      = $this->loadData('projects');

            $this->lng['email-synthese'] = $this->ln->selectFront('email-synthese', $this->language, $this->App);

            $dateDebutNewProject = mktime(9, 0, 0, date('m'), date('d'), date('Y'));
            $dateFinNewProject   = mktime(9, 30, 0, date('m'), date('d'), date('Y'));

            $dateDebutOffreAcceptee = mktime(9, 30, 0, date('m'), date('d'), date('Y'));
            $dateFinOffreAcceptee   = mktime(10, 0, 0, date('m'), date('d'), date('Y'));

            $dateDebutRemboursement = mktime(10, 0, 0, date('m'), date('d'), date('Y'));
            $dateFinRemboursement   = mktime(10, 30, 0, date('m'), date('d'), date('Y'));
            if (time() >= $dateDebutNewProject && time() < $dateFinNewProject) {
                $id_notif = 1;

                //////// on va checker que tous les preteurs ont leur ligne de notif nouveau projet ///////////
                $lPreteurs = $clients->selectPreteursByStatusSlim(60);
                $lProjects = $projects->selectProjectsByStatusSlim(50);
                foreach ($lPreteurs as $preteur) {
                    foreach ($lProjects as $projet) {
                        if ($clients_gestion_mails_notif->counter('id_client = ' . $preteur['id_client'] . ' AND id_project = ' . $projet['id_project']) <= 0) {
                            $notifications->type            = 8; // nouveau projet
                            $notifications->id_lender       = $preteur['id_lender'];
                            $notifications->id_project      = $projet['id_project'];
                            $notifications->status          = 1; // on le fait passé en deja lu car pas forcement du jour meme
                            $notifications->id_notification = $notifications->create();

                            $clients_gestion_mails_notif->id_client                      = $preteur['id_client'];
                            $clients_gestion_mails_notif->id_notif                       = 1; // type nouveau projet
                            $clients_gestion_mails_notif->id_notification                = $notifications->id_notification;
                            $clients_gestion_mails_notif->id_project                     = $projet['id_project'];
                            $clients_gestion_mails_notif->date_notif                     = $projet['date_publication_full'];
                            $clients_gestion_mails_notif->id_clients_gestion_mails_notif = $clients_gestion_mails_notif->create();
                        }
                    }
                }
            } elseif (time() >= $dateDebutOffreAcceptee && time() < $dateFinOffreAcceptee) {// Offre Acceptée
                $id_notif = 4;
            } elseif (time() >= $dateDebutRemboursement && time() < $dateFinRemboursement) {// Remboursement
                $id_notif = 5;
            } else {
                $this->stopCron();
                die;
            }
            $list_id_client = $clients_gestion_notifications->selectIdclientNotifs('hebdomadaire', $id_notif, 0, 250);

            $array_mail_nouveaux_projects = false;
            $array_offres_acceptees       = false;
            $array_remb                   = false;

            foreach ($list_id_client as $id_client) {
                $mails_notif = $clients_gestion_notifications->selectNotifsByClient($id_client, 'hebdomadaire', $id_notif);

                foreach ($mails_notif as $mail) {
                    if ($id_notif == 1) {
                        $array_mail_nouveaux_projects[$id_client][$mail['id_clients_gestion_mails_notif']] = $mail;
                    } elseif ($id_notif == 4) {// Offres accpectées
                        $array_offres_acceptees[$id_client][$mail['id_clients_gestion_mails_notif']] = $mail;
                    } elseif ($id_notif == 5) {// remb
                        $array_remb[$id_client][$mail['id_clients_gestion_mails_notif']] = $mail;
                    }
                }
            }
            if ($array_mail_nouveaux_projects != false) {
                $this->nouveaux_projets_synthese($array_mail_nouveaux_projects, 'hebdomadaire');
            }
            if ($array_offres_acceptees != false) {
                $this->offres_acceptees_synthese($array_offres_acceptees, 'hebdomadaire');
            }
            if ($array_remb != false) {
                $this->remb_synthese($array_remb, 'hebdomadaire');
            }

            $this->stopCron();
        }
        die;
    }

    // Cron le 1er de chaque mois à 9h00 (0 9 1 * * )
    public function _alertes_mensuelle()
    {
        if (true === $this->startCron('notification mensuelle', 5)) {
            $clients_gestion_notifications = $this->loadData('clients_gestion_notifications');

            $this->lng['email-synthese'] = $this->ln->selectFront('email-synthese', $this->language, $this->App);

            $dateDebutOffreAcceptee = mktime(10, 30, 0, date('m'), date('d'), date('Y'));
            $dateFinOffreAcceptee   = mktime(11, 0, 0, date('m'), date('d'), date('Y'));

            $dateDebutRemboursement = mktime(11, 0, 0, date('m'), date('d'), date('Y'));
            $dateFinRemboursement   = mktime(11, 30, 0, date('m'), date('d'), date('Y'));

            if (time() >= $dateDebutOffreAcceptee && time() < $dateFinOffreAcceptee) {
                $id_notif = 4;
            } elseif (time() >= $dateDebutRemboursement && time() < $dateFinRemboursement) {// Remboursement
                $id_notif = 5;
            } else {
                $this->stopCron();
                die;
            }

            $list_id_client = $clients_gestion_notifications->selectIdclientNotifs('mensuelle', $id_notif, 0, 250);

            $array_offres_acceptees = false;
            $array_remb             = false;
            foreach ($list_id_client as $id_client) {
                $mails_notif = $clients_gestion_notifications->selectNotifsByClient($id_client, 'mensuelle', $id_notif);
                foreach ($mails_notif as $mail) {
                    // Offres accpectées
                    if ($id_notif == 4) {
                        $array_offres_acceptees[$id_client][$mail['id_clients_gestion_mails_notif']] = $mail;
                    } elseif ($id_notif == 5) {// remb
                        $array_remb[$id_client][$mail['id_clients_gestion_mails_notif']] = $mail;
                    }
                }
            }

            if ($array_offres_acceptees != false) {
                $this->offres_acceptees_synthese($array_offres_acceptees, 'mensuelle');
            }
            if ($array_remb != false) {
                $this->remb_synthese($array_remb, 'mensuelle');
            }

            $this->stopCron();
        }
        die;
    }

    // Cron une fois par jour a 19h30 (30 19 * * *)
    public function _gestion_alertes_quotidiene()
    {
        die;
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '1024M');

        $clients                       = $this->loadData('clients');
        $lenders_accounts              = $this->loadData('lenders_accounts');
        $clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
        $clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $notifications                 = $this->loadData('notifications');
        $projects                      = $this->loadData('projects');

        $lPreteurs = $clients->selectPreteursByStatusSlim(60);
        $lProjects = $projects->selectProjectsByStatusSlim(50);

        foreach ($lPreteurs as $preteur) {
            foreach ($lProjects as $projet) {
                if (!$clients_gestion_mails_notif->get($projet['id_project'], 'id_client = ' . $preteur['id_client'] . ' AND id_project')) {

                    $notifications->type            = 8; // nouveau projet
                    $notifications->id_lender       = $preteur['id_lender'];
                    $notifications->id_project      = $projet['id_project'];
                    $notifications->status          = 1; // on le fait passé en deja lu car pas forcement du jour meme
                    $notifications->id_notification = $notifications->create();

                    $clients_gestion_mails_notif->id_client                      = $preteur['id_client'];
                    $clients_gestion_mails_notif->id_notif                       = 1; // type nouveau projet
                    $clients_gestion_mails_notif->id_notification                = $notifications->id_notification;
                    $clients_gestion_mails_notif->id_project                     = $projet['id_project'];
                    $clients_gestion_mails_notif->date_notif                     = $projet['date_publication_full'];
                    $clients_gestion_mails_notif->id_clients_gestion_mails_notif = $clients_gestion_mails_notif->create();
                }
            }
        }
        $mails_notif = $clients_gestion_notifications->selectNotifs('quotidienne');

        $array_mail_nouveaux_projects = false;
        $array_offres_placees         = false;
        $array_offres_refusees        = false;
        $array_offres_acceptees       = false;
        $array_remb                   = false;

        foreach ($mails_notif as $mail) {
            // Nouveaux projets
            if ($mail['id_notif'] == 1) {
                $array_mail_nouveaux_projects[$mail['id_client']][$mail['id_clients_gestion_mails_notif']] = $mail;
            } elseif ($mail['id_notif'] == 2) {// Offres placées

                $array_offres_placees[$mail['id_client']][$mail['id_clients_gestion_mails_notif']] = $mail;
            } elseif ($mail['id_notif'] == 3) {// Offres refusées

                $array_offres_refusees[$mail['id_client']][$mail['id_clients_gestion_mails_notif']] = $mail;
            } elseif ($mail['id_notif'] == 4) {// Offres accpectées

                $array_offres_acceptees[$mail['id_client']][$mail['id_clients_gestion_mails_notif']] = $mail;
            } elseif ($mail['id_notif'] == 5) {// remb
                $array_remb[$mail['id_client']][$mail['id_clients_gestion_mails_notif']] = $mail;
            }
        }

        if ($array_mail_nouveaux_projects != false) {
            $this->nouveaux_projets_synthese($array_mail_nouveaux_projects, 'quotidienne');
        }
        if ($array_offres_placees != false) {
            $this->offres_placees_synthese($array_offres_placees, 'quotidienne');
        }
        if ($array_offres_refusees != false) {
            $this->offres_refusees_synthese($array_offres_refusees, 'quotidienne');
        }
        if ($array_offres_acceptees != false) {
            $this->offres_acceptees_synthese($array_offres_acceptees, 'quotidienne');
        }
        if ($array_remb != false) {
            $this->remb_synthese($array_remb, 'quotidienne');
        }

        $this->stopCron();
        die;
    }

    // chaque samedi matin à 9h00  (0 9 * * 6 )
    public function _gestion_alertes_hebdomadaire()
    {
        die;
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '1024M');

        $clients                       = $this->loadData('clients');
        $lenders_accounts              = $this->loadData('lenders_accounts');
        $clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
        $clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $notifications                 = $this->loadData('notifications');
        $projects                      = $this->loadData('projects');

        $lPreteurs = $clients->selectPreteursByStatusSlim(60);
        // Liste des projets
        $lProjects = $projects->selectProjectsByStatusSlim(50);

        foreach ($lPreteurs as $preteur) {
            foreach ($lProjects as $projet) {
                if (!$clients_gestion_mails_notif->get($projet['id_project'], 'id_client = ' . $preteur['id_client'] . ' AND id_project')) {
                    $notifications->type            = 8; // nouveau projet
                    $notifications->id_lender       = $preteur['id_lender'];
                    $notifications->id_project      = $projet['id_project'];
                    $notifications->status          = 1; // on le fait passé en deja lu car pas forcement du jour meme
                    $notifications->id_notification = $notifications->create();

                    $clients_gestion_mails_notif->id_client                      = $preteur['id_client'];
                    $clients_gestion_mails_notif->id_notif                       = 1; // type nouveau projet
                    $clients_gestion_mails_notif->id_notification                = $notifications->id_notification;
                    $clients_gestion_mails_notif->id_project                     = $projet['id_project'];
                    $clients_gestion_mails_notif->date_notif                     = $projet['date_publication_full'];
                    $clients_gestion_mails_notif->id_clients_gestion_mails_notif = $clients_gestion_mails_notif->create();
                }
            }
        }
        $mails_notif = $clients_gestion_notifications->selectNotifs('hebdomadaire');

        $array_mail_nouveaux_projects = false;
        $array_offres_placees         = false;
        $array_offres_refusees        = false;
        $array_offres_acceptees       = false;
        $array_remb                   = false;

        foreach ($mails_notif as $mail) {
            // Nouveau projet
            if ($mail['id_notif'] == 1) {
                $array_mail_nouveaux_projects[$mail['id_client']][$mail['id_clients_gestion_mails_notif']] = $mail;
            } elseif ($mail['id_notif'] == 4) {// Offres accpectées
                $array_offres_acceptees[$mail['id_client']][$mail['id_clients_gestion_mails_notif']] = $mail;
            } elseif ($mail['id_notif'] == 5) {// remb
                $array_remb[$mail['id_client']][$mail['id_clients_gestion_mails_notif']] = $mail;
            }
        }
        if ($array_mail_nouveaux_projects != false) {
            $this->nouveaux_projets_synthese($array_mail_nouveaux_projects, 'hebdomadaire');
        }
        if ($array_offres_acceptees != false) {
            $this->offres_acceptees_synthese($array_offres_acceptees, 'hebdomadaire');
        }
        if ($array_remb != false) {
            $this->remb_synthese($array_remb, 'hebdomadaire');
        }
        mail($this->sDestinatairesDebug, 'cron gestion_alertes_hebdomadaire', 'cron gestion_alertes_hebdomadaire - ' . date('Y-m-d H:i:e'), $this->sHeadersDebug);
        die;
    }

    // Cron le 1er de chaque mois à 9h00 (0 9 1 * * )
    public function _gestion_alertes_mensuelle()
    {
        die;
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '1024M');

        $last_day_of_month = date('t');

        $clients                       = $this->loadData('clients');
        $lenders_accounts              = $this->loadData('lenders_accounts');
        $clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
        $clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $projects                      = $this->loadData('projects');

        $mails_notif = $clients_gestion_notifications->selectNotifs('mensuelle');

        $array_offres_acceptees = false;
        $array_remb             = false;

        foreach ($mails_notif as $mail) {
            // Offres accpectées
            if ($mail['id_notif'] == 4) {
                $array_offres_acceptees[$mail['id_client']][$mail['id_clients_gestion_mails_notif']] = $mail;
            } elseif ($mail['id_notif'] == 5) {// remb
                $array_remb[$mail['id_client']][$mail['id_clients_gestion_mails_notif']] = $mail;
            }
        }
        if ($array_offres_acceptees != false) {
            $this->offres_acceptees_synthese($array_offres_acceptees, 'mensuelle');
        }
        if ($array_remb != false) {
            $this->remb_synthese($array_remb, 'mensuelle');
        }
        die;
    }

    // Fonction qui crée les notification nouveaux projet pour les prêteurs (immediatement)(OK)
    public function nouveau_projet($id_project)
    {
        $this->clients                       = $this->loadData('clients');
        $this->notifications                 = $this->loadData('notifications');
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
        $this->projects                      = $this->loadData('projects');
        $this->companies                     = $this->loadData('companies');

        $oLogger = new ULogger('notifications', $this->logPath, 'notifications.log');
        $oLogger->addRecord(ULogger::DEBUG, 'Project ID: ' . $id_project);

        $this->settings->get('Facebook', 'type');
        $lien_fb = $this->settings->value;

        $this->settings->get('Twitter', 'type');
        $lien_tw = $this->settings->value;

        $this->projects->get($id_project, 'id_project');
        $this->companies->get($this->projects->id_company, 'id_company');

        $lPreteurs = $this->clients->selectPreteursByStatus(60);

        $oLogger->addRecord(ULogger::DEBUG, 'Lenders count: ' . count($lPreteurs));

        foreach ($lPreteurs as $preteur) {
            $this->notifications->type            = 8; // nouveau projet
            $this->notifications->id_lender       = $preteur['id_lender'];
            $this->notifications->id_project      = $id_project;
            $this->notifications->id_notification = $this->notifications->create();

            $this->clients_gestion_mails_notif->id_client                      = $preteur['id_client'];
            $this->clients_gestion_mails_notif->id_notif                       = 1; // type nouveau projet
            $this->clients_gestion_mails_notif->id_notification                = $this->notifications->id_notification;
            $this->clients_gestion_mails_notif->id_project                     = $id_project;
            $this->clients_gestion_mails_notif->date_notif                     = $this->projects->date_publication_full;
            $this->clients_gestion_mails_notif->id_clients_gestion_mails_notif = $this->clients_gestion_mails_notif->create();

            if ($this->clients_gestion_notifications->getNotif($preteur['id_client'], 1, 'immediatement') == true) {
                $bReturn = $this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');

                if (false === $bReturn) {
                    $oLogger->addRecord(ULogger::ALERT, 'Unable to get mail notification: ' . $this->clients_gestion_mails_notif->id_clients_gestion_mails_notif);
                }

                $this->clients_gestion_mails_notif->immediatement = 1; // on met a jour le statut immediatement
                $this->clients_gestion_mails_notif->update();

                $this->mails_text->get('nouveau-projet', 'lang = "' . $this->language . '" AND type');
                $p         = substr($this->ficelle->stripAccents(utf8_decode(trim($preteur['prenom']))), 0, 1);
                $nom       = $this->ficelle->stripAccents(utf8_decode(trim($preteur['nom'])));
                $id_client = str_pad($preteur['id_client'], 6, 0, STR_PAD_LEFT);
                $motif     = mb_strtoupper($id_client . $p . $nom, 'UTF-8');

                $varMail = array(
                    'surl'            => $this->surl,
                    'url'             => $this->furl,
                    'prenom_p'        => $preteur['prenom'],
                    'nom_entreprise'  => $this->companies->name,
                    'projet-p'        => $this->furl . '/projects/detail/' . $this->projects->slug,
                    'montant'         => $this->ficelle->formatNumber($this->projects->amount, 0),
                    'duree'           => $this->projects->period,
                    'motif_virement'  => $motif,
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

                if ($preteur['status'] == 1) {
                    $oLogger->addRecord(ULogger::DEBUG, 'Email sent to: ' . $preteur['email']);

                    if ($this->Config['env'] == 'prod') {
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $preteur['email'], $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($preteur['email']));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }
                }
            }
        }
        die;
    }

    // fonction synhtese nouveaux projets
    // $type = quotidienne,hebdomadaire,mensuelle
    public function nouveaux_projets_synthese($array_mail_nouveaux_projects, $type)
    {
        $this->clients       = $this->loadData('clients');
        $this->notifications = $this->loadData('notifications');
        $this->projects      = $this->loadData('projects');
        $this->companies     = $this->loadData('companies');

        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');

        if ($array_mail_nouveaux_projects != false) {
            $clients_gestion_notif_log                              = $this->loadData('clients_gestion_notif_log');
            $clients_gestion_notif_log->id_notif                    = 1;
            $clients_gestion_notif_log->type                        = $type;
            $clients_gestion_notif_log->debut                       = date('Y-m-d H:i:s');
            $clients_gestion_notif_log->fin                         = '0000-00-00 00:00:00';
            $clients_gestion_notif_log->id_client_gestion_notif_log = $clients_gestion_notif_log->create();

            $clients_gestion_notif_log->get($clients_gestion_notif_log->id_client_gestion_notif_log, 'id_client_gestion_notif_log');

            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            foreach ($array_mail_nouveaux_projects as $id_client => $mails_notif) {
                if ($this->clients_gestion_notifications->getNotif($id_client, 1, $type) == true) {
                    $this->clients->get($id_client, 'id_client');

                    $p              = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))), 0, 1);
                    $nom            = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
                    $le_id_client   = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
                    $motif          = mb_strtoupper($le_id_client . $p . $nom, 'UTF-8');
                    $nb_arrayoffres = count($mails_notif); // (BT 18180 04/08/2015)
                    $goMail         = true; // (BT 18180 04/08/2015)
                    $liste_projets  = '';

                    foreach ($mails_notif as $n) {
                        $this->notifications->get($n['id_notification'], 'id_notification');
                        $this->projects->get($this->notifications->id_project, 'id_project');
                        $this->companies->get($this->projects->id_company, 'id_company');

                        $this->clients_gestion_mails_notif->get($n['id_clients_gestion_mails_notif'], 'id_clients_gestion_mails_notif');
                        if ($type == 'quotidienne') {
                            if ($nb_arrayoffres <= 1 && $this->clients_gestion_mails_notif->immediatement == 1) {
                                $goMail = false;
                            } else {
                                $this->clients_gestion_mails_notif->quotidienne = 1;
                            }
                            $this->clients_gestion_mails_notif->status_check_quotidienne = 1;
                        } elseif ($type == 'hebdomadaire') {
                            $this->clients_gestion_mails_notif->hebdomadaire              = 1;
                            $this->clients_gestion_mails_notif->status_check_hebdomadaire = 1;
                        } elseif ($type == 'mensuelle') {
                            $this->clients_gestion_mails_notif->mensuelle              = 1;
                            $this->clients_gestion_mails_notif->status_check_mensuelle = 1;
                        }
                        $this->clients_gestion_mails_notif->update();

                        $liste_projets .= '
								<tr style="color:#b20066;">
									<td  style="font-family:Arial;font-size:14px;height: 25px;"><a style="color:#b20066;text-decoration:none;font-family:Arial;" href="' . $this->lurl . '/projects/detail/' . $this->projects->slug . '">' . $this->projects->title . '</a></td>
									<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($this->projects->amount, 0) . ' &euro;</td>
									<td align="right" style="font-family:Arial;font-size:14px;">' . $this->projects->period . ' mois</td>
								</tr>';
                    }
                    if ($goMail == true) {// (BT 18180 04/08/2015)
                        if ($type == 'quotidienne') {
                            $this->mails_text->get('nouveaux-projets-du-jour', 'lang = "' . $this->language . '" AND type');
                        } else {
                            $this->mails_text->get('nouveaux-projets-de-la-semaine', 'lang = "' . $this->language . '" AND type');
                        }
                        $lecontenu = '';

                        // on gère ici le cas du singulier/pluriel
                        if ($nb_arrayoffres <= 1) {
                            if ($type == 'quotidienne') {
                                $this->mails_text->subject = $this->lng['email-synthese']['sujet-nouveau-projet-du-jour-singulier'];
                                $sujet                     = $this->lng['email-synthese']['sujet-nouveau-projet-du-jour-singulier'];
                                $lecontenu                 = $this->lng['email-synthese']['contenu-synthese-nouveau-projet-du-jour-singulier'];
                                $objet                     = $this->lng['email-synthese']['objet-synthese-nouveau-projet-du-jour-singulier'];
                            } elseif ($type == 'hebdomadaire') {
                                $this->mails_text->subject = $this->lng['email-synthese']['sujet-nouveau-projet-hebdomadaire-singulier'];
                                $sujet                     = $this->lng['email-synthese']['sujet-nouveau-projet-hebdomadaire-singulier'];
                                $lecontenu                 = $this->lng['email-synthese']['contenu-synthese-nouveau-projet-hebdomadaire-singulier'];
                                $objet                     = $this->lng['email-synthese']['objet-synthese-nouveau-projet-hebdomadaire-singulier'];
                            }
                        } else {
                            if ($type == 'quotidienne') {
                                $sujet     = $this->lng['email-synthese']['sujet-nouveau-projet-du-jour-pluriel'];
                                $lecontenu = $this->lng['email-synthese']['contenu-synthese-nouveau-projet-du-jour-pluriel'];
                                $objet     = $this->lng['email-synthese']['objet-synthese-nouveau-projet-du-jour-pluriel'];
                            } elseif ($type == 'hebdomadaire') {
                                $sujet     = $this->lng['email-synthese']['sujet-nouveau-projet-hebdomadaire-pluriel'];
                                $lecontenu = $this->lng['email-synthese']['contenu-synthese-nouveau-projet-hebdomadaire-pluriel'];
                                $objet     = $this->lng['email-synthese']['objet-synthese-nouveau-projet-hebdomadaire-pluriel'];
                            }
                        }

                        $varMail = array(
                            'surl'            => $this->surl,
                            'url'             => $this->furl,
                            'prenom_p'        => $this->clients->prenom,
                            'liste_projets'   => $liste_projets,
                            'projet-p'        => $this->lurl . '/projets-a-financer',
                            'motif_virement'  => $motif,
                            'gestion_alertes' => $this->lurl . '/profile',
                            'objet'           => $objet,
                            'contenu'         => $lecontenu,
                            'sujet'           => $sujet,
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

                        if ($this->clients->status == 1) {
                            if ($this->Config['env'] == 'prod') {
                                Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                                $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                            } else {
                                $this->email->addRecipient($this->clients->email);
                                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                            }
                        }
                    }
                } else {// pas envie de recevoir le mail
                    foreach ($mails_notif as $n) {
                        $this->clients_gestion_mails_notif->get($n['id_clients_gestion_mails_notif'], 'id_clients_gestion_mails_notif');
                        if ($type == 'quotidienne') {
                            $this->clients_gestion_mails_notif->status_check_quotidienne = 1;
                        } elseif ($type == 'hebdomadaire') {
                            $this->clients_gestion_mails_notif->status_check_hebdomadaire = 1;
                        } elseif ($type == 'mensuelle') {
                            $this->clients_gestion_mails_notif->status_check_mensuelle = 1;
                        }
                        $this->clients_gestion_mails_notif->update();
                    }
                }
            }
            $clients_gestion_notif_log->fin                         = date('Y-m-d H:i:s');
            $clients_gestion_notif_log->id_client_gestion_notif_log = $clients_gestion_notif_log->update();
        }
    }

    public function offres_placees_synthese($array_offres_placees, $type)
    {
        $this->clients       = $this->loadData('clients');
        $this->notifications = $this->loadData('notifications');
        $this->projects      = $this->loadData('projects');
        $this->companies     = $this->loadData('companies');
        $this->bids          = $this->loadData('bids');

        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
        if ($array_offres_placees != false) {
            $clients_gestion_notif_log                              = $this->loadData('clients_gestion_notif_log');
            $clients_gestion_notif_log->id_notif                    = 2;
            $clients_gestion_notif_log->type                        = $type;
            $clients_gestion_notif_log->debut                       = date('Y-m-d H:i:s');
            $clients_gestion_notif_log->fin                         = '0000-00-00 00:00:00';
            $clients_gestion_notif_log->id_client_gestion_notif_log = $clients_gestion_notif_log->create();

            $clients_gestion_notif_log->get($clients_gestion_notif_log->id_client_gestion_notif_log, 'id_client_gestion_notif_log');

            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            foreach ($array_offres_placees as $id_client => $mails_notif) {
                if ($this->clients_gestion_notifications->getNotif($id_client, 2, $type) == true) {
                    $this->clients->get($id_client, 'id_client');

                    $p            = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))), 0, 1);
                    $nom          = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
                    $le_id_client = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
                    $motif        = mb_strtoupper($le_id_client . $p . $nom, 'UTF-8');
                    $pageProjets  = $this->tree->getSlug(4, $this->language);

                    if (count($mails_notif) > 1 || $type != 'quotidienne') {
                        $liste_offres   = '';
                        $i              = 1;
                        $total          = 0;
                        $nb_arrayoffres = count($mails_notif);
                        $goMail         = true;
                        foreach ($mails_notif as $n) {

                            $this->notifications->get($n['id_notification'], 'id_notification');
                            $this->projects->get($this->notifications->id_project, 'id_project');
                            $this->companies->get($this->projects->id_company, 'id_company');
                            $this->bids->get($this->notifications->id_bid, 'id_bid');

                            $this->clients_gestion_mails_notif->get($n['id_clients_gestion_mails_notif'], 'id_clients_gestion_mails_notif');
                            if ($type == 'quotidienne') {
                                $this->clients_gestion_mails_notif->quotidienne              = 1;
                                $this->clients_gestion_mails_notif->status_check_quotidienne = 1;
                            } elseif ($type == 'hebdomadaire') {
                                $this->clients_gestion_mails_notif->hebdomadaire              = 1;
                                $this->clients_gestion_mails_notif->status_check_hebdomadaire = 1;
                            } elseif ($type == 'mensuelle') {
                                $this->clients_gestion_mails_notif->mensuelle              = 1;
                                $this->clients_gestion_mails_notif->status_check_mensuelle = 1;
                            }
                            $this->clients_gestion_mails_notif->update();

                            $total += ($this->bids->amount / 100);

                            if ($i == $nb_arrayoffres) {
                                $liste_offres .= '
								<tr style="color:#b20066;">
									<td  style="height:25px;font-family:Arial;font-size:14px;"><a style="color:#b20066;text-decoration:none;" href="' . $this->lurl . '/projects/detail/' . $this->projects->slug . '">' . $this->projects->title . '</a></td>
									<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber(($this->bids->amount / 100), 0) . ' &euro;</td>
									<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($this->bids->rate) . ' %</td>
								</tr>
								<tr>
									<td style="height:25px;border-top:1px solid #727272;color: #727272;font-family:Arial;font-size:14px;">Total de vos offres</td>
									<td align="right" style="border-top:1px solid #727272;color:#b20066;font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($total, 0) . ' &euro;</td>
									<td style="border-top:1px solid #727272;color: #727272;font-family:Arial;font-size:14px;"></td>
								</tr>
								';
                            } else {
                                $liste_offres .= '
								<tr style="color:#b20066;">
									<td  style="height:25px;font-family:Arial;font-size:14px;"><a style="color:#b20066;text-decoration:none;" href="' . $this->lurl . '/projects/detail/' . $this->projects->slug . '">' . $this->projects->title . '</a></td>
									<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber(($this->bids->amount / 100), 0) . ' &euro;</td>
									<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($this->bids->rate) . ' %</td>
								</tr>';
                            }
                            $i++;
                        }

                        if ($goMail == true) {
                            if ($type == 'quotidienne') {
                                $this->mails_text->get('vos-offres-du-jour', 'lang = "' . $this->language . '" AND type');
                            }
                            $lecontenu = '';
                            // on gère ici le cas du singulier/pluriel
                            if ($nb_arrayoffres <= 1) {
                                if ($type == 'quotidienne') {
                                    $sujet                     = $this->lng['email-synthese']['sujet-synthese-quotidienne-offre-placee-singulier'];
                                    $this->mails_text->subject = $this->lng['email-synthese']['sujet-synthese-quotidienne-offre-placee-singulier'];
                                    $lecontenu                 = $this->lng['email-synthese']['contenu-synthese-offre-placee-quotidienne-singulier'];
                                    $objet                     = $this->lng['email-synthese']['objet-synthese-offre-placee-quotidienne-singulier'];
                                }
                            } else {
                                if ($type == 'quotidienne') {
                                    $sujet     = $this->lng['email-synthese']['sujet-synthese-quotidienne-offre-placee-pluriel'];
                                    $lecontenu = $this->lng['email-synthese']['contenu-synthese-offre-placee-quotidienne-pluriel'];
                                    $objet     = $this->lng['email-synthese']['objet-synthese-offre-placee-quotidienne-pluriel'];
                                }
                            }

                            $varMail = array(
                                'surl'            => $this->surl,
                                'url'             => $this->furl,
                                'prenom_p'        => $this->clients->prenom,
                                'liste_offres'    => $liste_offres,
                                'motif_virement'  => $motif,
                                'gestion_alertes' => $this->lurl . '/profile',
                                'objet'           => $objet,
                                'contenu'         => $lecontenu,
                                'sujet'           => $sujet,
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

                            if ($this->clients->status == 1) {
                                if ($this->Config['env'] == 'prod') {
                                    Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                                    $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                                } else {
                                    $this->email->addRecipient($this->clients->email);
                                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                                }
                            }
                        }
                    }
                } else {// Si il veut pas de mail
                    foreach ($mails_notif as $n) {
                        $this->clients_gestion_mails_notif->get($n['id_clients_gestion_mails_notif'], 'id_clients_gestion_mails_notif');
                        if ($type == 'quotidienne') {
                            $this->clients_gestion_mails_notif->status_check_quotidienne = 1;
                        } elseif ($type == 'hebdomadaire') {
                            $this->clients_gestion_mails_notif->status_check_hebdomadaire = 1;
                        } elseif ($type == 'mensuelle') {
                            $this->clients_gestion_mails_notif->status_check_mensuelle = 1;
                        }
                        $this->clients_gestion_mails_notif->update();
                    }
                }
            }
            $clients_gestion_notif_log->fin                         = date('Y-m-d H:i:s');
            $clients_gestion_notif_log->id_client_gestion_notif_log = $clients_gestion_notif_log->update();
        }
    }

    // offres refusées
    public function offres_refusees_synthese($array_offres_refusees, $type)
    {
        $this->clients       = $this->loadData('clients');
        $this->notifications = $this->loadData('notifications');
        $this->projects      = $this->loadData('projects');
        $this->companies     = $this->loadData('companies');
        $this->bids          = $this->loadData('bids');

        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');

        if ($array_offres_refusees != false) {
            $clients_gestion_notif_log                              = $this->loadData('clients_gestion_notif_log');
            $clients_gestion_notif_log->id_notif                    = 3;
            $clients_gestion_notif_log->type                        = $type;
            $clients_gestion_notif_log->debut                       = date('Y-m-d H:i:s');
            $clients_gestion_notif_log->fin                         = '0000-00-00 00:00:00';
            $clients_gestion_notif_log->id_client_gestion_notif_log = $clients_gestion_notif_log->create();

            $clients_gestion_notif_log->get($clients_gestion_notif_log->id_client_gestion_notif_log, 'id_client_gestion_notif_log');

            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            foreach ($array_offres_refusees as $id_client => $mails_notif) {
                if ($this->clients_gestion_notifications->getNotif($id_client, 3, $type) == true) {
                    if ($type == 'quotidienne') {
                        $this->mails_text->get('synthese-quotidienne-offres-non-retenues', 'lang = "' . $this->language . '" AND type');
                    }
                    $liste_offres   = '';
                    $i              = 1;
                    $total          = 0;
                    $nb_arrayoffres = count($mails_notif);
                    foreach ($mails_notif as $n) {

                        $this->notifications->get($n['id_notification'], 'id_notification');
                        $this->projects->get($this->notifications->id_project, 'id_project');
                        $this->companies->get($this->projects->id_company, 'id_company');
                        $this->bids->get($this->notifications->id_bid, 'id_bid');

                        $this->clients_gestion_mails_notif->get($n['id_clients_gestion_mails_notif'], 'id_clients_gestion_mails_notif');
                        if ($type == 'quotidienne') {
                            $this->clients_gestion_mails_notif->quotidienne              = 1;
                            $this->clients_gestion_mails_notif->status_check_quotidienne = 1;
                        } elseif ($type == 'hebdomadaire') {
                            $this->clients_gestion_mails_notif->hebdomadaire              = 1;
                            $this->clients_gestion_mails_notif->status_check_hebdomadaire = 1;
                        } elseif ($type == 'mensuelle') {
                            $this->clients_gestion_mails_notif->mensuelle              = 1;
                            $this->clients_gestion_mails_notif->status_check_mensuelle = 1;
                        }
                        $this->clients_gestion_mails_notif->update();

                        $total += ($this->notifications->amount / 100);

                        if ($i == $nb_arrayoffres) {
                            $liste_offres .= '
							<tr style="color:#b20066;">
								<td  style="height:25px; font-family:Arial;font-size:14px;"><a style="color:#b20066;text-decoration:none;" href="' . $this->lurl . '/projects/detail/' . $this->projects->slug . '">' . $this->projects->title . '</a></td>
								<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber(($this->notifications->amount / 100), 0) . ' &euro;</td>
								<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($this->bids->rate) . ' %</td>
							</tr>
							<tr>
								<td style="height:25px;border-top:1px solid #727272;color:#727272;font-family:Arial;font-size:14px;">Total de vos offres</td>
								<td align="right" style="border-top:1px solid #727272;color:#b20066;font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($total, 0) . ' &euro;</td>
								<td style="border-top:1px solid #727272;"></td>
							</tr>
							';
                        } else {
                            $liste_offres .= '
							<tr style="color:#b20066;">
								<td  style="height:25px;font-family:Arial;font-size:14px;"><a style="color:#b20066;text-decoration:none;" href="' . $this->lurl . '/projects/detail/' . $this->projects->slug . '">' . $this->projects->title . '</a></td>
								<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber(($this->notifications->amount / 100), 0) . ' &euro;</td>
								<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($this->bids->rate) . ' %</td>
							</tr>';
                        }
                        $i++;
                    }

                    $this->clients->get($id_client, 'id_client');

                    $p            = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))), 0, 1);
                    $nom          = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
                    $le_id_client = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
                    $motif        = mb_strtoupper($le_id_client . $p . $nom, 'UTF-8');

                    $lecontenu = '';

                    if ($nb_arrayoffres <= 1) {
                        if ($type == 'quotidienne') {
                            $sujet                     = $this->lng['email-synthese']['sujet-synthese-offres-refusees-quotidienne-singulier'];
                            $this->mails_text->subject = $this->lng['email-synthese']['sujet-synthese-offres-refusees-quotidienne-singulier'];
                            $lecontenu                 = $this->lng['email-synthese']['contenu-synthese-offres-refusees-quotidienne-singulier'];
                            $objet                     = $this->lng['email-synthese']['objet-synthese-offres-refusees-quotidienne-singulier'];
                        }
                    } else {
                        if ($type == 'quotidienne') {
                            $sujet     = $this->lng['email-synthese']['sujet-synthese-offres-refusees-quotidienne-pluriel'];
                            $lecontenu = $this->lng['email-synthese']['contenu-synthese-offres-refusees-quotidienne-pluriel'];
                            $objet     = $this->lng['email-synthese']['objet-synthese-offres-refusees-quotidienne-pluriel'];
                        }
                    }

                    $varMail = array(
                        'surl'            => $this->surl,
                        'url'             => $this->furl,
                        'prenom_p'        => $this->clients->prenom,
                        'liste_offres'    => $liste_offres,
                        'motif_virement'  => $motif,
                        'gestion_alertes' => $this->lurl . '/profile',
                        'contenu'         => $lecontenu,
                        'objet'           => $objet,
                        'sujet'           => $sujet,
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

                    if ($this->clients->status == 1) {
                        if ($this->Config['env'] == 'prod') {
                            Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                            // Injection du mail NMP dans la queue
                            $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                        } else {
                            $this->email->addRecipient($this->clients->email);
                            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        }
                    }
                } else {// si il veut pas de mail
                    foreach ($mails_notif as $n) {
                        $this->clients_gestion_mails_notif->get($n['id_clients_gestion_mails_notif'], 'id_clients_gestion_mails_notif');
                        if ($type == 'quotidienne') {
                            $this->clients_gestion_mails_notif->status_check_quotidienne = 1;
                        } elseif ($type == 'hebdomadaire') {
                            $this->clients_gestion_mails_notif->status_check_hebdomadaire = 1;
                        } elseif ($type == 'mensuelle') {
                            $this->clients_gestion_mails_notif->status_check_mensuelle = 1;
                        }
                        $this->clients_gestion_mails_notif->update();
                    }
                }
            }
            $clients_gestion_notif_log->fin                         = date('Y-m-d H:i:s');
            $clients_gestion_notif_log->id_client_gestion_notif_log = $clients_gestion_notif_log->update();
        }
    }

    // offres acceptées
    public function offres_acceptees_synthese($array_offres_acceptees, $type)
    {
        $this->clients       = $this->loadData('clients');
        $this->notifications = $this->loadData('notifications');
        $this->projects      = $this->loadData('projects');
        $this->companies     = $this->loadData('companies');
        $this->loans         = $this->loadData('loans');

        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');

        if ($array_offres_acceptees != false) {
            $clients_gestion_notif_log                              = $this->loadData('clients_gestion_notif_log');
            $clients_gestion_notif_log->id_notif                    = 4;
            $clients_gestion_notif_log->type                        = $type;
            $clients_gestion_notif_log->debut                       = date('Y-m-d H:i:s');
            $clients_gestion_notif_log->fin                         = '0000-00-00 00:00:00';
            $clients_gestion_notif_log->id_client_gestion_notif_log = $clients_gestion_notif_log->create();

            $clients_gestion_notif_log->get($clients_gestion_notif_log->id_client_gestion_notif_log, 'id_client_gestion_notif_log');

            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            foreach ($array_offres_acceptees as $id_client => $mails_notif) {
                if ($this->clients_gestion_notifications->getNotif($id_client, 4, $type) == true) {
                    $this->clients->get($id_client, 'id_client');

                    $p            = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))), 0, 1);
                    $nom          = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
                    $le_id_client = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
                    $motif        = mb_strtoupper($le_id_client . $p . $nom, 'UTF-8');

                    if (count($mails_notif) > 1 || $type != 'quotidienne') {
                        $liste_offres   = '';
                        $i              = 1;
                        $total          = 0;
                        $nb_arrayoffres = count($mails_notif);
                        $goMail         = true;
                        foreach ($mails_notif as $n) {

                            $this->notifications->get($n['id_notification'], 'id_notification');
                            $this->projects->get($this->notifications->id_project, 'id_project');
                            $this->companies->get($this->projects->id_company, 'id_company');
                            $this->loans->get($n['id_loan'], 'id_loan');

                            $this->clients_gestion_mails_notif->get($n['id_clients_gestion_mails_notif'], 'id_clients_gestion_mails_notif');
                            if ($type == 'quotidienne') {
                                $this->clients_gestion_mails_notif->quotidienne              = 1;
                                $this->clients_gestion_mails_notif->status_check_quotidienne = 1;
                            } elseif ($type == 'hebdomadaire') {
                                $this->clients_gestion_mails_notif->hebdomadaire              = 1;
                                $this->clients_gestion_mails_notif->status_check_hebdomadaire = 1;
                            } elseif ($type == 'mensuelle') {
                                $this->clients_gestion_mails_notif->mensuelle              = 1;
                                $this->clients_gestion_mails_notif->status_check_mensuelle = 1;
                            }
                            $this->clients_gestion_mails_notif->update();

                            $total += ($this->loans->amount / 100);

                            if ($i == $nb_arrayoffres) {
                                $liste_offres .= '
								<tr style="color:#b20066;">
									<td  style="height:25px;font-family:Arial;font-size:14px;"><a style="color:#b20066;text-decoration:none;" href="' . $this->lurl . '/projects/detail/' . $this->projects->slug . '">' . $this->projects->title . '</a></td>
									<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber(($this->loans->amount / 100), 0) . ' €</td>
									<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($this->loans->rate) . ' %</td>
								</tr>
								<tr>
									<td style="height:25px;border-top:1px solid #727272;color:#727272;font-family:Arial;font-size:14px;">Total de vos offres</td>
									<td align="right" style="border-top:1px solid #727272;color:#b20066;font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($total, 0) . ' €</td>
									<td style="border-top:1px solid #727272;font-family:Arial;font-size:14px;"></td>
								</tr>
								';
                            } else {
                                $liste_offres .= '
								<tr style="color:#b20066;">
									<td  style="height:25px;font-family:Arial;font-size:14px;"><a style="color:#b20066;text-decoration:none;" href="' . $this->lurl . '/projects/detail/' . $this->projects->slug . '">' . $this->projects->title . '</a></td>
									<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber(($this->loans->amount / 100), 0) . ' €</td>
									<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($this->loans->rate) . ' %</td>
								</tr>';
                            }
                            $i++;
                        }

                        if ($goMail == true) {// (BT : 18180 04/08/2015)

                            if ($type == 'quotidienne') {
                                $this->mails_text->get('synthese-quotidienne-offres-acceptees', 'lang = "' . $this->language . '" AND type');
                            } elseif ($type == 'hebdomadaire') {
                                $this->mails_text->get('synthese-hebdomadaire-offres-acceptees', 'lang = "' . $this->language . '" AND type');
                            } else {
                                $this->mails_text->get('synthese-mensuelle-offres-acceptees', 'lang = "' . $this->language . '" AND type');
                            }
                            // on gère ici le cas du singulier/pluriel
                            if ($nb_arrayoffres <= 1) {
                                if ($type == 'quotidienne') {
                                    $this->mails_text->subject = $this->lng['email-synthese']['sujet-synthese-quotidienne-offres-acceptees-singulier'];
                                    $sujet                     = $this->lng['email-synthese']['sujet-synthese-quotidienne-offres-acceptees-singulier'];
                                    $lecontenu                 = $this->lng['email-synthese']['contenu-synthese-quotidienne-offres-acceptees-singulier'];
                                    $objet                     = $this->lng['email-synthese']['objet-synthese-quotidienne-offres-acceptees-singulier'];
                                } elseif ($type == 'hebdomadaire') {
                                    $this->mails_text->subject = $this->lng['email-synthese']['sujet-synthese-hebdomadaire-offres-acceptees-singulier'];
                                    $sujet                     = $this->lng['email-synthese']['sujet-synthese-hebdomadaire-offres-acceptees-singulier'];
                                    $lecontenu                 = $this->lng['email-synthese']['contenu-synthese-hebdomadaire-offres-acceptees-singulier'];
                                    $objet                     = $this->lng['email-synthese']['objet-synthese-hebdomadaire-offres-acceptees-singulier'];
                                } elseif ($type == 'mensuelle') {
                                    $this->mails_text->subject = $this->lng['email-synthese']['sujet-synthese-mensuelle-offres-acceptees-singulier'];
                                    $sujet                     = $this->lng['email-synthese']['sujet-synthese-mensuelle-offres-acceptees-singulier'];
                                    $lecontenu                 = $this->lng['email-synthese']['contenu-synthese-mensuelle-offres-acceptees-singulier'];
                                    $objet                     = $this->lng['email-synthese']['objet-synthese-mensuelle-offres-acceptees-singulier'];
                                }
                            } else {
                                if ($type == 'quotidienne') {
                                    $sujet     = $this->lng['email-synthese']['sujet-synthese-quotidienne-offres-acceptees-pluriel'];
                                    $lecontenu = $this->lng['email-synthese']['contenu-synthese-quotidienne-offres-acceptees-pluriel'];
                                    $objet     = $this->lng['email-synthese']['objet-synthese-quotidienne-offres-acceptees-pluriel'];
                                } elseif ($type == 'hebdomadaire') {
                                    $sujet     = $this->lng['email-synthese']['sujet-synthese-hebdomadaire-offres-acceptees-pluriel'];
                                    $lecontenu = $this->lng['email-synthese']['contenu-synthese-hebdomadaire-offres-acceptees-pluriel'];
                                    $objet     = $this->lng['email-synthese']['objet-synthese-hebdomadaire-offres-acceptees-pluriel'];
                                } elseif ($type == 'mensuelle') {
                                    $sujet     = $this->lng['email-synthese']['sujet-synthese-mensuelle-offres-acceptees-pluriel'];
                                    $lecontenu = $this->lng['email-synthese']['contenu-synthese-mensuelle-offres-acceptees-pluriel'];
                                    $objet     = $this->lng['email-synthese']['objet-synthese-mensuelle-offres-acceptees-pluriel'];
                                }
                            }

                            $varMail = array(
                                'surl'            => $this->surl,
                                'url'             => $this->furl,
                                'prenom_p'        => $this->clients->prenom,
                                'liste_offres'    => $liste_offres,
                                'motif_virement'  => $motif,
                                'contenu'         => $lecontenu,
                                'objet'           => $objet,
                                'sujet'           => $sujet,
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

                            if ($this->clients->status == 1) {
                                if ($this->Config['env'] == 'prod') {
                                    Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                                    $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                                } else {
                                    $this->email->addRecipient($this->clients->email);
                                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                                }
                            }
                        }
                    }
                } else {// si il veut pas de mail
                    foreach ($mails_notif as $n) {
                        $this->clients_gestion_mails_notif->get($n['id_clients_gestion_mails_notif'], 'id_clients_gestion_mails_notif');
                        if ($type == 'quotidienne') {
                            $this->clients_gestion_mails_notif->status_check_quotidienne = 1;
                        } elseif ($type == 'hebdomadaire') {
                            $this->clients_gestion_mails_notif->status_check_hebdomadaire = 1;
                        } elseif ($type == 'mensuelle') {
                            $this->clients_gestion_mails_notif->status_check_mensuelle = 1;
                        }
                        $this->clients_gestion_mails_notif->update();
                    }
                }
            }
            $clients_gestion_notif_log->fin                         = date('Y-m-d H:i:s');
            $clients_gestion_notif_log->id_client_gestion_notif_log = $clients_gestion_notif_log->update();
        }
    }

    // remb
    public function remb_synthese($array_remb, $type)
    {
        $this->clients       = $this->loadData('clients');
        $this->notifications = $this->loadData('notifications');
        $this->projects      = $this->loadData('projects');
        $this->companies     = $this->loadData('companies');
        $this->echeanciers   = $this->loadData('echeanciers');
        $this->loans         = $this->loadData('loans');

        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');

        if ($array_remb != false) {
            $clients_gestion_notif_log                              = $this->loadData('clients_gestion_notif_log');
            $clients_gestion_notif_log->id_notif                    = 5;
            $clients_gestion_notif_log->type                        = $type;
            $clients_gestion_notif_log->debut                       = date('Y-m-d H:i:s');
            $clients_gestion_notif_log->fin                         = '0000-00-00 00:00:00';
            $clients_gestion_notif_log->id_client_gestion_notif_log = $clients_gestion_notif_log->create();

            $clients_gestion_notif_log->get($clients_gestion_notif_log->id_client_gestion_notif_log, 'id_client_gestion_notif_log');

            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            foreach ($array_remb as $id_client => $mails_notif) {
                if ($this->clients_gestion_notifications->getNotif($id_client, 5, $type) == true) {
                    if ($type == 'quotidienne') {
                        $this->mails_text->get('synthese-quotidienne-remboursements', 'lang = "' . $this->language . '" AND type');
                    } elseif ($type == 'hebdomadaire') {
                        $this->mails_text->get('synthese-hebdomadaire-remboursements', 'lang = "' . $this->language . '" AND type');
                    } else {
                        $this->mails_text->get('synthese-mensuelle-remboursements', 'lang = "' . $this->language . '" AND type');
                    }

                    $liste_remb   = '';
                    $i            = 1;
                    $nb_arrayRemb = count($mails_notif);

                    $totalinteretsNet = 0;
                    $totalinterets    = 0;
                    $totalcapital     = 0;

                    foreach ($mails_notif as $n) {

                        $this->notifications->get($n['id_notification'], 'id_notification');
                        $this->projects->get($this->notifications->id_project, 'id_project');
                        $this->companies->get($this->projects->id_company, 'id_company');

                        $this->transactions->get($n['id_transaction'], 'id_transaction');
                        $this->echeanciers->get($this->transactions->id_echeancier, 'id_echeancier');

                        $this->clients_gestion_mails_notif->get($n['id_clients_gestion_mails_notif'], 'id_clients_gestion_mails_notif');
                        if ($type == 'quotidienne') {
                            $this->clients_gestion_mails_notif->quotidienne              = 1;
                            $this->clients_gestion_mails_notif->status_check_quotidienne = 1;
                        } elseif ($type == 'hebdomadaire') {
                            $this->clients_gestion_mails_notif->hebdomadaire              = 1;
                            $this->clients_gestion_mails_notif->status_check_hebdomadaire = 1;
                        } elseif ($type == 'mensuelle') {
                            $this->clients_gestion_mails_notif->mensuelle              = 1;
                            $this->clients_gestion_mails_notif->status_check_mensuelle = 1;
                        }
                        $this->clients_gestion_mails_notif->update();
                        // On gère ici le cas ou on est dans un remboursement anticipé (on a pas de id_echeance car plusieurs echeances)
                        $contenu_remboursement_anticipe = "";
                        if ($this->transactions->type_transaction == 23) {
                            $this->echeanciers->prelevements_obligatoires    = 0;
                            $this->echeanciers->retenues_source              = 0;
                            $this->echeanciers->csg                          = 0;
                            $this->echeanciers->prelevements_sociaux         = 0;
                            $this->echeanciers->contributions_additionnelles = 0;
                            $this->echeanciers->prelevements_solidarite      = 0;
                            $this->echeanciers->crds                         = 0;
                            $this->echeanciers->interets                     = 0;
                            $this->echeanciers->capital                      = $this->transactions->montant;

                            $montantHaut = 0;
                            $montantBas  = 0;
                            foreach ($this->loans->select('id_project = ' . $this->projects->id_project . ' AND id_loan = ' . $this->transactions->id_loan_remb) as $b) {
                                $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                                $montantBas += ($b['amount'] / 100);
                            }
                            $AvgLoans = ($montantHaut / $montantBas);

                            $sumInt = $this->echeanciers->getSumRembByloan_remb_ra($this->transactions->id_loan_remb, 'interets');

                            $contenu_remboursement_anticipe = "
                            Important : le remboursement de <span style='color: #b20066;'>" . $this->ficelle->formatNumber($this->echeanciers->capital / 100) . "&euro;</span> correspond au remboursement total du capital restant dû de votre prêt à <span style='color: #b20066;'>" . $this->companies->name . "</span>. Comme le prévoient les règles d'Unilend, <span style='color: #b20066;'>" . $this->companies->name . "</span> a choisi de rembourser son emprunt par anticipation sans frais.
                            <br /><br />
                            Depuis l’origine, il vous a versé <span style='color: #b20066;'>" . $this->ficelle->formatNumber($sumInt) . "€</span> d’intérêts soit un taux d’intérêt annualisé moyen de <span style='color: #b20066;'>" . number_format($AvgLoans) . "%.</span><br><br> ";

                        } else {
                            $this->echeanciers->get($this->transactions->id_echeancier, 'id_echeancier');
                        }

                        $totalFiscal = ($this->echeanciers->prelevements_obligatoires + $this->echeanciers->retenues_source + $this->echeanciers->csg + $this->echeanciers->prelevements_sociaux + $this->echeanciers->contributions_additionnelles + $this->echeanciers->prelevements_solidarite + $this->echeanciers->crds);

                        $totalinteretsNet += ($this->echeanciers->interets / 100) - $totalFiscal;
                        $totalinterets += ($this->echeanciers->interets / 100);
                        $totalcapital += ($this->echeanciers->capital / 100);

                        if ($i == $nb_arrayRemb) {
                            $liste_remb .= '
							<tr style="color:#b20066;">
								<td  style="height:25px;font-family:Arial;font-size:14px;"><a style="color:#b20066;text-decoration:none;" href="' . $this->lurl . '/projects/detail/' . $this->projects->slug . '">' . $this->projects->title . '</a></td>
								<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($this->echeanciers->capital / 100) . ' &euro;</td>
								<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($this->echeanciers->interets / 100) . ' &euro;</td>
								<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($this->echeanciers->interets / 100 - $totalFiscal) . ' &euro;</td>
							</tr>
							<tr>
								<td style="height:25px;font-family:Arial;font-size:14px;border-top:1px solid #727272;color:#727272;">Total</td>
								<td align="right" style="font-family:Arial;font-size:14px;color:#b20066;border-top:1px solid #727272;">' . $this->ficelle->formatNumber($totalcapital) . ' &euro;</td>
								<td align="right" style="font-family:Arial;font-size:14px;color:#b20066;border-top:1px solid #727272;">' . $this->ficelle->formatNumber($totalinterets) . ' &euro;</td>
								<td align="right" style="font-family:Arial;font-size:14px;color:#b20066;border-top:1px solid #727272;">' . $this->ficelle->formatNumber($totalinteretsNet) . ' &euro;</td>
							</tr>
							';
                        } else {
                            $liste_remb .= '
							<tr style="color:#b20066;">
								<td  style="height:25px;font-family:Arial;font-size:14px;"><a style="color:#b20066;text-decoration:none;" href="' . $this->lurl . '/projects/detail/' . $this->projects->slug . '">' . $this->projects->title . '</a></td>
								<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($this->echeanciers->capital / 100) . ' &euro;</td>
								<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($this->echeanciers->interets / 100) . ' &euro;</td>
								<td align="right" style="font-family:Arial;font-size:14px;">' . $this->ficelle->formatNumber($this->echeanciers->interets / 100 - $totalFiscal) . ' &euro;</td>
							</tr>';
                        }
                        $i++;
                    }

                    $this->clients->get($id_client, 'id_client');

                    $p            = substr($this->ficelle->stripAccents(utf8_decode(trim($this->clients->prenom))), 0, 1);
                    $nom          = $this->ficelle->stripAccents(utf8_decode(trim($this->clients->nom)));
                    $le_id_client = str_pad($this->clients->id_client, 6, 0, STR_PAD_LEFT);
                    $motif        = mb_strtoupper($le_id_client . $p . $nom, 'UTF-8');

                    $getsolde = $this->transactions->getSolde($this->clients->id_client);

                    if ($this->Config['env'] != 'prod') {
                        $liste_remb = utf8_decode($liste_remb);
                    }

                    $lecontenu = '';
                    // on gère ici le cas du singulier/pluriel
                    if ($nb_arrayRemb <= 1) {
                        if ($type == 'quotidienne') {
                            $this->mails_text->subject = $this->lng['email-synthese']['sujet-synthese-quotidienne-singulier'];
                            $sujet                     = $this->lng['email-synthese']['sujet-synthese-quotidienne-singulier'];
                            $lecontenu                 = $this->lng['email-synthese']['contenu-synthese-quotidienne-singulier'];
                        } elseif ($type == 'hebdomadaire') {
                            $this->mails_text->subject = $this->lng['email-synthese']['sujet-synthese-hebdomadaire-singulier'];
                            $sujet                     = $this->lng['email-synthese']['sujet-synthese-hebdomadaire-singulier'];
                            $lecontenu                 = $this->lng['email-synthese']['contenu-synthese-quotidienne-singulier'];
                        } elseif ($type == 'mensuelle') {
                            $sujet                     = $this->lng['email-synthese']['sujet-synthese-mensuelle-singulier'];
                            $this->mails_text->subject = $this->lng['email-synthese']['sujet-synthese-mensuelle-singulier'];
                            $lecontenu                 = $this->lng['email-synthese']['contenu-synthese-quotidienne-singulier'];
                        }
                    } else {
                        if ($type == 'quotidienne') {
                            $sujet     = $this->lng['email-synthese']['sujet-synthese-quotidienne-pluriel'];
                            $lecontenu = $this->lng['email-synthese']['contenu-synthese-quotidienne-pluriel'];
                        } elseif ($type == 'hebdomadaire') {
                            $sujet     = $this->lng['email-synthese']['sujet-synthese-hebdomadaire-pluriel'];
                            $lecontenu = $this->lng['email-synthese']['contenu-synthese-hebdomadaire-pluriel'];
                        } elseif ($type == 'mensuelle') {
                            $sujet     = $this->lng['email-synthese']['sujet-synthese-mensuelle-pluriel'];
                            $lecontenu = $this->lng['email-synthese']['contenu-synthese-mensuelle-pluriel'];
                        }
                    }

                    $varMail = array(
                        'surl'                   => $this->surl,
                        'url'                    => $this->furl,
                        'prenom_p'               => $this->clients->prenom,
                        'liste_offres'           => $liste_remb,
                        'motif_virement'         => $motif,
                        'gestion_alertes'        => $this->lurl . '/profile',
                        'montant_dispo'          => $this->ficelle->formatNumber($getsolde),
                        'remboursement_anticipe' => $contenu_remboursement_anticipe,
                        'contenu'                => $lecontenu,
                        'sujet'                  => $sujet,
                        'lien_fb'                => $lien_fb,
                        'lien_tw'                => $lien_tw
                    );

                    $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                    $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                    $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                    $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                    $this->email = $this->loadLib('email');
                    $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                    $this->email->setSubject(stripslashes($sujetMail));
                    $this->email->setHTMLBody(stripslashes($texteMail));

                    if ($this->clients->status == 1) {
                        if ($this->Config['env'] == 'prod') {
                            Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                            // Injection du mail NMP dans la queue
                            $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                        } else {
                            $this->email->addRecipient($this->clients->email);
                            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        }
                    }
                } else {// si il veut pas de mail
                    // On ajout un statut comme quoi on a deja checké
                    foreach ($mails_notif as $n) {
                        $this->clients_gestion_mails_notif->get($n['id_clients_gestion_mails_notif'], 'id_clients_gestion_mails_notif');
                        if ($type == 'quotidienne') {
                            $this->clients_gestion_mails_notif->status_check_quotidienne = 1;
                        } elseif ($type == 'hebdomadaire') {
                            $this->clients_gestion_mails_notif->status_check_hebdomadaire = 1;
                        } elseif ($type == 'mensuelle') {
                            $this->clients_gestion_mails_notif->status_check_mensuelle = 1;
                        }
                        $this->clients_gestion_mails_notif->update();
                    }
                }
            }
            $clients_gestion_notif_log->fin                         = date('Y-m-d H:i:s');
            $clients_gestion_notif_log->id_client_gestion_notif_log = $clients_gestion_notif_log->update();
        }
    }

    // 1 fois par jour on regarde si on a une offre de parrainage a traiter pour donner l'argent
    public function _offre_parrainage()
    {
        die;
        $offres_parrains_filleuls     = $this->loadData('offres_parrains_filleuls'); // offre parrainage
        $parrains_filleuls            = $this->loadData('parrains_filleuls'); // liste des parrains et filleuls
        $parrains_filleuls_mouvements = $this->loadData('parrains_filleuls_mouvements');
        $transactions                 = $this->loadData('transactions');
        $wallets_lines                = $this->loadData('wallets_lines');
        $lenders_accounts             = $this->loadData('lenders_accounts');
        $bank_unilend                 = $this->loadData('bank_unilend');

        $parrain = $this->loadData('clients');
        $filleul = $this->loadData('clients');

        if ($offres_parrains_filleuls->get(1, 'status = 0 AND id_offre_parrain_filleul')) {
            $lparrains_filleuls = $parrains_filleuls->select('status = 1 AND etat = 0');
            foreach ($lparrains_filleuls as $pf) {
                $sumParrain           = $parrains_filleuls->sum('etat = 1 AND id_parrain = ' . $pf['id_parrain'], 'gains_parrain');
                $sumParrainPlusLeGain = $sumParrain + $pf['gains_parrain'];

                $nbFilleuls            = $parrains_filleuls->counter('etat = 1 AND id_parrain = ' . $pf['id_parrain']);
                $parrain_limit_filleul = $offres_parrains_filleuls->parrain_limit_filleul;

                if ($sumParrainPlusLeGain <= $offres_parrains_filleuls->limite_montant_gains_parrains || $nbFilleuls > $parrain_limit_filleul) {
                    $parrains_filleuls->get($pf['id_parrain_filleul'], 'id_parrain_filleul');
                    $parrains_filleuls->etat = 1;
                    $parrains_filleuls->update();

                    $lenders_accounts->get($pf['id_parrain'], 'id_client_owner');
                    $parrain->get($pf['id_parrain'], 'id_client');

                    $transactions->id_client          = $pf['id_parrain'];
                    $transactions->montant            = $pf['gains_parrain'];
                    $transactions->id_parrain_filleul = $pf['id_parrain_filleul'];
                    $transactions->id_langue          = 'fr';
                    $transactions->date_transaction   = date('Y-m-d H:i:s');
                    $transactions->status             = '1';
                    $transactions->etat               = '1';
                    $transactions->ip_client          = $_SERVER['REMOTE_ADDR'];
                    $transactions->type_transaction   = 20; // Gain parrain
                    $transactions->transaction        = 2; // transaction virtuelle
                    $transactions->id_transaction     = $transactions->create();

                    $wallets_lines->id_lender                = $lenders_accounts->id_lender_account;
                    $wallets_lines->type_financial_operation = 30; // alimentation
                    $wallets_lines->id_transaction           = $transactions->id_transaction;
                    $wallets_lines->status                   = 1;
                    $wallets_lines->type                     = 2; // transaction virtuelle
                    $wallets_lines->amount                   = $pf['gains_parrain'];
                    $wallets_lines->id_wallet_line           = $wallets_lines->create();

                    $bank_unilend->id_transaction = $transactions->id_transaction;
                    $bank_unilend->montant        = '-' . $pf['gains_parrain'];  // on retire cette somme du total dispo
                    $bank_unilend->type           = 4; // Unilend offre de bienvenue/parrainage
                    $bank_unilend->create();

                    $parrains_filleuls_mouvements->id_parrain_filleul = $pf['id_parrain_filleul'];
                    $parrains_filleuls_mouvements->id_client          = $pf['id_parrain'];
                    $parrains_filleuls_mouvements->type_preteur       = 1;
                    $parrains_filleuls_mouvements->montant            = $pf['gains_parrain'];
                    $parrains_filleuls_mouvements->id_bid             = 0;
                    $parrains_filleuls_mouvements->id_bid_remb        = 0;
                    $parrains_filleuls_mouvements->status             = 0;
                    $parrains_filleuls_mouvements->type               = 0;
                    $parrains_filleuls_mouvements->create();

                    $destinataire = $parrain->email;
                    $this->mails_text->get('confirmation-offre-parrain', 'lang = "' . $this->language . '" AND type');

                    $varMail = array(
                        'surl'            => $this->surl,
                        'url'             => $this->lurl,
                        'nom_parrain'     => $parrain->prenom,
                        'montant_parrain' => ($pf['gains_parrain'] / 100),
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
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $destinataire, $tabFiler);
                        // Injection du mail NMP dans la queue
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($destinataire));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }

                    $lenders_accounts->get($pf['id_filleul'], 'id_client_owner');
                    $filleul->get($pf['id_filleul'], 'id_client');

                    $transactions->id_client          = $pf['id_filleul'];
                    $transactions->montant            = $pf['gains_filleul'];
                    $transactions->id_parrain_filleul = $pf['id_parrain_filleul'];
                    $transactions->id_langue          = 'fr';
                    $transactions->date_transaction   = date('Y-m-d H:i:s');
                    $transactions->status             = '1';
                    $transactions->etat               = '1';
                    $transactions->ip_client          = $_SERVER['REMOTE_ADDR'];
                    $transactions->type_transaction   = 19; // Gain filleul
                    $transactions->transaction        = 2; // transaction virtuelle
                    $transactions->id_transaction     = $transactions->create();

                    $wallets_lines->id_lender                = $lenders_accounts->id_lender_account;
                    $wallets_lines->type_financial_operation = 30; // alimentation
                    $wallets_lines->id_transaction           = $transactions->id_transaction;
                    $wallets_lines->status                   = 1;
                    $wallets_lines->type                     = 2; // transaction virtuelle
                    $wallets_lines->amount                   = $pf['gains_filleul'];
                    $wallets_lines->id_wallet_line           = $wallets_lines->create();

                    $bank_unilend->id_transaction = $transactions->id_transaction;
                    $bank_unilend->montant        = '-' . $pf['gains_filleul'];  // on retire cette somme du total dispo
                    $bank_unilend->type           = 4; // Unilend offre de bienvenue/parrainage
                    $bank_unilend->create();

                    $parrains_filleuls_mouvements->id_parrain_filleul = $pf['id_parrain_filleul'];
                    $parrains_filleuls_mouvements->id_client          = $pf['id_filleul'];
                    $parrains_filleuls_mouvements->type_preteur       = 2;
                    $parrains_filleuls_mouvements->montant            = $pf['gains_filleul'];
                    $parrains_filleuls_mouvements->id_bid             = 0;
                    $parrains_filleuls_mouvements->id_bid_remb        = 0;
                    $parrains_filleuls_mouvements->status             = 0;
                    $parrains_filleuls_mouvements->type               = 0;
                    $parrains_filleuls_mouvements->create();

                    $destinataire = $filleul->email;
                    $this->mails_text->get('confirmation-offre-filleul', 'lang = "' . $this->language . '" AND type');

                    $varMail = array(
                        'surl'            => $this->surl,
                        'url'             => $this->lurl,
                        'nom_filleul'     => $filleul->prenom,
                        'montant_filleul' => ($pf['gains_filleul'] / 100),
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
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $destinataire, $tabFiler);
                        // Injection du mail NMP dans la queue
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($destinataire));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }
                } else {// si limite depassé on rejet l'offre de parrainage
                    $parrains_filleuls->get($pf['id_parrain_filleul'], 'id_parrain_filleul');
                    $parrains_filleuls->etat = 2;
                    $parrains_filleuls->update();
                }
            }// fin boucle
        }
    }

    // Toutes les minutes (cron en place) le 27/01/2015
    public function _send_email_remb_auto()
    {
        if (true === $this->startCron('send_email_remb_auto', 5)) {

            $echeanciers             = $this->loadData('echeanciers');
            $transactions            = $this->loadData('transactions');
            $lenders                 = $this->loadData('lenders_accounts');
            $clients                 = $this->loadData('clients');
            $companies               = $this->loadData('companies');
            $notifications           = $this->loadData('notifications');
            $loans                   = $this->loadData('loans');
            $projects_status_history = $this->loadData('projects_status_history');
            $projects                = $this->loadData('projects');

            // ajout KLE - 28-07-15 BT : 18157 *** pour ne pas envoyer de mail tant que le remb auto n'est pas terminé
            // On recup le param du remb auto
            $settingsControleRemb = $this->loadData('settings');
            $settingsControleRemb->get('Controle remboursements auto', 'type');

            // on rentre dans le cron si statut égale 1
            if ($settingsControleRemb->value == 1) {
                // BIEN PRENDRE EN COMPTE LA DATE DE DEBUT DE LA REQUETE POUR NE PAS TRATER LES ANCIENS PROJETS REMB <------------------------------------| !!!!!!!!!
                $lEcheances = $echeanciers->selectEcheances_a_remb('status = 1 AND status_email_remb = 0 AND status_emprunteur = 1 AND LEFT(date_echeance,10) > "2015-06-30"', '', 0, 300); // on limite a 300 mails par executions

                foreach ($lEcheances as $e) {
                    if ($transactions->get($e['id_echeancier'], 'id_echeancier') == true) {
                        $dernierStatut     = $projects_status_history->select('id_project = ' . $e['id_project'], 'added DESC', 0, 1);
                        $dateDernierStatut = $dernierStatut[0]['added'];

                        $timeAdd = strtotime($dateDernierStatut);
                        $day     = date('d', $timeAdd);
                        $month   = $this->dates->tableauMois['fr'][date('n', $timeAdd)];
                        $year    = date('Y', $timeAdd);

                        $rembNet = $e['rembNet'];

                        $lenders->get($e['id_lender'], 'id_lender_account');
                        $clients->get($lenders->id_client_owner, 'id_client');
                        $projects->get($e['id_project'], 'id_project');
                        $companies->get($projects->id_company, 'id_company');

                        $p         = substr($this->ficelle->stripAccents(utf8_decode(trim($clients->prenom))), 0, 1);
                        $nom       = $this->ficelle->stripAccents(utf8_decode(trim($clients->nom)));
                        $id_client = str_pad($clients->id_client, 6, 0, STR_PAD_LEFT);
                        $motif     = mb_strtoupper($id_client . $p . $nom, 'UTF-8');

                        $this->mails_text->get('preteur-remboursement', 'lang = "' . $this->language . '" AND type');

                        $nbpret = $loans->counter('id_lender = ' . $e['id_lender'] . ' AND id_project = ' . $e['id_project']);

                        if ($rembNet >= 2) {
                            $euros = ' euros';
                        } else {
                            $euros = ' euro';
                        }
                        $rembNetEmail = $this->ficelle->formatNumber($rembNet) . $euros;

                        $getsolde = $transactions->getSolde($clients->id_client);
                        if ($getsolde > 1) {
                            $euros = ' euros';
                        } else {
                            $euros = ' euro';
                        }
                        $solde = $this->ficelle->formatNumber($getsolde) . $euros;

                        $this->settings->get('Facebook', 'type');
                        $lien_fb = $this->settings->value;

                        $this->settings->get('Twitter', 'type');
                        $lien_tw = $this->settings->value;

                        $varMail = array(
                            'surl'                  => $this->surl,
                            'url'                   => $this->furl,
                            'prenom_p'              => $clients->prenom,
                            'mensualite_p'          => $rembNetEmail,
                            'mensualite_avantfisca' => ($e['montant'] / 100),
                            'nom_entreprise'        => $companies->name,
                            'date_bid_accepte'      => $day . ' ' . $month . ' ' . $year,
                            'nbre_prets'            => $nbpret,
                            'solde_p'               => $solde,
                            'motif_virement'        => $motif,
                            'lien_fb'               => $lien_fb,
                            'lien_tw'               => $lien_tw
                        );

                        $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                        $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                        $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                        $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                        $this->email = $this->loadLib('email');
                        $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                        $this->email->setSubject(stripslashes($sujetMail));
                        $this->email->setHTMLBody(stripslashes($texteMail));

                        $notifications->type            = 2; // remb
                        $notifications->id_lender       = $e['id_lender'];
                        $notifications->id_project      = $e['id_project'];
                        $notifications->amount          = ($rembNet * 100);
                        $notifications->id_notification = $notifications->create();

                        $this->clients_gestion_mails_notif                                 = $this->loadData('clients_gestion_mails_notif');
                        $this->clients_gestion_mails_notif->id_client                      = $lenders->id_client_owner;
                        $this->clients_gestion_mails_notif->id_notif                       = 5; // remb preteur
                        $this->clients_gestion_mails_notif->date_notif                     = date('Y-m-d H:i:s');
                        $this->clients_gestion_mails_notif->id_notification                = $notifications->id_notification;
                        $this->clients_gestion_mails_notif->id_transaction                 = $transactions->id_transaction;
                        $this->clients_gestion_mails_notif->id_clients_gestion_mails_notif = $this->clients_gestion_mails_notif->create();

                        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');

                        if ($this->clients_gestion_notifications->getNotif($clients->id_client, 5, 'immediatement') == true) {
                            $this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                            $this->clients_gestion_mails_notif->immediatement = 1; // on met a jour le statut immediatement
                            $this->clients_gestion_mails_notif->update();

                            if ($clients->status == 1) {
                                if ($this->Config['env'] == 'prod') {
                                    Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $clients->email, $tabFiler);
                                    $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                                } else {
                                    $this->email->addRecipient(trim($clients->email));
                                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                                }
                            }
                        }
                        $echeanciers->get($e['id_echeancier'], 'id_echeancier');
                        $echeanciers->status_email_remb = 1;
                        $echeanciers->update();
                    } // fin check transasction existante
                }
            }

            $this->stopCron();
        }
        die;
    }

    // Toutes les 5 minutes (cron en place)	le 27/01/2015
    public function _remboursement_preteurs_auto()
    {
        if (true === $this->startCron('remboursements auto', 5)) {

            $projects                = $this->loadData('projects');
            $echeanciers_emprunteur  = $this->loadData('echeanciers_emprunteur');
            $echeanciers             = $this->loadData('echeanciers');
            $companies               = $this->loadData('companies');
            $transactions            = $this->loadData('transactions');
            $lenders                 = $this->loadData('lenders_accounts');
            $clients                 = $this->loadData('clients');
            $projects_status_history = $this->loadData('projects_status_history');
            $wallets_lines           = $this->loadData('wallets_lines');
            $projects_remb_log       = $this->loadData('projects_remb_log');
            $bank_unilend            = $this->loadData('bank_unilend');
            $projects_remb           = $this->loadData('projects_remb');
            $oAccountUnilend = $this->loadData('platform_account_unilend');

            $settingsDebutRembAuto = $this->loadData('settings');
            $settingsDebutRembAuto->get('Heure de début de traitement des remboursements auto prêteurs', 'type');
            $paramDebut = $settingsDebutRembAuto->value;

            $timeDebut = strtotime(date('Y-m-d') . ' ' . $paramDebut . ':00'); // on commence le traitement du cron a l'heure demandé
            $timeFin   = mktime(0, 0, 0, date("m"), date("d") + 1, date("Y")); // on termine le cron a minuit

            if (date('H:i') == $paramDebut) {
                $this->check_remboursement_preteurs();
            } elseif ($timeDebut <= time() && $timeFin >= time()) { // Traitement des remb toutes les 5mins
                $lProjetsAremb = $projects_remb->select('status = 0 AND LEFT(date_remb_preteurs,10) <= "' . date('Y-m-d') . '"', '', 0, 1);
                if ($lProjetsAremb != false) {
                    foreach ($lProjetsAremb as $r) {
                        $projects_remb_log->id_project          = $r['id_project'];
                        $projects_remb_log->ordre               = $r['ordre'];
                        $projects_remb_log->debut               = date('Y-m-d H:i:s');
                        $projects_remb_log->fin                 = '0000-00-00 00:00:00';
                        $projects_remb_log->montant_remb_net    = 0;
                        $projects_remb_log->etat                = 0;
                        $projects_remb_log->nb_pret_remb        = 0;
                        $projects_remb_log->id_project_remb_log = $projects_remb_log->create();

                        $projects_remb_log->get($projects_remb_log->id_project_remb_log, 'id_project_remb_log');

                        $dernierStatut     = $projects_status_history->select('id_project = ' . $r['id_project'], 'added DESC', 0, 1);
                        $dateDernierStatut = $dernierStatut[0]['added'];

                        $timeAdd = strtotime($dateDernierStatut);
                        $day     = date('d', $timeAdd);
                        $month   = $this->dates->tableauMois['fr'][date('n', $timeAdd)];
                        $year    = date('Y', $timeAdd);
                        $Total_rembNet = 0;
                        $lEcheances = $echeanciers->selectEcheances_a_remb('id_project = ' . $r['id_project'] . ' AND status_emprunteur = 1 AND ordre = ' . $r['ordre'] . ' AND status = 0');
                        if ($lEcheances != false) {
                            $Total_etat    = 0;
                            $nb_pret_remb  = 0;

                            foreach ($lEcheances as $e) {
                                if ($transactions->get($e['id_echeancier'], 'id_echeancier') == false) {
                                    $rembNet = $e['rembNet'];
                                    $etat    = $e['etat'];

                                    $Total_rembNet += $rembNet;
                                    $Total_etat += $etat;
                                    $nb_pret_remb = ($nb_pret_remb + 1);

                                    $lenders->get($e['id_lender'], 'id_lender_account');
                                    $clients->get($lenders->id_client_owner, 'id_client');
                                    $companies->get($projects->id_company, 'id_company');

                                    $echeanciers->get($e['id_echeancier'], 'id_echeancier');
                                    $echeanciers->status             = 1; // remboursé
                                    $echeanciers->date_echeance_reel = date('Y-m-d H:i:s');
                                    $echeanciers->update();

                                    $transactions->id_client        = $lenders->id_client_owner;
                                    $transactions->montant          = ($rembNet * 100);
                                    $transactions->id_echeancier    = $e['id_echeancier']; // id de l'echeance remb
                                    $transactions->id_langue        = 'fr';
                                    $transactions->date_transaction = date('Y-m-d H:i:s');
                                    $transactions->status           = '1';
                                    $transactions->etat             = '1';
                                    $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                    $transactions->type_transaction = 5; // remb enchere
                                    $transactions->transaction      = 2; // transaction virtuelle
                                    $transactions->id_transaction   = $transactions->create();

                                    $wallets_lines->id_lender                = $e['id_lender'];
                                    $wallets_lines->type_financial_operation = 40;
                                    $wallets_lines->id_transaction           = $transactions->id_transaction;
                                    $wallets_lines->status                   = 1; // non utilisé
                                    $wallets_lines->type                     = 2; // transaction virtuelle
                                    $wallets_lines->amount                   = ($rembNet * 100);
                                    $wallets_lines->id_wallet_line           = $wallets_lines->create();
                                } // fin check transasction existante
                            } // fin boucle echeances preteurs
                        }

                        if ($Total_rembNet > 0) {
                            $emprunteur = $this->loadData('clients');

                            $projects->get($r['id_project'], 'id_project');
                            $companies->get($projects->id_company, 'id_company');
                            $emprunteur->get($companies->id_client_owner, 'id_client');
                            $echeanciers_emprunteur->get($r['id_project'], ' ordre = ' . $r['ordre'] . ' AND id_project');

                            $transactions->montant                  = 0;
                            $transactions->id_echeancier            = 0; // on reinitialise
                            $transactions->id_client                = 0; // on reinitialise
                            $transactions->montant_unilend          = '-' . $Total_rembNet * 100;
                            $transactions->montant_etat             = $Total_etat * 100;
                            $transactions->id_echeancier_emprunteur = $echeanciers_emprunteur->id_echeancier_emprunteur; // id de l'echeance emprunteur
                            $transactions->id_langue                = 'fr';
                            $transactions->date_transaction         = date('Y-m-d H:i:s');
                            $transactions->status                   = '1';
                            $transactions->etat                     = '1';
                            $transactions->ip_client                = $_SERVER['REMOTE_ADDR'];
                            $transactions->type_transaction         = 10; // remb unilend pour les preteurs
                            $transactions->transaction              = 2; // transaction virtuelle
                            $transactions->id_transaction           = $transactions->create();

                            $bank_unilend->id_transaction         = $transactions->id_transaction;
                            $bank_unilend->id_project             = $r['id_project'];
                            $bank_unilend->montant                = '-' . $Total_rembNet * 100;
                            $bank_unilend->etat                   = $Total_etat * 100;
                            $bank_unilend->type                   = 2; // remb unilend
                            $bank_unilend->id_echeance_emprunteur = $echeanciers_emprunteur->id_echeancier_emprunteur;
                            $bank_unilend->status                 = 1;
                            $bank_unilend->create();

                            $oAccountUnilend->addEcheanceCommssion($echeanciers_emprunteur->id_echeancier_emprunteur);

                            $this->mails_text->get('facture-emprunteur-remboursement', 'lang = "' . $this->language . '" AND type');

                            $this->settings->get('Facebook', 'type');
                            $lien_fb = $this->settings->value;

                            $this->settings->get('Twitter', 'type');
                            $lien_tw = $this->settings->value;

                            $varMail = array(
                                'surl'            => $this->surl,
                                'url'             => $this->furl,
                                'prenom'          => $emprunteur->prenom,
                                'pret'            => $this->ficelle->formatNumber($projects->amount),
                                'entreprise'      => stripslashes(trim($companies->name)),
                                'projet-title'    => $projects->title,
                                'compte-p'        => $this->furl,
                                'projet-p'        => $this->furl . '/projects/detail/' . $projects->slug,
                                'link_facture'    => $this->furl . '/pdf/facture_ER/' . $emprunteur->hash . '/' . $r['id_project'] . '/' . $r['ordre'],
                                'datedelafacture' => $day . ' ' . $month . ' ' . $year,
                                'mois'            => strtolower($this->dates->tableauMois['fr'][date('n')]),
                                'annee'           => date('Y'),
                                'lien_fb'         => $lien_fb,
                                'lien_tw'         => $lien_tw,
                                'montantRemb'     => $Total_rembNet
                            );

                            $tabVars   = $this->tnmp->constructionVariablesServeur($varMail);
                            $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                            $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                            $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                            $this->email = $this->loadLib('email');
                            $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                            $this->email->setSubject(stripslashes($sujetMail));
                            $this->email->setHTMLBody(stripslashes($texteMail));

                            if ($this->Config['env'] == 'prod') {
                                Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, trim($companies->email_facture), $tabFiler);
                                $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                            } else {
                                $this->email->addRecipient(trim($companies->email_facture));
                                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                            }

                            $lesRembEmprun = $bank_unilend->select('type = 1 AND status = 0 AND id_project = ' . $r['id_project']);

                            foreach ($lesRembEmprun as $leR) {
                                $bank_unilend->get($leR['id_unilend'], 'id_unilend');
                                $bank_unilend->status = 1;
                                $bank_unilend->update();
                            }

                            $projects_remb->get($r['id_project_remb'], 'id_project_remb');
                            $projects_remb->date_remb_preteurs_reel = date('Y-m-d H:i:s');
                            $projects_remb->status                  = 1; // remb aux preteurs
                            $projects_remb->update();

                            $projects_remb_log->fin              = date('Y-m-d H:i:s');
                            $projects_remb_log->montant_remb_net = $Total_rembNet * 100;
                            $projects_remb_log->etat             = $Total_etat * 100;
                            $projects_remb_log->nb_pret_remb     = $nb_pret_remb;
                            $projects_remb_log->update();
                        } // Fin check montant remb
                    } // Fin boucle lProjectsAremb
                } // Fin condition lProjectsAremb
            } // Fin condition heure de traitement

            $this->stopCron();
        }
    }

    // check les projets n'ayant pas eu de remb a la date theorique emprunteur
    public function check_remb_emprunteur()
    {
        $echeanciers = $this->loadData('echeanciers');
        $projects    = $this->loadData('projects');

        $date = date('Y-m-d');

        $lRemb_emprunteur = $echeanciers->selectfirstEcheanceByproject($date);
        if ($lRemb_emprunteur != false) {
            $table = '';
            foreach ($lRemb_emprunteur as $remb) {
                $projects->get($remb['id_project'], 'id_project');
                $table .= '
				<tr>
					<td align="center">' . $remb['id_project'] . ' - ' . utf8_decode($projects->title_bo) . '</td>
					<td align="center">' . number_format($remb['montant_emprunteur'], 2, ',', '') . '&euro;</td>
					<td align="center">' . date('Y-m-d', strtotime($remb['date_echeance_emprunteur'])) . '</td>
					<td align="center">' . date('Y-m-d', strtotime($remb['date_echeance'])) . '</td>
					<td align="center">' . $remb['ordre'] . '</td>
					<td align="center"><a href="' . $this->aurl . '/dossiers/detail_remb/' . $remb['id_project'] . '">lien projet</a></td>
				</tr>';
            }

            $this->settings->get('Adresse notification prelevement emprunteur', 'type');
            $destinataire = $this->settings->value;
            $this->mails_text->get('notification-prelevement-emprunteur', 'lang = "' . $this->language . '" AND type');

            $surl = $this->surl;
            $url = $this->lurl;
            $liste_remb = $table;

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
    }

    public function _indexation()
    {
        ini_set('max_execution_time', 3600);
        ini_set('memory_limit', '4096M');

        if (true === $this->startCron('indexation', 60)) {

            $heure_debut               = date("Y-m-d H:i:s");
            $indexage_1jour            = true; // Si true, on n'indexe que les clients avec une date de derniere indexation plus vieille de Xh.
            $heure_derniere_indexation = 24;
            $liste_id_a_forcer         = 0;  // force l'indexation juste pour ces id.  (Ex: 12,1,2), si on veut pas on met 0
            $limit_client              = 200;

            $uniquement_ceux_jamais_indexe = true;
            $nb_maj                        = $nb_creation = $nb_client_concernes = 0;

            $this->indexage_vos_operations = $this->loadData('indexage_vos_operations');
            $this->transactions            = $this->loadData('transactions');
            $this->clients                 = $this->loadData('clients');
            $this->echeanciers             = $this->loadData('echeanciers');
            $this->indexage_suivi          = $this->loadData('indexage_suivi');

            $this->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
            $this->lng['preteur-operations-pdf']            = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);
            $this->lng['preteur-operations']                = $this->ln->selectFront('preteur-operations', $this->language, $this->App);

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
                20 => $this->lng['preteur-operations-vos-operations']['gain-parrain']
            );

            $sql_forcage_id_client = "";
            if ($liste_id_a_forcer != 0) {
                $sql_forcage_id_client = " AND id_client IN(" . $liste_id_a_forcer . ")";
            }

            if ($uniquement_ceux_jamais_indexe) {
                $this->L_clients = $this->clients->select(' etape_inscription_preteur = 3 ' . $sql_forcage_id_client . ' AND id_client NOT IN (SELECT id_client FROM indexage_suivi WHERE deja_indexe = 1)', '', '', $limit_client);
            } else {
                $this->L_clients = $this->clients->select(' etape_inscription_preteur = 3 ' . $sql_forcage_id_client, '', '', $limit_client);
            }

            $nb_client_concernes = count($this->L_clients);

            foreach ($this->L_clients as $clt) {
                $client_a_indexer = true;
                if ($indexage_1jour) {
                    $time_ya_xh_stamp = mktime(date('H') - $heure_derniere_indexation, date('i'), date('s'), date("m"), date('d'), date("Y"));
                    $time_ya_xh       = date('Y-m-d H:i:s', $time_ya_xh_stamp);
                    if ($this->indexage_suivi->get($clt['id_client'], 'date_derniere_indexation > "' . $time_ya_xh . '" AND deja_indexe = 1 AND id_client')) {
                        $client_a_indexer = false;
                    }
                }
                if ($client_a_indexer) {
                    if ($this->clients->get($clt['id_client'], 'id_client')) {
                        $this->lTrans = $this->transactions->selectTransactionsOp($array_type_transactions, 't.type_transaction IN (1,2,3,4,5,7,8,16,17,19,20)
							AND t.status = 1
							AND t.etat = 1
							AND t.display = 0
							AND t.id_client = ' . $this->clients->id_client . '
							AND LEFT(t.date_transaction,10) >= "2013-01-01"', 'id_transaction DESC');

                        $sql = 'DELETE FROM `indexage_vos_operations` WHERE id_client =' . $this->clients->id_client;
                        $this->bdd->query($sql);

                        $nb_entrees = count($this->lTrans);
                        foreach ($this->lTrans as $t) {
                            $this->indexage_vos_operations = $this->loadData('indexage_vos_operations');
                            if (!$this->indexage_vos_operations->get($t['id_transaction'], ' id_client = ' . $t['id_client'] . ' AND type_transaction = "' . $t['type_transaction_alpha'] . '"  AND id_transaction')) {
                                $this->echeanciers->get($t['id_echeancier'], 'id_echeancier');

                                $retenuesfiscals = $this->echeanciers->prelevements_obligatoires + $this->echeanciers->retenues_source + $this->echeanciers->csg + $this->echeanciers->prelevements_sociaux + $this->echeanciers->contributions_additionnelles + $this->echeanciers->prelevements_solidarite + $this->echeanciers->crds;

                                $libelle_prelevements = $this->lng['preteur-operations-vos-operations']['prelevements-fiscaux-et-sociaux'];

                                // on check si il s'agit d'une PM ou PP
                                if ($this->clients->type == 1 or $this->clients->type == 3) {
                                    $this->lenders_imposition_history = $this->loadData('lenders_imposition_history');
                                    $exoneration                      = $this->lenders_imposition_history->is_exonere_at_date($this->lenders_accounts->id_lender_account, $t['date_transaction']);
                                    if ($exoneration) {
                                        $libelle_prelevements = $this->lng['preteur-operations-vos-operations']['cotisations-sociales'];
                                    }
                                } else {// PM
                                    $libelle_prelevements = $this->lng['preteur-operations-vos-operations']['retenues-a-la-source'];
                                }

                                $this->indexage_vos_operations->id_client           = $t['id_client'];
                                $this->indexage_vos_operations->id_transaction      = $t['id_transaction'];
                                $this->indexage_vos_operations->id_echeancier       = $t['id_echeancier'];
                                $this->indexage_vos_operations->id_projet           = $t['le_id_project'];
                                $this->indexage_vos_operations->type_transaction    = $t['type_transaction'];
                                $this->indexage_vos_operations->libelle_operation   = $t['type_transaction_alpha'];
                                $this->indexage_vos_operations->bdc                 = $t['bdc'];
                                $this->indexage_vos_operations->libelle_projet      = $t['title'];
                                $this->indexage_vos_operations->date_operation      = $t['date_tri'];
                                $this->indexage_vos_operations->solde               = $t['solde'] * 100;
                                $this->indexage_vos_operations->montant_operation   = $t['montant'];
                                $this->indexage_vos_operations->montant_capital     = $this->echeanciers->capital;
                                $this->indexage_vos_operations->montant_interet     = $this->echeanciers->interets;
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
                        mail($this->sDestinatairesDebug, 'UNILEND - Erreur cron indexage', 'Erreur de get sur le client :' . $clt['id_client'], $this->sHeadersDebug);
                    }
                }
            }

            $html = "Nombre client concernes : " . $nb_client_concernes . " <br />";
            $html .= "Nombre mise à jour : " . $nb_maj . " <br />";
            $html .= "Nombre creation : " . $nb_creation . " <br />";
            $html .= "Debut : " . $heure_debut . " -  Termine :" . date("Y-m-d H:i:s");

            if ($nb_maj > 0 or $nb_creation > 0) {
                mail($this->sDestinatairesDebug, 'INDEXATION - UNILEND', $html, $this->sHeadersDebug);
            }

            $this->stopCron();
        }
    }

    // Passe toutes les 5 minutes la nuit de 3h à 4h
    // copie données table -> enregistrement table backup -> suppression données table
    public function _stabilisation_mails()
    {
        if ($this->startCron('stabilisationMail', 10)) {
            $iStartTime       = time();
            $iRetentionDays   = 30;
            $iLimit           = 2000;
            $sMinimumDate     = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - $iRetentionDays, date('Y')));

            $this->oLogger->addRecord(ULogger::INFO, 'Current date with an offset of ' . $iRetentionDays . ' days: ' . $sMinimumDate, array('ID' => $iStartTime));

            $this->bdd->query("
                INSERT IGNORE INTO mails_filer_backup (`id_filermails`, `id_textemail`, `desabo`, `email_nmp`, `from`, `to`, `subject`, `content`, `headers`, `added`, `updated`)
                SELECT m1.* FROM mails_filer m1 WHERE LEFT(m1.added, 10) <= '" . $sMinimumDate . "' ORDER BY m1.added ASC LIMIT " . $iLimit
            );

            $this->oLogger->addRecord(ULogger::INFO, '`mails_filer` backuped lines: ' . mysql_affected_rows(), array('ID' => $iStartTime));

            $this->bdd->query('DELETE FROM `mails_filer` WHERE LEFT(added, 10) <= "' . $sMinimumDate . '" ORDER BY added ASC LIMIT ' . $iLimit);

            $iDeletedRows = mysql_affected_rows();
            $this->oLogger->addRecord(ULogger::INFO, '`mails_filer` deleted lines: ' . $iDeletedRows, array('ID' => $iStartTime));

            if ($iDeletedRows < $iLimit) {
                $this->bdd->query('OPTIMIZE TABLE `mails_filer`');
            }

            $this->bdd->query("
                INSERT IGNORE INTO nmp_backup (`id_nmp`, `serialize_content`, `date`, `mailto`, `reponse`, `erreur`, `status`, `date_sent`, `added`, `updated`)
                SELECT n1.* FROM nmp n1  WHERE LEFT(n1.added, 10) <= '" . $sMinimumDate . "' AND mailto NOT LIKE '%unilend.fr' ORDER BY n1.added ASC LIMIT " . $iLimit
            );

            $this->oLogger->addRecord(ULogger::INFO, '`nmp` backuped lines: ' . mysql_affected_rows(), array('ID' => $iStartTime));

            $this->bdd->query('DELETE FROM `nmp` WHERE LEFT(added, 10) <= "' . $sMinimumDate . '" ORDER BY added ASC LIMIT ' . $iLimit);

            $iDeletedRows = mysql_affected_rows();
            $this->oLogger->addRecord(ULogger::INFO, '`nmp` deleted lines: ' . $iDeletedRows, array('ID' => $iStartTime));

            if ($iDeletedRows < $iLimit) {
                $this->bdd->query('OPTIMIZE TABLE `nmp`');
            }

            $this->stopCron();
        }
    }

    public function deleteOldFichiers()
    {
        $path  = $this->path . 'protected/sftp_groupama/';
        $duree = 30; // jours
        // On parcourt le dossier
        $fichiers = scandir($path);
        unset($fichiers[0], $fichiers[1]);
        foreach ($fichiers as $f) {
            $le_fichier = $path . $f;

            $time            = filemtime($le_fichier);
            $time_plus_duree = mktime(date("H", $time), date("i", $time), date("s", $time), date("n", $time), date("d", $time) + $duree, date("Y", $time));

            // si la date du jour est superieur à la date du fichier plus n jours => on supprime
            if (time() >= $time_plus_duree) {
                unlink($le_fichier);
            }
        }
    }


    public function zippage($id_project)
    {
        $projects          = $this->loadData('projects');
        $companies         = $this->loadData('companies');
        $companies_details = $this->loadData('companies_details');

        $projects->get($id_project, 'id_project');
        $companies->get($projects->id_company, 'id_company');
        $companies_details->get($projects->id_company, 'id_company');

        $ext_cni  = substr(strrchr($companies_details->fichier_cni_passeport, '.'), 1);
        $ext_kbis = substr(strrchr($companies_details->fichier_extrait_kbis, '.'), 1);

        $path_cni  = $this->path . 'protected/companies/cni_passeport/' . $companies_details->fichier_cni_passeport;
        $path_kbis = $this->path . 'protected/companies/extrait_kbis/' . $companies_details->fichier_extrait_kbis;

        $new_nom_cni  = 'CNI-#' . $companies->siren . '.' . $ext_cni;
        $new_nom_kbis = 'KBIS-#' . $companies->siren . '.' . $ext_kbis;

        $path_nozip = $this->path . 'protected/sftp_groupama_nozip/';
        $path       = $this->path . 'protected/sftp_groupama/';

        $nom_dossier = $companies->siren;

        if (!is_dir($path_nozip . $nom_dossier)) {
            mkdir($path_nozip . $nom_dossier);
        }

        copy($path_cni, $path_nozip . $nom_dossier . '/' . $new_nom_cni);
        copy($path_kbis, $path_nozip . $nom_dossier . '/' . $new_nom_kbis);

        $zip = new ZipArchive();
        if (is_dir($path_nozip . $nom_dossier)) {
            if ($zip->open($path . $nom_dossier . '.zip', ZipArchive::CREATE) == TRUE) {
                $fichiers = scandir($path_nozip . $nom_dossier);
                unset($fichiers[0], $fichiers[1]);
                foreach ($fichiers as $f) {
                    $zip->addFile($path_nozip . $nom_dossier . '/' . $f, $f);
                }
                $zip->close();
            }
        }

        $this->deleteOldFichiers();
    }

    /**
     * Envoi des mails pour le remboursement anticipe
     * On va checker dans la table "remboursement_anticipe_mail_a_envoyer" si il y a des mails pour un remb anticiper a envoyer
     **/
    public function _RA_email()
    {
        if ($this->startCron('RAMail', 5)) {
            $this->projects                        = $this->loadData('projects');
            $this->echeanciers                     = $this->loadData('echeanciers');
            $this->receptions                      = $this->loadData('receptions');
            $this->echeanciers_emprunteur          = $this->loadData('echeanciers_emprunteur');
            $this->transactions                    = $this->loadData('transactions');
            $this->lenders_accounts                = $this->loadData('lenders_accounts');
            $this->clients                         = $this->loadData('clients');
            $this->wallets_lines                   = $this->loadData('wallets_lines');
            $this->notifications                   = $this->loadData('notifications');
            $this->clients_gestion_mails_notif     = $this->loadData('clients_gestion_mails_notif');
            $this->projects_status_history         = $this->loadData('projects_status_history');
            $this->clients_gestion_notifications   = $this->loadData('clients_gestion_notifications');
            $this->mails_text                      = $this->loadData('mails_text');
            $this->companies                       = $this->loadData('companies');
            $this->loans                           = $this->loadData('loans');
            $loans                                 = $this->loadData('loans');
            $remboursement_anticipe_mail_a_envoyer = $this->loadData('remboursement_anticipe_mail_a_envoyer');

            // recup des mails à envoyer pour les projets en ra en attente, 1 seul à la fois car traitement pouvant etre lourd
            $L_mail_ra_en_attente = $remboursement_anticipe_mail_a_envoyer->select('statut = 0', 'added ASC', '', 1);

            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            $this->mails_text->get('preteur-remboursement-anticipe', 'lang = "' . $this->language . '" AND type');

            foreach ($L_mail_ra_en_attente as $ra_email) {
                $this->oLogger->addRecord(ULogger::INFO, 'Start email ' . $ra_email['id_reception'], array('ID' => $this->iStartTime, 'time' => time() - $this->iStartTime));

                // Tout se base sur cette variable !
                $id_reception = $ra_email['id_reception'];

                $this->receptions->get($id_reception);
                $this->projects->get($this->receptions->id_project);
                $this->companies->get($this->projects->id_company, 'id_company');

                $L_preteur_on_projet = $this->echeanciers->get_liste_preteur_on_project($this->projects->id_project);
                $sum_ech_restant     = $this->echeanciers_emprunteur->counter('id_project = ' . $this->projects->id_project . ' AND status_ra = 1');

                foreach ($L_preteur_on_projet as $preteur) {
                    $this->oLogger->addRecord(ULogger::INFO, 'Lender ' . $preteur['id_lender'], array('ID' => $this->iStartTime, 'time' => time() - $this->iStartTime));

                    $reste_a_payer_pour_preteur = $this->echeanciers->getSumRestanteARembByProject_capital(' AND id_lender =' . $preteur['id_lender'] . ' AND id_loan = ' . $preteur['id_loan'] . ' AND status_ra = 1 AND id_project = ' . $this->projects->id_project);

                    $this->lenders_accounts->get($preteur['id_lender'], 'id_lender_account');
                    $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');

                    if ($this->clients->status == 1) {
                        $notifications                  = $this->loadData('notifications');
                        $notifications->type            = 2; // remb
                        $notifications->id_lender       = $preteur['id_lender'];
                        $notifications->id_project      = $this->projects->id_project;
                        $notifications->amount          = ($reste_a_payer_pour_preteur * 100);
                        $notifications->id_notification = $notifications->create();

                        $this->transactions->get($preteur['id_loan'], 'id_loan_remb');

                        $this->clients_gestion_mails_notif                                 = $this->loadData('clients_gestion_mails_notif');
                        $this->clients_gestion_mails_notif->id_client                      = $this->clients->id_client;
                        $this->clients_gestion_mails_notif->id_notif                       = 5; // remb preteur
                        $this->clients_gestion_mails_notif->date_notif                     = date('Y-m-d H:i:s');
                        $this->clients_gestion_mails_notif->id_notification                = $notifications->id_notification;
                        $this->clients_gestion_mails_notif->id_transaction                 = $this->transactions->id_transaction;
                        $this->clients_gestion_mails_notif->id_clients_gestion_mails_notif = $this->clients_gestion_mails_notif->create();

                        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');

                        if ($this->clients_gestion_notifications->getNotif($this->clients->id_client, 5, 'immediatement') == true) {
                            $this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                            $this->clients_gestion_mails_notif->immediatement = 1; // on met a jour le statut immediatement
                            $this->clients_gestion_mails_notif->update();

                            $loans->get($preteur['id_loan'], 'id_loan');

                            $getsolde = $this->transactions->getSolde($this->clients->id_client);
                            $varMail  = array(
                                'surl'                 => $this->surl,
                                'url'                  => $this->furl,
                                'prenom_p'             => $this->clients->prenom,
                                'nomproject'           => $this->projects->title,
                                'nom_entreprise'       => $this->companies->name,
                                'taux_bid'             => $this->ficelle->formatNumber($loans->rate),
                                'nbecheancesrestantes' => $sum_ech_restant,
                                'interetsdejaverses'   => $this->ficelle->formatNumber($this->echeanciers->sum('id_project = ' . $this->projects->id_project . ' AND id_loan = ' . $preteur['id_loan'] . ' AND status_ra = 0 AND status = 1 AND id_lender =' . $preteur['id_lender'], 'interets')),
                                'crdpreteur'           => $this->ficelle->formatNumber($reste_a_payer_pour_preteur) . (($reste_a_payer_pour_preteur >= 2) ? ' euros' : ' euro'),
                                'Datera'               => date('d/m/Y'),
                                'solde_p'              => $this->ficelle->formatNumber($getsolde) . (($getsolde >= 2) ? ' euros' : ' euro'),
                                'motif_virement'       => $motif,
                                'lien_fb'              => $lien_fb,
                                'lien_tw'              => $lien_tw
                            );
                            $tabVars  = $this->tnmp->constructionVariablesServeur($varMail);

                            $this->email = $this->loadLib('email');
                            $this->email->setFrom($this->mails_text->exp_email, strtr(utf8_decode($this->mails_text->exp_name), $tabVars));
                            $this->email->setSubject(stripslashes(strtr(utf8_decode($this->mails_text->subject), $tabVars)));
                            $this->email->setHTMLBody(stripslashes(strtr(utf8_decode($this->mails_text->content), $tabVars)));

                            if ($this->Config['env'] === 'prod') {
                                Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                                $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                            } else {
                                $this->email->addRecipient(trim($this->clients->email));
                                $this->email->addBCCRecipient($this->sDestinatairesDebug);
                                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                            }
                        }
                    }
                }

                $remboursement_anticipe_mail_a_envoyer = $this->loadData('remboursement_anticipe_mail_a_envoyer');
                $remboursement_anticipe_mail_a_envoyer->get($ra_email['id_remboursement_anticipe_mail_a_envoyer']);
                $remboursement_anticipe_mail_a_envoyer->statut = 1;
                $remboursement_anticipe_mail_a_envoyer->update();

                $this->oLogger->addRecord(ULogger::INFO, 'End email ' . $ra_email['id_reception'], array('ID' => $this->iStartTime, 'time' => time() - $this->iStartTime));
            }

            $this->stopCron();
        }
    }

    /**
     * Send reminder email for project submissions
     */
    public function _relance_completude_emprunteurs()
    {
        ini_set('memory_limit', '1G');
        ini_set('max_execution_time', 300);

        if ($this->startCron('relance completude emprunteurs', 5)) {
            $this->prescripteurs                = $this->loadData('prescripteurs');
            $this->projects_status              = $this->loadData('projects_status');
            $this->projects_status_history      = $this->loadData('projects_status_history');
            $this->projects_last_status_history = $this->loadData('projects_last_status_history');

            $this->settings->get('Intervales relances emprunteurs', 'type');
            $aReminderIntervals = json_decode($this->settings->value, true);

            $this->settings->get('Durée moyenne financement', 'type');
            $aAverageFundingDurations = json_decode($this->settings->value, true);

            $this->settings->get('Facebook', 'type');
            $sFacebookURL = $this->settings->value;

            $this->settings->get('Twitter', 'type');
            $sTwitterURL = $this->settings->value;

            $this->settings->get('Adresse emprunteur', 'type');
            $sBorrowerEmail = $this->settings->value;

            $this->settings->get('Téléphone emprunteur', 'type');
            $sBorrowerPhoneNumber = $this->settings->value;

            $aReplacements = array(
                'adresse_emprunteur'   => $sBorrowerEmail,
                'telephone_emprunteur' => $sBorrowerPhoneNumber,
                'furl'                 => $this->furl,
                'surl'                 => $this->surl,
                'lien_fb'              => $sFacebookURL,
                'lien_tw'              => $sTwitterURL,
            );

            foreach ($aReminderIntervals as $sStatus => $aIntervals) {
                if (1 === preg_match('/^status-([1-9][0-9]*)$/', $sStatus, $aMatches)) {
                    $iStatus                       = (int) $aMatches[1];
                    $iLastIndex                    = count($aIntervals);
                    $iPreviousReminderDaysInterval = 0;
                    $iDaysSincePreviousReminder    = 0;

                    foreach ($aIntervals as $iReminderIndex => $iDaysInterval) {
                        $iDaysSincePreviousReminder = $iDaysInterval - $iPreviousReminderDaysInterval;

                        foreach ($this->projects->getReminders($iStatus, $iDaysSincePreviousReminder, $iReminderIndex - 1) as $iProjectId) {
                            if ($this->mails_text->get('depot-dossier-relance-status-' . $iStatus . '-' . $iReminderIndex, 'lang = "' . $this->language . '" AND type')) {
                                $this->projects->get($iProjectId, 'id_project');
                                $this->companies->get($this->projects->id_company, 'id_company');

                                if ($this->projects->id_prescripteur > 0) {
                                    $this->prescripteurs->get($this->projects->id_prescripteur, 'id_prescripteur');
                                    $this->clients->get($this->prescripteurs->id_client, 'id_client');
                                } else {
                                    $this->clients->get($this->companies->id_client_owner, 'id_client');
                                }

                                if (false === empty($this->clients->email)) {
                                    $this->projects_last_status_history->get($this->projects->id_project, 'id_project');
                                    $this->projects_status_history->get($this->projects_last_status_history->id_project_status_history, 'id_project_status_history');

                                    $oSubmissionDate = new \DateTime($this->projects->added);

                                    // @todo arbitrary default value
                                    $iAverageFundingDuration = 15;
                                    reset($aAverageFundingDurations);
                                    foreach ($aAverageFundingDurations as $aAverageFundingDuration) {
                                        if ($this->projects->amount >= $aAverageFundingDuration['min'] && $this->projects->amount <= $aAverageFundingDuration['max']) {
                                            $iAverageFundingDuration = $aAverageFundingDuration['heures'] / 24;
                                            break;
                                        }
                                    }

                                    $aReplacements['liste_pieces']            = $this->projects_status_history->content;
                                    $aReplacements['raison_sociale']          = $this->companies->name;
                                    $aReplacements['prenom']                  = $this->clients->prenom;
                                    $aReplacements['montant']                 = $this->ficelle->formatNumber($this->projects->amount, 0);
                                    $aReplacements['delai_demande']           = $iDaysInterval;
                                    $aReplacements['lien_reprise_dossier']    = $this->furl . '/depot_de_dossier/reprise/' . $this->projects->hash;
                                    $aReplacements['lien_stop_relance']       = $this->furl . '/depot_de_dossier/emails/' . $this->projects->hash;
                                    $aReplacements['date_demande']            = strftime('%d %B %Y', $oSubmissionDate->getTimestamp());
                                    $aReplacements['pourcentage_financement'] = $iDaysInterval > $iAverageFundingDuration ? 100 : round(100 - ($iAverageFundingDuration - $iDaysInterval) / $iAverageFundingDuration * 100);
                                    $aReplacements['sujet']                   = htmlentities($this->mails_text->subject, null, 'UTF-8');

                                    $sRecipientEmail  = preg_replace('/^(.+)-[0-9]+$/', '$1', trim($this->clients->email));
                                    $aDYNReplacements = $this->tnmp->constructionVariablesServeur($aReplacements);

                                    $this->email = $this->loadLib('email');
                                    $this->email->setFrom($this->mails_text->exp_email, strtr(utf8_decode($this->mails_text->exp_name), $aDYNReplacements));
                                    $this->email->setSubject(stripslashes(utf8_decode($this->mails_text->subject)));
                                    $this->email->setHTMLBody(stripslashes(strtr(utf8_decode($this->mails_text->content), $aDYNReplacements)));

                                    if ($this->Config['env'] === 'prod') {
                                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $sRecipientEmail, $aNMPFilters);
                                        $this->tnmp->sendMailNMP($aNMPFilters, $aReplacements, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                                    } else {
                                        $this->email->addRecipient($sRecipientEmail);
                                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                                    }
                                }

                                /**
                                 * When project is pending documents, abort status is not automatic and must be set manually in BO
                                 */
                                if ($iReminderIndex === $iLastIndex && $iStatus != \projects_status::EN_ATTENTE_PIECES) {
                                    $this->projects_status_history->addStatus(-1, \projects_status::ABANDON, $iProjectId, $iReminderIndex, $this->projects_status_history->content);
                                } else {
                                    $this->projects_status_history->addStatus(-1, $iStatus, $iProjectId, $iReminderIndex, $this->projects_status_history->content);
                                }
                            }
                        }

                        $iPreviousReminderDaysInterval = $iDaysInterval;
                    }
                }
            }
            $this->stopCron();
        }
    }

    public function _projet_process_fast_completude()
    {
        if ($this->startCron('projet process fast completude', 5)) {
            $this->projects_status         = $this->loadData('projects_status');
            $this->projects_status_history = $this->loadData('projects_status_history');

            foreach ($this->projects->getFastProcessStep3() as $iProjectId) {
                $this->projects_status_history->addStatus(-1, \projects_status::A_TRAITER, $iProjectId);
            }

            $this->stopCron();
        }
    }

    /**
     * @param $sName  string Cron name (used for settings name)
     * @param $iDelay int    Minimum delay (in minutes) before we consider cron has crashed and needs to be restarted
     * @return bool
     */
    private function startCron($sName, $iDelay)
    {
        $this->iStartTime = time();
        $this->oLogger    = new ULogger($sName, $this->logPath, 'cron.log');
        $this->oSemaphore = $this->loadData('settings');
        $this->oSemaphore->get('Controle cron ' . $sName, 'type');

        if ($this->oSemaphore->value == 0) {
            $iUpdatedDateTime      = strtotime($this->oSemaphore->updated);
            $iMinimumDelayDateTime = mktime(date('H'), date('i') - $iDelay, 0, date('m'), date('d'), date('Y'));

            if ($iUpdatedDateTime <= $iMinimumDelayDateTime) {
                $this->oSemaphore->value = 1;
                $this->oSemaphore->update();
            }
        }

        if ($this->oSemaphore->value == 1) {
            $this->oSemaphore->value = 0;
            $this->oSemaphore->update();

            $this->oLogger->addRecord(ULogger::INFO, 'Start cron', array('ID' => $this->iStartTime));

            return true;
        }

        $this->oLogger->addRecord(ULogger::INFO, 'Semaphore locked', array('ID' => $this->iStartTime));

        return false;
    }

    private function stopCron()
    {
        $this->oSemaphore->value = 1;
        $this->oSemaphore->update();

        $this->oLogger->addRecord(ULogger::INFO, 'End cron', array('ID' => $this->iStartTime));
    }

    /**
     * Function to delete after tests salesforce
     * @param string $sType name of treatment (preteurs, emprunteurs, projects or companies)
     */
    public function _sendDataloader()
    {
        $sType = $this->params[0];
        $iTimeStartDataloader = microtime(true);
        //TODO a passer en crontab
        exec('java -cp ' . $this->Config['dataloader_path'][$this->Config['env']] . 'dataloader-26.0.0-uber.jar -Dsalesforce.config.dir=' . $this->Config['path'][$this->Config['env']] . 'dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=' . escapeshellarg($sType), $aReturnDataloader, $sReturn);
        var_dump($aReturnDataloader);
        $iTimeEndDataloader = microtime(true) - $iTimeStartDataloader;
        $oLogger    = new ULogger('SendDataloader', $this->logPath, 'cron.log');
        $oLogger->addRecord(ULogger::INFO, 'Send to dataloader type ' . $sType . ' in ' . round($iTimeEndDataloader, 2),
            array(__FILE__ . ' on line ' . __LINE__));
    }

    /**
     * Function to calculate the IRR (Internal Rate of Return) for each lender on a regular basis
     * Given the amount of lenders and the time and resources needed for calculation
     * it does one iteration par day on 800 accounts if not specified otherwise
     */
    public function _calculateIRRForAllLenders()
    {
        if (true === $this->startCron('LendersStats', 30)){
            set_time_limit (2000);
            $iAmountOfLenderAccounts = isset($this->params[0]) ? $this->params[0] : 800;
            $oDateTime               = new DateTime('NOW');
            $fTimeStart              = microtime(true);
            $oLoggerIRR              = new ULogger('Calculate IRR', $this->logPath, 'IRR.log');
            $oLendersAccounts        = $this->loadData('lenders_accounts');
            $oLendersAccountStats    = $this->loadData('lenders_account_stats');
            $aLendersAccounts        = $oLendersAccounts->selectLendersForIRR($iAmountOfLenderAccounts);

            foreach ($aLendersAccounts as $aLender) {
                try {
                    $fXIRR                                   = $oLendersAccounts->calculateIRR($aLender['id_lender_account']);
                    $oLendersAccountStats->id_lender_account = $aLender['id_lender_account'];
                    $oLendersAccountStats->tri_date          = $oDateTime->format('Y-m-d H:i:s');
                    $oLendersAccountStats->tri_value         = $fXIRR;
                    $oLendersAccountStats->create();

                } catch (Exception $e) {
                    $oLoggerIRR->addRecord(ULogger::WARNING, 'Caught Exception: '.$e->getMessage(). $e->getTraceAsString());
                }

                $this->oLogger->addRecord(ULogger::INFO, 'Temps calcul TRI : ' . round(microtime(true) - $fTimeStart, 2));
            }
            $this->stopCron();
        }
    }
}
