<?php
namespace Unilend\Service;

use Unilend\core\Loader;
use Unilend\librairies\ULogger;

/**
 * Class BidManager
 * @package Unilend\Service
 */
class BidManager
{
    private $sLanguage;
    /** @var \dates */
    private $oDate;
    /** @var \ficelle */
    private $oFicelle;
    /** @var \tnmp */
    private $oTNMP;
    /** @var \email */
    private $oEmail;
    /** @var array */
    private $aConfig;
    /** @var  ULogger */
    private $oLogger;
    /** @var MailerManager */
    private $oMailerManager;

    public function __construct()
    {
        $this->aConfig = Loader::loadConfig();

        $this->oNMP       = Loader::loadData('nmp');
        $this->oNMPDesabo = Loader::loadData('nmp_desabo');

        $this->oDate    = Loader::loadLib('dates');
        $this->oFicelle = Loader::loadLib('ficelle');

        $this->oTNMP  = Loader::loadLib('tnmp', array($this->oNMP, $this->oNMPDesabo, $this->aConfig['env']));
        $this->oEmail = Loader::loadLib('email');

        $this->oMailerManager = Loader::loadService('MailerManager');

        $this->sLanguage = 'fr';
    }

    /**
     * @param ULogger $oLogger
     */
    public function setLogger(ULogger $oLogger)
    {
        $this->oLogger = $oLogger;
    }

