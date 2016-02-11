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
    /** @var  \projects */
    private $oProject;
    /** @var  ULogger */
    private $oLogger;
    /** @var  BidManager */
    private $oBidManager;

    public function __construct()
    {
        $this->oAutoBidManager = Loader::loadService('AutoBidManager');
        $this->oBidManager     = Loader::loadService('BidManager');

        $this->oBid           = Loader::loadData('bids');
        $this->oBidLog        = Loader::loadData('bids_logs');
        $this->oMailText      = Loader::loadData('mails_text');
        $this->oSettings      = Loader::loadData('settings');
        $this->oMailFiler     = Loader::loadData('mails_filer');
        $this->oCompanies     = Loader::loadData('companies');
        $this->oBorrower      = Loader::loadData('clients');
        $this->oAutoBidQueue  = Loader::loadData('autobid_queue');
        $this->oAutoBid       = Loader::loadData('autobid');
        $this->oProject       = Loader::loadData('projects');
        $this->oProjectStatus = Loader::loadData('projects_status');

        $this->oTNMP    = Loader::loadLib('tnmp');
        $this->oEmail   = Loader::loadLib('email');
        $this->oFicelle = Loader::loadLib('ficelle');
        $this->oDates   = Loader::loadLib('dates');

        $this->aConfig = Loader::loadConfig();

        $this->sLanguage = 'fr';
    }

    /**
     * @param mixed $oLogger
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
}