<?php
namespace Unilend\Service;

use Unilend\core\Loader;
use Symfony\Bridge\Monolog\Logger;
use Unilend\Service\Simulator\EntityManager;

/**
 * Class BidManager
 * @package Unilend\Service
 */
class BidManager
{
    const MODE_REBID_AUTO_BID_CREATE = 1;
    const MODE_REBID_AUTO_BID_UPDATE = 2;

    /** @var \dates */
    private $oDate;

    /** @var \ficelle */
    private $oFicelle;

    /** @var array */
    private $aConfig;

    /** @var Logger */
    private $oLogger;

    /** @var NotificationManager */
    private $oNotificationManager;

    /** @var AutoBidSettingsManager */
    private $oAutoBidSettingsManager;

    /** @var LenderManager */
    private $oLenderManager;

    /** @var EntityManager */
    private $oEntityManager;

    public function __construct(EntityManager $oEntityManager, NotificationManager $oNotificationManager, AutoBidSettingsManager $oAutoBidSettingsManager, LenderManager $oLenderManager)
    {
        $this->aConfig = Loader::loadConfig();

        $this->oEntityManager          = $oEntityManager;
        $this->oNotificationManager    = $oNotificationManager;
        $this->oAutoBidSettingsManager = $oAutoBidSettingsManager;
        $this->oLenderManager          = $oLenderManager;

        $this->oDate    = Loader::loadLib('dates');
        $this->oFicelle = Loader::loadLib('ficelle');
    }

    /**
     * @param Logger $oLogger
     */
    public function setLogger(Logger $oLogger)
    {
        $this->oLogger = $oLogger;
    }

    public function bid(\bids $oBid, $bSendNotification)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->oEntityManager->getRepository('settings');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \transactions $oTransaction */
        $oTransaction = $this->oEntityManager->getRepository('transactions');
        /** @var \wallets_lines $oWalletsLine */
        $oWalletsLine = $this->oEntityManager->getRepository('wallets_lines');
        /** @var \offres_bienvenues_details $oWelcomeOfferDetails */
        $oWelcomeOfferDetails = $this->oEntityManager->getRepository('offres_bienvenues_details');

        $this->oEntityManager->getRepository('transactions_types'); //load for constant use

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
        if (false === $this->oLenderManager->canBid($oLenderAccount)) {
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
                        $oWelcomeOfferDetails->montant            = $iAmountRepayment * 100;
                        $oWelcomeOfferDetails->create();
                    }
                } else {
                    break;
                }
            }
        }

        if ($bSendNotification) {
            $this->oNotificationManager->create(
                \notifications::TYPE_BID_PLACED,
                $oBid->id_autobid > 0 ? \clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID : \clients_gestion_type_notif::TYPE_BID_PLACED,
                $iClientId,
                'sendBidConfirmation',
                $oBid->id_project,
                $fAmount,
                $oBid->id_bid,
                $oTransaction->id_transaction
            );
        }

        return true;
    }

    /**
     * @param \autobid  $oAutoBid
     * @param \projects $oProject
     * @param float     $fRate
     * @param bool      $bSendNotification
     */
    public function bidByAutoBidSettings(\autobid $oAutoBid, \projects $oProject, $fRate, $bSendNotification)
    {
        if ($oAutoBid->rate_min <= $fRate) {
            /** @var \bids $oBid */
            $oBid = $this->oEntityManager->getRepository('bids');
            /** @var \lenders_accounts $LenderAccount */
            $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');

            if ($oLenderAccount->get($oAutoBid->id_lender) && $this->oAutoBidSettingsManager->isOn($oLenderAccount)) {
                $oBid->id_autobid        = $oAutoBid->id_autobid;
                $oBid->id_lender_account = $oAutoBid->id_lender;
                $oBid->id_project        = $oProject->id_project;
                $oBid->amount            = $oAutoBid->amount * 100;
                $oBid->rate              = $fRate;
                $this->bid($oBid, $bSendNotification);
            }
        }
    }

    /**
     * @param \bids $oBid
     * @param bool  $bSendNotification
     */
    public function reject(\bids $oBid, $bSendNotification)
    {
        if ($oBid->status == \bids::STATUS_BID_PENDING || $oBid->status == \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY) {
            $oTransaction = $this->creditRejectedBid($oBid, $oBid->amount / 100);

            if ($bSendNotification) {
                $this->notificationRejection($oBid, $oTransaction);
            }

            $oBid->status = \bids::STATUS_BID_REJECTED;
            $oBid->update();
        }
    }

    /**
     * @param \bids $oBid
     * @param float $fRepaymentAmount
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
        }
    }

    /**
     * @param \bids  $oBid
     * @param string $currentRate
     * @param int    $iMode
     * @param bool   $bSendNotification
     */
    public function reBidAutoBidOrReject(\bids $oBid, $currentRate, $iMode, $bSendNotification)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->oEntityManager->getRepository('autobid');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');

        if (false === empty($oBid->id_autobid) && false === empty($oBid->id_bid) && $oAutoBid->get($oBid->id_autobid)) {
            if (
                bccomp($currentRate, \bids::BID_RATE_MIN, 1) > 0
                && bccomp($currentRate, $oAutoBid->rate_min, 1) >= 0
                && $oLenderAccount->get($oBid->id_lender_account)
                && $oClient->get($oLenderAccount->id_client_owner)
                && $oClient->status == \clients::STATUS_ONLINE
            ) { //check online/offline instead of LenderManager::canBid() because of the performance issue.
                if (self::MODE_REBID_AUTO_BID_CREATE === $iMode) {
                    $iBidOrder = $oBid->counter('id_project = ' . $oBid->id_project) + 1;

                    $oNewBid         = clone $oBid;
                    $oNewBid->ordre  = $iBidOrder;
                    $oNewBid->rate   = $currentRate;
                    $oNewBid->status = \bids::STATUS_BID_PENDING;
                    $oNewBid->create();

                    $oBid->status = \bids::STATUS_BID_REJECTED;
                    $oBid->update();
                } else {
                    $oBid->rate   = $currentRate;
                    $oBid->status = \bids::STATUS_BID_PENDING;
                    $oBid->update();
                }
            } else {
                $this->reject($oBid, $bSendNotification);
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
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \transactions $oTransaction */
        $oTransaction = $this->oEntityManager->getRepository('transactions');
        /** @var \wallets_lines $oWalletsLine */
        $oWalletsLine = $this->oEntityManager->getRepository('wallets_lines');
        /** @var \offres_bienvenues_details $oWelcomeOfferDetails */
        $oWelcomeOfferDetails = $this->oEntityManager->getRepository('offres_bienvenues_details');
        // Loaded for class constants
        $this->oEntityManager->getRepository('transactions_types');

        $oLenderAccount->get($oBid->id_lender_account, 'id_lender_account');
        $fAmountX100 = $fAmount * 100;

        $oTransaction->id_client        = $oLenderAccount->id_client_owner;
        $oTransaction->montant          = $fAmountX100;
        $oTransaction->id_langue        = 'fr';
        $oTransaction->date_transaction = date('Y-m-d H:i:s');
        $oTransaction->status           = \transactions::PAYMENT_STATUS_OK;
        $oTransaction->etat             = \transactions::STATUS_VALID;
        $oTransaction->id_project       = $oBid->id_project;
        $oTransaction->ip_client        = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
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
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        if ($oLenderAccount->get($oBid->id_lender_account)) {
            $this->oNotificationManager->create(
                \notifications::TYPE_BID_REJECTED,
                $oBid->id_autobid > 0 ? \clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID : \clients_gestion_type_notif::TYPE_BID_REJECTED,
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
