<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 09/02/2016
 * Time: 09:38
 */

namespace Unilend\Service;

use Unilend\core\Loader;
use Unilend\librairies\ULogger;

class ProjectManager
{
    /** @var AutoBidManager */
    private $oAutoBidManager;
    /** @var \bids */
    private $oBid;
    /** @var \bids_logs */
    private $oBidLog;
    /** @var \mails_text */
    private $oMailText;
    /** @var \settings */
    private $oSettings;
    /** @var \mails_filer */
    private $oMailFiler;
    /** @var \email */
    private $oEmail;
    /** @var \ficelle */
    private $oFicelle;
    private $aConfig;
    /** @var \dates */
    private $oDates;
    /** @var  \companies */
    private $oCompanies;
    /** @var  \clients */
    private $oBorrower;
    /** @var  \tnmp */
    private $oTNMP;
    /** @var  \autobid_queue */
    private $oAutoBidQueue;
    /** @var  \autobid */
    private $oAutoBid;
    /** @var  \projects_status */
    private $oProjectStatus;
    /** @var  \projects_status_history */
    private $oProjectStatusHistory;
    /** @var  \projects */
    private $oProject;
    /** @var  ULogger */
    private $oLogger;
    /** @var  BidManager */
    private $oBidManager;
    /** @var  \lenders_accounts */
    private $oLenderAccount;
    /** @var  \loans */
    private $oLoan;
    /** @var  \accepted_bids */
    private $oAcceptedBid;
    /** @var  LoanManager */
    private $oLoanManager;
    /** @var \echeanciers */
    private $oSchedule;
    /** @var \clients_adresses */
    private $oClientAdresse;
    /** @var \jours_ouvres */
    private $oWorkingDay;
    /** @var \clients */
    private $oClient;

    public function __construct()
    {
        $this->aConfig = Loader::loadConfig();

        $this->oAutoBidManager = Loader::loadService('AutoBidManager');
        $this->oBidManager     = Loader::loadService('BidManager');
        $this->oLoanManager    = Loader::loadService('LoanManager');

        $this->oBid                  = Loader::loadData('bids');
        $this->oBidLog               = Loader::loadData('bids_logs');
        $this->oMailText             = Loader::loadData('mails_text');
        $this->oSettings             = Loader::loadData('settings');
        $this->oMailFiler            = Loader::loadData('mails_filer');
        $this->oCompanies            = Loader::loadData('companies');
        $this->oBorrower             = Loader::loadData('clients');
        $this->oAutoBidQueue         = Loader::loadData('autobid_queue');
        $this->oAutoBid              = Loader::loadData('autobid');
        $this->oProject              = Loader::loadData('projects');
        $this->oProjectStatus        = Loader::loadData('projects_status');
        $this->oProjectStatusHistory = Loader::loadData('projects_last_status_history');
        $this->oNMP                  = Loader::loadData('nmp');
        $this->oNMPDesabo            = Loader::loadData('nmp_desabo');
        $this->oLenderAccount        = Loader::loadData('lenders_accounts');
        $this->oLoan                 = Loader::loadData('loans');
        $this->oAcceptedBid          = Loader::loadData('accepted_bids');
        $this->oSchedule             = Loader::loadData('echeanciers');
        $this->oClientAdresse        = Loader::loadData('clients_adresses');
        $this->oClient               = Loader::loadData('clients');

        $this->oTNMP       = Loader::loadLib('tnmp', array($this->oNMP, $this->oNMPDesabo, $this->aConfig['env']));
        $this->oEmail      = Loader::loadLib('email');
        $this->oFicelle    = Loader::loadLib('ficelle');
        $this->oDates      = Loader::loadLib('dates');
        $this->oWorkingDay = Loader::loadLib('jours_ouvres');

        $this->sLanguage = 'fr';
    }

    /**
     * @param ULogger $oLogger
     */
    public function setLogger(ULogger $oLogger)
    {
        $this->oLogger = $oLogger;
    }

    public function checkBids($iProjectId)
    {
        if ($this->oProject->get($iProjectId)) {
            $aLogContext      = array();
            $bBidsLogs        = false;
            $nb_bids_ko       = 0;
            $iBidsAccumulated = 0;
            $iBorrowAmount    = $this->oProject->amount;
            $BidsNbPending    = $this->oBid->counter('id_project = ' . $this->oProject->id_project . ' AND status = 0');
            $iBidsNbTotal     = $this->oBid->counter('id_project = ' . $this->oProject->id_project);
            $iBidTotal        = $this->oBid->getSoldeBid($this->oProject->id_project);

            $this->oBidLog->debut      = date('Y-m-d H:i:s');
            $this->oBidLog->id_project = $this->oProject->id_project;

            if ($iBidTotal >= $iBorrowAmount) {
                foreach ($this->oBid->select('id_project = ' . $this->oProject->id_project . ' AND status = 0', 'rate ASC, added ASC') as $aBid) {
                    if ($iBidsAccumulated < $iBorrowAmount) {
                        $iBidsAccumulated += ($aBid['amount'] / 100);
                    } else { // Les bid qui depassent on leurs redonne leur argent et on met en ko
                        $bBidsLogs = true;
                        $this->oBid->get($aBid['id_bid']);

                        if (0 == $this->oBid->id_autobid) { // non-auto-bid
                            $this->oBidManager->reject($aBid['id_bid']);
                        } else {
                            $this->oBid->status = \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY;
                        }

                        $nb_bids_ko++;
                        $this->oBid->update();
                    }

                    if (1 != $this->oBid->checked) {
                        $this->oBid->checked = 1;
                        $this->oBid->update();
                    }
                }

                $aLogContext['Project ID']    = $this->oProject->id_project;
                $aLogContext['Balance']       = $iBidTotal;
                $aLogContext['Rejected bids'] = $nb_bids_ko;
            }

            if ($bBidsLogs == true) {
                $this->oBidLog->unsetData();
                $this->oBidLog->nb_bids_encours = $BidsNbPending;
                $this->oBidLog->nb_bids_ko      = $nb_bids_ko;
                $this->oBidLog->total_bids      = $iBidsNbTotal;
                $this->oBidLog->total_bids_ko   = $this->oBid->counter('id_project = ' . $this->oProject->id_project . ' AND status = 2');
                $this->oBidLog->fin             = date('Y-m-d H:i:s');
                $this->oBidLog->create();
            }
            if ($this->oLogger instanceof ULogger) {
                $this->oLogger->addRecord(ULogger::INFO, 'Project ID: ' . $this->oProject->id_project, $aLogContext);
            }
        }
    }

