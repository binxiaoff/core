<?php
namespace Unilend\Service;

use Unilend\core\Loader;

/**
 * Class AutoBidManager
 * @package Unilend\Service
 */
class AutoBidManager
{
    /**
     * AutoBidManager constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param $iClientId
     */
    public static function on($iClientId)
    {
        if (self::isQualified($iClientId)) {
            self::onOff($iClientId, true);
        }
    }

    /**
     * @param $iClientId
     */
    public static function off($iClientId)
    {
        self::onOff($iClientId, false);
    }

    /**
     * @param $iClientId
     * @param $bActive
     */
    private static function onOff($iClientId, $bActive)
    {
        /** @var \client_settings $oClientSettings */
        $oClientSettings = Loader::loadData('client_settings');

        $iValue = $bActive ? 1 : 0;
        $sOnOff = $bActive ? 'on' : 'off';

        if ($oClientSettings->get($iClientId, 'id_type = ' . \client_setting_type::TYPE_AUTO_BID_SWITCH . ' AND id_client')) {
            $oClientSettings->value = $iValue;
            $oClientSettings->update();
        } else {
            $oClientSettings->id_client = $iClientId;
            $oClientSettings->id_type   = \client_setting_type::TYPE_AUTO_BID_SWITCH;
            $oClientSettings->value     = $iValue;
            $oClientSettings->create();
        }

        /** @var \clients_history_actions $oClientHistoryActions */
        $oClientHistoryActions = Loader::loadData('clients_history_actions');
        // BO user
        $iUserId     = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : null;
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

        if (true === $bGlobalActive || true === (bool)$oClientSettings->getSetting($iClientId, \client_setting_type::TYPE_BETA_TESTER)) {
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
                self::autoBidBeforePublication($oProject);
            } else {
                if ($oProjectStatus->status == \projects_status::EN_FUNDING) {
                    self::autoBidAfterPublication($oProject);
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * @param \projects $oProject
     */
    private static function autoBidBeforePublication(\projects $oProject)
    {
        $iPeriod     = (int)$oProject->period;
        $sEvaluation = $oProject->risk;
        $iCurrentRate    = 10;

        /** @var \autobid_queue $oAutoBidQueue */
        $oAutoBidQueue = Loader::loadData('autobid_queue');
        $oBid = Loader::loadData('bids');

        $iOffset = 0;
        $iLimit  = 100;
        while ($aAutoBidList = $oAutoBidQueue->getAutoBids($iPeriod, $sEvaluation, $iCurrentRate, $iOffset, $iLimit)) {
            $iOffset += $iLimit;

            foreach ($aAutoBidList as $aAutoBidSettings) {
                $oBid->id_lender_account     = $aAutoBidSettings['id_lender'];
                $oBid->id_project            = $oProject->id_project;
                $oBid->id_autobid            = $aAutoBidSettings['id_autobid'];
                $oBid->amount                = $aAutoBidSettings['amount'] * 100;
                $oBid->rate                  = $iCurrentRate;
                BidManager::bid($oBid);
                $oAutoBidQueue->addToQueue($aAutoBidSettings['id_lender'], \autobid_queue::STATUS_NEW);
            }
        }
    }

    private static function autoBidAfterPublication(\projects $oProject)
    {
        /** @var \settings $oSettings */
        $oSettings = Loader::loadData('settings');
        $oSettings->get('Auto-bid step', 'type');
        $fStep = (float) $oSettings->value;

        /** @var \bids $oBidData */
        $oBidData = Loader::loadData('bids');
        $fCurrentRate = (float) $oBidData->getProjectMaxRate($oProject->id_project) - $fStep;

        /** @var \autobid_queue $oAutoBidQueue */
        $oAutoBidQueue = Loader::loadData('autobid_queue');

        $iOffset = 0;
        $iLimit  = 100;
        while ($aAutoBidList = $oBidData->getRefusedAutoBids($oProject->id_project)) {
            $iOffset += $iLimit;

            foreach ($aAutoBidList as $aAutobid) {
                if (false === $oBidData->get($aAutobid['id_bid'])) {
                    continue;
                }
                if ($aAutobid['rate_min'] <= $fCurrentRate) {
                    $oBidData->status = \bids::STATUS_BID_PENDING;
                    $oBidData->rate = $fCurrentRate;
                    $oBidData->checked = 0;
                    $oBidData->update();
                } else {
                    BidManager::reject($oBidData);
                    $oAutoBidQueue->addToQueue($aAutobid['id_lender'], \autobid_queue::STATUS_TOP);
                }
            }
        }
    }
}