<?php

use Unilend\librairies\CacheKeys;
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
