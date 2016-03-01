<?php
namespace Unilend\Service;

use Unilend\core\Loader;

/**
 * Class ClientSettingsManager
 * @package Unilend\Service
 */

class ClientSettingsManager {

    /** @var \client_settings ClientSettings */
    private $oClientSettings;

    public function __construct()
    {
        $this->oClientSettings = Loader::loadData('client_settings');
    }

    /**
     * @param \clients $oClient
     * @param $iSettingType
     * @param $sValue
     * @return bool
     */
    public function saveClientSetting(\clients $oClient, $iSettingType, $sValue)
    {
        /** @var \client_settings $oClientSettings */
        $oClientSettings = Loader::loadData('client_settings');

        if ($oClientSettings->get($oClient->id_client, 'id_type = ' . $iSettingType . ' AND id_client')) {
            if ($sValue != $oClientSettings->value) {
                $oClientSettings->value = $sValue;
                $oClientSettings->update();
                return true;
            } else {
                return false;
            }
        } else {
            $oClientSettings->unsetData();
            $oClientSettings->id_client = $oClient->id_client;
            $oClientSettings->id_type   = $iSettingType;
            $oClientSettings->value     = $sValue;
            $oClientSettings->create();
            return true;
        }
    }

    /**
     * @param \clients $oClient
     * @return bool
     */
    public function isBetaTester(\clients $oClient)
    {
        return $this->isOn($oClient, \client_setting_type::TYPE_BETA_TESTER);
    }

    /**
     * @param \clients $oClient
     * @param int $iTypeSetting
     *
     * @return bool
     */
    public function isOn(\clients $oClient, $iTypeSetting)
    {
        return (bool)$this->oClientSettings->getSetting($oClient->id_client, $iTypeSetting);
    }

}
