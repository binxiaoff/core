<?php
namespace Unilend\Service;

use Unilend\core\Loader;
use Unilend\Service\AutoBidSettingsManager;

/**
 * Class ClientSettingsManager
 * @package Unilend\Service
 */

class ClientSettingsManager {

    const BETA_TESTER_ON  = 1;
    const BETA_TESTER_OFF = 0;

    /** @var ClientSettings */
    private $oClientSettings;

    public function __construct()
    {
        $this->oClientSettings = Loader::loadData('client_settings');
    }

    /**
     * @param \clients  $oClient
     * @param           $iSettingType
     * @param           $sValue
     */
    public function saveClientSetting(\clients $oClient, $iSettingType, $sValue)
    {
        /** @var \client_settings $oClientSettings */
        $oClientSettings = Loader::loadData('client_settings');

        $bValueChange = false;

        if ($oClientSettings->get($oClient->id_client, 'id_type = ' . $iSettingType . ' AND id_client')) {
            if ($sValue != $oClientSettings->value) {
                $oClientSettings->value = $sValue;
                $oClientSettings->update();
                $bValueChange = true;
            }
        } else {
            $oClientSettings->unsetData();
            $oClientSettings->id_client = $oClient->id_client;
            $oClientSettings->id_type   = $iSettingType;
            $oClientSettings->value     = $sValue;
            $oClientSettings->create();
            $bValueChange = true;
        }

        if (\client_setting_type::TYPE_AUTO_BID_SWITCH === $iSettingType && $bValueChange){
            $this->saveAutoBidSwitchHistory($oClient->id_client, $sValue);
        }
    }

    /**
     * @param $iClientId
     * @param $sValue
     */
    private function saveAutoBidSwitchHistory($iClientId, $sValue)
    {
        /** @var \clients_history_actions $oClientHistoryActions */
        $oClientHistoryActions = Loader::loadData('clients_history_actions');

        $sOnOff      = $sValue === AutoBidSettingsManager::AUTO_BID_ON ? 'on' : 'off';
        $iUserId     = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : null;
        $sSerialized = serialize(array('id_user' => $iUserId, 'id_client' => $iClientId, 'autobid_switch' => $sOnOff));
        $oClientHistoryActions->histo(21, 'autobid_on_off', $iClientId, $sSerialized);
    }

    public function isBetaTester(\clients $oClient)
    {
        return (bool)$this->oClientSettings->getSetting($oClient->id_client, \client_setting_type::TYPE_AUTO_BID_BETA_TESTER);
    }

}
