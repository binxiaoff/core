<?php
namespace Unilend\Service;

use Unilend\core\Loader;

/**
 * Class AutoBidSettingsManager
 * @package Unilend\Service
 */
class AutoBidSettingsManager
{
    const AUTO_BID_ON  = 1;
    const AUTO_BID_OFF = 0;

    /** @var BidManager */
    private $oBidManager;

    /**
     * @var ClientSettingsManager
     */
    private $oClientSettingsManager;

    public function __construct()
    {
        $this->oBidManager            = Loader::loadService('BidManager');
        $this->oClientSettingsManager = Loader::loadService('ClientSettingsManager');
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
            $this->oClientSettingsManager->saveClientSetting($oClient, \client_setting_type::TYPE_AUTO_BID_SWITCH, self::AUTO_BID_ON);

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

        $this->oClientSettingsManager->saveClientSetting($oClient, \client_setting_type::TYPE_AUTO_BID_SWITCH, self::AUTO_BID_OFF);

        $oLendersAccount->get($oClient->id_client, 'id_client_owner');
        $oAutoBidQueue->delete($oLendersAccount->id_lender_account, 'id_lender');
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
     * @param       $iLenderId
     * @param       $sEvaluation
     * @param       $iAutoBidPeriodId
     * @param array $aStatus
     *
     * @return mixed
     */
    public function getSettings($iLenderId = null, $sEvaluation = null, $iAutoBidPeriodId = null, $aStatus = array(\autobid::STATUS_ACTIVE))
    {
        return Loader::loadData('autobid')->getSettings($iLenderId, $sEvaluation, $iAutoBidPeriodId, $aStatus);
    }

    /**
     * @param $iLenderId
     *
     * @return bool
     */
    public function isNovice($iLenderId)
    {
        $oAutoBid              = Loader::loadData('autobid');
        $oClientHistoryActions = Loader::loadData('clients_history_actions');
        $bIsNovice             = true;

        if ($oClientHistoryActions->counter('id_client = ' . $this->clients->id_client . ' AND nom_form = "autobid_on_off" ') > 0 && $oAutoBid->counter('id_lender = ' . $iLenderId) > 0) {
            if ($oAutoBid->select('id_lender = ' . $iLenderId . ' AND status = ' . \autobid::STATUS_INACTIVE, null, null, 1)) {
                $bIsNovice = false;
            } else {
                $aAutobids = $oAutoBid->select('id_lender = ' . $iLenderId . ' AND status = ' . \autobid::STATUS_ACTIVE);
                $fRate     = $aAutobids[0]['rate_min'];
                $iAmount   = $aAutobids[0]['amount'];

                foreach ($aAutobids as $aAutobid) {
                    if ($fRate !== $aAutobid['rate_min'] || $iAmount !== $aAutobid['amount']) {
                        $bIsNovice = false;
                        break;
                    }
                }
            }
        }

        return $bIsNovice;
    }

    /**
     * @param $iLenderId
     * @param $fRate
     * @param $iAmount
     */
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

    /**
     * @param $sEvaluation
     * @param $iDuration
     *
     * @return mixed
     */
    public function predictAmount($sEvaluation, $iDuration)
    {
        return Loader::loadData('autobid')->sumAmount($sEvaluation, $iDuration);
    }

    /**
     * @param $iClientID
     *
     * @return array
     */
    public function getLastDateOnOff($iClientID)
    {
        $oClientsHistoryActions = Loader::loadData('clients_history_actions');
        $aAutoBidHistory        = $oClientsHistoryActions->getLastAutoBidOnOffActions($iClientID);

        $aDates = array();

        foreach ($aAutoBidHistory as $aHistoryAction){
            $aAction = unserialize($aHistoryAction['serialize']);
            $aDates[$aAction['autobid_switch']] = $aHistoryAction['added'];
        }
        return $aDates;
    }

    /**
     * @param \clients $oClient
     *
     * @return bool
     */
    public function isOn(\clients $oClient)
    {
        $oClientSettings = Loader::loadData('client_settings');
        return (bool)$oClientSettings->getSetting($oClient->id_client, 'id_type = ' . \client_setting_type::TYPE_AUTO_BID_SWITCH . ' AND id_client');
    }
}
