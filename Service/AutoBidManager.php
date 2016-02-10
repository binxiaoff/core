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

    /**
     * @param $iClientId
     */
    public static function on($iClientId)
    {
        if (self::isQualified($iClientId)) {
            self::onOff($iClientId, self::AUTO_BID_ON);
        }
    }

    /**
     * @param $iClientId
     */
    public static function off($iClientId)
    {
        self::onOff($iClientId, self::AUTO_BID_OFF);
    }

    /**
     * @param $iClientId
     * @param $iAutoBidOnOff
     *
     * @return bool
     */
    private static function onOff($iClientId, $iAutoBidOnOff)
    {
        /** @var \client_settings $oClientSettings */
        $oClientSettings = Loader::loadData('client_settings');

        if ($oClientSettings->get($iClientId, 'id_type = ' . \client_setting_type::TYPE_AUTO_BID_SWITCH . ' AND id_client')) {
            $oClientSettings->value = $iAutoBidOnOff;
            $oClientSettings->update();
        } else {
            $oClientSettings->id_client = $iClientId;
            $oClientSettings->id_type   = \client_setting_type::TYPE_AUTO_BID_SWITCH;
            $oClientSettings->value     = $iAutoBidOnOff;
            $oClientSettings->create();
        }

        /** @var \clients_history_actions $oClientHistoryActions */
        $oClientHistoryActions = Loader::loadData('clients_history_actions');
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
    public static function isQualified($iClientId)
    {
        $oClientSettings = Loader::loadData('client_settings');
        $oSettings       = Loader::loadData('settings');

        $oSettings->get('Auto-bid global switch', 'type');
        $bGlobalActive = (bool)$oSettings->value;

        if (true === $bGlobalActive || true === (bool)$oClientSettings->getSetting($iClientId, \client_setting_type::TYPE_AUTO_BID_BETA_TESTER)) {
            $oClients = Loader::loadData('clients');

            if ($oClients->getLastStatut($iClientId)) {
                return true;
            }
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
    public static function saveSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount)
    {
        /** @var \settings $oSetting */
        $oSetting = Loader::loadData('settings');
        $oSetting->get('Pret min', 'type');
        $iAmountMin = (int)$oSetting->value;

        if ($iAmount < $iAmountMin) {
            return false;
        }

        if ($fRate < 4 || $fRate > 10) {
            return false;
        }

        /** @var \autobid $oAutoBid */
        $oAutoBid = Loader::loadData('autobid');
        if ($oAutoBid->exist($iLenderId, 'evaluation = "' . $sEvaluation . '" AND id_autobid_period = ' . $iAutoBidPeriodId . ' AND status != ' . \autobid::STATUS_ARCHIVED . ' AND id_lender')) {
            $aAutoBids = $oAutoBid->select('evaluation = "' . $sEvaluation . '" AND id_autobid_period = ' . $iAutoBidPeriodId . ' AND status != ' . \autobid::STATUS_ARCHIVED . ' AND id_lender = ' . $iLenderId);
            /** @var \bids $oBid */
            $oBid = Loader::loadData('bids');

            if ($oBid->exist($aAutoBids[0]['id_autobid'], 'id_autobid')) {
                foreach ($aAutoBids as $aBid) {
                    $oAutoBid->get($aBid['id_autobid']);
                    $oAutoBid->status = \autobid::STATUS_ARCHIVED;
                    $oAutoBid->update();
                }
                self::createSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount);
            } else {
                $aAutoBidActive = array_shift($aAutoBids);
                self::updateSetting($aAutoBidActive['id_autobid'], $fRate, $iAmount);

                foreach ($aAutoBids as $aBid) {
                    $oAutoBid->get($aBid['id_autobid']);
                    $oAutoBid->status = \autobid::STATUS_ARCHIVED;
                    $oAutoBid->update();
                }
            }
        } else {
            self::createSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount);
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
    private static function createSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid                    = Loader::loadData('autobid');
        $oAutoBid->id_lender         = $iLenderId;
        $oAutoBid->status            = \autobid::STATUS_ACTIVE;
        $oAutoBid->evaluation        = $sEvaluation;
        $oAutoBid->id_autobid_period = $iAutoBidPeriodId;
        $oAutoBid->rate_min          = $fRate;
        $oAutoBid->amount            = $iAmount;
        $oAutoBid->create();

        /** @var \autobid_queue $oAutoBidQueue */
        $oAutoBidQueue = Loader::loadData('autobid_queue');
        if (false === $oAutoBidQueue->exist($iLenderId, 'id_lender')) {
            $oAutoBidQueue->id_lender = $iLenderId;
            $oAutoBidQueue->status    = \autobid_queue::STATUS_NEW;
            $oAutoBidQueue->create();
        }
    }

    /**
     * @param $iAutoBidId
     * @param $fRate
     * @param $iAmount
     */
    private static function updateSetting($iAutoBidId, $fRate, $iAmount)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = Loader::loadData('autobid');
        $oAutoBid->get($iAutoBidId);
        $oAutoBid->rate_min = $fRate;
        $oAutoBid->amount   = $iAmount;
        $oAutoBid->update();
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
    public static function getSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $fAmount, $iStatus = \autobid::STATUS_ACTIVE)
    {
        return Loader::loadData('autobid')->get(
            $iLenderId,
            'status = ' . $iStatus . ' AND evaluation = "' . $sEvaluation . '"" AND id_autobid_period = '
            . $iAutoBidPeriodId . ' AND rate_min = ' . $fRate . ' AND amount = ' . $fAmount . ' AND id_lender'
        );
    }

    public static function bid(\autobid $oAutoBid, \projects $oProject, $fRate)
    {
        if ($oAutoBid->rate_min <= $fRate) {
            /** @var \bids $oBid */
            $oBid                    = Loader::loadData('bids');
            $oBid->id_lender_account = $oAutoBid->id_lender;
            $oBid->id_project        = $oProject->id_project;
            $oBid->id_autobid        = $oAutoBid->id_autobid;
            $oBid->amount            = $oAutoBid->amount * 100;
            $oBid->rate              = $fRate;
            BidManager::bid($oBid);

            $oAutoBidQueue = Loader::loadData('autobid_queue');
            $oAutoBidQueue->addToQueue($oAutoBid->id_lender, \autobid_queue::STATUS_NEW);
        }
    }

    public static function refreshRateOrReject(\bids $oBid, $fCurrentRate)
    {
        /** @var \autobid $oAutoBidSetting */
        $oAutoBidSetting = Loader::loadData('autobids');

        if (false === empty($oBid->id_autobid) && $oAutoBidSetting->get($oBid->id_autobid)) {
            if ($oAutoBidSetting->rate_min <= $fCurrentRate) {
                $oBid->status = \bids::STATUS_BID_PENDING;
                $oBid->rate   = $fCurrentRate;
                $oBid->update();
            } else {
                self::reject($oBid);
            }
        }

        return $oBid;
    }

    public static function reject(\bids $oBid)
    {
        /** @var \autobid_queue $oAutoBidQueue */
        $oAutoBidQueue = Loader::loadData('autobid_queue');
        BidManager::reject($oBid);
        $oAutoBidQueue->addToQueue($oBid->id_lender, \autobid_queue::STATUS_TOP);
    }
}