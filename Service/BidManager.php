<?php
namespace Unilend\Service;

use Unilend\core\Loader;

/**
 * Class BidManager
 * @package Unilend\Service
 */
class BidManager
{
    public static function bid(\bids $oBid)
    {
        /** @var \settings $oSetting */
        $oSetting = Loader::loadData('settings');
        $oSetting->get('Pret min', 'type');
        $iAmountMin = (int)$oSetting->value;

        $iAmount     = $oBid->amount / 100;
        $iAmountX100 = $oBid->amount;

        if ($iAmountMin > $iAmount) {
            return false;
        }

        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');
        if (false === $oLenderAccount->get($oBid->id_lender)) {
            return false;
        }
        $iClientId = $oLenderAccount->id_client_owner;

        /** @var \clients_status $oClientStatus */
        $oClientStatus = Loader::loadData('clients_status');
        if ($oClientStatus->getLastStatut($iClientId)) {
            if ($oClientStatus->status < 60) {
                return false;
            }
        } else {
            return false;
        }
        /** @var \transactions $oTransaction */
        $oTransaction = Loader::loadData('transactions');
        $iBalance     = $oTransaction->getSolde($iClientId);
        if ($iBalance < $iAmount) {
            return false;
        }

        $oTransaction->id_client        = $iClientId;
        $oTransaction->montant          = '-' . ($iAmountX100);
        $oTransaction->id_langue        = 'fr';
        $oTransaction->date_transaction = date('Y-m-d H:i:s');
        $oTransaction->status           = '1';
        $oTransaction->etat             = '1';
        $oTransaction->id_project       = $oBid->id_project;
        $oTransaction->transaction      = '2';
        $oTransaction->type_transaction = '2';
        $oTransaction->create();

        /** @var \wallets_lines $oWalletsLine */
        $oWalletsLine                           = Loader::loadData('wallets_lines');
        $oWalletsLine->id_lender                = $oBid->id_lender;
        $oWalletsLine->type_financial_operation = 20; // enchere
        $oWalletsLine->id_transaction           = $oTransaction->id_transaction;
        $oWalletsLine->status                   = 1;
        $oWalletsLine->type                     = 2; // transaction virtuelle
        $oWalletsLine->amount                   = '-' . ($iAmountX100);
        $oWalletsLine->id_project               = $oBid->id_project;
        $oWalletsLine->create();

        /** @var \bids $oBids */
        $oBids  = Loader::loadData('bids');
        $iBidNb = $oBids->counter('id_project = ' . $oBid->id_project);
        $iBidNb += 1;

        $oBids->id_lender_wallet_line = $oWalletsLine->id_wallet_line;
        $oBids->ordre                 = $iBidNb;
        $oBids->create();

        /** @var \offres_bienvenues_details $oWelcomeOffer */
        $oWelcomeOffer = Loader::loadData('offres_bienvenues_details');

        // Liste des offres non utilisées
        $aAllOffers = $oWelcomeOffer->select('id_client = ' . $iClientId . ' AND status = 0');
        if ($aAllOffers != false) {
            $iOfferTotal = 0;
            foreach ($aAllOffers as $aOffer) {
                // Tant que le total des offres est infèrieur
                if ($iOfferTotal <= $iAmount) {
                    $iOfferTotal += ($aOffer['montant'] / 100); // total des offres

                    $oWelcomeOffer->get($aOffer['id_offre_bienvenue_detail'], 'id_offre_bienvenue_detail');
                    $oWelcomeOffer->status = 1;
                    $oWelcomeOffer->id_bid = $oBids->id_bid;
                    $oWelcomeOffer->update();

                    // Apres addition de la derniere offre on se rend compte que le total depasse
                    if ($iOfferTotal > $iAmount) {
                        // On fait la diff et on créer un remb du trop plein d'offres
                        $iAmountRepayment                  = $iOfferTotal - $iAmount;
                        $oWelcomeOffer->id_offre_bienvenue = 0;
                        $oWelcomeOffer->id_client          = $iClientId;
                        $oWelcomeOffer->id_bid             = 0;
                        $oWelcomeOffer->id_bid_remb        = $oBids->id_bid;
                        $oWelcomeOffer->status             = 0;
                        $oWelcomeOffer->type               = 1;
                        $oWelcomeOffer->montant            = ($iAmountRepayment * 100);
                        $oWelcomeOffer->create();
                    }
                } else {
                    break;
                }
            }
        }

        ///// NOTIFICATION OFFRE PLACEE ///////
        /** @var \notifications $oNotification */
        $oNotification             = Loader::loadData('notifications');
        $oNotification->type       = \clients_gestion_type_notif::TYPE_BID_PLACED; // offre placée
        $oNotification->id_lender  = $oBid->id_lender;
        $oNotification->id_project = $oBid->id_project;
        $oNotification->amount     = $iAmountX100;
        $oNotification->id_bid     = $oBids->id_bid;
        $oNotification->create();
        ///// FIN NOTIFICATION OFFRE PLACEE ///////

        /** @var \clients_gestion_notifications $oNotificationSettings */
        $oNotificationSettings = Loader::loadData('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = Loader::loadData('clients_gestion_mails_notif');
        if ($oNotificationSettings->getNotif($iClientId, \clients_gestion_type_notif::TYPE_BID_PLACED, 'immediatement') == true) {
            /** @var \clients $oClient */
            $oClient = Loader::loadData('clients');
            $oClient->get($iClientId);

            $sPurpose = ClientManager::getClientTransferPurpose($oClient);

            //*********************************//
            //*** ENVOI DU MAIL CONFIRM BID ***//
            //*********************************//
            //Todo: create the language in client settings in case of multi-language site (project Italy)
            $sLanguage = 'fr';
            /** @var \mails_text $oMailText */
            $oMailText = Loader::loadData('mails_text');
            $oMailText->get('confirmation-bid', 'lang = "' . $sLanguage . '" AND type');

            $oSetting->get('Facebook', 'type');
            $lien_fb = $oSetting->value;

            $oSetting->get('Twitter', 'type');
            $lien_tw = $oSetting->value;

            $timeAdd = strtotime($oBids->added);
            /** @var \dates $oDate */
            $oDate = Loader::loadLib('dates');
            $month = $oDate->tableauMois['fr'][date('n', $timeAdd)];

            $config = Loader::loadConfig();
            $sSUrl  = $config['static_url'][$config['env']];
            $sLUrl  = $config['url'][$config['env']]['default'] . ($config['multilanguage']['enabled'] ? '/' . $sLanguage : '');

            /** @var \tree $oTree */
            $oTree       = Loader::loadData('tree');
            $pageProjets = $oTree->getSlug(4, $sLanguage);

            /** @var \ficelle $oFicelle */
            $oFicelle = Loader::loadLib('ficelle');

            /** @var \companies $oCompany */
            $oCompany = Loader::loadData('companies');

            $varMail = array(
                'surl' => $sSUrl,
                'url' => $sLUrl,
                'prenom_p' => $oClient->prenom,
                'nom_entreprise' => $oCompany->name,
                'valeur_bid' => $oFicelle->formatNumber($iAmount),
                'taux_bid' => $oFicelle->formatNumber($oBid->rate, 1),
                'date_bid' => date('d', $timeAdd) . ' ' . $month . ' ' . date('Y', $timeAdd),
                'heure_bid' => date('H:i:s', strtotime($oBids->added)),
                'projet-p' => $sLUrl . '/' . $pageProjets,
                'motif_virement' => $sPurpose,
                'lien_fb' => $lien_fb,
                'lien_tw' => $lien_tw
            );

            /** @var \tnmp $oTNMP */
            $oTNMP   = Loader::loadLib('tnmp');
            $tabVars = $oTNMP->constructionVariablesServeur($varMail);

            $sujetMail = strtr(utf8_decode($oMailText->subject), $tabVars);
            $texteMail = strtr(utf8_decode($oMailText->content), $tabVars);
            $exp_name  = strtr(utf8_decode($oMailText->exp_name), $tabVars);

            /** @var \email $oEmail */
            $oEmail = Loader::loadLib('email');
            $oEmail->setFrom($oMailText->exp_email, $exp_name);
            $oEmail->setSubject(stripslashes($sujetMail));
            $oEmail->setHTMLBody(stripslashes($texteMail));

            /** @var \mails_filer $oMailFiler */
            $oMailFiler = Loader::loadData('mails_filer');

            if ($config['env'] === 'prod') {
                \Mailer::sendNMP($oEmail, $oMailFiler, $oMailText->id_textemail, $oClient->email, $tabFiler);
                $oTNMP->sendMailNMP($tabFiler, $varMail, $oMailText->nmp_secure, $oMailText->id_nmp, $oMailText->nmp_unique, $oMailText->mode);
            } else {
                $oEmail->addRecipient(trim($oClient->email));
                \Mailer::send($oEmail, $oMailFiler, $oMailText->id_textemail);
            }
            // fin mail confirmation bid //

            $oMailNotification->immediatement = 1;
        } else {
            $oMailNotification->immediatement = 0;
        }

        $oMailNotification->id_client       = $oClient->id_client;
        $oMailNotification->id_notif        = 2; // offre placée
        $oMailNotification->date_notif      = date('Y-m-d H:i:s');
        $oMailNotification->id_notification = $oNotification->id_notification;
        $oMailNotification->id_transaction  = $oTransaction->id_transaction;
        $oMailNotification->create();
    }

    /**
     * @param \bids $oBid
     */
    public static function reject(\bids $oBid)
    {
        if ($oBid->status == \bids::STATUS_BID_PENDING || $oBid->status == \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY) {
            /** @var \lenders_accounts $oLenderAccount */
            $oLenderAccount = Loader::loadData('lenders_accounts');
            $oLenderAccount->get($oBid->id_lender_account, 'id_lender_account');

            self::credit($oBid, $oLenderAccount, $oBid->amount / 100);
            $oBid->status = \bids::STATUS_BID_REJECTED;
            $oBid->update();
        }
    }

    public static function rejectPartially(\bids $oBid, $fRepaymentAmount)
    {
        if ($oBid->status == \bids::STATUS_BID_PENDING || $oBid->status == \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY) {
            /** @var \lenders_accounts $oLenderAccount */
            $oLenderAccount = Loader::loadData('lenders_accounts');
            $oLenderAccount->get($oBid->id_lender_account, 'id_lender_account');

            self::credit($oBid, $oLenderAccount, $fRepaymentAmount);
            // Save new amount of the bid after repayment
            $oBid->amount -= $fRepaymentAmount * 100;
            $oBid->status = \bids::STATUS_BID_ACCEPTED;
            $oBid->update();
        }
    }

    private static function credit(\bids $oBid, \lenders_accounts $oLenderAccount, $fAmount)
    {
        $iAmountX100 = $fAmount * 100;

        /** @var \transactions $oTransaction */
        $oTransaction                   = Loader::loadData('transactions');
        $oTransaction->id_client        = $oLenderAccount->id_client_owner;
        $oTransaction->montant          = $iAmountX100;
        $oTransaction->id_langue        = 'fr';
        $oTransaction->date_transaction = date('Y-m-d H:i:s');
        $oTransaction->status           = '1';
        $oTransaction->etat             = '1';
        $oTransaction->id_project       = $oBid->id_project;
        $oTransaction->ip_client        = $_SERVER['REMOTE_ADDR'];
        $oTransaction->type_transaction = 2;
        $oTransaction->id_bid_remb      = $oBid->id_bid;
        $oTransaction->transaction      = 2; // transaction virtuelle
        $oTransaction->create();

        /** @var \wallets_lines $oTransaction */
        $oWalletsLine                           = Loader::loadData('wallets_lines');
        $oWalletsLine->id_lender                = $oBid->id_lender_account;
        $oWalletsLine->type_financial_operation = 20;
        $oWalletsLine->id_transaction           = $oTransaction->id_transaction;
        $oWalletsLine->status                   = 1;
        $oWalletsLine->type                     = 2;
        $oWalletsLine->id_bid_remb              = $oBid->id_bid;
        $oWalletsLine->amount                   = $iAmountX100;
        $oWalletsLine->id_project               = $oBid->id_project;
        $oWalletsLine->create();

        /** @var \offres_bienvenues_details $oWelcomeOffer */
        $oWelcomeOffer      = Loader::loadData('offres_bienvenues_details');
        $iWelcomeOfferTotal = $oWelcomeOffer->sum('id_client = ' . $oLenderAccount->id_client_owner . ' AND id_bid = ' . $oBid->id_bid, 'montant');
        if ($iWelcomeOfferTotal > 0) {
            if ($oBid->amount === $iAmountX100) { //Totally credit
                $oWelcomeOffer->montant = min($iWelcomeOfferTotal, $iAmountX100);
            } elseif (($oBid->amount - $iAmountX100) <= $iWelcomeOfferTotal) { //Partially credit
                $oWelcomeOffer->montant = $iWelcomeOfferTotal - ($oBid->amount - $iAmountX100);
            }

            if (false === empty($oWelcomeOffer->montant)) {
                $oWelcomeOffer->id_offre_bienvenue = 0;
                $oWelcomeOffer->id_client          = $oLenderAccount->id_client_owner;
                $oWelcomeOffer->id_bid             = 0;
                $oWelcomeOffer->id_bid_remb        = $oBid->id_bid;
                $oWelcomeOffer->status             = 0;
                $oWelcomeOffer->type               = 2;
                $oWelcomeOffer->create();
            }
        }

        /** @var \notifications $oNotification */
        $oNotification             = Loader::loadData('notifications');
        $oNotification->type       = 1; // rejet
        $oNotification->id_lender  = $oBid->id_lender_account;
        $oNotification->id_project = $oBid->id_project;
        $oNotification->amount     = $iAmountX100;
        $oNotification->id_bid     = $oBid->id_bid;
        $oNotification->create();

        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification                  = Loader::loadData('clients_gestion_mails_notif');
        $oMailNotification->id_client       = $oLenderAccount->id_client_owner;
        $oMailNotification->id_notif        = 3; // rejet
        $oMailNotification->date_notif      = date('Y-m-d H:i:s');
        $oMailNotification->id_notification = $oNotification->id_notification;
        $oMailNotification->id_transaction  = $oTransaction->id_transaction;
        $oMailNotification->create();
    }
}