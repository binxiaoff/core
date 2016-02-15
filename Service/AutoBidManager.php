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

    /** @var \client_settings */
    private $oClientSettings;
    /** @var \clients_history_actions */
    private $oClientHistoryActions;
    /** @var \settings */
    private $oSettings;
    /** @var \clients */
    private $oClient;
    /** @var \autobid */
    private $oAutoBid;
    /** @var  \projects */
    private $oProject;
    /** @var  \bids */
    private $oBid;
    /** @var \autobid_queue */
    private $oAutoBidQueue;
    /** @var BidManager */
    private $oBidManager;

    public function __construct()
    {
        $this->oClientSettings       = Loader::loadData('client_settings');
        $this->oClientHistoryActions = Loader::loadData('clients_history_actions');
        $this->oSettings             = Loader::loadData('settings');
        $this->oClient               = Loader::loadData('clients');
        $this->oAutoBid              = Loader::loadData('autobid');
        $this->oProject              = Loader::loadData('projects');
        $this->oBid                  = Loader::loadData('bids');
        $this->oAutoBidQueue         = Loader::loadData('autobid_queue');

        $this->oBidManager = Loader::loadService('BidManager');
    }

    /**
     * @param $iClientId
     */
    public function on($iClientId)
    {
        if ($this->isQualified($iClientId)) {
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
        if ($this->oClientSettings->get($iClientId, 'id_type = ' . \client_setting_type::TYPE_AUTO_BID_SWITCH . ' AND id_client')) {
            $this->oClientSettings->value = $iAutoBidOnOff;
            $this->oClientSettings->update();
        } else {
            $this->oClientSettings->unsetData();
            $this->oClientSettings->id_client = $iClientId;
            $this->oClientSettings->id_type   = \client_setting_type::TYPE_AUTO_BID_SWITCH;
            $this->oClientSettings->value     = $iAutoBidOnOff;
            $this->oClientSettings->create();
        }
        // BO user
        $iUserId     = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : null;
        $sOnOff      = $iAutoBidOnOff === self::AUTO_BID_ON ? 'on' : 'off';
        $sSerialized = serialize(array('id_user' => $iUserId, 'id_client' => $iClientId, 'autobid_switch' => $sOnOff));
        $this->oClientHistoryActions->histo(20, 'autobid_on_off', $iClientId, $sSerialized);
    }

    /**
     * @param $iClientId
     *
     * @return bool
     */
    public function isQualified($iClientId)
    {
        $this->oSettings->get('Auto-bid global switch', 'type');
        $bGlobalActive = (bool)$this->oSettings->value;

        if ((true === $bGlobalActive || true === (bool)$this->oClientSettings->getSetting($iClientId, \client_setting_type::TYPE_AUTO_BID_BETA_TESTER)) && $this->oClient->getLastStatut($iClientId)) {
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
        $this->oSettings->get('Pret min', 'type');
        $iAmountMin = (int)$this->oSettings->value;

        if ($iAmount < $iAmountMin) {
            return false;
        }

        if ($fRate < 4 || $fRate > 10) {
            return false;
        }

        if ($this->oAutoBid->exist($iLenderId, 'evaluation = "' . $sEvaluation . '" AND id_autobid_period = ' . $iAutoBidPeriodId . ' AND status != ' . \autobid::STATUS_ARCHIVED . ' AND id_lender')) {
            $aAutoBids = $this->oAutoBid->select('evaluation = "' . $sEvaluation . '" AND id_autobid_period = ' . $iAutoBidPeriodId . ' AND status != ' . \autobid::STATUS_ARCHIVED . ' AND id_lender = ' . $iLenderId);

            if ($this->oBid->exist($aAutoBids[0]['id_autobid'], 'id_autobid')) {
                foreach ($aAutoBids as $aBid) {
                    $this->oAutoBid->get($aBid['id_autobid']);
                    $this->oAutoBid->status = \autobid::STATUS_ARCHIVED;
                    $this->oAutoBid->update();
                }
                $this->createSetting($iLenderId, $sEvaluation, $iAutoBidPeriodId, $fRate, $iAmount);
            } else {
                $aAutoBidActive = array_shift($aAutoBids);
                $this->updateSetting($aAutoBidActive['id_autobid'], $fRate, $iAmount);

                foreach ($aAutoBids as $aBid) {
                    $this->oAutoBid->get($aBid['id_autobid']);
                    $this->oAutoBid->status = \autobid::STATUS_ARCHIVED;
                    $this->oAutoBid->update();
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
        $this->oAutoBid->unsetData();
        $this->oAutoBid->id_lender         = $iLenderId;
        $this->oAutoBid->status            = \autobid::STATUS_ACTIVE;
        $this->oAutoBid->evaluation        = $sEvaluation;
        $this->oAutoBid->id_autobid_period = $iAutoBidPeriodId;
        $this->oAutoBid->rate_min          = $fRate;
        $this->oAutoBid->amount            = $iAmount;
        $this->oAutoBid->create();

        if (false === $this->oAutoBidQueue->exist($iLenderId, 'id_lender')) {
            $this->oAutoBidQueue->unsetData();
            $this->oAutoBidQueue->id_lender = $iLenderId;
            $this->oAutoBidQueue->status    = \autobid_queue::STATUS_NEW;
            $this->oAutoBidQueue->create();
        }
    }

    /**
     * @param $iAutoBidId
     * @param $fRate
     * @param $iAmount
     */
    private function updateSetting($iAutoBidId, $fRate, $iAmount)
    {
        $this->oAutoBid->get($iAutoBidId);
        $this->oAutoBid->rate_min = $fRate;
        $this->oAutoBid->amount   = $iAmount;
        $this->oAutoBid->update();
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
        return $this->oAutoBid->get(
            $iLenderId,
            'status = ' . $iStatus . ' AND evaluation = "' . $sEvaluation . '"" AND id_autobid_period = '
            . $iAutoBidPeriodId . ' AND rate_min = ' . $fRate . ' AND amount = ' . $fAmount . ' AND id_lender'
        );
    }

    public function bid($iAutoBidID, $iProjectId, $fRate)
    {
        $this->oAutoBid->get($iAutoBidID);
        if ($this->oAutoBid->rate_min <= $fRate) {
            $this->oProject->get($iProjectId);
            $this->oBidManager->bid($this->oAutoBid->id_lender, $this->oProject->id_project, $this->oAutoBid->id_autobid, $this->oAutoBid->amount, $fRate);
            $this->oAutoBidQueue->addToQueue($this->oAutoBid->id_lender, \autobid_queue::STATUS_NEW);
        }
    }

    public function refreshRateOrReject($iBidId, $fCurrentRate)
    {

        if ($this->oBid->get($iBidId) && false === empty($this->oBid->id_autobid) && $this->oAutoBid->get($this->oBid->id_autobid)) {
            if ($this->oAutoBid->rate_min <= $fCurrentRate) {
                $this->oBid->status = \bids::STATUS_BID_PENDING;
                $this->oBid->rate   = $fCurrentRate;
                $this->oBid->update();
            } else {
                $this->reject($iBidId);
            }
        }
    }

    public function reject($iBidId)
    {
        if ($this->oBid->get($iBidId)) {
            $this->oBidManager->reject($iBidId);
            $this->oAutoBidQueue->addToQueue($this->oBid->id_lender_account, \autobid_queue::STATUS_TOP);
        }
    }
}