<?php
namespace Unilend\Service;

use Unilend\Service\Simulator\EntityManager;

/**
 * Class AutoBidSettingsManager
 * @package Unilend\Service
 */
class AutoBidSettingsManager
{
    const CGV_AUTOBID = 474;

    /** @var ClientSettingsManager */
    private $oClientSettingsManager;

    /** @var ClientManager */
    private $oClientManager;

    /** @var NotificationManager */
    private $oNotificationManager;

    /** @var LenderManager */
    private $oLenderManager;

    public function __construct(EntityManager $oEntityManager, ClientSettingsManager $oClientSettingsManager, ClientManager $oClientManager, NotificationManager $oNotificationManager, LenderManager $oLenderManager)
    {
        $this->oEntityManager         = $oEntityManager;
        $this->oClientSettingsManager = $oClientSettingsManager;
        $this->oClientManager         = $oClientManager;
        $this->oNotificationManager   = $oNotificationManager;
        $this->oLenderManager         = $oLenderManager;
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     */
    public function on(\lenders_accounts $oLenderAccount)
    {
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');

        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->oEntityManager->getRepository('autobid');

        if (false === empty($oLenderAccount->id_client_owner) && $oClient->get($oLenderAccount->id_client_owner) && $this->isQualified($oLenderAccount)
            && $this->oLenderManager->canBid($oLenderAccount)
            && $this->oClientSettingsManager->saveClientSetting($oClient, \client_setting_type::TYPE_AUTO_BID_SWITCH, \client_settings::AUTO_BID_ON)
        ) {
            $this->saveAutoBidSwitchHistory($oClient->id_client, \client_settings::AUTO_BID_ON);

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
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');

        if (false === empty($oLenderAccount->id_client_owner) && $oClient->get($oLenderAccount->id_client_owner)
            && $this->oClientSettingsManager->saveClientSetting($oClient, \client_setting_type::TYPE_AUTO_BID_SWITCH, \client_settings::AUTO_BID_OFF)
        ) {
            $this->saveAutoBidSwitchHistory($oClient->id_client, \client_settings::AUTO_BID_OFF);
        }
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return bool
     */
    public function isQualified(\lenders_accounts $oLenderAccount)
    {
        if (empty($oLenderAccount->id_lender_account) || empty($oLenderAccount->id_client_owner)) {
            return false;
        }
        /** @var \settings $oSettings */
        $oSettings = $this->oEntityManager->getRepository('settings');
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');

        if (false === $oSettings->get('Auto-bid global switch', 'type')) {
            return false;
        }

        if (false === $oClient->get($oLenderAccount->id_client_owner)) {
            return false;
        }

        if ($oSettings->value && $this->oClientManager->isAcceptedCGV($oClient, self::CGV_AUTOBID) || $this->oClientManager->isBetaTester($oClient)) {
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
        $oSettings = $this->oEntityManager->getRepository('settings');
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->oEntityManager->getRepository('autobid');
        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');

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
        $oAutoBid = $this->oEntityManager->getRepository('autobid');

        $oAutoBid->id_lender         = $iLenderId;
        $oAutoBid->status            = \autobid::STATUS_ACTIVE;
        $oAutoBid->evaluation        = $sEvaluation;
        $oAutoBid->id_autobid_period = $iAutoBidPeriodId;
        $oAutoBid->rate_min          = $fRate;
        $oAutoBid->amount            = $iAmount;
        $oAutoBid->create();
    }

    /**
     * Get all settings of which their period is active and fit the criteria
     *
     * @param int    $iLenderId
     * @param string $sEvaluation
     * @param int    $iAutoBidPeriodId
     * @param array  $aStatus
     * @param string $sOrder
     *
     * @return array
     */
    public function getSettings($iLenderId = null, $sEvaluation = null, $iAutoBidPeriodId = null, $aStatus = array(\autobid::STATUS_ACTIVE), $sOrder = null, $iLimit = null, $iOffset = null)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->oEntityManager->getRepository('autobid');
        return $oAutoBid->getSettings($iLenderId, $sEvaluation, $iAutoBidPeriodId, $aStatus, $sOrder, $iLimit, $iOffset);
    }

    /**
     * @param \lenders_accounts $oLendersAccount
     *
     * @return bool
     */
    public function isNovice(\lenders_accounts $oLendersAccount)
    {
        /** @var \autobid $oAutobid */
        $oAutobid  = $this->oEntityManager->getRepository('autobid');
        $bIsNovice = true;

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
        $oAutoBidPeriods = $this->oEntityManager->getRepository('autobid_periods');
        /** @var \projects $oProject */
        $oProject        = $this->oEntityManager->getRepository('projects');
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
        return $this->oEntityManager->getRepository('autobid')->sumAmount($sEvaluation, $iDuration);
    }

    /**
     * @param int    $iLenderId
     * @param string $sEvaluation
     * @param int    $iAutoBidPeriodId
     * @param int    $iNewStatus
     */
    public function activateDeactivateSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $iNewStatus)
    {
        $oAutoBid = $this->oEntityManager->getRepository('autobid');
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
        /** @var \clients_history_actions $oClientsHistoryActions */
        $oClientsHistoryActions = $this->oEntityManager->getRepository('clients_history_actions');
        $aAutoBidHistory        = $oClientsHistoryActions->getLastAutoBidOnOffActions($iClientID);
        $aDates                 = array();

        foreach ($aAutoBidHistory as $aHistoryAction) {
            $aAction                            = unserialize($aHistoryAction['serialize']);
            $aDates[$aAction['autobid_switch']] = \DateTime::createFromFormat('Y-m-d H:i:s', $aHistoryAction['added']);
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
        $oClientHistoryActions = $this->oEntityManager->getRepository('clients_history_actions');

        $sOnOff      = $sValue === \client_settings::AUTO_BID_ON ? 'on' : 'off';
        $iUserId     = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : null;
        $sSerialized = serialize(array('id_user' => $iUserId, 'id_client' => $iClientId, 'autobid_switch' => $sOnOff));
        $oClientHistoryActions->histo(21, 'autobid_on_off', $iClientId, $sSerialized);
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return \DateTime
     */
    public function getValidationDate(\lenders_accounts $oLenderAccount)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->oEntityManager->getRepository('autobid');
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
        $oClient = $this->oEntityManager->getRepository('clients');
        if (false === empty($oLenderAccount->id_client_owner) && $oClient->get($oLenderAccount->id_client_owner)) {
            return (bool)$this->oClientSettingsManager->getSetting($oClient, \client_setting_type::TYPE_AUTO_BID_SWITCH);
        }

        return false;
    }

    /**
     * @param \clients $oClient
     *
     * @return \DateTime|null
     */
    public function getActivationTime(\clients $oClient)
    {
        /** @var \client_settings $oClientSettings */
        $oClientSettings = $this->oEntityManager->getRepository('client_settings');

        if ($oClientSettings->get($oClient->id_client, 'id_type = ' . \client_setting_type::TYPE_AUTO_BID_SWITCH . ' AND id_client')) {
            $oActivationTime = new \DateTime($oClientSettings->added);
        } else {
            $oActivationTime = new \DateTime();
        }
        return $oActivationTime;
    }

    /**
     * @param \lenders_accounts $oLendersAccount
     *
     * @return bool
     */

    public function hasAutoBidActivationHistory(\lenders_accounts $oLendersAccount)
    {
        /** @var \clients_history_actions $oClientHistoryActions */
        $oClientHistoryActions = $this->oEntityManager->getRepository('clients_history_actions');
        return $oClientHistoryActions->counter('id_client = ' . $oLendersAccount->id_client_owner . ' AND nom_form = "autobid_on_off" ') > 0;
    }

    /**
     * Get autobid_period object by a duration
     *
     * @param $iDuration
     *
     * @return bool|\autobid_periods
     */
    public function getPeriod($iDuration)
    {
        /** @var \autobid_periods $oAutoBidPeriods */
        $oAutoBidPeriods = $this->oEntityManager->getRepository('autobid_periods');
        return $oAutoBidPeriods->getPeriod($iDuration);
    }
}
