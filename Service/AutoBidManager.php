<?php
namespace Unilend\Service;

use Unilend\core\Loader;

/**
 * Class AutoBidManager
 * @package Unilend\Service
 */
class AutoBidManager
{
    const AUTO_BID_ON  = 1;
    const AUTO_BID_OFF = 0;

    /** @var BidManager */
    private $oBidManager;

    public function __construct()
    {
        $this->oBidManager = Loader::loadService('BidManager');
    }

    /**
     * @param \clients $oClient
     */
    public function on(\clients $oClient)
    {
        /** @var \lenders_accounts $oLendersAccount */
        $oLendersAccount = Loader::loadData('lenders_accounts');
        /** @var \autobid_queue $oAutoBidQueue */
        $oAutoBidQueue = Loader::loadData('autobid_queue');

        if ($this->isQualified($oClient) && $this->oBidManager->canBid($oClient)) {
            $this->onOff($oClient, self::AUTO_BID_ON);

            $oLendersAccount->get($oClient->id_client, 'id_client_owner');
            $oAutoBidQueue->addToQueue($oLendersAccount->id_lender_account, \autobid_queue::TYPE_QUEUE_NEW);
        }
    }

    /**
     * @param \clients $oClient
     */
    public function off(\clients $oClient)
    {
        /** @var \lenders_accounts $oLendersAccount */
        $oLendersAccount = Loader::loadData('lenders_accounts');
        /** @var \autobid_queue $oAutoBidQueue */
        $oAutoBidQueue = Loader::loadData('autobid_queue');

        $this->onOff($oClient, self::AUTO_BID_OFF);

        $oLendersAccount->get($oClient->id_client, 'id_client_owner');
        $oAutoBidQueue->delete($oLendersAccount->id_lender_account, 'id_lender');
    }

    /**
     * @param \clients $oClient
     * @param          $iAutoBidOnOff
     */
    private function onOff(\clients $oClient, $iAutoBidOnOff)
    {
        /** @var \client_settings $oClientSettings */
        $oClientSettings = Loader::loadData('client_settings');
        /** @var \clients_history_actions $oClientHistoryActions */
        $oClientHistoryActions = Loader::loadData('clients_history_actions');

        if ($oClientSettings->get($oClient->id_client, 'id_type = ' . \client_setting_type::TYPE_AUTO_BID_SWITCH . ' AND id_client')) {
            $oClientSettings->value = $iAutoBidOnOff;
            $oClientSettings->update();
        } else {
            $oClientSettings->unsetData();
            $oClientSettings->id_client = $oClient->id_client;
            $oClientSettings->id_type   = \client_setting_type::TYPE_AUTO_BID_SWITCH;
            $oClientSettings->value     = $iAutoBidOnOff;
            $oClientSettings->create();
        }
        // BO user
        $iUserId     = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : null;
        $sOnOff      = $iAutoBidOnOff === self::AUTO_BID_ON ? 'on' : 'off';
        $sSerialized = serialize(array('id_user' => $iUserId, 'id_client' => $oClient->id_client, 'autobid_switch' => $sOnOff));
        $oClientHistoryActions->histo(20, 'autobid_on_off', $oClient->id_client, $sSerialized);
    }

    /**
     * @param \clients $oClient
     *
     * @return bool
     */
    public function isQualified(\clients $oClient)
    {
        /** @var \settings $oSettings */
        $oSettings = Loader::loadData('settings');
        /** @var \client_settings $oClientSettings */
        $oClientSettings = Loader::loadData('client_settings');

        $oSettings->get('Auto-bid global switch', 'type');
        $bGlobalActive = (bool)$oSettings->value;

        $bBetaTester = false;
        if (false === empty($oClient->id_client) && true === (bool)$oClientSettings->getSetting($oClient->id_client, \client_setting_type::TYPE_AUTO_BID_BETA_TESTER)) {
            $bBetaTester = true;
        }

        if ($bGlobalActive || $bBetaTester) {
            return true;
        }

        return false;
    }

