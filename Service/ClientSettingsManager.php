<?php
namespace Unilend\Service;

use Unilend\core\Loader;
use Unilend\librairies\Cache;

/**
 * Class ClientSettingsManager
 * @package Unilend\Service
 */
class ClientSettingsManager extends DataService
{
    const CACHE_KEY_GET_SETTING = 'UNILEND_SERVICE_CLIENTSETTINGSMANAGER_GETSETTING';

    /** @var \client_settings ClientSettings */
    private $oClientSettings;

    public function __construct()
    {
        $this->oClientSettings = $this->loadData('client_settings');
        $this->loadData('client_setting_type'); //load for use of constants
    }

    /**
     * @param \clients $oClient
     * @param          $iSettingType
     * @param          $sValue
     *
     * @return bool
     */
    public function saveClientSetting(\clients $oClient, $iSettingType, $sValue)
    {
        /** @var \client_settings $oClientSettings */
        $oClientSettings = $this->loadData('client_settings');

        if ($oClientSettings->get($oClient->id_client, 'id_type = ' . $iSettingType . ' AND id_client')) {
            if ($sValue != $oClientSettings->value) {
                $oClientSettings->value = $sValue;
                $oClientSettings->update();
                $this->flushSettingCache($oClient, $iSettingType);
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
            $this->flushSettingCache($oClient, $iSettingType);
            return true;
        }
    }

    /**
     * @param \clients $oClient
     * @param int      $iSettingType
     *
     * @return string
     */
    public function getSetting(\clients $oClient, $iSettingType)
    {
        $oCache = Cache::getInstance();
        $sKey   = $oCache->makeKey(self::CACHE_KEY_GET_SETTING, $oClient->id_client, $iSettingType);
        $mValue = $oCache->get($sKey);

        if (false === $mValue) {
            $mValue = $this->oClientSettings->getSetting($oClient->id_client, $iSettingType);
            $oCache->set($sKey, $mValue);
        }

        return $mValue;
    }

    private function flushSettingCache(\clients $oClient, $iSettingType)
    {
        $oCache = Cache::getInstance();
        $sKey   = $oCache->makeKey(self::CACHE_KEY_GET_SETTING, $oClient->id_client, $iSettingType);
        $oCache->delete($sKey);
    }
}
