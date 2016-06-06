<?php

use Unilend\librairies\Cache;
use Symfony\Bridge\Monolog\Logger;
use Unilend\librairies\greenPoint\greenPoint;
use Unilend\librairies\greenPoint\greenPointStatus;

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

    /** @var  Logger */
    private $oLogger;

    public function initialize()
    {
        parent::initialize();

        // Inclusion controller pdf
        include_once $this->path . '/apps/default/controllers/pdf.php';

        $this->hideDecoration();
        $this->autoFireView = false;
        $this->oLogger = $this->get('monolog.logger.console');

        $this->settings->get('DebugMailFrom', 'type');
        $debugEmail = $this->settings->value;
        $this->settings->get('DebugMailIt', 'type');
        $this->sDestinatairesDebug = $this->settings->value;
        $this->sHeadersDebug       = 'MIME-Version: 1.0' . "\r\n";
        $this->sHeadersDebug .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $this->sHeadersDebug .= 'From: ' . $debugEmail . "\r\n";
    }

    /**
     * @param $sName  string Cron name (used for settings name)
     * @param $iDelay int    Minimum delay (in minutes) before we consider cron has crashed and needs to be restarted
     * @return bool
     */
    private function startCron($sName, $iDelay)
    {
        $this->iStartTime = time();
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
            $this->oLogger->info('Started cron ' . $sName . ' - Cron ID=' . $this->iStartTime, array('class' => __CLASS__, 'function' => __FUNCTION__));

            return true;
        }
        $this->oLogger->info('Semaphore locked', array('class' => __CLASS__, 'function' => __FUNCTION__));

        return false;
    }

    private function stopCron()
    {
        $this->oSemaphore->value = 1;
        $this->oSemaphore->update();
        $this->oLogger->info('End cron ID=' . $this->iStartTime, array('class' => __CLASS__, 'function' => __FUNCTION__));
    }

    public function _default()
    {
        die;
    }

    // toutes les minute on check //
    // on regarde si il y a des projets au statut "a funder" et on les passe en statut "en funding"
    public function _check_projet_a_funder()
    {
        if (true === $this->startCron('check_projet_a_funder', 5)) {
            ini_set('max_execution_time', '300');
            ini_set('memory_limit', '1G');

            /** @var \projects $oProject */
            $oProject = $this->loadData('projects');
            /** @var \Unilend\Service\ProjectManager $oProjectManager */
            $oProjectManager = $this->get('unilend.service.project_manager');

            $bHasProjectPublished = false;
            $aProjectToFund       = $oProject->selectProjectsByStatus(\projects_status::AUTO_BID_PLACED, "AND p.date_publication_full <= NOW()", '', array(), '', '', false);

            foreach ($aProjectToFund as $aProject) {
                if ($oProject->get($aProject['id_project'])) {
                    $bHasProjectPublished = true;

                   $oProjectManager->publish($oProject);

                    $this->zippage($oProject->id_project);
                    $this->sendNewProjectEmail($oProject);
                }
            }

            if ($bHasProjectPublished) {
                $oCachePool    = $this->get('memcache.default');
                $oCachePool->deleteItem(Cache::LIST_PROJECTS . '_' . $this->tabProjectDisplay);
            }

            $this->stopCron();
        }
    }

    // check les statuts remb
    public function _check_status()
    {
        // die temporaire pour eviter de changer le statut du prelevement en retard
        die;

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

        $this->settings->get('Cabinet de recouvrement', 'type');
        $ca_recou = $this->settings->value;

        $today = date('Y-m-d');
        $time = strtotime($today . ' 00:00:00');

        $lProjects = $projects->selectProjectsByStatus(\projects_status::REMBOURSEMENT . ', ' . \projects_status::PROBLEME, '', '', array(), '', '', false);

        foreach ($lProjects as $p) {
            $projects_status->getLastStatut($p['id_project']);

            // On recup les echeances inferieur a la date du jour
            $lEcheancesEmp = $echeanciers_emprunteur->select('id_project = ' . $p['id_project'] . ' AND  	status_emprunteur = 0 AND date_echeance_emprunteur < "' . $today . ' 00:00:00"');

            foreach ($lEcheancesEmp as $e) {
                $dateRemb = strtotime($e['date_echeance_emprunteur']);

                // si statut remb
                if ($projects_status->status == \projects_status::REMBOURSEMENT) {
                    // date echeance emprunteur +5j (probleme)
                    $laDate = mktime(0, 0, 0, date("m", $dateRemb), date("d", $dateRemb) + 5, date("Y", $dateRemb));
                    $type   = 'probleme';
                } // statut probleme
                elseif ($projects_status->status == \projects_status::PROBLEME) {
                    // date echeance emprunteur +8j (recouvrement)
                    $laDate = mktime(0, 0, 0, date("m", $dateRemb), date("d", $dateRemb) + 8, date("Y", $dateRemb));
                    $type   = 'recouvrement';
                }

                // si la date +nJ est eqale ou depasse
                if ($laDate <= $time) {
                    // probleme
                    if ($type == 'probleme') {
                        echo 'probleme<br>';
                        $projects_status_history->addStatus(\users::USER_ID_CRON, \projects_status::PROBLEME, $p['id_project']);
                    } // recouvrement
                    else {
                        echo 'recouvrement<br>';
                        $projects_status_history->addStatus(\users::USER_ID_CRON, \projects_status::RECOUVREMENT, $p['id_project']);

                        // date du probleme
                        $statusProbleme = $projects_status_history->select('id_project = ' . $p['id_project'] . ' AND  	id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::PROBLEME . ')', 'id_project_status_history DESC');

                        $timeAdd = strtotime($statusProbleme[0]['added']);
                        $month   = $this->dates->tableauMois['fr'][date('n', $timeAdd)];

                        $DateProbleme = date('d', $timeAdd) . ' ' . $month . ' ' . date('Y', $timeAdd);
                    }

                    $lLoans = $loans->select('id_project = ' . $p['id_project']);

                    $projects->get($p['id_project'], 'id_project');
                    $companies->get($projects->id_company, 'id_company');

                    foreach ($lLoans as $l) {
                        $lender->get($l['id_lender'], 'id_lender_account');
                        $preteur->get($lender->id_client_owner, 'id_client');

                        $rembNet = 0;

                        if ($type == 'probleme') {
                            ////////////////////////////////////////////
                            // on recup la somme deja remb du preteur //
                            ////////////////////////////////////////////
                            $lEchea = $echeanciers->select('id_loan = ' . $l['id_loan'] . ' AND id_project = ' . $p['id_project'] . ' AND status = 1');

                            foreach ($lEchea as $e) {
                                $rembNet += ($e['montant'] / 100) - $e['prelevements_obligatoires'] - $e['retenues_source'] - $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];
                            }

                            //**************************************//
                            //*** ENVOI DU MAIL PROBLEME PRETEUR ***//
                            //**************************************//
                            $this->mail_template->get('preteur-erreur-remboursement', 'lang = "' . $this->language . '" AND type');

                            $varMail = array(
                                'surl'              => $this->surl,
                                'url'               => $this->furl,
                                'prenom_p'          => $preteur->prenom,
                                'valeur_bid'        => $this->ficelle->formatNumber($l['amount'] / 100),
                                'nom_entreprise'    => $companies->name,
                                'montant_rembourse' => $this->ficelle->formatNumber($rembNet),
                                'cab_recouvrement'  => $ca_recou,
                                'motif_virement'    => $preteur->getLenderPattern($preteur->id_client),
                                'lien_fb'           => $this->like_fb,
                                'lien_tw'           => $this->twitter
                            );
                        } else { // recouvrement
                            //******************************************//
                            //*** ENVOI DU MAIL RECOUVREMENT PRETEUR ***//
                            //******************************************//
                            $this->mail_template->get('preteur-dossier-recouvrement', 'lang = "' . $this->language . '" AND type');

                            $varMail = array(
                                'surl'             => $this->surl,
                                'url'              => $this->furl,
                                'prenom_p'         => $preteur->prenom,
                                'date_probleme'    => $DateProbleme,
                                'cab_recouvrement' => $ca_recou,
                                'nom_entreprise'   => $companies->name,
                                'motif_virement'   => $preteur->getLenderPattern($preteur->id_client),
                                'lien_fb'          => $this->like_fb,
                                'lien_tw'          => $this->twitter
                            );
                        }
                    }
                    break;
                }
            }
        }
    }

    // cron toutes les heures
    // lors des virements si on a toujours pas recu on relance le client
    public function _relance_payment_preteur()
    {
        // relance retiré apres demande
        die;

        $this->clients          = $this->loadData('clients');
        $this->lenders_accounts = $this->loadData('lenders_accounts');

        $lLenderNok = $this->lenders_accounts->select('status = 0');

        $time          = date('Y-m-d H');
        $ladate        = strtotime($l['added']);
        $ladatePlus12H = mktime(date("H", $ladate) + 12, date("i", $ladate), 0, date("m", $ladate), date("d", $ladate), date("Y", $ladate));
        $ladatePlus24H = mktime(date("H", $ladate), date("i", $ladate), 0, date("m", $ladate), date("d", $ladate) + 1, date("Y", $ladate));
        $ladatePlus3J  = mktime(date("H", $ladate), date("i", $ladate), 0, date("m", $ladate), date("d", $ladate) + 3, date("Y", $ladate));
        $ladatePlus7J  = mktime(date("H", $ladate), date("i", $ladate), 0, date("m", $ladate), date("d", $ladate) + 7, date("Y", $ladate));
        $ladatePlus12H = date('Y-m-d H', $ladatePlus12H);
        $ladatePlus24H = date('Y-m-d H', $ladatePlus24H);
        $ladatePlus3J  = date('Y-m-d H', $ladatePlus3J);
        $ladatePlus7J  = date('Y-m-d H', $ladatePlus7J);

        $this->mail_template->get('preteur-relance-paiement-inscription', 'lang = "' . $this->language . '" AND type');

        foreach ($lLenderNok as $l) {
            $this->clients->get($l['id_client_owner'], 'id_client');

            echo 'Preteur : ' . $this->clients->id_client . ' - Nom : ' . $this->clients->prenom . ' ' . $this->clients->nom . '<br>';
            echo $l['added'] . '<br>';
            echo '+12h : ' . $ladatePlus12H . '<br>';
            echo '+24h : ' . $ladatePlus24H . '<br>';
            echo '+3j : ' . $ladatePlus3J . '<br>';
            echo '+7j : ' . $ladatePlus7J . '<br>';
            echo '---------------<br>';

            if ($ladatePlus12H == $time || $ladatePlus24H == $time || $ladatePlus3J == $time || $ladatePlus7J == $time) {
                $varMail = array(
                    'surl'              => $this->surl,
                    'url'               => $this->lurl,
                    'prenom_p'          => $this->clients->prenom,
                    'date_p'            => date('d/m/Y', strtotime($this->clients->added)),
                    'compte-p'          => $this->lurl . '/inscription_preteur/etape3/' . $this->clients->hash . '/2',
                    'compte-p-virement' => $this->lurl . '/inscription_preteur/etape3/' . $this->clients->hash,
                    'motif_virement'    => $this->clients->getLenderPattern($this->clients->id_client),
                    'lien_fb'           => $this->like_fb,
                    'lien_tw'           => $this->twitter
                );

                $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                $sujetMail = strtr(utf8_decode($this->mail_template->subject), $tabVars);
                $texteMail = strtr(utf8_decode($this->mail_template->content), $tabVars);
                $exp_name  = strtr(utf8_decode($this->mail_template->exp_name), $tabVars);

                $this->email = $this->loadLib('email');
                $this->email->setFrom($this->mail_template->exp_email, $exp_name);
                $this->email->setSubject(stripslashes($sujetMail));
                $this->email->setHTMLBody(stripslashes($texteMail));

                if ($this->clients->status == 1) {
                    if ($this->Config['env'] === 'prod') {
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mail_template->id_textemail, $this->clients->email, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mail_template->nmp_secure, $this->mail_template->id_nmp, $this->mail_template->nmp_unique, $this->mail_template->mode);
                    } else {
                        $this->email->addRecipient(trim($this->clients->email));
                        Mailer::send($this->email, $this->mails_filer, $this->mail_template->id_textemail);
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

        $jo = $this->loadLib('jours_ouvres');

        $this->projects     = $this->loadData('projects');
        $this->echeanciers  = $this->loadData('echeanciers');
        $this->prelevements = $this->loadData('prelevements');
        $this->companies    = $this->loadData('companies');
        $this->transactions = $this->loadData('transactions');

        $today = date('Y-m-d');

        $this->lProjects = $this->projects->selectProjectsByStatus(\projects_status::REMBOURSEMENT, '', '', array(), '', '', false);

        foreach ($this->lProjects as $k => $p) {
            // on recup la companie
            $this->companies->get($p['id_company'], 'id_company');

            // les echeances non remboursé du projet
            $lEcheances = $this->echeanciers->getSumRembEmpruntByMonths($p['id_project'], '', '0');

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
    private function recus2array($file)
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

        $url            = $file;
        $array          = array();
        $tabRestriction = array();
        $handle         = @fopen($url, 'r');

        if ($handle) {
            $i = 0;
            while (($ligne = fgets($handle)) !== false) {
                if (false !== stripos($ligne, 'CANTONNEMENT') || false !== stripos($ligne, 'DECANTON')) {
                    $codeEnregi = substr($ligne, 0, 2);
                    if ($codeEnregi == 04) {
                        $i++;
                    }
                    $tabRestriction[$i] = $i;
                } else {
                    $codeEnregi = substr($ligne, 0, 2);

                    if ($codeEnregi == 04) {
                        $i++;
                        $laligne = 1;

                        if (strpos($ligne, 'BIENVENUE') == true) {
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
                        $array[$i]['zoneReserv2']         = substr($ligne, 79, 2);
                        $array[$i]['numEcriture']         = substr($ligne, 81, 7);
                        $array[$i]['codeExoneration']     = substr($ligne, 88, 1);
                        $array[$i]['zoneReserv3']         = substr($ligne, 89, 1);
                        $array[$i]['refOp']               = substr($ligne, 104, 16);
                        $array[$i]['ligne1']              = $ligne;

                        // On affiche la ligne seulement si c'est un virement
                        if (! in_array(substr($ligne, 32, 2), array(23, 25, 'A1', 'B1'))) {
                            $array[$i]['libelleOpe1'] = substr($ligne, 48, 31);
                        }

                        $montant              = substr($ligne, 90, 14);
                        $Debutmontant         = ltrim(substr($montant, 0, 13), '0');
                        $dernier              = substr($montant, -1, 1);
                        $array[$i]['montant'] = $Debutmontant . $tablemontant[$dernier];
                    }

                    if ($codeEnregi == 05) {
                        // On check si on a la restriction "BIENVENUE"
                        if (strpos($ligne, 'BIENVENUE') == true) {
                            $array[$i]['unilend_bienvenue'] = true;
                        }

                        // si prelevement
                        if (in_array(substr($ligne, 32, 2), array(23, 25, 'A1', 'B1'))) {
                            // On veut recuperer ques ces 2 lignes
                            if (in_array(trim(substr($ligne, 45, 3)), array('LCC', 'LC2'))) {
                                $laligne += 1;
                                $array[$i]['libelleOpe' . $laligne] = trim(substr($ligne, 45));
                            }
                        } // virement
                        else {
                            $laligne += 1;
                            $array[$i]['libelleOpe' . $laligne] = trim(substr($ligne, 45));
                        }
                    }
                }
            }
            if (! feof($handle)) {
                $this->stopCron();
            }
            fclose($handle);

            // on retire les indésirables
            foreach ($tabRestriction as $r) {
                unset($array[$r]);
            }
            return $array;
        }
    }

    // reception virements/prelevements (toutes les 30 min)
    public function _reception()
    {
        if (true === $this->startCron('reception', 5)) {
            $receptions                          = $this->loadData('receptions');
            $clients                             = $this->loadData('clients');
            $lenders                             = $this->loadData('lenders_accounts');
            $transactions                        = $this->loadData('transactions');
            $wallets                             = $this->loadData('wallets_lines');
            $bank                                = $this->loadData('bank_lines');
            $projects                            = $this->loadData('projects');
            $companies                           = $this->loadData('companies');
            $bank_unilend                        = $this->loadData('bank_unilend');
            $this->notifications                 = $this->loadData('notifications');
            $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
            $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
            $this->loadData('transactions_types'); // Variable is not used but we must call it in order to create CRUD if not existing :'(

            $statusVirementRecu  = array(05, 18, 45, 13);
            $statusVirementEmis  = array(06, 21);
            $statusVirementRejet = array(12);

            $statusPrelevementEmi    = array(23, 25, 'A1', 'B1');
            $statusPrelevementRejete = array(10, 27, 'A3', 'B3');

            if ($this->Config['env'] === 'prod') {
                $connection = ssh2_connect('ssh.reagi.com', 22);
                ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
                $sftp = ssh2_sftp($connection);

                $lien = 'ssh2.sftp://' . $sftp . '/home/sfpmei/receptions';

                if (false === file_exists($lien)) {
                    $this->oLogger->error('SFTP connection error', array('class' => __CLASS__, 'function' => __FUNCTION__));
                    mail($this->sDestinatairesDebug, '[Alert] Unilend SFTP connection error', '[Alert] Unilend SFTP connection error - cron reception', $this->sHeadersDebug);
                    $this->stopCron();
                    die;
                }
            } else {
                $lien = $this->path . 'protected/sftp/sfpmei/reception';
            }

//            $lien .= '/UNILEND-00040631007-' . date('Ymd') . '.txt';'
            $lien .= '/UNILEND-00040631007-20141030.txt';

            $file = @file_get_contents($lien);
            if ($file === false) {
                $ladate = time();

                // le cron passe a 15 et 45, nous on va check a 15
                $NotifHeure    = mktime(10, 0, 0, date('m'), date('d'), date('Y'));
                $NotifHeurefin = mktime(10, 20, 0, date('m'), date('d'), date('Y'));

                // Si a 10h on a pas encore de fichier bah on lance un mail notif
                if ($ladate >= $NotifHeure && $ladate <= $NotifHeurefin) {
                    $oSettings = $this->loadData('settings');
                    $oSettings->get('Adresse notification aucun virement', 'type');
                    $destinataire = $oSettings->value;

                    $varMail = array(
                        '$surl' => $this->surl,
                        '$url'  => $this->lurl
                    );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-aucun-virement', $varMail, false);
                    $message->setTo($destinataire);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
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

                $recep = $receptions->select('DATE(added) = "' . date('Y-m-d') . '"');
                // si on a un fichier et qu'il n'est pas deja present en bdd
                // on enregistre qu'une fois par jour
                if ($lrecus != false && ($recep == false || isset($this->params[0]) && $this->params[0] === 'forceReplay')) {
//                    file_put_contents($this->path . 'protected/sftp/reception/UNILEND-00040631007-' . date('Ymd') . '.txt', $file);

                    foreach ($lrecus as $r) {
                        $transactions->unsetData();
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
                            if (false === empty($r['libelleOpe' . $i])) {
                                $motif .= trim($r['libelleOpe' . $i]) . '<br>';
                            }
                        }

                        // Si on a un virement unilend offre de bienvenue
                        if (isset($r['unilend_bienvenue'])) {
                            $this->oLogger->info('Bank transfer welcome offer (offre de bienvenue)', array('class' => __CLASS__, 'function' => __FUNCTION__));

                            $transactions->id_prelevement   = 0;
                            $transactions->id_client        = 0;
                            $transactions->montant          = $r['montant'];
                            $transactions->id_langue        = 'fr';
                            $transactions->date_transaction = date('Y-m-d H:i:s');
                            $transactions->status           = 1;
                            $transactions->etat             = 1;
                            $transactions->transaction      = 1;
                            $transactions->type_transaction = \transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER;
                            $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                            $transactions->create();

                            $bank_unilend->id_transaction = $transactions->id_transaction;
                            $bank_unilend->id_project     = 0;
                            $bank_unilend->montant        = $receptions->montant;
                            $bank_unilend->type           = 4; // Unilend offre de bienvenue
                            $bank_unilend->create();
                        } else {
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
                            $receptions->create();

                            if ($type === 1 && $status_prelevement === 2) { // Prélèvements
                                preg_match('#[0-9]+#', $motif, $extract);
                                $iProjectId = (int) $extract[0];

                                /** @var \echeanciers_emprunteur $oRepaymentSchedule */
                                $oRepaymentSchedule = $this->loadData('echeanciers_emprunteur');
                                $aNextRepayment     = $oRepaymentSchedule->select('id_project = ' . $iProjectId . ' AND status_emprunteur = 0', 'ordre ASC', 0, 1);

                                /** @var \prelevements $oBankDirectDebit */
                                $oBankDirectDebit = $this->loadData('prelevements');
                                if (
                                    count($aNextRepayment) > 0
                                    && $oBankDirectDebit->get($iProjectId . '" AND num_prelevement = "' . $aNextRepayment[0]['ordre'], 'id_project')
                                    && false !== strpos($motif, $oBankDirectDebit->motif)
                                    && false === $transactions->get($receptions->id_reception, 'status = 1 AND etat = 1 AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT . ' AND id_prelevement')
                                ) {
                                    $projects->get($iProjectId, 'id_project');
                                    $companies->get($projects->id_company, 'id_company');
                                    $clients->get($companies->id_client_owner, 'id_client');

                                    $receptions->id_client  = $clients->id_client;
                                    $receptions->id_project = $projects->id_project;
                                    $receptions->status_bo  = 2;
                                    $receptions->remb       = 1;
                                    $receptions->update();

                                    $transactions->id_prelevement   = $receptions->id_reception;
                                    $transactions->id_client        = $clients->id_client;
                                    $transactions->montant          = $receptions->montant;
                                    $transactions->id_langue        = 'fr';
                                    $transactions->date_transaction = date('Y-m-d H:i:s');
                                    $transactions->status           = 1;
                                    $transactions->etat             = 1;
                                    $transactions->transaction      = 1;
                                    $transactions->type_transaction = \transactions_types::TYPE_BORROWER_REPAYMENT;
                                    $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                    $transactions->create();

                                    $bank_unilend->id_transaction = $transactions->id_transaction;
                                    $bank_unilend->id_project     = $projects->id_project;
                                    $bank_unilend->montant        = $receptions->montant;
                                    $bank_unilend->type           = 1;
                                    $bank_unilend->create();

                                    $this->updateEcheances($projects->id_project, $receptions->montant);
                                }
                            } elseif ($type === 2 && $status_virement === 1) { // Virements reçus
                                if (
                                    isset($r['libelleOpe3'])
                                    && 1 === preg_match('/RA-?([0-9]+)/', $r['libelleOpe3'], $aMatches)
                                    && $this->projects->get((int) $aMatches[1])
                                    && false === $transactions->get($receptions->id_reception, 'status = 1 AND etat = 1 AND id_virement')
                                ) {
                                    $receptions->id_project = $this->projects->id_project;
                                    $receptions->type_remb  = \receptions::REPAYMENT_TYPE_EARLY;
                                    $receptions->status_bo  = 2; // attri auto
                                    $receptions->update();

                                    $transactions->id_virement      = $receptions->id_reception;
                                    $transactions->id_project       = $this->projects->id_project;
                                    $transactions->montant          = $receptions->montant;
                                    $transactions->id_langue        = 'fr';
                                    $transactions->date_transaction = date('Y-m-d H:i:s');
                                    $transactions->status           = 1;
                                    $transactions->etat             = 1;
                                    $transactions->transaction      = 1;
                                    $transactions->type_transaction = \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT;
                                    $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                    $transactions->create();

                                    $bank_unilend                 = $this->loadData('bank_unilend');
                                    $bank_unilend->id_transaction = $transactions->id_transaction;
                                    $bank_unilend->id_project     = $this->projects->id_project;
                                    $bank_unilend->montant        = $receptions->montant;
                                    $bank_unilend->type           = 1; // remb emprunteur
                                    $bank_unilend->status         = 0; // chez unilend
                                    $bank_unilend->create();

                                    $oSettings = $this->loadData('settings');
                                    $oSettings->get('Adresse notification nouveau remboursement anticipe', 'type');
                                    $destinataire = $oSettings->value;

                                    $varMail = array(
                                        '$surl'       => $this->surl,
                                        '$url'        => $this->lurl,
                                        '$id_projet'  => $this->projects->id_project,
                                        '$montant'    => $transactions->montant / 100,
                                        '$nom_projet' => $this->projects->title
                                    );

                                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-nouveau-remboursement-anticipe', $varMail, false);
                                    $message->setTo($destinataire);
                                    $mailer = $this->get('mailer');
                                    $mailer->send($message);

                                } elseif (isset($r['libelleOpe3']) && strstr($r['libelleOpe3'], 'REGULARISATION')) { // Régularisation
                                    preg_match_all('#[0-9]+#', $r['libelleOpe3'], $extract);

                                    foreach ($extract[0] as $nombre) {
                                        if ($projects->get((int) $nombre, 'id_project')) {
                                            $companies->get($projects->id_company, 'id_company');

                                            // @todo duplicate code in transferts::_non_attribues()
                                            $receptions->motif      = $motif;
                                            $receptions->id_client  = $companies->id_client_owner;
                                            $receptions->id_project = $projects->id_project;
                                            $receptions->status_bo  = 2;
                                            $receptions->type_remb  = 2;
                                            $receptions->remb       = 1;
                                            $receptions->update();

                                            $transactions->id_virement      = $receptions->id_reception;
                                            $transactions->montant          = $receptions->montant;
                                            $transactions->id_langue        = 'fr';
                                            $transactions->date_transaction = date('Y-m-d H:i:s');
                                            $transactions->status           = 1;
                                            $transactions->etat             = 1;
                                            $transactions->transaction      = 1;
                                            $transactions->type_transaction = \transactions_types::TYPE_REGULATION_BANK_TRANSFER;
                                            $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                            $transactions->create();

                                            $bank_unilend->id_transaction = $transactions->id_transaction;
                                            $bank_unilend->id_project     = $projects->id_project;
                                            $bank_unilend->montant        = $receptions->montant;
                                            $bank_unilend->type           = 1;
                                            $bank_unilend->create();

                                            $this->updateEcheances($projects->id_project, $receptions->montant);
                                            break;
                                        }
                                    }
                                } else { // Virement prêteur
                                    preg_match_all('#[0-9]+#', $motif, $extract);

                                    foreach ($extract[0] as $nombre) {
                                        if ($clients->get((int) $nombre, 'id_client')) {
                                            $sLenderPattern = str_replace(' ', '', $clients->getLenderPattern($clients->id_client));

                                            if (
                                                (false !== strpos(str_replace(' ', '', $motif), $sLenderPattern) || true === $clients->isLenderPattern($clients->id_client, str_replace(' ', '', $motif)))
                                                && false === $transactions->get($receptions->id_reception, 'status = 1 AND etat = 1 AND id_virement')
                                            ) {
                                                $receptions->get($receptions->id_reception, 'id_reception');
                                                $receptions->id_client = $clients->id_client;
                                                $receptions->status_bo = 2;
                                                $receptions->remb      = 1;
                                                $receptions->update();

                                                $lenders->get($clients->id_client, 'id_client_owner');
                                                $lenders->status = 1;
                                                $lenders->update();

                                                $transactions->id_virement      = $receptions->id_reception;
                                                $transactions->id_client        = $lenders->id_client_owner;
                                                $transactions->montant          = $receptions->montant;
                                                $transactions->id_langue        = 'fr';
                                                $transactions->date_transaction = date('Y-m-d H:i:s');
                                                $transactions->status           = 1;
                                                $transactions->etat             = 1;
                                                $transactions->transaction      = 1;
                                                $transactions->type_transaction = \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT;
                                                $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                                $transactions->create();

                                                $wallets->id_lender                = $lenders->id_lender_account;
                                                $wallets->type_financial_operation = 30; // alimenation
                                                $wallets->id_transaction           = $transactions->id_transaction;
                                                $wallets->type                     = 1; // physique
                                                $wallets->amount                   = $receptions->montant;
                                                $wallets->status                   = 1;
                                                $wallets->create();

                                                $bank->id_wallet_line    = $wallets->id_wallet_line;
                                                $bank->id_lender_account = $lenders->id_lender_account;
                                                $bank->status            = 1;
                                                $bank->amount            = $receptions->montant;
                                                $bank->create();

                                                if ($clients->etape_inscription_preteur < 3) {
                                                    $clients->etape_inscription_preteur = 3;
                                                    $clients->update();
                                                }

                                                if ($clients->status == 1) {
                                                    $this->notifications->type      = \notifications::TYPE_BANK_TRANSFER_CREDIT;
                                                    $this->notifications->id_lender = $lenders->id_lender_account;
                                                    $this->notifications->amount    = $receptions->montant;
                                                    $this->notifications->create();

                                                    $this->clients_gestion_mails_notif->id_client       = $lenders->id_client_owner;
                                                    $this->clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT;
                                                    $this->clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
                                                    $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                                                    $this->clients_gestion_mails_notif->id_transaction  = $transactions->id_transaction;
                                                    $this->clients_gestion_mails_notif->create();

                                                    if ($this->clients_gestion_notifications->getNotif($lenders->id_client_owner, \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT, 'immediatement') == true) {
                                                        $this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                                                        $this->clients_gestion_mails_notif->immediatement = 1;
                                                        $this->clients_gestion_mails_notif->update();

                                                        $varMail = array(
                                                            'surl'            => $this->surl,
                                                            'url'             => $this->lurl,
                                                            'prenom_p'        => utf8_decode($clients->prenom),
                                                            'fonds_depot'     => $this->ficelle->formatNumber($receptions->montant / 100),
                                                            'solde_p'         => $this->ficelle->formatNumber($transactions->getSolde($receptions->id_client)),
                                                            'motif_virement'  => $sLenderPattern,
                                                            'projets'         => $this->lurl . '/projets-a-financer',
                                                            'gestion_alertes' => $this->lurl . '/profile',
                                                            'lien_fb'         => $this->like_fb,
                                                            'lien_tw'         => $this->twitter
                                                        );

                                                        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                                        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-alimentation', $varMail);
                                                        $message->setTo($clients->email);
                                                        $mailer = $this->get('mailer');
                                                        $mailer->send($message);
                                                    }
                                                }
                                            }
                                            break;
                                        }
                                    }
                                }
                            } elseif ($type === 1 && $status_prelevement === 3) {
                                $oCompanies             = $this->loadData('companies');
                                $oEcheanciers           = $this->loadData('echeanciers');
                                $oEcheanciersEmprunteur = $this->loadData('echeanciers_emprunteur');
                                $oPrelevements          = $this->loadData('prelevements');
                                $oProjectsRemb          = $this->loadData('projects_remb');
                                $oTransactions          = $this->loadData('transactions');

                                if (
                                    1 === preg_match('#^RUM[^0-9]*([0-9]+)#', $r['libelleOpe3'], $aMatches)
                                    && $this->projects->get((int) $aMatches[1])
                                    && 1 === preg_match('#^RCNUNILEND/([0-9]{8})/([0-9]+)#', $r['libelleOpe4'], $aMatches)
                                    && $oPrelevements->get((int) $aMatches[2])
                                    && $this->projects->id_project == $oPrelevements->id_project
                                    && $oCompanies->get($this->projects->id_company)
                                    && $this->transactions->get($r['montant'], 'status = 1 AND etat = 1 AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT . ' AND DATE(date_transaction) >= STR_TO_DATE("' . $aMatches[1] . '", "%Y%m%d") AND id_client = ' . $oCompanies->id_client_owner . ' AND montant')
                                    && false === $oTransactions->get($this->transactions->id_prelevement, 'status = 1 AND etat = 1 AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION . ' AND id_prelevement')
                                ) {
                                    $this->projects->remb_auto = 1;
                                    $this->projects->update();

                                    // @todo duplicate code of transferts::_rejeter_prelevement_projet()
                                    $oTransactions->id_prelevement   = $this->transactions->id_prelevement;
                                    $oTransactions->id_client        = $oCompanies->id_client_owner;
                                    $oTransactions->montant          = - $receptions->montant;
                                    $oTransactions->id_langue        = 'fr';
                                    $oTransactions->date_transaction = date('Y-m-d H:i:s');
                                    $oTransactions->status           = 1;
                                    $oTransactions->etat             = 1;
                                    $oTransactions->transaction      = 1;
                                    $oTransactions->type_transaction = \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION;
                                    $oTransactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                    $oTransactions->create();

                                    $bank_unilend->id_transaction = $oTransactions->id_transaction;
                                    $bank_unilend->id_project     = $this->projects->id_project;
                                    $bank_unilend->montant        = - $receptions->montant;
                                    $bank_unilend->type           = 1;
                                    $bank_unilend->create();

                                    $receptions->get($this->transactions->id_prelevement);
                                    $receptions->status_bo = 3; // rejeté
                                    $receptions->remb      = 0;
                                    $receptions->update();

                                    $newsum = $receptions->montant / 100;

                                    foreach ($oEcheanciersEmprunteur->select('status_emprunteur = 1 AND id_project = ' . $this->projects->id_project, 'ordre DESC') as $e) {
                                        $montantDuMois = $oEcheanciers->getMontantRembEmprunteur($e['montant'] / 100, $e['commission'] / 100, $e['tva'] / 100);

                                        if ($montantDuMois <= $newsum) {
                                            $oEcheanciers->updateStatusEmprunteur($this->projects->id_project, $e['ordre'], 'annuler');

                                            $oEcheanciersEmprunteur->get($this->projects->id_project, 'ordre = ' . $e['ordre'] . ' AND id_project');
                                            $oEcheanciersEmprunteur->status_emprunteur             = 0;
                                            $oEcheanciersEmprunteur->date_echeance_emprunteur_reel = '0000-00-00 00:00:00';
                                            $oEcheanciersEmprunteur->update();

                                            $newsum = $newsum - $montantDuMois;

                                            if ($oProjectsRemb->counter('id_project = "' . $this->projects->id_project . '" AND ordre = "' . $e['ordre'] . '" AND status = 0') > 0) {
                                                $oProjectsRemb->get($e['ordre'], 'status = 0 AND id_project = "' . $this->projects->id_project . '" AND ordre');
                                                $oProjectsRemb->status = \projects_remb::STATUS_REJECTED;
                                                $oProjectsRemb->update();
                                            }
                                        } else {
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->stopCron();
        }
    }

    private function updateEcheances($id_project, $montant)
    {
        $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        $echeanciers            = $this->loadData('echeanciers');
        $projects_remb          = $this->loadData('projects_remb');

        $eche   = $echeanciers_emprunteur->select('status_emprunteur = 0 AND id_project = ' . $id_project, 'ordre ASC');
        $newsum = $montant / 100;

        foreach ($eche as $e) {
            $ordre         = $e['ordre'];
            $montantDuMois = $echeanciers->getMontantRembEmprunteur($e['montant'] / 100, $e['commission'] / 100, $e['tva'] / 100);

            if ($montantDuMois <= $newsum) {
                $echeanciers->updateStatusEmprunteur($id_project, $ordre);

                $echeanciers_emprunteur->get($id_project, 'ordre = ' . $ordre . ' AND id_project');
                $echeanciers_emprunteur->status_emprunteur             = 1;
                $echeanciers_emprunteur->date_echeance_emprunteur_reel = date('Y-m-d H:i:s');
                $echeanciers_emprunteur->update();

                $newsum = $newsum - $montantDuMois;

                if ($projects_remb->counter('id_project = "' . $id_project . '" AND ordre = "' . $ordre . '" AND status IN(0, 1)') <= 0) {
                    $date_echeance_preteur = $echeanciers->select('id_project = "' . $id_project . '" AND ordre = "' . $ordre . '"', '', 0, 1);

                    $projects_remb->id_project                = $id_project;
                    $projects_remb->ordre                     = $ordre;
                    $projects_remb->date_remb_emprunteur_reel = date('Y-m-d H:i:s');
                    $projects_remb->date_remb_preteurs        = $date_echeance_preteur[0]['date_echeance'];
                    $projects_remb->date_remb_preteurs_reel   = '0000-00-00 00:00:00';
                    $projects_remb->status                    = \projects_remb::STATUS_PENDING;
                    $projects_remb->create();
                }
            } else {
                break;
            }
        }
    }

    // passe a 1h du matin le 1er du mois
    public function _etat_fiscal()
    {
        if (true === $this->startCron('etat_fiscal', 5)) {
            /** @var \echeanciers $echeanciers */
            $echeanciers = $this->loadData('echeanciers');
            /** @var \bank_unilend $bank_unilend */
            $bank_unilend = $this->loadData('bank_unilend');
            /** @var \transactions $transactions */
            $transactions = $this->loadData('transactions');

            $this->settings->get('EQ-Acompte d\'impôt sur le revenu', 'type');
            $prelevements_obligatoires = $this->settings->value * 100;

            $this->settings->get('EQ-Contribution additionnelle au Prélèvement Social', 'type');
            $txcontributions_additionnelles = $this->settings->value * 100;

            $this->settings->get('EQ-CRDS', 'type');
            $txcrds = $this->settings->value * 100;

            $this->settings->get('EQ-CSG', 'type');
            $txcsg = $this->settings->value * 100;

            $this->settings->get('EQ-Prélèvement de Solidarité', 'type');
            $txprelevements_solidarite = $this->settings->value * 100;

            $this->settings->get('EQ-Prélèvement social', 'type');
            $txprelevements_sociaux = $this->settings->value * 100;

            $this->settings->get('EQ-Retenue à la source', 'type');
            $tauxRetenuSource = $this->settings->value * 100;

            $mois          = date('m');
            $annee         = date('Y');
            $dateDebutTime = mktime(0, 0, 0, $mois - 1, 1, $annee);
            $dateDebutSql  = date('Y-m-d', $dateDebutTime);
            $dateFinTime   = mktime(0, 0, 0, $mois, 0, $annee);
            $dateFinSql    = date('Y-m-d', $dateFinTime);

            // personnes morale //
            $Morale1    = $echeanciers->getEcheanceBetweenDates($dateDebutSql, $dateFinSql, '0', '2'); // entreprises
            $etranger   = $echeanciers->getEcheanceBetweenDatesEtranger($dateDebutSql, $dateFinSql); // etrangers
            $MoraleInte = (array_sum(array_column($Morale1, 'interets')) + array_sum(array_column($etranger, 'interets'))) / 100;

            $prelevementRetenuSoucre[1] = $Morale1[1]['retenues_source'] + $etranger[1]['retenues_source'];

            // Physique non exoneré //
            $PhysiqueNoExo     = $echeanciers->getEcheanceBetweenDates($dateDebutSql, $dateFinSql, '0', array(1, 3));
            $PhysiqueNoExoInte[1] = ($PhysiqueNoExo[1]['interets'] - $etranger[1]['interets']) / 100;
            $PhysiqueNoExoInte[2] = ($PhysiqueNoExo[2]['interets'] - $etranger[2]['interets']) / 100;

            // prelevements pour physiques non exonéré
            $lesPrelevSurPhysiqueNoExo[1] = $PhysiqueNoExo[1]['prelevements_obligatoires'] - $etranger[1]['prelevements_obligatoires'];
            $lesPrelevSurPhysiqueNoExo[2] = $PhysiqueNoExo[2]['prelevements_obligatoires'] - $etranger[2]['prelevements_obligatoires'];

            // Physique non exoneré dans la peride //
            $PhysiqueNonExoPourLaPeriode = $echeanciers->getEcheanceBetweenDates_exonere_mais_pas_dans_les_dates($dateDebutSql, $dateFinSql);
            $PhysiqueNoExoInte[1] += $PhysiqueNonExoPourLaPeriode[1]['interets'] / 100;
            $PhysiqueNoExoInte[2] += $PhysiqueNonExoPourLaPeriode[2]['interets'] / 100;

            // prelevements pour physiques non exonéré
            $lesPrelevSurPhysiqueNoExo[1] += $PhysiqueNonExoPourLaPeriode[1]['prelevements_obligatoires'];
            $lesPrelevSurPhysiqueNoExo[2] += $PhysiqueNonExoPourLaPeriode[2]['prelevements_obligatoires'];

            // Physique exoneré //
            $PhysiqueExo     = $echeanciers->getEcheanceBetweenDates($dateDebutSql, $dateFinSql, '1', array(1, 3));
            $PhysiqueExoInte = array_sum(array_column($PhysiqueExo, 'interets')) / 100;

            // prelevements pour physiques exonéré
            $lesPrelevSurPhysiqueExo = array_sum(array_column($PhysiqueExo, 'prelevements_obligatoires'));

            // Physique //
            $Physique     = $echeanciers->getEcheanceBetweenDates($dateDebutSql, $dateFinSql, '', array(1, 3));
            $PhysiqueInte = (array_sum(array_column($Physique, 'interets')) - array_sum(array_column($etranger, 'interets'))) / 100;

            // prelevements pour physiques
            $lesPrelevSurPhysique         = array_sum(array_column($Physique, 'prelevements_obligatoires')) - array_sum(array_column($etranger, 'prelevements_obligatoires'));
            $csg                          = array_sum(array_column($Physique, 'csg')) - array_sum(array_column($etranger, 'csg'));
            $prelevements_sociaux         = array_sum(array_column($Physique, 'prelevements_sociaux')) - array_sum(array_column($etranger, 'prelevements_sociaux'));
            $contributions_additionnelles = array_sum(array_column($Physique, 'contributions_additionnelles')) - array_sum(array_column($etranger, 'contributions_additionnelles'));
            $prelevements_solidarite      = array_sum(array_column($Physique, 'prelevements_solidarite')) - array_sum(array_column($etranger, 'prelevements_solidarite'));
            $crds                         = array_sum(array_column($Physique, 'crds')) - array_sum(array_column($etranger, 'crds'));

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
                <th style="background-color:#C9DAF2;">' . date('d/m/Y', $dateDebutTime) . '</th>
                <th style="background-color:#C9DAF2;">au</th>
                <th style="background-color:#C9DAF2;">' . date('d/m/Y', $dateFinTime) . '</th>
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
                <th style="background-color:#E6F4DA;">Soumis au prélèvement (bons de caisse)</th>
                <td class="right">' . $this->ficelle->formatNumber($PhysiqueNoExoInte[1]) . '</td>
                <td class="right">' . $this->ficelle->formatNumber($lesPrelevSurPhysiqueNoExo[1]) . '</td>
                <td style="background-color:#DDDAF4;" class="right">' . $this->ficelle->formatNumber($prelevements_obligatoires) . '%</td>
            </tr>
            <tr>
                <th style="background-color:#E6F4DA;">Soumis au prélèvement (prêt IFP)</th>
                <td class="right">' . $this->ficelle->formatNumber($PhysiqueNoExoInte[2]) . '</td>
                <td class="right">' . $this->ficelle->formatNumber($lesPrelevSurPhysiqueNoExo[2]) . '</td>
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
                <th style="background-color:#ECAEAE;" colspan="4">Retenue à la source (bons de caisse)</th>
            </tr>
            <tr>
                <th style="background-color:#E6F4DA;">Retenue à la source</th>
                <td class="right">' . $this->ficelle->formatNumber($MoraleInte) . '</td>
                <td class="right">' . $this->ficelle->formatNumber($prelevementRetenuSoucre[1]) . '</td>
                <td style="background-color:#DDDAF4;" class="right">' . $this->ficelle->formatNumber($tauxRetenuSource) . '%</td>
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

            $connection = ssh2_connect('ssh.reagi.com', 22);
            ssh2_auth_password($connection, 'sfpmei', '769kBa5v48Sh3Nug');
            $sftp       = ssh2_sftp($connection);
            $sftpStream = @fopen('ssh2.sftp://' . $sftp . '/home/sfpmei/emissions/etat_fiscal/' . $filename . '.xls', 'w');
            fwrite($sftpStream, $table);
            fclose($sftpStream);

            /** @var \settings $oSettings */
            $oSettings = $this->loadData('settings');
            $oSettings->get('Adresse notification etat fiscal', 'type');
            $destinataire = $oSettings->value;

            $varMail = array(
                '$surl' => $this->surl,
                '$url'  => $this->lurl
            );

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-etat-fiscal', $varMail, false);
            $message->setTo(trim($destinataire));
            $mailer = $this->get('mailer');
            $mailer->send($message);

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
                $transactions->id_client        = 0;
                $transactions->montant          = $etatRemb;
                $transactions->id_langue        = 'fr';
                $transactions->date_transaction = date('Y-m-d H:i:s');
                $transactions->status           = '1';
                $transactions->etat             = '1';
                $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                $transactions->type_transaction = 12; // virement etat (retrait)
                $transactions->transaction      = 1; // transaction virtuelle
                $transactions->create();

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

    // Toutes les minutes de 21h à 7h
    public function _declarationContratPret()
    {
        if (true === $this->startCron('declarationContratPret', 5)) {
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', 300);

            $loans    = $this->loadData('loans');
            $projects = $this->loadData('projects');

            $lProjects = $projects->selectProjectsByStatus(implode(', ', array(\projects_status::REMBOURSEMENT, \projects_status::REMBOURSE, \projects_status::PROBLEME, \projects_status::RECOUVREMENT, \projects_status::DEFAUT, \projects_status::REMBOURSEMENT_ANTICIPE, \projects_status::PROBLEME_J_X, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE)), '', '', array(), '', '', false);

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
                        $nom  = 'Unilend_declarationContratPret_' . $l['id_loan'] . '.pdf';

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

    // a 16 h 10 (10 16 * * *)
    public function _checkFinProjet()
    {
        if (true === $this->startCron('checkFinProjet', 5)) {
            $projects       = $this->loadData('projects');
            $bids           = $this->loadData('bids');
            $loans          = $this->loadData('loans');
            $transactions   = $this->loadData('transactions');
            $projects_check = $this->loadData('projects_check');

            $lProjets = $projects->selectProjectsByStatus(\projects_status::FUNDE, ' AND DATE(p.date_fin) = "' . date('Y-m-d') . '"', '', array(), '', '', false);

            foreach ($lProjets as $p) {
                if ($projects_check->get($p['id_project'], 'id_project') === false ) {
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
                            </html>';
                        mail($this->sDestinatairesDebug, $subject, $message, $this->sHeadersDebug);
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

            foreach ($lPreteurs as $p) {
                $timestamp_date = $this->dates->formatDateMySqlToTimeStamp($p['added_status']);

                // on ajoute une restriction. Plus de 7j et le premier samedi qui suit.
                if ($timestamp_date <= $timeMoins8 && date('w') == 6) {
                    $this->clients_status_history->get($p['id_client_status_history'], 'id_client_status_history');

                    $timeCreate = strtotime($p['added_status']);
                    $month      = $this->dates->tableauMois['fr'][date('n', $timeCreate)];

                    $varMail = array(
                        'furl'          => $this->lurl,
                        'surl'          => $this->surl,
                        'url'           => $this->lurl,
                        'prenom_p'      => $p['prenom'],
                        'date_creation' => date('d', $timeCreate) . ' ' . $month . ' ' . date('Y', $timeCreate),
                        'content'       => $this->clients_status_history->content,
                        'lien_fb'       => $this->like_fb,
                        'lien_tw'       => $this->twitter
                    );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('completude', $varMail);
                    $message->setTo($p['email']);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);

                    $this->clients_status_history->addStatus(\users::USER_ID_CRON, \clients_status::COMPLETENESS_REMINDER, $p['id_client'], $this->clients_status_history->content);
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
                    $this->clients_status_history->addStatus(\users::USER_ID_CRON, \clients_status::COMPLETENESS_REMINDER, $p['id_client'], $data_clients_status_history['content'], 2);
                } elseif ($timestamp_date <= $timeMoins8 && $numero_relance == 2 && date('w') == 6) {// Relance J+30
                    $op_pour_relance = true;
                    $this->clients_status_history->addStatus(\users::USER_ID_CRON, \clients_status::COMPLETENESS_REMINDER, $p['id_client'], $data_clients_status_history['content'], 3);
                } elseif ($timestamp_date <= $timeMoins30 && $numero_relance == 3 && date('w') == 6) {// Relance J+60
                    $op_pour_relance = true;
                    $this->clients_status_history->addStatus(\users::USER_ID_CRON, \clients_status::COMPLETENESS_REMINDER, $p['id_client'], $data_clients_status_history['content'], 4);
                }

                if ($op_pour_relance) {
                    $timeCreate = strtotime($p['added_status']);
                    $month      = $this->dates->tableauMois['fr'][date('n', $timeCreate)];

                    $varMail = array(
                        'furl'          => $this->lurl,
                        'surl'          => $this->surl,
                        'url'           => $this->lurl,
                        'prenom_p'      => $p['prenom'],
                        'date_creation' => date('d', $timeCreate) . ' ' . $month . ' ' . date('Y', $timeCreate),
                        'content'       => $data_clients_status_history['content'],
                        'lien_fb'       => $this->like_fb,
                        'lien_tw'       => $this->twitter
                    );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('completude', $varMail);
                    $message->setTo($p['email']);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                }
            }

            $this->stopCron();
        }
    }

    // 1 fois par jour et on check les transactions non validés sur une journée (00:30)
    public function _check_alim_cb()
    {
        if (true === $this->startCron('checkAlimCb', 5)) {
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
                            $this->backpayline->create();

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
                            $this->wallets_lines->create();

                            $this->bank_lines->id_wallet_line    = $this->wallets_lines->id_wallet_line;
                            $this->bank_lines->id_lender_account = $this->lenders_accounts->id_lender_account;
                            $this->bank_lines->status            = 1;
                            $this->bank_lines->amount            = $response['payment']['amount'];
                            $this->bank_lines->create();

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
                                </html>';

                            mail($this->sDestinatairesDebug, $subject, $message, $this->sHeadersDebug);
                        }
                    }
                }
            }

            $this->stopCron();
        }
    }

    // Une fois par jour (crée le 27/04/2015)
    public function _check_prelevements_emprunteurs()
    {
        $echeanciers = $this->loadData('echeanciers');
        $projects    = $this->loadData('projects');

        $liste       = $echeanciers->selectEcheanciersByprojetEtOrdre(); // <--- a rajouter en prod
        $liste_remb  = '';
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
                    <td>' . ((int) $l['status_emprunteur'] === 1 ? 'Oui' : 'Non') . '</td>
                </tr>';
        }

        $varMail = array(
            '$surl'       => $this->surl,
            '$liste_remb' => $liste_remb
        );

        /** @var \settings $oSettings */
        $oSettings = $this->loadData('settings');
        $oSettings->get('Adresse notification check remb preteurs', 'type');
        $destinataire = $oSettings->value;

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-prelevement-emprunteur', $varMail, false);
        $message->setTo($destinataire);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    public function _check_remboursement_preteurs()
    {
        $oRepayment = $this->loadData('echeanciers');
        $oProject    = $this->loadData('projects');
        $oDate       = new \DateTime();
        $aRepayments = $oRepayment->getRepaymentOfTheDay($oDate);
        $sRepayments  = '';
        foreach ($aRepayments as $aRepayment) {
            $oProject->get($aRepayment['id_project'], 'id_project');
            $sRepayments .= '
                <tr>
                    <td>' . $aRepayment['id_project'] . '</td>
                    <td>' . $oProject->title_bo . '</td>
                    <td>' . $aRepayment['ordre'] . '</td>
                    <td>' . $aRepayment['nb_repayment'] . '</td>
                    <td>' . $aRepayment['nb_repayment_paid'] . '</td>
                    <td>' . ($aRepayment['nb_repayment'] === $aRepayment['nb_repayment_paid'] ? 'Oui' : 'Non') . '</td>
                </tr>';
        }

        $aReplacements = array(
            '[#SURL#]'         => $this->surl,
            '[#REPAYMENTS#]'   => $sRepayments
        );

        /** @var \settings $oSettings */
        $oSettings = $this->loadData('settings');
        $oSettings->get('Adresse notification check remb preteurs', 'type');
        $sRecipient  = $oSettings->value;

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-check-remboursements-preteurs', $aReplacements, false);
        $message->setTo(trim($sRecipient));
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    // Fonction qui crée les notification nouveaux projet pour les prêteurs (immediatement)(OK)
    private function sendNewProjectEmail(\projects $oProject)
    {
        $this->clients                       = $this->loadData('clients');
        $this->notifications                 = $this->loadData('notifications');
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
        $this->projects                      = $this->loadData('projects');
        $this->companies                     = $this->loadData('companies');
        // Loaded for class constants
        $this->loadData('clients_status');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->loadData('lenders_accounts');
        /** @var \transactions $oTransaction */
        $oTransaction = $this->loadData('transactions');
        /** @var \Unilend\Service\AutoBidSettingsManager $oAutobidSettingsManager */
        $oAutobidSettingsManager = $this->get('AutoBidSettingsManager');

        $this->oLogger->debug('Sending new project email : id_project=' . $id_project, array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $id_project));

        $this->projects->get($oProject->id_project, 'id_project');
        $this->companies->get($this->projects->id_company, 'id_company');

        $varMail = array(
            'surl'            => $this->surl,
            'url'             => $this->furl,
            'nom_entreprise'  => $this->companies->name,
            'projet-p'        => $this->furl . '/projects/detail/' . $this->projects->slug,
            'montant'         => $this->ficelle->formatNumber($this->projects->amount, 0),
            'duree'           => $this->projects->period,
            'gestion_alertes' => $this->lurl . '/profile',
            'lien_fb'         => $this->like_fb,
            'lien_tw'         => $this->twitter,
            'annee'           => date('Y')
        );
        $this->lng['email-nouveau-projet'] = $this->ln->selectFront('email-nouveau-projet', $this->language, $this->App);

        /** @var \autobid_periods $oAutobidPeriods */
        $oAutobidPeriods = $this->loadData('autobid_periods');
        $aPeriod         = $oAutobidPeriods->getPeriod($oProject->period);

        /** @var \autobid $oAutobid */
        $oAutobid    = $this->loadData('autobid');
        $aAutobiders = array_column($oAutobid->getSettings(null, $oProject->risk, $aPeriod['id_period'], array(\autobid::STATUS_ACTIVE)), 'amount', 'id_lender');

        /** @var \bids $oBids */
        $oBids            = $this->loadData('bids');
        $aBids            = $oBids->getLenders($oProject->id_project);
        $aNoAutobidPlaced = array_diff(array_keys($aAutobiders), array_column($aBids, 'id_lender_account'));

        $iOffset = 0;
        $iLimit  = 100;

        while ($aLenders = $this->clients->selectPreteursByStatus(\clients_status::VALIDATED, 'c.status = 1', 'c.id_client ASC', $iOffset, $iLimit)) {
            $iEmails = 0;
            $iOffset += $iLimit;

             $this->oLogger->debug('Lenders retrieved: ' . count($aLenders), array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $id_project));

            foreach ($aLenders as $aLender) {
                $this->notifications->type       = \notifications::TYPE_NEW_PROJECT;
                $this->notifications->id_lender  = $aLender['id_lender'];
                $this->notifications->id_project = $oProject->id_project;
                $this->notifications->create();

                if (false === $this->clients_gestion_mails_notif->exist(\clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID . '" AND id_project = ' . $oProject->id_project . ' AND id_client = ' . $aLender['id_client'] . ' AND immediatement = "1', 'id_notif')) {
                    $this->clients_gestion_mails_notif->id_client       = $aLender['id_client'];
                    $this->clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_NEW_PROJECT;
                    $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                    $this->clients_gestion_mails_notif->id_project      = $oProject->id_project;
                    $this->clients_gestion_mails_notif->date_notif      = $this->projects->date_publication_full;

                    if (empty($sAutobidInsufficientBalance) && $this->clients_gestion_notifications->getNotif($aLender['id_client'], \clients_gestion_type_notif::TYPE_NEW_PROJECT, 'immediatement')) {
                        $this->clients_gestion_mails_notif->immediatement = 1;

                        $sAutobidInsufficientBalance = '';

                        if (
                            in_array($aLender['id_lender'], $aNoAutobidPlaced)
                            && $oLenderAccount->get($aLender['id_lender'])
                            && $oAutobidSettingsManager->isOn($oLenderAccount)
                            && $oTransaction->getSolde($oLenderAccount->id_client_owner) < $aAutobiders[$oLenderAccount->id_lender_account]
                        ) {
                            $sAutobidInsufficientBalance = '
                                    <table width=\'100%\' border=\'1\' cellspacing=\'0\' cellpadding=\'5\' bgcolor="d8b5ce" bordercolor="b20066">
                                        <tr>
                                            <td align="center" style="color: #b20066">' . $this->lng['email-nouveau-projet']['solde-insuffisant-nouveau-projet'] . '</td>
                                        </tr>
                                    </table>';
                        }

                        $varMail['autobid_insufficient_balance'] = $sAutobidInsufficientBalance;
                        $varMail['prenom_p']                     = $aLender['prenom'];
                        $varMail['motif_virement']               = $this->clients->getLenderPattern($aLender['id_client']);

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('nouveau-projet', $varMail);
                    $message->setTo($aLender['email']);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);

                        ++$iEmails;
                    }
                }

                $this->clients_gestion_mails_notif->create();
            }

            $this->oLogger->debug('New project notification emails sent: ' . $iEmails, array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $id_project));
        }
    }

    // 1 fois par jour on regarde si on a une offre de parrainage a traiter pour donner l'argent
    public function _offre_parrainage()
    {
        die;

        $offres_parrains_filleuls     = $this->loadData('offres_parrains_filleuls');
        $parrains_filleuls            = $this->loadData('parrains_filleuls');
        $parrains_filleuls_mouvements = $this->loadData('parrains_filleuls_mouvements');
        $transactions                 = $this->loadData('transactions');
        $wallets_lines                = $this->loadData('wallets_lines');
        $lenders_accounts             = $this->loadData('lenders_accounts');
        $bank_unilend                 = $this->loadData('bank_unilend');
        $parrain                      = $this->loadData('clients');
        $filleul                      = $this->loadData('clients');

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
                    $this->mail_template->get('confirmation-offre-parrain', 'lang = "' . $this->language . '" AND type');

                    $varMail = array(
                        'surl'            => $this->surl,
                        'url'             => $this->lurl,
                        'nom_parrain'     => $parrain->prenom,
                        'montant_parrain' => ($pf['gains_parrain'] / 100),
                        'lien_fb'         => $this->like_fb,
                        'lien_tw'         => $this->twitter
                    );
                    $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                    $sujetMail = strtr(utf8_decode($this->mail_template->subject), $tabVars);
                    $texteMail = strtr(utf8_decode($this->mail_template->content), $tabVars);
                    $exp_name  = strtr(utf8_decode($this->mail_template->exp_name), $tabVars);

                    $this->email = $this->loadLib('email');
                    $this->email->setFrom($this->mail_template->exp_email, $exp_name);

                    $this->email->setSubject(stripslashes($sujetMail));
                    $this->email->setHTMLBody(stripslashes($texteMail));

                    if ($this->Config['env'] === 'prod') {
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mail_template->id_textemail, $destinataire, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mail_template->nmp_secure, $this->mail_template->id_nmp, $this->mail_template->nmp_unique, $this->mail_template->mode);
                    } else {
                        $this->email->addRecipient(trim($destinataire));
                        Mailer::send($this->email, $this->mails_filer, $this->mail_template->id_textemail);
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
                    $this->mail_template->get('confirmation-offre-filleul', 'lang = "' . $this->language . '" AND type');

                    $varMail = array(
                        'surl'            => $this->surl,
                        'url'             => $this->lurl,
                        'nom_filleul'     => $filleul->prenom,
                        'montant_filleul' => ($pf['gains_filleul'] / 100),
                        'lien_fb'         => $this->like_fb,
                        'lien_tw'         => $this->twitter
                    );

                    $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                    $sujetMail = strtr(utf8_decode($this->mail_template->subject), $tabVars);
                    $texteMail = strtr(utf8_decode($this->mail_template->content), $tabVars);
                    $exp_name  = strtr(utf8_decode($this->mail_template->exp_name), $tabVars);

                    $this->email = $this->loadLib('email');
                    $this->email->setFrom($this->mail_template->exp_email, $exp_name);

                    $this->email->setSubject(stripslashes($sujetMail));
                    $this->email->setHTMLBody(stripslashes($texteMail));

                    if ($this->Config['env'] === 'prod') {
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mail_template->id_textemail, $destinataire, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mail_template->nmp_secure, $this->mail_template->id_nmp, $this->mail_template->nmp_unique, $this->mail_template->mode);
                    } else {
                        $this->email->addRecipient(trim($destinataire));
                        Mailer::send($this->email, $this->mails_filer, $this->mail_template->id_textemail);
                    }
                } else {// si limite depassé on rejet l'offre de parrainage
                    $parrains_filleuls->get($pf['id_parrain_filleul'], 'id_parrain_filleul');
                    $parrains_filleuls->etat = 2;
                    $parrains_filleuls->update();
                }
            }
        }
    }

    // Toutes les 5 minutes (cron en place)	le 27/01/2015
    public function _remboursement_preteurs_auto()
    {
        if (true === $this->startCron('remboursements auto', 5)) {
            /** @var \projects $projects */
            $projects = $this->loadData('projects');

            $echeanciers_emprunteur  = $this->loadData('echeanciers_emprunteur');
            $echeanciers             = $this->loadData('echeanciers');
            $companies               = $this->loadData('companies');
            $transactions            = $this->loadData('transactions');
            $lenders                 = $this->loadData('lenders_accounts');
            $projects_status_history = $this->loadData('projects_status_history');
            $wallets_lines           = $this->loadData('wallets_lines');
            $bank_unilend            = $this->loadData('bank_unilend');
            $oAccountUnilend         = $this->loadData('platform_account_unilend');

            /** @var \projects_remb_log $oRepaymentLog */
            $oRepaymentLog = $this->loadData('projects_remb_log');

            /** @var \projects_remb $oProjectRepayment */
            $oProjectRepayment = $this->loadData('projects_remb');

            foreach ($oProjectRepayment->getProjectsToRepay(new \DateTime(), 1) as $r) {
                $oRepaymentLog->id_project       = $r['id_project'];
                $oRepaymentLog->ordre            = $r['ordre'];
                $oRepaymentLog->debut            = date('Y-m-d H:i:s');
                $oRepaymentLog->fin              = '0000-00-00 00:00:00';
                $oRepaymentLog->montant_remb_net = 0;
                $oRepaymentLog->etat             = 0;
                $oRepaymentLog->nb_pret_remb     = 0;
                $oRepaymentLog->create();

                $dernierStatut     = $projects_status_history->select('id_project = ' . $r['id_project'], 'id_project_status_history DESC', 0, 1);
                $dateDernierStatut = $dernierStatut[0]['added'];
                $timeAdd           = strtotime($dateDernierStatut);
                $day               = date('d', $timeAdd);
                $month             = $this->dates->tableauMois['fr'][date('n', $timeAdd)];
                $year              = date('Y', $timeAdd);
                $Total_rembNet     = 0;
                $lEcheances        = $echeanciers->selectEcheances_a_remb('id_project = ' . $r['id_project'] . ' AND status_emprunteur = 1 AND ordre = ' . $r['ordre'] . ' AND status = 0');

                if ($lEcheances != false) {
                    $Total_etat   = 0;
                    $nb_pret_remb = 0;

                    foreach ($lEcheances as $e) {
                        if ($transactions->get($e['id_echeancier'], 'id_echeancier') == false) {
                            $rembNet = $e['rembNet'];
                            $etat    = $e['etat'];

                            $Total_rembNet += $rembNet;
                            $Total_etat += $etat;
                            $nb_pret_remb = ($nb_pret_remb + 1);

                            $lenders->get($e['id_lender'], 'id_lender_account');

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
                    $transactions->create();

                    $bank_unilend->id_transaction         = $transactions->id_transaction;
                    $bank_unilend->id_project             = $r['id_project'];
                    $bank_unilend->montant                = '-' . $Total_rembNet * 100;
                    $bank_unilend->etat                   = $Total_etat * 100;
                    $bank_unilend->type                   = 2; // remb unilend
                    $bank_unilend->id_echeance_emprunteur = $echeanciers_emprunteur->id_echeancier_emprunteur;
                    $bank_unilend->status                 = 1;
                    $bank_unilend->create();

                    $oAccountUnilend->addDueDateCommssion($echeanciers_emprunteur->id_echeancier_emprunteur);

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
                        'lien_fb'         => $this->like_fb,
                        'lien_tw'         => $this->twitter,
                        'montantRemb'     => $Total_rembNet
                    );

                    $tabVars   = $this->tnmp->constructionVariablesServeur($varMail);
                    $sujetMail = strtr(utf8_decode($this->mail_template->subject), $tabVars);
                    $texteMail = strtr(utf8_decode($this->mail_template->content), $tabVars);
                    $exp_name  = strtr(utf8_decode($this->mail_template->exp_name), $tabVars);

                    $this->email = $this->loadLib('email');
                    $this->email->setFrom($this->mail_template->exp_email, $exp_name);
                    $this->email->setSubject(stripslashes($sujetMail));
                    $this->email->setHTMLBody(stripslashes($texteMail));

                    if ($this->Config['env'] === 'prod') {
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mail_template->id_textemail, trim($companies->email_facture), $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mail_template->nmp_secure, $this->mail_template->id_nmp, $this->mail_template->nmp_unique, $this->mail_template->mode);
                    } else {
                        $this->email->addRecipient(trim($companies->email_facture));
                        Mailer::send($this->email, $this->mails_filer, $this->mail_template->id_textemail);
                    }

                    $oInvoiceCounter            = $this->loadData('compteur_factures');
                    $oLenderRepaymentSchedule   = $this->loadData('echeanciers');
                    $oBorrowerRepaymentSchedule = $this->loadData('echeanciers_emprunteur');
                    $oInvoice                   = $this->loadData('factures');

                    $this->settings->get('Commission remboursement', 'type');
                    $fCommissionRate = $this->settings->value;

                    $aLenderRepayment = $oLenderRepaymentSchedule->select('id_project = ' . $projects->id_project . ' AND ordre = ' . $r['ordre'], '', 0, 1);

                    if ($oBorrowerRepaymentSchedule->get($projects->id_project, 'ordre = ' . $r['ordre'] . '  AND id_project')) {
                        $oInvoice->num_facture     = 'FR-E' . date('Ymd', strtotime($aLenderRepayment[0]['date_echeance_reel'])) . str_pad($oInvoiceCounter->compteurJournalier($projects->id_project, $aLenderRepayment[0]['date_echeance_reel']), 5, '0', STR_PAD_LEFT);
                        $oInvoice->date            = $aLenderRepayment[0]['date_echeance_reel'];
                        $oInvoice->id_company      = $companies->id_company;
                        $oInvoice->id_project      = $projects->id_project;
                        $oInvoice->ordre           = $r['ordre'];
                        $oInvoice->type_commission = \factures::TYPE_COMMISSION_REMBOURSEMENT;
                        $oInvoice->commission      = $fCommissionRate * 100;
                        $oInvoice->montant_ht      = $oBorrowerRepaymentSchedule->commission;
                        $oInvoice->tva             = $oBorrowerRepaymentSchedule->tva;
                        $oInvoice->montant_ttc     = $oBorrowerRepaymentSchedule->commission + $oBorrowerRepaymentSchedule->tva;
                        $oInvoice->create();
                    }

                    $lesRembEmprun = $bank_unilend->select('type = 1 AND status = 0 AND id_project = ' . $r['id_project']);

                    foreach ($lesRembEmprun as $leR) {
                        $bank_unilend->get($leR['id_unilend'], 'id_unilend');
                        $bank_unilend->status = 1;
                        $bank_unilend->update();
                    }

                    $oProjectRepayment->get($r['id_project_remb'], 'id_project_remb');
                    $oProjectRepayment->date_remb_preteurs_reel = date('Y-m-d H:i:s');
                    $oProjectRepayment->status                  = \projects_remb::STATUS_REFUNDED;
                    $oProjectRepayment->update();

                    $oRepaymentLog->fin              = date('Y-m-d H:i:s');
                    $oRepaymentLog->montant_remb_net = $Total_rembNet * 100;
                    $oRepaymentLog->etat             = $Total_etat * 100;
                    $oRepaymentLog->nb_pret_remb     = $nb_pret_remb;
                    $oRepaymentLog->update();
                }
            }

            $this->stopCron();
        }
    }

    // Passe toutes les 5 minutes la nuit de 3h à 4h
    // copie données table -> enregistrement table backup -> suppression données table
    public function _stabilisation_mails()
    {
        if ($this->startCron('stabilisationMail', 10)) {
            $iStartTime     = time();
            $iRetentionDays = 30;
            $iLimit         = 2000;
            $sMinimumDate   = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - $iRetentionDays, date('Y')));

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

    private function deleteOldFichiers()
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

    private function zippage($id_project)
    {
        $projects        = $this->loadData('projects');
        $companies       = $this->loadData('companies');
        $oAttachment     = $this->loadData('attachment');
        $oAttachmentType = $this->loadData('attachment_type');

        $projects->get($id_project, 'id_project');
        $companies->get($projects->id_company, 'id_company');

        $sPathNoZip = $this->path . 'protected/sftp_groupama_nozip/';
        $sPath      = $this->path . 'protected/sftp_groupama/';

        if (!is_dir($sPathNoZip . $companies->siren)) {
            mkdir($sPathNoZip . $companies->siren);
        }

        /** @var attachment_helper $oAttachmentHelper */
        $oAttachmentHelper = $this->loadLib('attachment_helper', array($oAttachment, $oAttachmentType, $this->path));
        $aAttachments      = $projects->getAttachments();

        $this->copyAttachment($oAttachmentHelper, $aAttachments, attachment_type::CNI_PASSPORTE_DIRIGEANT, 'CNI-#', $companies->siren, $sPathNoZip);
        $this->copyAttachment($oAttachmentHelper, $aAttachments, attachment_type::CNI_PASSPORTE_VERSO, 'CNI-VERSO-#', $companies->siren, $sPathNoZip);

        $this->copyAttachment($oAttachmentHelper, $aAttachments, attachment_type::KBIS, 'KBIS-#', $companies->siren, $sPathNoZip);

        $this->copyAttachment($oAttachmentHelper, $aAttachments, attachment_type::CNI_BENEFICIAIRE_EFFECTIF_1, 'CNI-25-1-#', $companies->siren, $sPathNoZip);
        $this->copyAttachment($oAttachmentHelper, $aAttachments, attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_1, 'CNI-25-1-VERSO-#', $companies->siren, $sPathNoZip);

        $this->copyAttachment($oAttachmentHelper, $aAttachments, attachment_type::CNI_BENEFICIAIRE_EFFECTIF_2, 'CNI-25-2-#', $companies->siren, $sPathNoZip);
        $this->copyAttachment($oAttachmentHelper, $aAttachments, attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_2, 'CNI-25-2-VERSO-#', $companies->siren, $sPathNoZip);

        $this->copyAttachment($oAttachmentHelper, $aAttachments, attachment_type::CNI_BENEFICIAIRE_EFFECTIF_3, 'CNI-25-3-#', $companies->siren, $sPathNoZip);
        $this->copyAttachment($oAttachmentHelper, $aAttachments, attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_3, 'CNI-25-3-VERSO-#', $companies->siren, $sPathNoZip);

        $zip = new ZipArchive();
        if (is_dir($sPathNoZip . $companies->siren)) {
            if ($zip->open($sPath . $companies->siren . '.zip', ZipArchive::CREATE) == true) {
                $fichiers = scandir($sPathNoZip . $companies->siren);
                unset($fichiers[0], $fichiers[1]);
                foreach ($fichiers as $f) {
                    $zip->addFile($sPathNoZip . $companies->siren . '/' . $f, $f);
                }
                $zip->close();
            }
        }

        $this->deleteOldFichiers();
    }

    private function copyAttachment($oAttachmentHelper, $aAttachments, $sAttachmentType, $sPrefix, $sSiren, $sPathNoZip)
    {
        if (! isset($aAttachments[$sAttachmentType]['path'])) {
            return;
        }

        $sFromPath =  $oAttachmentHelper->getFullPath(attachment::PROJECT, $sAttachmentType) . $aAttachments[$sAttachmentType]['path'];
        $aPathInfo = pathinfo($sFromPath);
        $sExtension = isset($aPathInfo['extension']) ? $aPathInfo['extension'] : '';
        $sNewName = $sPrefix . $sSiren . '.' . $sExtension;

        copy($sFromPath, $sPathNoZip . $sSiren . '/' . $sNewName);
    }

    /**
     * Send reminder email for project submissions
     */
    public function _relance_completude_emprunteurs()
    {
        if ($this->startCron('relance completude emprunteurs', 5)) {

            $this->stopCron();
        }
    }

    public function _projet_process_fast_completude()
    {
        if ($this->startCron('projet process fast completude', 5)) {
            $this->loadData('projects_status'); // Loaded for class constants
            $this->loadData('users'); // Loaded for class constants

            /** @var \projects $oProject */
            $oProject = $this->loadData('projects');
            /** @var \Unilend\Service\ProjectManager $oProjectManager */
            $oProjectManager = $this->get('unilend.service.project_manager');

            foreach ($oProject->getFastProcessStep3() as $iProjectId) {
                $oProject->get($iProjectId, 'id_project');
                $oProjectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::A_TRAITER, $oProject);
            }

            $this->stopCron();
        }
    }

    public function _emprunteur_impaye_avant_echeance()
    {
        if ($this->startCron('emprunteur impaye avant echeance', 5)) {
            $oProjects = $this->loadData('projects');
            $aProjects = $oProjects->getProblematicProjectsWithUpcomingRepayment();

            if (false === empty($aProjects)) {
                $oClients               = $this->loadData('clients');
                $oCompanies             = $this->loadData('companies');
                $oEcheanciers           = $this->loadData('echeanciers');
                $oEcheanciersEmprunteur = $this->loadData('echeanciers_emprunteur');
                $oLoans                 = $this->loadData('loans');
                $oMailTemplate          = $this->loadData('mail_templates');
                $oSettings              = $this->loadData('settings');

                $oMailTemplate->get('emprunteur-projet-statut-probleme-j-x-avant-prochaine-echeance', 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND lang = "' . $this->language . '" AND type');

                $oSettings->get('Virement - BIC', 'type');
                $sBIC = $oSettings->value;

                $oSettings->get('Virement - IBAN', 'type');
                $sIBAN = $oSettings->value;

                $oSettings->get('Téléphone emprunteur', 'type');
                $sBorrowerPhoneNumber = $oSettings->value;

                $oSettings->get('Adresse emprunteur', 'type');
                $sBorrowerEmail = $oSettings->value;

                $aCommonReplacements = array(
                    'url'              => $this->furl,
                    'surl'             => $this->surl,
                    'lien_fb'          => $this->like_fb,
                    'lien_tw'          => $this->twitter,
                    'bic_sfpmei'       => $sBIC,
                    'iban_sfpmei'      => $sIBAN,
                    'tel_emprunteur'   => $sBorrowerPhoneNumber,
                    'email_emprunteur' => $sBorrowerEmail,
                    'annee'            => date('Y')
                );

                foreach ($aProjects as $aProject) {
                    $oProjects->get($aProject['id_project']);
                    $oCompanies->get($oProjects->id_company);
                    $oClients->get($oCompanies->id_client_owner);

                    $aNextRepayment = $oEcheanciersEmprunteur->select('id_project = ' . $oProjects->id_project . ' AND date_echeance_emprunteur > DATE(NOW())', 'date_echeance_emprunteur ASC', 0, 1);

                    $aReplacements = $aCommonReplacements + array(
                            'sujet'                              => htmlentities($oMailTemplate->subject, null, 'UTF-8'),
                            'entreprise'                         => htmlentities($oCompanies->name, null, 'UTF-8'),
                            'civilite_e'                         => $oClients->civilite,
                            'prenom_e'                           => htmlentities($oClients->prenom, null, 'UTF-8'),
                            'nom_e'                              => htmlentities($oClients->nom, null, 'UTF-8'),
                            'mensualite_e'                       => $this->ficelle->formatNumber(($aNextRepayment[0]['montant'] + $aNextRepayment[0]['commission'] + $aNextRepayment[0]['tva']) / 100),
                            'num_dossier'                        => $oProjects->id_project,
                            'nb_preteurs'                        => $oLoans->getNbPreteurs($oProjects->id_project),
                            'CRD'                                => $this->ficelle->formatNumber($oEcheanciers->sum('id_project = ' . $oProjects->id_project . ' AND status = 0', 'capital')),
                            'date_prochaine_echeance_emprunteur' => $this->dates->formatDate($aNextRepayment[0]['date_echeance_emprunteur'], 'd/m/Y'), // @todo Intl
                        );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($oMailTemplate->type, $this->language, $aReplacements);
                    $message->setTo(trim($oClients->email));
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                }
            }
            $this->stopCron();
        }
    }

    /**
     * Function to delete after tests salesforce
     * @param string $sType name of treatment (preteurs, emprunteurs, projects or companies)
     */
    public function _sendDataloader()
    {
        $sType                = $this->params[0];
        $iTimeStartDataloader = microtime(true);
        //TODO a passer en crontab
        exec('java -cp ' . $this->Config['dataloader_path'][$this->Config['env']] . 'dataloader-26.0.0-uber.jar -Dsalesforce.config.dir=' . $this->Config['path'][$this->Config['env']] . 'dataloader/conf/ com.salesforce.dataloader.process.ProcessRunner process.name=' . escapeshellarg($sType), $aReturnDataloader, $sReturn);

        $iTimeEndDataloader = microtime(true) - $iTimeStartDataloader;
        $this->oLogger->info('Send to dataloader type ' . $sType . ' in ' . round($iTimeEndDataloader, 2),array('class' => __CLASS__, 'function' => __FUNCTION__));
    }

    public function _greenPointValidation()
    {
        if (true === $this->startCron('green_point_attachment_validation', 10)) {
            /** @var \clients $oClients */
            $oClients = $this->loadData('clients');

            /** @var \greenpoint_attachment $oGreenPointAttachment */
            $oGreenPointAttachment = $this->loadData('greenpoint_attachment');

            /** @var \greenpoint_kyc $oGreenPointKyc */
            $oGreenPointKyc = $this->loadData('greenpoint_kyc');

            $bDebug = true;
            if ($bDebug) {
                $this->oLogger->info('************************************* Begin GreenPoint Validation *************************************', array('class' => __CLASS__, 'function' => __FUNCTION__));
            }
            $aStatusToCheck = array(
                \clients_status::TO_BE_CHECKED,
                \clients_status::COMPLETENESS_REPLY,
                \clients_status::MODIFICATION
            );

            $aQueryID        = array();
            $aClientsToCheck = $oClients->selectLendersByLastStatus($aStatusToCheck);

            if (false === empty($aClientsToCheck)) {
                /** @var \lenders_accounts $oLendersAccount */
                $oLendersAccount = $this->loadData('lenders_accounts');

                /** @var greenPoint $oGreenPoint */
                $oGreenPoint = new greenPoint();

                /** @var \attachment $oAttachment */
                $oAttachment = $this->loadData('attachment');

                /** @var \attachment_type $oAttachmentType */
                $oAttachmentType = $this->loadData('attachment_type');

                /** @var \attachment_helper $oAttachmentHelper */
                $oAttachmentHelper = $this->loadLib('attachment_helper', array($oAttachment, $oAttachmentType, $this->path));

                foreach ($aClientsToCheck as $iClientId => $aClient) {
                    $aAttachments = $oLendersAccount->getAttachments($aClient['id_lender_account']);

                    /** @var array $aAttachmentsToRevalidate */
                    $aAttachmentsToRevalidate = array();

                    if (false === empty($aAttachments)) {
                        $aError = array();
                        foreach ($aAttachments as $iAttachmentTypeId => $aAttachment) {
                            if ($oGreenPointAttachment->get($aAttachment['id'], 'id_attachment') && 0 == $oGreenPointAttachment->revalidate) {
                                continue;
                            } elseif (1 == $oGreenPointAttachment->revalidate) {
                                $aAttachmentsToRevalidate[$iAttachmentTypeId] = $oGreenPointAttachment->id_greenpoint_attachment;
                            }
                            $sAttachmentPath = $oAttachmentHelper->getFullPath($aAttachment['type_owner'], $aAttachment['id_type']) . $aAttachment['path'];
                            $sFullPath       = realpath($sAttachmentPath);

                            if (false == $sFullPath) {
                                if ($bDebug) {
                                    $this->oLogger->error('Attachment not found - ID=' . $aAttachment['id'], array('class' => __CLASS__, 'function' => __FUNCTION__));
                                }
                                continue;
                            }
                            try {
                                switch ($iAttachmentTypeId) {
                                    case \attachment_type::CNI_PASSPORTE:
                                    case \attachment_type::CNI_PASSPORTE_VERSO:
                                    case \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT:
                                    case \attachment_type::CNI_PASSPORTE_DIRIGEANT:
                                        $aData            = $this->getGreenPointData($iClientId, $aAttachment['id'], $sFullPath, $aClient, 'idcontrol');
                                        $iQRID            = $oGreenPoint->idControl($aData, false);
                                        $aQueryID[$iQRID] = $iAttachmentTypeId;
                                        break;
                                    case \attachment_type::RIB:
                                        $aData            = $this->getGreenPointData($iClientId, $aAttachment['id'], $sFullPath, $aClient, 'ibanflash');
                                        $iQRID            = $oGreenPoint->ibanFlash($aData, false);
                                        $aQueryID[$iQRID] = $iAttachmentTypeId;
                                        break;
                                    case \attachment_type::JUSTIFICATIF_DOMICILE:
                                    case \attachment_type::ATTESTATION_HEBERGEMENT_TIERS:
                                        $aData            = $this->getGreenPointData($iClientId, $aAttachment['id'], $sFullPath, $aClient, 'addresscontrol');
                                        $iQRID            = $oGreenPoint->addressControl($aData, false);
                                        $aQueryID[$iQRID] = $iAttachmentTypeId;
                                        break;
                                }
                            } catch (\Exception $oException) {
                                $aError[$aAttachment['id']][$iAttachmentTypeId] = array('iErrorCode' => $oException->getCode(), 'sErrorMessage' => $oException->getMessage());
                                unset($oException);
                            }
                        }
                        if ($bDebug && false === empty($aError)) {
                            $this->oLogger->error('CLIENT_ID=' . $iClientId . ' - Catched Exceptions : ' . var_export($aError, 1), __METHOD__);
                        }
                        if (false === empty($aQueryID) && is_array($aQueryID)) {
                            $aResult = $oGreenPoint->sendRequests();
                            if ($bDebug) {
                                $this->oLogger->info('CLIENT_ID=' . $iClientId . ' - Request Details : ' . var_export($aResult, 1), array('class' => __CLASS__, 'function' => __FUNCTION__));
                            }
                            $this->processGreenPointResponse($iClientId, $aResult, $aQueryID, $aAttachmentsToRevalidate);
                            unset($aResult, $aQueryID);
                            greenPointStatus::addCustomer($iClientId, $oGreenPoint, $oGreenPointKyc);
                        }
                    }
                }
            }
            if ($bDebug) {
                $this->oLogger->info('************************************* End GreenPoint Validation *************************************', array('class' => __CLASS__, 'function' => __FUNCTION__));
            }
            $this->stopCron();
        }
    }

    /**
     * @param int $iClientId
     * @param int $iAttachmentId
     * @param string $sPath
     * @param array $aClient
     * @param string $sType
     * @return array
     */
    private function getGreenPointData($iClientId, $iAttachmentId, $sPath, array $aClient, $sType)
    {
        $aData = array(
            'files'    => '@' . $sPath,
            'dossier'  => $iClientId,
            'document' => $iAttachmentId,
            'detail'   => 1,
            'nom'      => $this->getFamilyNames($aClient['nom'], $aClient['nom_usage']),
            'prenom'   => $aClient['prenom']
        );

        switch ($sType) {
            case 'idcontrol':
                $this->addIdControlData($aData, $aClient);
                return $aData;
            case 'ibanflash':
                $this->addIbanData($aData, $aClient);
                return $aData;
            case 'addresscontrol':
                $this->addAddressData($aData, $aClient);
                return $aData;
            default:
                return $aData;
        }
    }

    /**
     * @param string $sFamilyName
     * @param string $sUseName
     * @return string
     */
    private function getFamilyNames($sFamilyName, $sUseName)
    {
        $sAllowedNames = $sFamilyName;
        if (false === empty($sUseName)) {
            $sAllowedNames .= '|' . $sUseName;
        }
        return $sAllowedNames;
    }

    /**
     * @param array $aData
     * @param array $aClient
     */
    private function addIdControlData(array &$aData, array $aClient)
    {
        $aData['date_naissance'] = $aClient['naissance'];
    }

    /**
     * @param array $aData
     * @param array $aClient
     */
    private function addIbanData(array &$aData, array $aClient)
    {
        $aData['iban'] = $aClient['iban'];
        $aData['bic']  = $aClient['bic'];
    }

    /**
     * @param array $aData
     * @param array $aClient
     */
    private function addAddressData(array &$aData, array $aClient)
    {
        $aData['adresse']     = $this->getFullAddress($aClient['adresse1'], $aClient['adresse2'], $aClient['adresse3']);
        $aData['code_postal'] = $aClient['cp'];
        $aData['ville']       = $aClient['ville'];
        $aData['pays']        = strtoupper($aClient['fr']);
    }

    /**
     * @param string $sAddress1
     * @param string $sAddress2
     * @param string $sAddress3
     * @return string
     */
    private function getFullAddress($sAddress1, $sAddress2, $sAddress3)
    {
        $sFullAddress = $sAddress1;
        if (false === empty($sAddress2)) {
            $sFullAddress .= ' ' . $sAddress2;
        }
        if (false === empty($sAddress3)) {
            $sFullAddress .= ' ' . $sAddress3;
        }
        return $sFullAddress;
    }

    /**
     * @param int $iClientId
     * @param array $aResponseDetail
     * @param array $aResponseKeys
     * @param array $aExistingAttachment
     */
    private function processGreenPointResponse($iClientId, array $aResponseDetail, array $aResponseKeys, array $aExistingAttachment)
    {
        /** @var \greenpoint_attachment $oGreenPointAttachment */
        $oGreenPointAttachment = $this->loadData('greenpoint_attachment');

        /** @var \greenpoint_attachment_detail $oGreenPointAttachmentDetail */
        $oGreenPointAttachmentDetail = $this->loadData('greenpoint_attachment_detail');

        foreach ($aResponseKeys as $iQRID => $iAttachmentTypeId) {
            if (false === isset($aResponseDetail[$iQRID])) {
                continue;
            }

            if (isset($aExistingAttachment[$iAttachmentTypeId]) && $oGreenPointAttachment->get($aExistingAttachment[$iAttachmentTypeId], 'id_greenpoint_attachment')) {
                $bUpdate = true;
            } else {
                $bUpdate = false;
            }
            $oGreenPointAttachment->control_level = 1;
            $oGreenPointAttachment->revalidate    = 0;
            $oGreenPointAttachment->final_status  = 0;
            $iAttachmentId                        = $aResponseDetail[$iQRID]['REQUEST_PARAMS']['document'];
            $aResponse                            = json_decode($aResponseDetail[$iQRID]['RESPONSE'], true);

            if (isset($aResponse['resource']) && is_array($aResponse['resource'])) {
                $aGreenPointData = greenPointStatus::getGreenPointData($aResponse['resource'], $iAttachmentTypeId, $iAttachmentId, $iClientId, $aResponse['code']);
            } else {
                $aGreenPointData = greenPointStatus::getGreenPointData(array(), $iAttachmentTypeId, $iAttachmentId, $iClientId, $aResponse['code']);
            }

            foreach ($aGreenPointData['greenpoint_attachment'] as $sKey => $mValue) {
                if (false === is_null($mValue)) {
                    $oGreenPointAttachment->$sKey = $mValue;
                }
            }

            if ($bUpdate) {
                $oGreenPointAttachment->update();
                $oGreenPointAttachmentDetail->get($oGreenPointAttachment->id_greenpoint_attachment, 'id_greenpoint_attachment');
            } else {
                $oGreenPointAttachment->create();
                $oGreenPointAttachmentDetail->id_greenpoint_attachment = $oGreenPointAttachment->id_greenpoint_attachment;
            }

            foreach ($aGreenPointData['greenpoint_attachment_detail'] as $sKey => $mValue) {
                if (false === is_null($mValue)) {
                    $oGreenPointAttachmentDetail->$sKey = $mValue;
                }
            }

            if ($bUpdate) {
                $oGreenPointAttachmentDetail->update();
            } else {
                $oGreenPointAttachmentDetail->create();
            }
            $oGreenPointAttachment->unsetData();
            $oGreenPointAttachmentDetail->unsetData();
        }
    }
}
