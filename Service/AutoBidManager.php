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
     * @param $iClientId
     */
    public function on($iClientId)
    {
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');
        if ($this->isQualified($iClientId) && $oClient->getLastStatut($iClientId)) {
            $this->onOff($iClientId, self::AUTO_BID_ON);
        }
    }

    /**
     * @param $iClientId
     */
    public function off($iClientId)
    {
        $this->onOff($iClientId, self::AUTO_BID_OFF);
    }

    /**
     * @param $iClientId
     * @param $iAutoBidOnOff
     *
     * @return bool
     */
    private function onOff($iClientId, $iAutoBidOnOff)
    {
        /** @var \client_settings $oClientSettings */
        $oClientSettings = Loader::loadData('client_settings');
        /** @var \clients_history_actions $oClientHistoryActions */
        $oClientHistoryActions = Loader::loadData('clients_history_actions');

        if ($oClientSettings->get($iClientId, 'id_type = ' . \client_setting_type::TYPE_AUTO_BID_SWITCH . ' AND id_client')) {
            $oClientSettings->value = $iAutoBidOnOff;
            $oClientSettings->update();
        } else {
            $oClientSettings->unsetData();
            $oClientSettings->id_client = $iClientId;
            $oClientSettings->id_type   = \client_setting_type::TYPE_AUTO_BID_SWITCH;
            $oClientSettings->value     = $iAutoBidOnOff;
            $oClientSettings->create();
        }
        // BO user
        $iUserId     = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : null;
        $sOnOff      = $iAutoBidOnOff === self::AUTO_BID_ON ? 'on' : 'off';
        $sSerialized = serialize(array('id_user' => $iUserId, 'id_client' => $iClientId, 'autobid_switch' => $sOnOff));
        $oClientHistoryActions->histo(20, 'autobid_on_off', $iClientId, $sSerialized);
    }

    /**
     * @param $iClientId
     *
     * @return bool
     */
    public function isQualified($iClientId)
    {
        /** @var \settings $oSettings */
        $oSettings = Loader::loadData('settings');
        /** @var \client_settings $oClientSettings */
        $oClientSettings = Loader::loadData('client_settings');

        $oSettings->get('Auto-bid global switch', 'type');
        $bGlobalActive = (bool)$oSettings->value;

        if ((true === $bGlobalActive || true === (bool)$oClientSettings->getSetting($iClientId, \client_setting_type::TYPE_AUTO_BID_BETA_TESTER))) {
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
        $oAutoBid                    = Loader::loadData('autobid');
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
            $oAutoBidQueue->unsetData();
            $oAutoBidQueue->id_lender = $iLenderId;
            $oAutoBidQueue->status    = \autobid_queue::STATUS_NEW;
            $oAutoBidQueue->create();
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
            $oBid                    = Loader::loadData('bids');
            /** @var \autobid_queue $oAutoBidQueue */
            $oAutoBidQueue = Loader::loadData('autobid_queue');

            $oBid->id_autobid        = $oAutoBid->id_autobid;
            $oBid->id_lender_account = $oProject->id_lender;
            $oBid->id_project        = $oProject->id_project;
            $oBid->amount            = $oAutoBid->amount * 100;
            $oBid->rate              = $fRate;
            $this->oBidManager->bid($oBid);
            $oAutoBidQueue->addToQueue($oAutoBid->id_lender, \autobid_queue::STATUS_NEW);
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
                $this->reject($oBid);
            }
        }
    }

    public function reject(\bids $oBid)
    {
        if (false === empty($oBid->id_bid)) {
            $this->oBidManager->reject($oBid);
            /** @var \autobid_queue $oAutoBidQueue */
            $oAutoBidQueue = Loader::loadData('autobid_queue');
            $oAutoBidQueue->addToQueue($oBid->id_lender_account, \autobid_queue::STATUS_TOP);
        }
    }
}