    public function sendMailFonded($iProjectId)
    {
        if ($this->oProject->get($iProjectId)) {
            $iBidTotal = $this->oBid->getSoldeBid($this->oProject->id_project);

            if ($iBidTotal >= $this->oProject->amount && $this->oProject->status_solde == 0) {
                // EMAIL EMPRUNTEUR FUNDE //
                if ($this->oLogger instanceof ULogger) {
                    $this->oLogger->addRecord(ULogger::INFO, 'Project funded - send email to borrower', array('Project ID' => $this->oProject->id_project));
                }
                // Mise a jour du statut pour envoyer qu'une seule fois le mail a l'emprunteur
                $this->oProject->status_solde = 1;
                $this->oProject->update();

                $this->oSettings->get('Facebook', 'type');
                $lien_fb = $this->oSettings->value;

                $this->oSettings->get('Twitter', 'type');
                $lien_tw = $this->oSettings->value;

                $this->oSettings->get('Heure fin periode funding', 'type');
                $iFinFunding = $this->oSettings->value;

                $this->oCompanies->get($this->oProject->id_company, 'id_company');
                $this->oBorrower->get($this->oCompanies->id_client_owner, 'id_client');

                $tab_date_retrait = explode(' ', $this->oProject->date_retrait_full);
                $tab_date_retrait = explode(':', $tab_date_retrait[1]);
                $heure_retrait    = $tab_date_retrait[0] . ':' . $tab_date_retrait[1];

                if ($heure_retrait == '00:00') {
                    $heure_retrait = $iFinFunding;
                }

                $inter = $this->oDates->intervalDates(date('Y-m-d H:i:s'), $this->oProject->date_retrait . ' ' . $heure_retrait . ':00');

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

                // Taux moyen pondéré
                $montantHaut = 0;
                $montantBas  = 0;
                foreach ($this->oBid->select('id_project = ' . $this->oProject->id_project . ' AND status = 0') as $b) {
                    $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                    $montantBas += ($b['amount'] / 100);
                }
                $taux_moyen = ($montantHaut / $montantBas);
                $taux_moyen = $this->oFicelle->formatNumber($taux_moyen);


                $sSUrl = $this->aConfig['static_url'][$this->aConfig['env']];
                $sLUrl = $this->aConfig['url'][$this->aConfig['env']]['default'] . ($this->aConfig['multilanguage']['enabled'] ? '/' . $this->sLanguage : '');

                // Pas de mail si le compte est desactivé
                if ($this->oBorrower->status == 1) {
                    //*** ENVOI DU MAIL FUNDE EMPRUNTEUR ***//
                    $this->oMailText->get('emprunteur-dossier-funde', 'lang = "' . $this->sLanguage . '" AND type');

                    $varMail = array(
                        'surl' => $sSUrl,
                        'url' => $sLUrl,
                        'prenom_e' => utf8_decode($this->oBorrower->prenom),
                        'taux_moyen' => $taux_moyen,
                        'temps_restant' => $tempsRest,
                        'projet' => $this->oProject->title,
                        'lien_fb' => $lien_fb,
                        'lien_tw' => $lien_tw
                    );

                    $tabVars = $this->oTNMP->constructionVariablesServeur($varMail);

                    $sujetMail = strtr(utf8_decode($this->oMailText->subject), $tabVars);
                    $texteMail = strtr(utf8_decode($this->oMailText->content), $tabVars);
                    $exp_name  = strtr(utf8_decode($this->oMailText->exp_name), $tabVars);

                    $this->oEmail->setFrom($this->oMailText->exp_email, $exp_name);
                    $this->oEmail->setSubject(stripslashes($sujetMail));
                    $this->oEmail->setHTMLBody(stripslashes($texteMail));

                    if ($this->aConfig['env'] == 'prod') {
                        \Mailer::sendNMP($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail, $this->oBorrower->email, $tabFiler);
                        $this->oTNMP->sendMailNMP($tabFiler, $varMail, $this->oMailText->nmp_secure, $this->oMailText->id_nmp, $this->oMailText->nmp_unique, $this->oMailText->mode);
                    } else {
                        $this->oEmail->addRecipient(trim($this->oBorrower->email));
                        \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
                    }
                }
                //*** ENVOI DU MAIL NOTIFICATION FUNDE 100% ***//

                $this->oSettings->get('Adresse notification projet funde a 100', 'type');
                $destinataire = $this->oSettings->value;

                $nbPeteurs = $this->oBid->getNbPreteurs($this->oProject->id_project);

                $this->oMailText->get('notification-projet-funde-a-100', 'lang = "' . $this->sLanguage . '" AND type');

                $surl         = $sSUrl;
                $url          = $sLUrl;
                $id_projet    = $this->oProject->title;
                $title_projet = utf8_decode($this->oProject->title);
                $nbPeteurs    = $nbPeteurs;
                $tx           = $taux_moyen;
                $periode      = $tempsRest;

                $sujetMail = htmlentities($this->oMailText->subject);
                eval("\$sujetMail = \"$sujetMail\";");

                $texteMail = $this->oMailText->content;
                eval("\$texteMail = \"$texteMail\";");

                $exp_name = $this->oMailText->exp_name;
                eval("\$exp_name = \"$exp_name\";");

                $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
                $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

                $this->oEmail->setFrom($this->oMailText->exp_email, $exp_name);
                $this->oEmail->addRecipient(trim($destinataire));

                $this->oEmail->setSubject('=?UTF-8?B?' . base64_encode(html_entity_decode($sujetMail)) . '?=');
                $this->oEmail->setHTMLBody($texteMail);
                \Mailer::send($this->oEmail, $this->oMailFiler, $this->oMailText->id_textemail);
            }
        }
    }

