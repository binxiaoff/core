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
    public static function checkBids(\projects $oProject, ULogger $oLogger = null)
    {
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');
        /** @var \bids_logs $oBidLog */
        $oBidLog = Loader::loadData('bids_logs');

        $aLogContext      = array();
        $bBidsLogs        = false;
        $nb_bids_ko       = 0;
        $iBidsAccumulated = 0;
        $iBorrowAmount    = $oProject->amount;
        $nb_bids_encours  = $oBid->counter('id_project = ' . $oProject->id_project . ' AND status = 0');
        $iBidsNbTotal     = $oBid->counter('id_project = ' . $oProject->id_project);
        $soldeBid         = $oBid->getSoldeBid($oProject->id_project);

        $oBidLog->debut      = date('Y-m-d H:i:s');
        $oBidLog->id_project = $oProject->id_project;

        if ($soldeBid >= $iBorrowAmount) {
            foreach ($oBid->select('id_project = ' . $oProject->id_project . ' AND status = 0', 'rate ASC, added ASC') as $aBid) {
                if ($iBidsAccumulated < $iBorrowAmount) {
                    $iBidsAccumulated += ($aBid['amount'] / 100);
                } else { // Les bid qui depassent on leurs redonne leur argent et on met en ko
                    $bBidsLogs = true;
                    $oBid->get($aBid['id_bid']);

                    if (0 == $oBid->id_autobid) { // non-auto-bid
                        self::reject($oBid);
                    } else {
                        $oBid->status = \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY;
                    }

                    $nb_bids_ko++;
                    $oBid->update();
                }

                if (1 != $oBid->checked) {
                    $oBid->checked = 1;
                    $oBid->update();
                }
            }

            $aLogContext['Project ID']    = $oProject->id_project;
            $aLogContext['Balance']       = $soldeBid;
            $aLogContext['Rejected bids'] = $nb_bids_ko;
        }

        if ($bBidsLogs == true) {
            $oBidLog->nb_bids_encours = $nb_bids_encours;
            $oBidLog->nb_bids_ko      = $nb_bids_ko;
            $oBidLog->total_bids      = $iBidsNbTotal;
            $oBidLog->total_bids_ko   = $oBid->counter('id_project = ' . $oProject->id_project . ' AND status = 2');
            $oBidLog->fin             = date('Y-m-d H:i:s');
            $oBidLog->create();
        }
        if ($oLogger instanceof ULogger) {
            $oLogger->addRecord(ULogger::INFO, 'Project ID: ' . $oProject->id_project, $aLogContext);
        }
    }

    public static function sendMailFonded(\projects $oProject, ULogger $oLogger = null)
    {
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');
        $soldeBid         = $oBid->getSoldeBid($oProject->id_project);

        if ($soldeBid >= $oProject->amount && $oProject->status_solde == 0) {
            /** @var \mails_text $oMailText */
            $oMailText = Loader::loadData('mails_text');
            /** @var \settings $oSettings */
            $oSettings = Loader::loadData('settings');
            /** @var \mails_filer $oMailFiler */
            $oMailFiler = Loader::loadData('mails_filer');
            /** @var \email $oEmail */
            $oEmail = Loader::loadLib('email');
            /** @var \ficelle $oFicelle */
            $oFicelle  = Loader::loadLib('ficelle');
            $aConfig   = Loader::loadConfig();
            $sLanguage = 'fr';

            // EMAIL EMPRUNTEUR FUNDE //
            if ($oLogger instanceof ULogger) {
                $oLogger->addRecord(ULogger::INFO, 'Project funded - send email to borrower', array('Project ID' => $oProject->id_project));
            }
            // Mise a jour du statut pour envoyer qu'une seule fois le mail a l'emprunteur
            $oProject->status_solde = 1;
            $oProject->update();

            $oSettings->get('Facebook', 'type');
            $lien_fb = $oSettings->value;

            $oSettings->get('Twitter', 'type');
            $lien_tw = $oSettings->value;

            $oSettings->get('Heure fin periode funding', 'type');
            $iFinFunding = $oSettings->value;

            /** @var \companies $oCompanies */
            $oCompanies = Loader::loadData('companies');
            $oCompanies->get($oProject->id_company, 'id_company');

            /** @var \clients $oEmprunteur */
            $oEmprunteur = Loader::loadData('clients');
            $oEmprunteur->get($oCompanies->id_client_owner, 'id_client');

            $tab_date_retrait = explode(' ', $oProject->date_retrait_full);
            $tab_date_retrait = explode(':', $tab_date_retrait[1]);
            $heure_retrait    = $tab_date_retrait[0] . ':' . $tab_date_retrait[1];

            if ($heure_retrait == '00:00') {
                $heure_retrait = $iFinFunding;
            }

            $oDates = Loader::loadLib('dates');
            $inter  = $oDates->intervalDates(date('Y-m-d H:i:s'), $oProject->date_retrait . ' ' . $heure_retrait . ':00');

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
            foreach ($oBid->select('id_project = ' . $oProject->id_project . ' AND status = 0') as $b) {
                $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                $montantBas += ($b['amount'] / 100);
            }
            $taux_moyen = ($montantHaut / $montantBas);
            $taux_moyen = $oFicelle->formatNumber($taux_moyen);


            $sSUrl = $aConfig['static_url'][$aConfig['env']];
            $sLUrl = $aConfig['url'][$aConfig['env']]['default'] . ($aConfig['multilanguage']['enabled'] ? '/' . $sLanguage : '');

            // Pas de mail si le compte est desactivé
            if ($oEmprunteur->status == 1) {
                //*** ENVOI DU MAIL FUNDE EMPRUNTEUR ***//
                $oMailText->get('emprunteur-dossier-funde', 'lang = "' . $sLanguage . '" AND type');

                $varMail = array(
                    'surl' => $sSUrl,
                    'url' => $sLUrl,
                    'prenom_e' => utf8_decode($oEmprunteur->prenom),
                    'taux_moyen' => $taux_moyen,
                    'temps_restant' => $tempsRest,
                    'projet' => $oProject->title,
                    'lien_fb' => $lien_fb,
                    'lien_tw' => $lien_tw
                );

                $oTNMP   = Loader::loadLib('tnmp');
                $tabVars = $oTNMP->constructionVariablesServeur($varMail);

                $sujetMail = strtr(utf8_decode($oMailText->subject), $tabVars);
                $texteMail = strtr(utf8_decode($oMailText->content), $tabVars);
                $exp_name  = strtr(utf8_decode($oMailText->exp_name), $tabVars);

                $oEmail->setFrom($oMailText->exp_email, $exp_name);
                $oEmail->setSubject(stripslashes($sujetMail));
                $oEmail->setHTMLBody(stripslashes($texteMail));

                if ($aConfig['env'] == 'prod') {
                    \Mailer::sendNMP($oEmail, $oMailFiler, $oMailText->id_textemail, $oEmprunteur->email, $tabFiler);
                    $oTNMP->sendMailNMP($tabFiler, $varMail, $oMailText->nmp_secure, $oMailText->id_nmp, $oMailText->nmp_unique, $oMailText->mode);
                } else {
                    $oEmail->addRecipient(trim($oEmprunteur->email));
                    \Mailer::send($oEmail, $oMailFiler, $oMailText->id_textemail);
                }
            }
            //*** ENVOI DU MAIL NOTIFICATION FUNDE 100% ***//

            $oSettings->get('Adresse notification projet funde a 100', 'type');
            $destinataire = $oSettings->value;

            $nbPeteurs = $oBid->getNbPreteurs($oProject->id_project);

            $oMailText->get('notification-projet-funde-a-100', 'lang = "' . $sLanguage . '" AND type');

            $surl         = $sSUrl;
            $url          = $sLUrl;
            $id_projet    = $oProject->title;
            $title_projet = utf8_decode($oProject->title);
            $nbPeteurs    = $nbPeteurs;
            $tx           = $taux_moyen;
            $periode      = $tempsRest;

            $sujetMail = htmlentities($oMailText->subject);
            eval("\$sujetMail = \"$sujetMail\";");

            $texteMail = $oMailText->content;
            eval("\$texteMail = \"$texteMail\";");

            $exp_name = $oMailText->exp_name;
            eval("\$exp_name = \"$exp_name\";");

            $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
            $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

            $oEmail->setFrom($oMailText->exp_email, $exp_name);
            $oEmail->addRecipient(trim($destinataire));

            $oEmail->setSubject('=?UTF-8?B?' . base64_encode(html_entity_decode($sujetMail)) . '?=');
            $oEmail->setHTMLBody($texteMail);
            /** @var \mails_filer $oMailFiler */
            $oMailFiler = Loader::loadData('mails_filer');
            \Mailer::send($oEmail, $oMailFiler, $oMailText->id_textemail);
        }

    }

    /**
     * @param \projects $oProject
     *
     * @return bool
     */
    public static function autoBid(\projects $oProject)
    {
        if ($oProject->date_fin != '0000-00-00 00:00:00' && time() >= strtotime($oProject->date_fin)) {
            return false;
        }

        $oProjectStatus = Loader::loadData('projects_status');
        if ($oProjectStatus->getLastStatut($oProject->id_project)) {
            if ($oProjectStatus->status == \projects_status::AUTO_BID) {
                return self::bidAllAutoBid($oProject);
            } else {
                if ($oProjectStatus->status == \projects_status::EN_FUNDING) {
                    return self::refreshAllAutoBidRate($oProject);
                }
            }
        }

        return false;
    }

    /**
     * @param \projects $oProject
     *
     * @return bool
     */
    private static function bidAllAutoBid(\projects $oProject)
    {
        $iPeriod      = (int)$oProject->period;
        $sEvaluation  = $oProject->risk;
        $iCurrentRate = 10;

        /** @var \autobid_queue $oAutoBidQueue */
        $oAutoBidQueue = Loader::loadData('autobid_queue');
        $oBid          = Loader::loadData('bids');
        /** @var \autobid $oAutoBid */
        $oAutoBid = Loader::loadData('autobid');

        $iOffset = 0;
        $iLimit  = 100;
        while ($aAutoBidList = $oAutoBidQueue->getAutoBids($iPeriod, $sEvaluation, $iCurrentRate, $iOffset, $iLimit)) {
            $iOffset += $iLimit;

            foreach ($aAutoBidList as $aAutoBidSetting) {
                $oAutoBid->get($aAutoBidSetting['id_autobid']);
                AutoBidManager::bid($oAutoBid, $oProject, $iCurrentRate);
            }
        }

        return true;
    }

    private static function refreshAllAutoBidRate(\projects $oProject)
    {
        /** @var \settings $oSettings */
        $oSettings = Loader::loadData('settings');
        $oSettings->get('Auto-bid step', 'type');
        $fStep = (float)$oSettings->value;

        /** @var \bids $oBid */
        $oBid         = Loader::loadData('bids');
        $fCurrentRate = (float)$oBid->getProjectMaxRate($oProject->id_project) - $fStep;

        while ($aAutoBidList = $oBid->getTempRefusedAutoBids($oProject->id_project)) {
            foreach ($aAutoBidList as $aAutobid) {
                if ($oBid->get($aAutobid['id_bid'])) {
                    AutoBidManager::refreshRateOrReject($oBid, $fCurrentRate);
                }
            }
        }

        return true;
    }

    public static function cleanTempRefusedAutobid(\projects $oProject)
    {
        /** @var \bids $oBid */
        $oBid            = Loader::loadData('bids');
        $aRefusedAutoBid = $oBid->getTempRefusedAutoBids($oProject->id_project, 1);
        if (false === empty($aRefusedAutoBid)) {
            self::checkBids($oProject);
            self::refreshAllAutoBidRate($oProject);
            self::cleanTempRefusedAutobid($oProject);
        }
    }
}