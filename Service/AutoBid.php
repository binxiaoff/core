<?php
namespace Unilend\Service;

use Unilend\librairies\Data;

/**
 * Class AutoBid
 * @package Unilend\Service
 */
class AutoBid
{
    /**
     * Autobid constructor.
     */
    public function __construct()
    {
    }

    public function on($iClientId)
    {
        if ($this->isQualified($iClientId)) {
            $this->onOff($iClientId, true);
        }
    }

    public function off($iClientId)
    {
        $this->onOff($iClientId, false);
    }

    private function onOff($iClientId, $bActive)
    {
        /** @var \client_settings $oClientSettings */
        $oClientSettings = Data::loadData('client_settings');

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
        $oClientHistoryActions = Data::loadData('clients_history_actions');
        // BO user
        $iUserId     = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : null;
        $sSerialized = serialize(array('id_user' => $iUserId, 'id_client' => $iClientId, 'autobid_switch' => $sOnOff));
        $oClientHistoryActions->histo(20, 'autobid_on_off', $iClientId, $sSerialized);
    }

    public function isQualified($iClientId)
    {
        $oClientSettings = Data::loadData('client_settings');
        $oSettings       = Data::loadData('settings');

        $oSettings->get('Auto-bid global switch', 'type');
        $bGlobalActive = (bool)$oSettings->value;

        if (true === $bGlobalActive || true === (bool)$oClientSettings->getSetting($iClientId, \client_setting_type::TYPE_BETA_TEST_SWITCH)) {
            $oClients = Data::loadData('clients');

            if ($oClients->getLastStatut($iClientId)) {
                return true;
            }
        }

        return false;
    }

}