    /**
     * @param $iProjectId
     *
     * @return bool
     */
    public function autoBid($iProjectId)
    {
        if ($this->oProject->get($iProjectId)) {
            if ($this->oProject->date_fin != '0000-00-00 00:00:00' && time() >= strtotime($this->oProject->date_fin)) {
                return false;
            }
            if ($this->oProjectStatus->getLastStatut($iProjectId)) {
                if ($this->oProjectStatus->status == \projects_status::AUTO_BID) {
                    $this->bidAllAutoBid();
                } else {
                    if ($this->oProjectStatus->status == \projects_status::EN_FUNDING) {
                        $this->refreshAllAutoBidRate();
                    }
                }
            }
        }
    }

    private function bidAllAutoBid()
    {
        $iPeriod      = (int)$this->oProject->period;
        $sEvaluation  = $this->oProject->risk;
        $iCurrentRate = 10;

        $iOffset = 0;
        $iLimit  = 100;
        while (0 !== count($aAutoBidList = $this->oAutoBidQueue->getAutoBids($iPeriod, $sEvaluation, $iCurrentRate, $iOffset, $iLimit))) {
            $iOffset += $iLimit;

            foreach ($aAutoBidList as $aAutoBidSetting) {
                $this->oAutoBid->get($aAutoBidSetting['id_autobid']);
                $this->oAutoBidManager->bid($aAutoBidSetting['id_autobid'], $this->oProject->id_project, $iCurrentRate);
            }
        }
    }

    private function refreshAllAutoBidRate()
    {
        $this->oSettings->get('Auto-bid step', 'type');
        $fStep = (float)$this->oSettings->value;

        $fCurrentRate = (float)$this->oBid->getProjectMaxRate($this->oProject->id_project) - $fStep;

        while ($aAutoBidList = $this->oBid->getTempRefusedAutoBids($this->oProject->id_project)) {
            foreach ($aAutoBidList as $aAutobid) {
                $this->oAutoBidManager->refreshRateOrReject($aAutobid['id_bid'], $fCurrentRate);
            }
        }
    }

    public function cleanTempRefusedAutobid($iProjectId)
    {
        if ($this->oProject->get($iProjectId)) {
            $aRefusedAutoBid = $this->oBid->getTempRefusedAutoBids($this->oProject->id_project, 1);
            if (false === empty($aRefusedAutoBid)) {
                $this->checkBids($iProjectId);
                $this->refreshAllAutoBidRate();
                $this->cleanTempRefusedAutobid($iProjectId);
            }
        }
    }

