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
    /** @var string */
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

    /** @var ULogger */
    private $oLogger;

    /** @var NotificationManager */
    private $oNotificationManager;
    /** @var AutoBidSettingsManager */
    private $oAutoBidManager;

    public function __construct()
    {
        $this->aConfig = Loader::loadConfig();

        $this->oNMP       = Loader::loadData('nmp');
        $this->oNMPDesabo = Loader::loadData('nmp_desabo');

        $this->oDate    = Loader::loadLib('dates');
        $this->oFicelle = Loader::loadLib('ficelle');

        $this->oTNMP  = Loader::loadLib('tnmp', array($this->oNMP, $this->oNMPDesabo, $this->aConfig['env']));
        $this->oEmail = Loader::loadLib('email');

        $this->oNotificationManager = Loader::loadService('NotificationManager');
        $this->oAutoBidManager      = Loader::loadService('AutoBidManager');

        $this->sLanguage = 'fr';
    }

    /**
     * @param ULogger $oLogger
     */
    public function setLogger(ULogger $oLogger)
    {
        $this->oLogger = $oLogger;
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return bool
     */
    public function canBid(\lenders_accounts $oLenderAccount)
    {
        /** @var \clients_status $oClientStatus */
        $oClientStatus = Loader::loadData('clients_status');
        if ($oClientStatus->getLastStatut($oLenderAccount->id_client_owner) && $oClientStatus->status == 60) {
            return true;
        }
        return false;
    }

    public function bid(\bids $oBid)
    {
        /** @var \settings $oSettings */
        $oSettings = Loader::loadData('settings');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');
        /** @var \clients_status $oClient */
        $oClient = Loader::loadData('clients');
        /** @var \transactions $oTransaction */
        $oTransaction = Loader::loadData('transactions');
        /** @var \wallets_lines $oWalletsLine */
        $oWalletsLine = Loader::loadData('wallets_lines');
        /** @var \offres_bienvenues_details $oWelcomeOfferDetails */
        $oWelcomeOfferDetails = Loader::loadData('offres_bienvenues_details');
        /** @var \autobid_queue $oAutoBidQueue */
        $oAutoBidQueue = Loader::loadData('autobid_queue');

        $oSettings->get('Pret min', 'type');
        $iAmountMin = (int)$oSettings->value;

        $iLenderId   = $oBid->id_lender_account;
        $iProjectId  = $oBid->id_project;
        $fAmountX100 = $oBid->amount;
        $fAmount     = $oBid->amount / 100;
        $fRate       = round(floatval($oBid->rate), 1);

        if ($iAmountMin > $fAmount) {
            return false;
        }

        if ($fRate > \bids::BID_RATE_MAX || $fRate < \bids::BID_RATE_MIN) {
            return false;
        }

        if (false === $oLenderAccount->get($iLenderId)) {
            return false;
        }

        $iClientId = $oLenderAccount->id_client_owner;
        if (false === $oClient->get($iClientId) || false === $this->canBid($oClient)) {
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

        if (false === empty($oBid->id_autobid)) {
            $oAutoBidQueue->addToQueue($oBid->id_lender_account, \autobid_queue::TYPE_QUEUE_BID);
        }

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
                        $oWelcomeOfferDetails->montant            = $iAmountRepayment * 100;
                        $oWelcomeOfferDetails->create();
                    }
                } else {
                    break;
                }
            }
        }

        $this->oNotificationManager->create(
            \notifications::TYPE_BID_PLACED,
            \clients_gestion_type_notif::TYPE_BID_PLACED,
            $iClientId,
            'sendBidConfirmation',
            $oBid->id_project,
            $fAmount,
            $oBid->id_bid,
            $oTransaction->id_transaction
        );

        return true;
    }

    /**
     * @param \autobid  $oAutoBid
     * @param \projects $oProject
     * @param float     $fRate
     */
    public function bidByAutoBidSettings(\autobid $oAutoBid, \projects $oProject, $fRate)
    {
        if ($oAutoBid->rate_min <= $fRate) {
            /** @var \bids $oBid */
            $oBid = Loader::loadData('bids');
            /** @var \lenders_accounts $LenderAccount */
            $oLenderAccount = Loader::loadData('lenders_accounts');

            if ($oLenderAccount->get($oAutoBid->id_lender) && $this->oAutoBidManager->isOn($oLenderAccount)) {
                $oBid->id_autobid        = $oAutoBid->id_autobid;
                $oBid->id_lender_account = $oAutoBid->id_lender;
                $oBid->id_project        = $oProject->id_project;
                $oBid->amount            = $oAutoBid->amount * 100;
                $oBid->rate              = $fRate;
                $this->bid($oBid);
            }
        }
    }

    /**
     * @param \bids $oBid
     */
    public function reject(\bids $oBid)
    {
        if ($oBid->status == \bids::STATUS_BID_PENDING || $oBid->status == \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY) {
            $oTransaction = $this->creditRejectedBid($oBid, $oBid->amount / 100);
            $this->notificationRejection($oBid, $oTransaction);
            $oBid->status = \bids::STATUS_BID_REJECTED;
            //todo : do a hotfix to remove status_email_bid_ko when all the old ko mail are sent.
            $oBid->status_email_bid_ko = 1;
            $oBid->update();

            if (false === empty($oBid->id_autobid)) {
                /** @var \autobid_queue $oAutoBidQueue */
                $oAutoBidQueue = Loader::loadData('autobid_queue');
                $oAutoBidQueue->addToQueue($oBid->id_lender_account, \autobid_queue::TYPE_QUEUE_REJECTED);
            }
        }
    }

    /**
     * @param \bids $oBid
     * @param       $fRepaymentAmount
     */
    public function rejectPartially(\bids $oBid, $fRepaymentAmount)
    {
        if ($oBid->status == \bids::STATUS_BID_PENDING || $oBid->status == \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY) {
            $oTransaction = $this->creditRejectedBid($oBid, $fRepaymentAmount);
            $this->notificationRejection($oBid, $oTransaction);
            // Save new amount of the bid after repayment
            $oBid->amount -= $fRepaymentAmount * 100;
            $oBid->status = \bids::STATUS_BID_ACCEPTED;
            $oBid->update();
            // We don't update the auto-bid queue, because the bid is accepted partially.
        }
    }

    /**
     * @param \bids $oBid
     * @param float $fCurrentRate
     */
    public function refreshAutoBidRateOrReject(\bids $oBid, $fCurrentRate)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = Loader::loadData('autobid');
        if (false === empty($oBid->id_autobid) && false === empty($oBid->id_bid) && $oAutoBid->get($oBid->id_autobid)) {
            if ($oAutoBid->rate_min <= $fCurrentRate) {
                $oBid->status = \bids::STATUS_BID_PENDING;
                $oBid->rate   = $fCurrentRate;
                $oBid->update();
            } else {
                $this->reject($oBid);
            }
        }
    }

    /**
     * @param $oBid
     * @param $fAmount
     *
     * @return \transactions
     */
    private function creditRejectedBid($oBid, $fAmount)
    {
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');
        /** @var \transactions $oTransaction */
        $oTransaction = Loader::loadData('transactions');
        /** @var \wallets_lines $oWalletsLine */
        $oWalletsLine = Loader::loadData('wallets_lines');
        /** @var \offres_bienvenues_details $oWelcomeOfferDetails */
        $oWelcomeOfferDetails = Loader::loadData('offres_bienvenues_details');

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

        return $oTransaction;

    }

    /**
     * @param \bids         $oBid
     * @param \transactions $oTransaction
     */
    private function notificationRejection(\bids $oBid, \transactions $oTransaction)
    {
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');
        if ($oLenderAccount->get($oBid->id_lender_account)) {
            $this->oNotificationManager->create(
                \notifications::TYPE_BID_REJECTED,
                \clients_gestion_type_notif::TYPE_BID_REJECTED,
                $oLenderAccount->id_client_owner,
                'sendBidRejected',
                $oBid->id_project,
                $oTransaction->montant / 100,
                $oBid->id_bid,
                $oTransaction->id_transaction
            );
        }
    }
}
