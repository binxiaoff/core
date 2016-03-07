<?php
namespace Unilend\Service;

use Unilend\core\Loader;

/**
 * Class AutoBidSettingsManager
 * @package Unilend\Service
 */
class AutoBidSettingsManager
{
    //todo: To be defined
    const CGV_AUTOBID = 92;

    /** @var ClientSettingsManager */
    private $oClientSettingsManager;

    /** @var ClientManager */
    private $oClientManager;

    /** @var NotificationManager */
    private $oNotificationManager;

    /** @var LenderManager */
    private $oLenderManager;

    public function __construct()
    {
        $this->oClientSettingsManager = Loader::loadService('ClientSettingsManager');
        $this->oClientManager         = Loader::loadService('ClientManager');
        $this->oNotificationManager   = Loader::loadService('NotificationManager');
        $this->oLenderManager         = Loader::loadService('LenderManager');
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     */
    public function on(\lenders_accounts $oLenderAccount)
    {
        /** @var \autobid_queue $oAutoBidQueue */
        $oAutoBidQueue = Loader::loadData('autobid_queue');

        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');

        /** @var \autobid $oAutoBid */
        $oAutoBid = Loader::loadData('autobid');

        if (false === empty($oLenderAccount->id_client_owner) && $oClient->get($oLenderAccount->id_client_owner) && $this->isQualified($oLenderAccount)
            && $this->oLenderManager->canBid($oLenderAccount)
            && $this->oClientSettingsManager->saveClientSetting($oClient, \client_setting_type::TYPE_AUTO_BID_SWITCH, \client_settings::AUTO_BID_ON)
        ) {
            $this->saveAutoBidSwitchHistory($oClient->id_client, \client_settings::AUTO_BID_ON);
            $oAutoBidQueue->addToQueue($oLenderAccount->id_lender_account, \autobid_queue::TYPE_QUEUE_NEW);

            if ($oAutoBid->counter('id_lender = ' . $oLenderAccount->id_lender_account) == 0) {
                $this->oNotificationManager->create(
                    \notifications::TYPE_AUTOBID_FIRST_ACTIVATION,
                    \clients_gestion_type_notif::TYPE_AUTOBID_FIRST_ACTIVATION,
                    $oClient->id_client,
                    'sendFirstAutoBidActivation'
                );
            }
        }
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     */
    public function off(\lenders_accounts $oLenderAccount)
    {
        /** @var \autobid_queue $oAutoBidQueue */
        $oAutoBidQueue = Loader::loadData('autobid_queue');
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');

        if (false === empty($oLenderAccount->id_client_owner) && $oClient->get($oLenderAccount->id_client_owner)
            && $this->oClientSettingsManager->saveClientSetting($oClient, \client_setting_type::TYPE_AUTO_BID_SWITCH, \client_settings::AUTO_BID_OFF)
        ) {
            $this->saveAutoBidSwitchHistory($oClient->id_client, \client_settings::AUTO_BID_OFF);
            $oAutoBidQueue->delete($oLenderAccount->id_lender_account, 'id_lender');
        }
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return bool
     */
    public function isQualified(\lenders_accounts $oLenderAccount)
    {
        if (empty($oLenderAccount->id_lender_account)) {
            return false;
        }
        /** @var \settings $oSettings */
        $oSettings = Loader::loadData('settings');
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');

        $oSettings->get('Auto-bid global switch', 'type');
        if ($oSettings->value && $oClient->get($oLenderAccount->id_client_owner) && $this->oClientManager->isAcceptedCGV($oClient, self::CGV_AUTOBID)
            || (
                false === empty($oLenderAccount->id_client_owner)
                && $this->oClientManager->isBetaTester($oClient)
            )) {
            return true;
        }

        return false;
    }

    /**
     * @param int    $iLenderId
     * @param string $sEvaluation
     * @param int    $iAutoBidPeriodId
     * @param float  $fRate
     * @param int    $iAmount
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

        if ($fRate < \bids::BID_RATE_MIN || $fRate > \bids::BID_RATE_MAX) {
            return false;
        }

        $aAutoBids = $oAutoBid->select('evaluation = "' . $sEvaluation . '" AND id_autobid_period = ' . $iAutoBidPeriodId . ' AND status != ' . \autobid::STATUS_ARCHIVED . ' AND id_lender = ' . $iLenderId);

        if (empty($aAutoBids)) {
            $this->createSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount);
        } else {
            $aAutoBidActive = array_shift($aAutoBids);

            if ($oBid->exist($aAutoBidActive['id_autobid'], 'id_autobid')) {
                $oAutoBid->get($aAutoBidActive['id_autobid']);
                $oAutoBid->status = \autobid::STATUS_ARCHIVED;
                $oAutoBid->update();
                $this->createSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount);
            } else {
                $oAutoBid->get($aAutoBidActive['id_autobid']);
                $oAutoBid->rate_min = $fRate;
                $oAutoBid->amount   = $iAmount;
                $oAutoBid->update();
            }

            // It shouldn't have more than one autobid settings for each category, but if we have, archive them all.
            if (false === empty($aAutoBids)) {
                foreach ($aAutoBids as $aBid) {
                    $oAutoBid->get($aBid['id_autobid']);
                    $oAutoBid->status = \autobid::STATUS_ARCHIVED;
                    $oAutoBid->update();
                }
            }
        }

        return true;
    }

    /**
     * @param int    $iLenderId
     * @param string $sEvaluation
     * @param int    $iAutoBidPeriodId
     * @param float  $fRate
     * @param int    $iAmount
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
     * @param int    $iLenderId
     * @param string $sEvaluation
     * @param int    $iAutoBidPeriodId
     * @param array  $aStatus
     *
     * @return bool
     */
    public function getSettings($iLenderId = null, $sEvaluation = null, $iAutoBidPeriodId = null, $aStatus = array(\autobid::STATUS_ACTIVE), $sOrder = null)
    {
        return Loader::loadData('autobid')->getSettings($iLenderId, $sEvaluation, $iAutoBidPeriodId, $aStatus, $sOrder);
    }

    /**
     * @param \lenders_accounts $oLendersAccount
     *
     * @return bool
     */
    public function isNovice(\lenders_accounts $oLendersAccount)
    {
        /** @var \autobid $oAutobid */
        $oAutobid              = Loader::loadData('autobid');
        $bIsNovice             = true;

        if ($this->hasAutoBidActivationHistory($oLendersAccount) && $oAutobid->counter('id_lender = ' . $oLendersAccount->id_lender_account) > 0) {
            if ($oAutobid->exist($oLendersAccount->id_lender_account . '" AND status = "' . \autobid::STATUS_INACTIVE, 'id_lender')) {
                $bIsNovice = false;
            } else {
                $aAutobids = $this->getSettings($oLendersAccount->id_lender_account, null, null, array(\autobid::STATUS_ACTIVE, \autobid::STATUS_INACTIVE), null);
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
     * @param int   $iLenderId
     * @param float $fRate
     * @param int   $iAmount
     */
    public function saveNoviceSetting($iLenderId, $fRate, $iAmount)
    {
        /** @var \autobid_periods $oAutoBidPeriods */
        $oAutoBidPeriods = Loader::loadData('autobid_periods');
        /** @var \projects $oProject */
        $oProject        = Loader::loadData('projects');
        $aAutoBidPeriods = $oAutoBidPeriods->select('status = ' . \autobid_periods::STATUS_ACTIVE);
        $aRiskValues     = $oProject->getAvailableRisks();

        foreach ($aAutoBidPeriods as $aPeriod) {
            foreach ($aRiskValues as $sEvaluation) {
                $this->saveSetting($iLenderId, $sEvaluation, $aPeriod['id_period'], $fRate, $iAmount);
                $this->activateDeactivateSetting($iLenderId, $sEvaluation, $aPeriod['id_period'], \autobid::STATUS_ACTIVE);
            }
        }
    }

    /**
     * @param string $sEvaluation
     * @param int    $iDuration
     *
     * @return int
     */
    public function predictAmount($sEvaluation, $iDuration)
    {
        return Loader::loadData('autobid')->sumAmount($sEvaluation, $iDuration);
    }

    /**
     * @param int    $iLenderId
     * @param string $sEvaluation
     * @param int    $iAutoBidPeriodId
     * @param int    $iNewStatus
     */
    public function activateDeactivateSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $iNewStatus)
    {
        $oAutoBid = Loader::loadData('autobid');
        $oAutoBid->get(
            $iLenderId,
            'status != ' . \autobid::STATUS_ARCHIVED . ' AND evaluation = "' . $sEvaluation . '" AND id_autobid_period = '
            . $iAutoBidPeriodId . ' AND id_lender'
        );

        if (in_array($iNewStatus, array(\autobid::STATUS_ACTIVE, \autobid::STATUS_INACTIVE))) {
            $oAutoBid->status = $iNewStatus;
            $oAutoBid->update();
        }
    }


    /**
     * @param int $iClientID
     *
     * @return array
     */
    public function getLastDateOnOff($iClientID)
    {
        $oClientsHistoryActions = Loader::loadData('clients_history_actions');
        $aAutoBidHistory        = $oClientsHistoryActions->getLastAutoBidOnOffActions($iClientID);

        $aDates = array();

        foreach ($aAutoBidHistory as $aHistoryAction) {
            $aAction                            = unserialize($aHistoryAction['serialize']);
            $aDates[$aAction['autobid_switch']] = $aHistoryAction['added'];
        }
        return $aDates;
    }

    /**
     * @param $iClientId
     *
     * @param $sValue
     */
    private function saveAutoBidSwitchHistory($iClientId, $sValue)
    {
        /** @var \clients_history_actions $oClientHistoryActions */
        $oClientHistoryActions = Loader::loadData('clients_history_actions');

        $sOnOff      = $sValue === \client_settings::AUTO_BID_ON ? 'on' : 'off';
        $iUserId     = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : null;
        $sSerialized = serialize(array('id_user' => $iUserId, 'id_client' => $iClientId, 'autobid_switch' => $sOnOff));
        $oClientHistoryActions->histo(21, 'autobid_on_off', $iClientId, $sSerialized);
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return mixed
     */

    public function getValidationDate(\lenders_accounts $oLenderAccount)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = Loader::loadData('autobid');
        return new \DateTime($oAutoBid->getValidationDate($oLenderAccount->id_lender_account));
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return bool
     */
    public function isOn(\lenders_accounts $oLenderAccount)
    {
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');
        if (false === empty($oLenderAccount->id_client_owner) && $oClient->get($oLenderAccount->id_client_owner)) {
            return (bool)$this->oClientSettingsManager->getSetting($oClient, \client_setting_type::TYPE_AUTO_BID_SWITCH);
        }
    }

    /**
     * @param \clients $oClient
     *
     * @return mixed
     */

    public function getActivationTime(\clients $oClient)
    {
        /** @var \client_settings $oClientSettings */
        $oClientSettings = Loader::loadData('client_settings');
        $oClientSettings->get($oClient->id_client, 'id_type = ' . \client_setting_type::TYPE_AUTO_BID_SWITCH . ' AND id_client');
        return str_replace(':', 'h', date('G:i', strtotime($oClientSettings->added)));
    }

    /**
     * @param \lenders_accounts $oLendersAccount
     *
     * @return bool
     */

    public function hasAutoBidActivationHistory(\lenders_accounts $oLendersAccount)
    {
        /** @var \clients_history_actions $oClientHistoryActions */
        $oClientHistoryActions = Loader::loadData('clients_history_actions');
        return $oClientHistoryActions->counter('id_client = ' . $oLendersAccount->id_client_owner . ' AND nom_form = "autobid_on_off" ') > 0;
    }

}