    /**
     * @param $iLenderId
     * @param $sEvaluation
     * @param $iAutoBidPeriodId
     * @param $fRate
     * @param $iAmount
     *
     * @return bool
     */
    public function saveSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount)
    {
        /** @var \settings $oSettings */
        $oSettings = Loader::loadData('settings');
        /** @var \autobid $oAutoBid */
        $oAutoBid = Loader::loadData('autobid');
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');

        $oSettings->get('Pret min', 'type');
        $iAmountMin = (int)$oSettings->value;

        if ($iAmount < $iAmountMin) {
            return false;
        }

        if ($fRate < 4 || $fRate > 10) {
            return false;
        }
        if ($oAutoBid->exist($iLenderId, 'evaluation = "' . $sEvaluation . '" AND id_autobid_period = ' . $iAutoBidPeriodId . ' AND status != ' . \autobid::STATUS_ARCHIVED . ' AND id_lender')) {
            $aAutoBids = $oAutoBid->select('evaluation = "' . $sEvaluation . '" AND id_autobid_period = ' . $iAutoBidPeriodId . ' AND status != ' . \autobid::STATUS_ARCHIVED . ' AND id_lender = ' . $iLenderId);

            if ($oBid->exist($aAutoBids[0]['id_autobid'], 'id_autobid')) {
                foreach ($aAutoBids as $aBid) {
                    $oAutoBid->get($aBid['id_autobid']);
                    $oAutoBid->status = \autobid::STATUS_ARCHIVED;
                    $oAutoBid->update();
                }
                $this->createSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount);
            } else {
                $aAutoBidActive = array_shift($aAutoBids);
                $oAutoBid->get($aAutoBidActive['id_autobid']);
                $oAutoBid->rate_min = $fRate;
                $oAutoBid->amount   = $iAmount;
                $oAutoBid->update();

                foreach ($aAutoBids as $aBid) {
                    $oAutoBid->get($aBid['id_autobid']);
                    $oAutoBid->status = \autobid::STATUS_ARCHIVED;
                    $oAutoBid->update();
                }
            }
        } else {
            $this->createSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount);
        }
    }

    /**
     * @param $iLenderId
     * @param $sEvaluation
     * @param $iAutoBidPeriodId
     * @param $fRate
     * @param $iAmount
     *
     * @return bool
     */
    private function createSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = Loader::loadData('autobid');
        /** @var \autobid_queue $oAutoBidQueue */
        $oAutoBidQueue = Loader::loadData('autobid_queue');

        $oAutoBid->id_lender         = $iLenderId;
        $oAutoBid->status            = \autobid::STATUS_ACTIVE;
        $oAutoBid->evaluation        = $sEvaluation;
        $oAutoBid->id_autobid_period = $iAutoBidPeriodId;
        $oAutoBid->rate_min          = $fRate;
        $oAutoBid->amount            = $iAmount;
        $oAutoBid->create();

        if (false === $oAutoBidQueue->exist($iLenderId, 'id_lender')) {
            $oAutoBidQueue->addToQueue($iLenderId, \autobid_queue::TYPE_QUEUE_NEW);
        }
    }

    /**
     * @param     $iLenderId
     * @param     $sEvaluation
     * @param     $iAutoBidPeriodId
     * @param     $fRate
     * @param     $fAmount
     * @param int $iStatus
     *
     * @return mixed
     */
    public function getSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $fAmount, $iStatus = \autobid::STATUS_ACTIVE)
    {
        return Loader::loadData('autobid')->get(
            $iLenderId,
            'status = ' . $iStatus . ' AND evaluation = "' . $sEvaluation . '"" AND id_autobid_period = '
            . $iAutoBidPeriodId . ' AND rate_min = ' . $fRate . ' AND amount = ' . $fAmount . ' AND id_lender'
        );
    }

    public function bid(\autobid $oAutoBid, $oProject, $fRate)
    {
        if ($oAutoBid->rate_min <= $fRate) {
            /** @var \bids $oBid */
            $oBid = Loader::loadData('bids');
            /** @var \autobid_queue $oAutoBidQueue */
            $oAutoBidQueue = Loader::loadData('autobid_queue');

            $oBid->id_autobid        = $oAutoBid->id_autobid;
            $oBid->id_lender_account = $oAutoBid->id_lender;
            $oBid->id_project        = $oProject->id_project;
            $oBid->amount            = $oAutoBid->amount * 100;
            $oBid->rate              = $fRate;
            if ($this->oBidManager->bid($oBid)) {
                $oAutoBidQueue->addToQueue($oAutoBid->id_lender, \autobid_queue::TYPE_QUEUE_BID);
            }
        }
    }

    public function refreshRateOrReject(\bids $oBid, $fCurrentRate)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = Loader::loadData('autobid');
        if (false === empty($oBid->id_autobid) && false === empty($oBid->id_bid) && $oAutoBid->get($oBid->id_autobid)) {
            if ($oAutoBid->rate_min <= $fCurrentRate) {
                $oBid->status = \bids::STATUS_BID_PENDING;
                $oBid->rate   = $fCurrentRate;
                $oBid->update();
            } else {
                $this->oBidManager->reject($oBid);
            }
        }
    }

    public function isNovice($iLenderId)
    {
        $oAutobid        = Loader::loadData('autobid');

        if ($oAutobid->counter('id_lender = ' . $iLenderId ) === 0 ) {
            $bIsNovice = true;
        } else {
            if ($oAutobid->select('id_lender = ' . $iLenderId . ' AND status = ' . \autobid::STATUS_INACTIVE, null, null, 1)) {
                $bIsNovice = false;
            } else {
                $aAutobids = $oAutobid->select('id_lender = ' . $iLenderId . ' AND status = ' . \autobid::STATUS_ACTIVE);

                $fRate   = $aAutobids[0]['rate_min'];
                $iAmount = $aAutobids[0]['amount'];

                foreach ($aAutobids as $aAutobid) {
                    if ($fRate !== $aAutobid['rate_min'] || $iAmount !== $aAutobid['amount']) {
                        $bIsNovice = false;
                        break;
                    } else {
                        $fRate   = $aAutobid['rate_min'];
                        $iAmount = $aAutobid['amount'];
                        $bIsNovice = true;
                    }
                }
            }
        }

        return $bIsNovice;
    }

    public function saveNoviceSetting($iLenderId, $fRate, $iAmount)
    {
        $oAutoBidPeriods = Loader::loadData('autobid_periods');
        $aAutoBidPeriods = $oAutoBidPeriods->select();
        $aRiskValues     = array("A", "B", "C", "D", "E");

        foreach ($aAutoBidPeriods as $aPeriod) {
            foreach ($aRiskValues as $sEvaluation) {
                $this->saveSetting($iLenderId, $sEvaluation, $aPeriod['id_period'], $fRate, $iAmount);
            }
        }
    }
}