    public function buildLoans($iProjectId)
    {
        if (false === $this->oProject->get($iProjectId)) {
            return false;
        }
        $oEndDate = new \DateTime($this->oProject->date_retrait_full);
        if ($this->oProject->date_fin != '0000-00-00 00:00:00') {
            $oEndDate = new \DateTime($this->oProject->date_fin);
        }

        if ($oEndDate <= new \DateTime()) {
            $this->oProject->date_fin = date('Y-m-d H:i:s');
            $this->oProject->update();

            // Solde total obtenue dans l'enchere
            $iBidTotal = $this->oBid->getSoldeBid($iProjectId);

            // Fundé
            if ($iBidTotal >= $this->oProject->amount) {
                $this->cleanTempRefusedAutobid($iProjectId);

                // on passe le projet en fundé
                $this->oProjectStatusHistory->addStatus(\users::USER_ID_CRON, \projects_status::FUNDE, $iProjectId);

                if ($this->oLogger instanceof ULogger) {
                    $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $iProjectId . ' is now changed to status funded.');
                }

                $aBidList    = $this->oBid->select('id_project = ' . $iProjectId . ' AND status = ' . \bids::STATUS_BID_PENDING, 'rate ASC, added ASC');
                $iBidBalance = 0;

                $iBidNbTotal   = count($aBidList);
                $iTreatedBitNb = 0;
                if ($this->oLogger instanceof ULogger) {
                    $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $iProjectId . ' : ' . $iBidNbTotal . ' bids in total.');
                }
                foreach ($aBidList as $aBid) {
                    if ($iBidBalance < $this->oProject->amount) {
                        $iBidBalance += ($aBid['amount'] / 100);
                        // Pour la partie qui depasse le montant de l'emprunt ( ca cest que pour le mec a qui on decoupe son montant)
                        if ($iBidBalance > $this->oProject->amount) {
                            $fAmountToCredit = $iBidBalance - $this->oProject->amount;
                            $this->oBidManager->rejectPartially($aBid['id_bid'], $fAmountToCredit);
                        } else {
                            $this->oBid->get($aBid['id_bid']);
                            $this->oBid->status = \bids::STATUS_BID_ACCEPTED;
                            $this->oBid->update();
                        }
                        if ($this->oLogger instanceof ULogger) {
                            $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $iProjectId . ' : The bid (' . $aBid['id_bid'] . ') status has been updated to 1');
                        }
                    } else {// Pour les encheres qui depassent on rend l'argent
                        // On regarde si on a pas deja un remb pour ce bid
                        $this->oBidManager->reject($aBid['id_bid']);
                    }
                    $iTreatedBitNb++;
                    if ($this->oLogger instanceof ULogger) {
                        $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $iProjectId . ' : ' . $iTreatedBitNb . '/' . $iBidNbTotal . ' bids treated.');
                    }
                }

                // Traite the accepted bid by lender
                $aLenderList = $this->oBid->getLenders($iProjectId, array(\bids::STATUS_BID_ACCEPTED));
                foreach ($aLenderList as $aLender) {
                    $iLenderId   = $aLender['id_lender_account'];
                    $aLenderBids = $this->oBid->select(
                        'id_lender_account = ' . $iLenderId . ' AND id_project = ' . $iProjectId . ' AND status = ' . \bids::STATUS_BID_ACCEPTED,
                        'rate DESC'
                    );

                    if ($this->oLenderAccount->isNaturalPerson($iLenderId)) {
                        $fLoansLenderSum = 0;
                        $fInterests      = 0;
                        $bIFPContract    = true;
                        $aBidIFP         = array();

                        foreach ($aLenderBids as $iIndex => $aBid) {
                            $fBidAmount = $aBid['amount'] / 100;

                            if (true === $bIFPContract && ($fLoansLenderSum + $fBidAmount) <= \loans::IFP_AMOUNT_MAX) {
                                $fInterests += $aBid['rate'] * $fBidAmount;
                                $fLoansLenderSum += $fBidAmount;
                                $aBidIFP[] = array(
                                    'bid_id' => $aBid['id_bid'],
                                    'amount' => $fBidAmount
                                );
                            } else {
                                // Greater than \loans::IFP_AMOUNT_MAX ? create BDC loan, split it if needed.
                                $bIFPContract = false;

                                $fDiff        = $fLoansLenderSum + $fBidAmount - \loans::IFP_AMOUNT_MAX;
                                $aAcceptedBid = array('bid_id' => $aBid['id_bid'], 'amount' => $fDiff);
                                $this->oLoanManager->create($iLenderId, $iProjectId, $fDiff, $aBid['rate'], \loans::TYPE_CONTRACT_BDC, $aAcceptedBid);

                                $fRest = $fBidAmount - $fDiff;
                                if (0 < $fRest) {
                                    $fInterests += $aBid['rate'] * $fRest;
                                    $aBidIFP[] = array(
                                        'bid_id' => $aBid['id_bid'],
                                        'amount' => $fRest
                                    );
                                }
                                $fLoansLenderSum = \loans::IFP_AMOUNT_MAX;
                            }
                        }

                        // Create IFP loan from the grouped bids
                        $this->oLoanManager->create($iLenderId, $iProjectId, $fLoansLenderSum, round($fInterests / $fLoansLenderSum, 2), \loans::TYPE_CONTRACT_IFP, $aBidIFP);
                    } else {
                        foreach ($aLenderBids as $aBid) {
                            $fBidAmount   = $aBid['amount'] / 100;
                            $aAcceptedBid = array('bid_id' => $aBid['id_bid'], 'amount' => $fBidAmount);
                            $this->oLoanManager->create($iLenderId, $iProjectId, $fBidAmount, $aBid['rate'], \loans::TYPE_CONTRACT_BDC, $aAcceptedBid);
                        }
                    }
                }

                $this->createRepaymentSchedule($iProjectId);
                $this->createEcheancesEmprunteur($iProjectId, $oLogger);

                $e                      = $this->loadData('clients');
                $loan                   = $this->loadData('loans');
                $project                = $this->loadData('projects');
                $companie               = $this->loadData('companies');
                $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');

                $project->get($iProjectId, 'id_project');
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
                    'surl' => $surl,
                    'url' => $url,
                    'prenom_e' => $e->prenom,
                    'nom_e' => $companie->name,
                    'mensualite' => $this->ficelle->formatNumber($mensualite),
                    'montant' => $this->ficelle->formatNumber($project->amount, 0),
                    'taux_moyen' => $this->ficelle->formatNumber($taux_moyen),
                    'link_compte_emprunteur' => $this->lurl . '/projects/detail/' . $project->id_project,
                    'link_mandat' => $link_mandat,
                    'link_pouvoir' => $link_pouvoir,
                    'projet' => $projet,
                    'lien_fb' => $this->like_fb,
                    'lien_tw' => $this->twitter
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
                    if ($this->Config['env'] === 'prod') {
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $e->email, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($e->email));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }
                    $oLogger->addRecord(ULogger::INFO, 'project : ' . $iProjectId . ' : email emprunteur-dossier-funde-et-termine sent');
                }

                $this->projects->get($iProjectId, 'id_project');
                $this->companies->get($this->projects->id_company, 'id_company');
                $this->clients->get($this->companies->id_client_owner, 'id_client');

                $this->settings->get('Adresse notification projet funde a 100', 'type');
                $destinataire    = $this->settings->value;
                $montant_collect = $this->oBid->getSoldeBid($this->projects->id_project);

                // si le solde des enchere est supperieur au montant du pret on affiche le montant du pret
                if (($montant_collect / 100) >= $this->projects->amount) {
                    $montant_collect = $this->projects->amount;
                }

                $this->nbPeteurs = $this->oLoan->getNbPreteurs($this->projects->id_project);

                $this->mails_text->get('notification-projet-funde-a-100', 'lang = "' . $this->language . '" AND type');

                $surl         = $this->surl;
                $url          = $this->lurl;
                $id_projet    = $this->projects->id_project;
                $title_projet = utf8_decode($this->projects->title);
                $nbPeteurs    = $this->nbPeteurs;
                $tx           = $taux_moyen;
                $montant_pret = $this->projects->amount;
                $montant      = $montant_collect;
                $periode      = $this->projects->period;

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
                \Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);

                $aLendersIds        = $this->oLoan->getProjectLoansByLender($this->projects->id_project);
                $oClient            = $this->loadData('clients');
                $oLender            = $this->loadData('lenders_accounts');
                $oCompanies         = $this->loadData('companies');
                $oPaymentSchedule   = $this->loadData('echeanciers');
                $this->oAcceptedBid = $this->loadData('accepted_bids');

                $iNbLenders        = count($aLendersIds);
                $iNbTreatedLenders = 0;

                $oLogger->addRecord(ULogger::INFO, 'project : ' . $iNbLenders . ' lenders to send email');

                foreach ($aLendersIds as $aLenderID) {
                    $oLender->get($aLenderID['id_lender'], 'id_lender_account');
                    $oClient->get($oLender->id_client_owner, 'id_client');
                    $oCompanies->get($this->projects->id_company, 'id_company');

                    $bLenderIsNaturalPerson  = $oLender->isNaturalPerson($oLender->id_lender_account);
                    $aLoansOfLender          = $this->oLoan->select('id_project = ' . $this->projects->id_project . ' AND id_lender = ' . $oLender->id_lender_account, '`id_type_contract` DESC');
                    $iNumberOfLoansForLender = count($aLoansOfLender);
                    $iNumberOfAcceptedBids   = $this->oAcceptedBid->getDistinctBidsForLenderAndProject($oLender->id_lender_account, $this->projects->id_project);
                    $sLoansDetails           = '';
                    $sLinkExplication        = '';
                    $sContract               = '';
                    $sStyleTD                = 'border: 1px solid; padding: 5px; text-align: center; text-decoration:none;';

                    if ($bLenderIsNaturalPerson) {
                        $aLoanIFP               = $this->oLoan->select('id_project = ' . $this->projects->id_project . ' AND id_lender = ' . $oLender->id_lender_account . ' AND id_type_contract = ' . \loans::TYPE_CONTRACT_IFP);
                        $iNumberOfBidsInLoanIFP = $this->oAcceptedBid->counter('id_loan = ' . $aLoanIFP[0]['id_loan']);

                        if ($iNumberOfBidsInLoanIFP > 1) {
                            $sContract = '<br>L&rsquo;ensemble de vos offres &agrave; concurrence de 1 000 euros seront regroup&eacute;es sous la forme d&rsquo;un seul contrat de pr&ecirc;t. Son taux d&rsquo;int&eacute;r&ecirc;t correspondra donc &agrave; la moyenne pond&eacute;r&eacute;e de vos <span style="color:#b20066;">' . $iNumberOfBidsInLoanIFP . ' offres de pr&ecirc;t</span>. ';

                            $sLinkExplication = '<br><br>Pour en savoir plus sur les r&egrave;gles de regroupement des offres de pr&ecirc;t, vous pouvez consulter <a style="color:#b20066;" href="' . $this->surl . '/document-de-pret">cette page</a>.';
                        }
                    }

                    if ($iNumberOfAcceptedBids > 1) {
                        $sSelectedOffers = 'vos offres ont &eacute;t&eacute; s&eacute;lectionn&eacute;es';
                        $sOffers         = 'vos offres';
                        $sDoes           = 'font';
                    } else {
                        $sSelectedOffers = 'votre offre a &eacute;t&eacute; s&eacute;lectionn&eacute;e';
                        $sOffers         = 'votre offre';
                        $sDoes           = 'fait';
                    }

                    $sLoans = ($iNumberOfLoansForLender > 1) ? 'vos pr&ecirc;ts' : 'votre pr&ecirc;t';

                    foreach ($aLoansOfLender as $aLoan) {
                        $aFirstPayment = $oPaymentSchedule->getPremiereEcheancePreteurByLoans($aLoan['id_project'], $aLoan['id_lender'], $aLoan['id_loan']);

                        switch ($aLoan['id_type_contract']) {
                            case \loans::TYPE_CONTRACT_BDC:
                                $sContractType = 'Bon de caisse';
                                break;
                            case \loans::TYPE_CONTRACT_IFP:
                                $sContractType = 'Contrat de pr&ecirc;t';
                                break;
                            default:
                                $sContractType = '';
                                break;
                        }
                        $sLoansDetails .= '<tr>
                                               <td style="' . $sStyleTD . '">' . $this->ficelle->formatNumber($aLoan['amount'] / 100) . ' &euro;</td>
                                               <td style="' . $sStyleTD . '">' . $this->ficelle->formatNumber($aLoan['rate']) . ' %</td>
                                               <td style="' . $sStyleTD . '">' . $this->projects->period . ' mois</td>
                                               <td style="' . $sStyleTD . '">' . $this->ficelle->formatNumber($aFirstPayment['montant'] / 100) . ' &euro;</td>
                                               <td style="' . $sStyleTD . '">' . $sContractType . '</td>
                                               </tr>';
                    }

                    $this->mails_text->get('preteur-bid-ok', 'lang = "' . $this->language . '" AND type');

                    $varMail = array(
                        'surl' => $this->surl,
                        'url' => $this->furl,
                        'offre_s_selectionne_s' => $sSelectedOffers,
                        'prenom_p' => $oClient->prenom,
                        'nom_entreprise' => $oCompanies->name,
                        'fait' => $sDoes,
                        'contrat_pret' => $sContract,
                        'detail_loans' => $sLoansDetails,
                        'offre_s' => $sOffers,
                        'pret_s' => $sLoans,
                        'projet-p' => $this->furl . '/projects/detail/' . $this->projects->slug,
                        'link_explication' => $sLinkExplication,
                        'motif_virement' => $oClient->getLenderPattern($oClient->id_client),
                        'lien_fb' => $this->like_fb,
                        'lien_tw' => $this->twitter,
                        'annee' => date('Y')
                    );

                    $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                    $this->email = $this->loadLib('email');
                    $this->email->setFrom($this->mails_text->exp_email, strtr(utf8_decode($this->mails_text->exp_name), $tabVars));
                    $this->email->setSubject(stripslashes(strtr(utf8_decode($this->mails_text->subject), $tabVars)));
                    $this->email->setHTMLBody(stripslashes(strtr(utf8_decode($this->mails_text->content), $tabVars)));

                    if ($oClient->status == 1) {
                        if ($this->Config['env'] === 'prod') {
                            \Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $oClient->email, $tabFiler);
                            $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                        } else {
                            $this->email->addRecipient(trim($oClient->email));
                            \Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        }
                        $oLogger->addRecord(ULogger::INFO, 'project : ' . $iProjectId . ' : email preteur-bid-ok sent for lender (' . $oLender->id_lender_account . ')');
                    }
                    $iNbTreatedLenders++;

                    $oLogger->addRecord(ULogger::INFO, 'project : ' . $iProjectId . ' : ' . $iNbTreatedLenders . '/' . $iNbLenders . ' loan notification mail sent');
                }
            } else {// Funding KO (le solde demandé n'a pas ete atteint par les encheres)
                // On passe le projet en funding ko
                $this->projects_status_history->addStatus(\users::USER_ID_CRON, \projects_status::FUNDING_KO, $iProjectId);

                $this->projects->get($iProjectId, 'id_project');
                $this->companies->get($this->projects->id_company, 'id_company');
                $this->clients->get($this->companies->id_client_owner, 'id_client');

                $this->mails_text->get('emprunteur-dossier-funding-ko', 'lang = "' . $this->language . '" AND type');

                $varMail = array(
                    'surl' => $this->surl,
                    'url' => $this->lurl,
                    'prenom_e' => $this->clients->prenom,
                    'projet' => $this->projects->title,
                    'lien_fb' => $this->like_fb,
                    'lien_tw' => $this->twitter
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
                    if ($this->Config['env'] === 'prod') {
                        \Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    } else {
                        $this->email->addRecipient(trim($this->clients->email));
                        \Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }
                    $oLogger->addRecord(ULogger::INFO, 'project : ' . $iProjectId . ' : email emprunteur-dossier-funding-ko sent');
                }

                $this->lEnchere = $this->oBid->select('id_project = ' . $iProjectId, 'rate ASC,added ASC');

                $iBidNbTotal   = count($this->lEnchere);
                $iTreatedBitNb = 0;
                $oLogger->addRecord(ULogger::INFO, 'project : ' . $iProjectId . ' : ' . $iBidNbTotal . 'bids in total.');

                foreach ($this->lEnchere as $k => $e) {
                    $this->oBid->get($e['id_bid'], 'id_bid');
                    $this->oBid->status = \bids::STATUS_BID_REJECTED;
                    $this->oBid->update();

                    $this->oLenderAccount->get($e['id_lender_account'], 'id_lender_account');

                    $this->transactions->id_client        = $this->oLenderAccount->id_client_owner;
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

                    $oLogger->addRecord(ULogger::INFO, 'project : ' . $iProjectId . ' : The bid (' . $e['id_bid'] . ') status has been updated to 2');

                    $this->notifications->type            = 1; // rejet
                    $this->notifications->id_lender       = $e['id_lender_account'];
                    $this->notifications->id_project      = $e['id_project'];
                    $this->notifications->amount          = $e['amount'];
                    $this->notifications->id_bid          = $e['id_bid'];
                    $this->notifications->id_notification = $this->notifications->create();

                    $this->clients_gestion_mails_notif->id_client       = $this->oLenderAccount->id_client_owner;
                    $this->clients_gestion_mails_notif->id_notif        = 3; // offre refusée
                    $this->clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
                    $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                    $this->clients_gestion_mails_notif->id_transaction  = $this->transactions->id_transaction;
                    $this->clients_gestion_mails_notif->create();

                    $sumOffres = $this->offres_bienvenues_details->sum('id_client = ' . $this->oLenderAccount->id_client_owner . ' AND id_bid = ' . $e['id_bid'], 'montant');
                    if ($sumOffres > 0) {
                        // sum des offres inferieur au montant a remb
                        if ($sumOffres <= $e['amount']) {
                            $this->offres_bienvenues_details->montant = $sumOffres;
                        } else {// Si montant des offres superieur au remb on remb le montant a crediter
                            $this->offres_bienvenues_details->montant = $e['amount'];
                        }

                        $this->offres_bienvenues_details->id_offre_bienvenue = 0;
                        $this->offres_bienvenues_details->id_client          = $this->oLenderAccount->id_client_owner;
                        $this->offres_bienvenues_details->id_bid             = 0;
                        $this->offres_bienvenues_details->id_bid_remb        = $e['id_bid'];
                        $this->offres_bienvenues_details->status             = 0;
                        $this->offres_bienvenues_details->type               = 2;
                        $this->offres_bienvenues_details->create();
                    }

                    $this->projects->get($e['id_project'], 'id_project');
                    $this->clients->get($this->oLenderAccount->id_client_owner, 'id_client');

                    $solde_p = $this->transactions->getSolde($this->clients->id_client);

                    $this->mails_text->get('preteur-dossier-funding-ko', 'lang = "' . $this->language . '" AND type');

                    $timeAdd = strtotime($e['added']);
                    $month   = $this->oDates->tableauMois['fr'][date('n', $timeAdd)];

                    $varMail = array(
                        'surl' => $this->surl,
                        'url' => $this->lurl,
                        'prenom_p' => $this->clients->prenom,
                        'entreprise' => $this->companies->name,
                        'projet' => $this->projects->title,
                        'montant' => $this->ficelle->formatNumber($e['amount'] / 100),
                        'proposition_pret' => $this->ficelle->formatNumber(($e['amount'] / 100)),
                        'date_proposition_pret' => date('d', $timeAdd) . ' ' . $month . ' ' . date('Y', $timeAdd),
                        'taux_proposition_pret' => $e['rate'],
                        'compte-p' => '/projets-a-financer',
                        'motif_virement' => $this->clients->getLenderPattern($this->clients->id_client),
                        'solde_p' => $solde_p,
                        'lien_fb' => $this->like_fb,
                        'lien_tw' => $this->twitter
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
                        if ($this->Config['env'] === 'prod') {
                            \Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                            $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                        } else {
                            $this->email->addRecipient(trim($this->clients->email));
                            \Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        }
                        $oLogger->addRecord(ULogger::INFO, 'project : ' . $iProjectId . ' : email preteur-dossier-funding-ko sent');
                    }

                    $iTreatedBitNb++;
                    $oLogger->addRecord(ULogger::INFO, 'project : ' . $iProjectId . ' : ' . $iTreatedBitNb . '/' . $iBidNbTotal . 'bids treated.');
                }
            } // fin funding ko

            $this->projects->get($iProjectId, 'id_project');
            $this->companies->get($this->projects->id_company, 'id_company');
            $this->clients->get($this->companies->id_client_owner, 'id_client');

            $this->settings->get('Adresse notification projet fini', 'type');
            $destinataire = $this->settings->value;

            $montant_collect = $this->oBid->getSoldeBid($this->projects->id_project);

            // si le solde des enchere est supperieur au montant du pret on affiche le montant du pret
            if (($montant_collect / 100) >= $this->projects->amount) {
                $montant_collect = $this->projects->amount;
            }

            $this->nbPeteurs = $this->oLoan->getNbPreteurs($this->projects->id_project);

            $this->mails_text->get('notification-projet-fini', 'lang = "' . $this->language . '" AND type');

            $surl         = $this->surl;
            $url          = $this->lurl;
            $id_projet    = $this->projects->id_project;
            $title_projet = utf8_decode($this->projects->title);
            $nbPeteurs    = $this->nbPeteurs;
            $tx           = $this->projects->target_rate;
            $montant_pret = $this->projects->amount;
            $montant      = $montant_collect;
            $sujetMail    = htmlentities($this->mails_text->subject);

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
            \Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
        }
    }

    public function createRepaymentSchedule($iProjectId)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        $this->oSettings->get('Commission remboursement', 'type');
        $commission = $this->oSettings->value;

        $this->oSettings->get('TVA', 'type');
        $tva = $this->oSettings->value;

        $this->oSettings->get('EQ-Acompte d\'impôt sur le revenu', 'type');
        $prelevements_obligatoires = $this->oSettings->value;

        $this->oSettings->get('EQ-Contribution additionnelle au Prélèvement Social', 'type');
        $contributions_additionnelles = $this->oSettings->value;

        $this->oSettings->get('EQ-CRDS', 'type');
        $crds = $this->oSettings->value;

        $this->oSettings->get('EQ-CSG', 'type');
        $csg = $this->oSettings->value;

        $this->oSettings->get('EQ-Prélèvement de Solidarité', 'type');
        $prelevements_solidarite = $this->oSettings->value;

        $this->oSettings->get('EQ-Prélèvement social', 'type');
        $prelevements_sociaux = $this->oSettings->value;

        $this->oSettings->get('EQ-Retenue à la source', 'type');
        $retenues_source = $this->oSettings->value;

        $this->oProjectStatus->getLastStatut($iProjectId);

        // Si le projet est bien en funde on créer les echeances
        if ($this->oProjectStatus->status == \projects_status::FUNDE) {
            $this->oProject->get($iProjectId, 'id_project');
            $lLoans = $this->oLoan->select('id_project = ' . $this->oProject->id_project);

            $iLoanNbTotal   = count($lLoans);
            $iTreatedLoanNb = 0;
            if ($this->oLogger instanceof ULogger) {
                $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $iProjectId . ' : ' . $iLoanNbTotal . ' in total.');
            }

            // on parcourt les loans du projet en remboursement
            foreach ($lLoans as $l) {
                //////////////////////////////
                // Echeancier remboursement //
                //////////////////////////////

                $this->oLenderAccount->get($l['id_lender'], 'id_lender_account');
                $this->oClient->get($this->oLenderAccount->id_client_owner, 'id_client');

                $this->oClientAdresse->get($this->oLenderAccount->id_client_owner, 'id_client');

                // 0 : fr/fr
                // 1 : fr/resident etranger
                // 2 : no fr/resident etranger
                $etranger = 0;
                // fr/resident etranger
                if ($this->oClient->id_nationalite <= 1 && $this->oClientAdresse->id_pays_fiscal > 1) {
                    $etranger = 1;
                } // no fr/resident etranger
                elseif ($this->oClient->id_nationalite > 1 && $this->oClientAdresse->id_pays_fiscal > 1) {
                    $etranger = 2;
                }

                $this->oLoan->get($l['id_loan']);
                $tabl = $this->oLoan->getRepaymentSchedule($commission, $tva);

                // on crée les echeances de chaques preteurs
                foreach ($tabl['repayment_schedule'] as $k => $e) {
                    // Date d'echeance preteur
                    $dateEcheance = $this->oDates->dateAddMoisJoursV3($this->oProject->date_fin, $k);
                    $dateEcheance = date('Y-m-d H:i', $dateEcheance) . ':00';

                    // Date d'echeance emprunteur
                    $dateEcheance_emprunteur = $this->oDates->dateAddMoisJoursV3($this->oProject->date_fin, $k);
                    // on retire 6 jours ouvrés
                    $dateEcheance_emprunteur = $this->oWorkingDay->display_jours_ouvres($dateEcheance_emprunteur, 6);
                    $dateEcheance_emprunteur = date('Y-m-d H:i', $dateEcheance_emprunteur) . ':00';

                    // particulier
                    if (in_array($this->oClient->type, array(1, 3))) {
                        if ($etranger > 0) {
                            $montant_prelevements_obligatoires    = 0;
                            $montant_contributions_additionnelles = 0;
                            $montant_crds                         = 0;
                            $montant_csg                          = 0;
                            $montant_prelevements_solidarite      = 0;
                            $montant_prelevements_sociaux         = 0;

                            switch ($this->oLoan->id_type_contract) {
                                case \loans::TYPE_CONTRACT_BDC:
                                    $montant_retenues_source = round($retenues_source * $e['interest'], 2);
                                    break;
                                case \loans::TYPE_CONTRACT_IFP:
                                    $montant_retenues_source = 0;
                                    break;
                                default:
                                    $montant_retenues_source = 0;
                                    trigger_error('Unknown contract type: ' . $this->oLoan->id_type_contract, E_USER_WARNING);
                                    break;
                            }
                        } else {
                            if (
                                $this->oLenderAccount->exonere == 1 // @todo should not be usefull and field should be deleted from DB but as long as it exists and BO interface is based on it, we must use it
                                && $this->oLenderAccount->debut_exoneration != '0000-00-00'
                                && $this->oLenderAccount->fin_exoneration != '0000-00-00'
                                && date('Y-m-d', strtotime($dateEcheance)) >= $this->oLenderAccount->debut_exoneration
                                && date('Y-m-d', strtotime($dateEcheance)) <= $this->oLenderAccount->fin_exoneration
                            ) {
                                $montant_prelevements_obligatoires = 0;
                            } else {
                                $montant_prelevements_obligatoires = round($prelevements_obligatoires * $e['interest'], 2);
                            }

                            $montant_contributions_additionnelles = round($contributions_additionnelles * $e['interest'], 2);
                            $montant_crds                         = round($crds * $e['interest'], 2);
                            $montant_csg                          = round($csg * $e['interest'], 2);
                            $montant_prelevements_solidarite      = round($prelevements_solidarite * $e['interest'], 2);
                            $montant_prelevements_sociaux         = round($prelevements_sociaux * $e['interest'], 2);
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

                        switch ($this->oLoan->id_type_contract) {
                            case \loans::TYPE_CONTRACT_BDC:
                                $montant_retenues_source = round($retenues_source * $e['interest'], 2);
                                break;
                            case \loans::TYPE_CONTRACT_IFP:
                                $montant_retenues_source = 0;
                                break;
                            default:
                                $montant_retenues_source = 0;
                                trigger_error('Unknown contract type: ' . $this->oLoan->id_type_contract, E_USER_WARNING);
                                break;
                        }
                    }

                    $this->oSchedule->id_lender                    = $l['id_lender'];
                    $this->oSchedule->id_project                   = $this->oProject->id_project;
                    $this->oSchedule->id_loan                      = $l['id_loan'];
                    $this->oSchedule->ordre                        = $k;
                    $this->oSchedule->montant                      = $e['repayment'] * 100;
                    $this->oSchedule->capital                      = $e['capital'] * 100;
                    $this->oSchedule->interets                     = $e['interest'] * 100;
                    $this->oSchedule->commission                   = $e['commission'] * 100;
                    $this->oSchedule->tva                          = $e['vat_amount'] * 100;
                    $this->oSchedule->prelevements_obligatoires    = $montant_prelevements_obligatoires;
                    $this->oSchedule->contributions_additionnelles = $montant_contributions_additionnelles;
                    $this->oSchedule->crds                         = $montant_crds;
                    $this->oSchedule->csg                          = $montant_csg;
                    $this->oSchedule->prelevements_solidarite      = $montant_prelevements_solidarite;
                    $this->oSchedule->prelevements_sociaux         = $montant_prelevements_sociaux;
                    $this->oSchedule->retenues_source              = $montant_retenues_source;
                    $this->oSchedule->date_echeance                = $dateEcheance;
                    $this->oSchedule->date_echeance_emprunteur     = $dateEcheance_emprunteur;
                    $this->oSchedule->create();
                }
                $iTreatedLoanNb++;
                if ($this->oLogger instanceof ULogger) {
                    $this->oLogger->addRecord(ULogger::INFO,
                        'project : ' . $iProjectId . ' : ' . $iTreatedLoanNb . '/' . $iLoanNbTotal . ' lender loan treated. ' . $k . ' repayment schedules created.');
                }
            }
        }
    }
}