    public function bid(\bids $oBid)
    {
        /** @var \settings $oSettings */
        $oSettings = Loader::loadData('settings');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');
        /** @var \clients_status $oClientStatus */
        $oClientStatus = Loader::loadData('clients_status');
        /** @var \transactions $oTransaction */
        $oTransaction = Loader::loadData('transactions');
        /** @var \wallets_lines $oWalletsLine */
        $oWalletsLine = Loader::loadData('wallets_lines');
        /** @var \offres_bienvenues_details $oWelcomeOfferDetails */
        $oWelcomeOfferDetails = Loader::loadData('offres_bienvenues_details');
        /** @var \notifications $oNotification */
        $oNotification = Loader::loadData('notifications');
        /** @var \clients_gestion_notifications $oNotificationSettings */
        $oNotificationSettings = Loader::loadData('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = Loader::loadData('clients_gestion_mails_notif');

        $oSettings->get('Pret min', 'type');
        $iAmountMin = (int)$oSettings->value;

        $iLenderId   = $oBid->id_lender_account;
        $iProjectId  = $oBid->id_project;
        $fAmountX100 = $oBid->amount;
        $fAmount     = $oBid->amount / 100;

        if ($iAmountMin > $fAmount) {
            return false;
        }

        if (false === $oLenderAccount->get($iLenderId)) {
            return false;
        }

        $iClientId = $oLenderAccount->id_client_owner;

        if ($oClientStatus->getLastStatut($iClientId)) {
            if ($oClientStatus->status < 60) {
                return false;
            }
        } else {
            return false;
        }

        $iBalance = $oTransaction->getSolde($iClientId);
        if ($iBalance < $fAmount) {
            return false;
        }

        $oTransaction->id_client        = $iClientId;
        $oTransaction->montant          = -$fAmountX100;
        $oTransaction->id_langue        = 'fr';
        $oTransaction->date_transaction = date('Y-m-d H:i:s');
        $oTransaction->status           = \transactions::PAYMENT_STATUS_OK;
        $oTransaction->etat             = \transactions::STATUS_VALID;
        $oTransaction->id_project       = $iProjectId;
        $oTransaction->transaction      = \transactions::VIRTUAL;
        $oTransaction->type_transaction = \transactions_types::TYPE_LENDER_LOAN;
        $oTransaction->create();

        $oWalletsLine->id_lender                = $oBid->id_lender_account;
        $oWalletsLine->type_financial_operation = \wallets_lines::TYPE_BID;
        $oWalletsLine->id_transaction           = $oTransaction->id_transaction;
        $oWalletsLine->status                   = \wallets_lines::STATUS_VALID;
        $oWalletsLine->type                     = \wallets_lines::VIRTUAL;
        $oWalletsLine->amount                   = -$fAmountX100;
        $oWalletsLine->id_project               = $oBid->id_project;
        $oWalletsLine->create();

        $iBidNb = $oBid->counter('id_project = ' . $oBid->id_project);
        $iBidNb++;

        $oBid->id_lender_wallet_line = $oWalletsLine->id_wallet_line;
        $oBid->ordre                 = $iBidNb;
        $oBid->create();

        // Liste des offres non utilisées
        $aAllOffers = $oWelcomeOfferDetails->select('id_client = ' . $iClientId . ' AND status = 0');
        if ($aAllOffers != false) {
            $iOfferTotal = 0;
            foreach ($aAllOffers as $aOffer) {
                if ($iOfferTotal <= $fAmount) {
                    $iOfferTotal += ($aOffer['montant'] / 100); // total des offres

                    $oWelcomeOfferDetails->get($aOffer['id_offre_bienvenue_detail'], 'id_offre_bienvenue_detail');
                    $oWelcomeOfferDetails->status = \offres_bienvenues_details::STATUS_USED;
                    $oWelcomeOfferDetails->id_bid = $oBid->id_bid;
                    $oWelcomeOfferDetails->update();

                    // Apres addition de la derniere offre on se rend compte que le total depasse
                    if ($iOfferTotal > $fAmount) {
                        // On fait la diff et on créer un remb du trop plein d'offres
                        $iAmountRepayment = $iOfferTotal - $fAmount;
                        $oWelcomeOfferDetails->unsetData();
                        $oWelcomeOfferDetails->id_offre_bienvenue = 0;
                        $oWelcomeOfferDetails->id_client          = $iClientId;
                        $oWelcomeOfferDetails->id_bid             = 0;
                        $oWelcomeOfferDetails->id_bid_remb        = $oBid->id_bid;
                        $oWelcomeOfferDetails->status             = \offres_bienvenues_details::STATUS_NEW;
                        $oWelcomeOfferDetails->type               = \offres_bienvenues_details::TYPE_CUT;
                        $oWelcomeOfferDetails->montant            = ($iAmountRepayment * 100);
                        $oWelcomeOfferDetails->create();
                    }
                } else {
                    break;
                }
            }
        }

        ///// NOTIFICATION OFFRE PLACEE ///////
        $oNotification->type       = \clients_gestion_type_notif::TYPE_BID_PLACED;
        $oNotification->id_lender  = $oBid->id_lender_account;
        $oNotification->id_project = $oBid->id_project;
        $oNotification->amount     = $fAmountX100;
        $oNotification->id_bid     = $oBid->id_bid;
        $oNotification->create();
        ///// FIN NOTIFICATION OFFRE PLACEE ///////
        if ($oNotificationSettings->getNotif($iClientId, \clients_gestion_type_notif::TYPE_BID_PLACED, 'immediatement') == true) {
            $this->oMailerManager->sendBidConfirmation($oBid);
            $oMailNotification->immediatement = 1;
        } else {
            $oMailNotification->immediatement = 0;
        }

        $oMailNotification->id_client       = $iClientId;
        $oMailNotification->id_notif        = \clients_gestion_type_notif::TYPE_BID_PLACED;
        $oMailNotification->date_notif      = date('Y-m-d H:i:s');
        $oMailNotification->id_notification = $oNotification->id_notification;
        $oMailNotification->id_transaction  = $oTransaction->id_transaction;
        $oMailNotification->create();

        return true;
    }

    /**
     * @param \bids $oBid
     */
    public function reject(\bids $oBid)
    {
        if ($oBid->status == \bids::STATUS_BID_PENDING || $oBid->status == \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY) {
            $this->credit($oBid, $oBid->amount / 100);
            $oBid->status = \bids::STATUS_BID_REJECTED;
            $oBid->update();
            if (false === empty($oBid->id_autobid)) {
                /** @var \autobid_queue $oAutoBidQueue */
                $oAutoBidQueue = Loader::loadData('autobid_queue');
                $oAutoBidQueue->addToQueue($oBid->id_lender_account, \autobid_queue::TYPE_QUEUE_REJECTED);
            }
        }
    }

    public function rejectPartially(\bids $oBid, $fRepaymentAmount)
    {
        if ($oBid->status == \bids::STATUS_BID_PENDING || $oBid->status == \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY) {
            $this->credit($oBid, $fRepaymentAmount);
            // Save new amount of the bid after repayment
            $oBid->amount -= $fRepaymentAmount * 100;
            $oBid->status = \bids::STATUS_BID_ACCEPTED;
            $oBid->update();
        }
    }

    private function credit($oBid, $fAmount)
    {
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');
        /** @var \transactions $oTransaction */
        $oTransaction = Loader::loadData('transactions');
        /** @var \wallets_lines $oWalletsLine */
        $oWalletsLine = Loader::loadData('wallets_lines');
        /** @var \offres_bienvenues_details $oWelcomeOfferDetails */
        $oWelcomeOfferDetails = Loader::loadData('offres_bienvenues_details');
        /** @var \notifications $oNotification */
        $oNotification = Loader::loadData('notifications');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = Loader::loadData('clients_gestion_mails_notif');

        $oLenderAccount->get($oBid->id_lender_account, 'id_lender_account');
        $fAmountX100 = $fAmount * 100;

        $oTransaction->id_client        = $oLenderAccount->id_client_owner;
        $oTransaction->montant          = $fAmountX100;
        $oTransaction->id_langue        = 'fr';
        $oTransaction->date_transaction = date('Y-m-d H:i:s');
        $oTransaction->status           = \transactions::PAYMENT_STATUS_OK;
        $oTransaction->etat             = \transactions::STATUS_VALID;
        $oTransaction->id_project       = $oBid->id_project;
        $oTransaction->ip_client        = $_SERVER['REMOTE_ADDR'];
        $oTransaction->type_transaction = \transactions_types::TYPE_LENDER_LOAN;
        $oTransaction->id_bid_remb      = $oBid->id_bid;
        $oTransaction->transaction      = \transactions::VIRTUAL;
        $oTransaction->create();

        $oWalletsLine->id_lender                = $oBid->id_lender_account;
        $oWalletsLine->type_financial_operation = \wallets_lines::TYPE_BID;
        $oWalletsLine->id_transaction           = $oTransaction->id_transaction;
        $oWalletsLine->status                   = \wallets_lines::STATUS_VALID;
        $oWalletsLine->type                     = \wallets_lines::VIRTUAL;
        $oWalletsLine->id_bid_remb              = $oBid->id_bid;
        $oWalletsLine->amount                   = $fAmountX100;
        $oWalletsLine->id_project               = $oBid->id_project;
        $oWalletsLine->create();

        $iWelcomeOfferTotal = $oWelcomeOfferDetails->sum('id_client = ' . $oLenderAccount->id_client_owner . ' AND id_bid = ' . $oBid->id_bid, 'montant');
        if ($iWelcomeOfferTotal > 0) {
            if ($oBid->amount === $fAmountX100) { //Totally credit
                $oWelcomeOfferDetails->montant = min($iWelcomeOfferTotal, $fAmountX100);
            } elseif (($oBid->amount - $fAmountX100) <= $iWelcomeOfferTotal
            ) { //Partially credit
                $oWelcomeOfferDetails->montant = $iWelcomeOfferTotal - ($oBid->amount - $fAmountX100);
            }

            if (false === empty($oWelcomeOfferDetails->montant)) {
                $oWelcomeOfferDetails->unsetData();
                $oWelcomeOfferDetails->id_offre_bienvenue = 0;
                $oWelcomeOfferDetails->id_client          = $oLenderAccount->id_client_owner;
                $oWelcomeOfferDetails->id_bid             = 0;
                $oWelcomeOfferDetails->id_bid_remb        = $oBid->id_bid;
                $oWelcomeOfferDetails->status             = \offres_bienvenues_details::STATUS_NEW;
                $oWelcomeOfferDetails->type               = \offres_bienvenues_details::TYPE_PAYBACK;
                $oWelcomeOfferDetails->create();
            }
        }

        $oNotification->type       = \notifications::TYPE_BID_REJECTED; // rejet
        $oNotification->id_lender  = $oBid->id_lender_account;
        $oNotification->id_project = $oBid->id_project;
        $oNotification->amount     = $fAmountX100;
        $oNotification->id_bid     = $oBid->id_bid;
        $oNotification->create();

        $oMailNotification->id_client       = $oLenderAccount->id_client_owner;
        $oMailNotification->id_notif        = \clients_gestion_type_notif::TYPE_BID_REJECTED;
        $oMailNotification->date_notif      = date('Y-m-d H:i:s');
        $oMailNotification->id_notification = $oNotification->id_notification;
        $oMailNotification->id_transaction  = $oTransaction->id_transaction;
        $oMailNotification->create();
    }
}