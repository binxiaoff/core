<?php
namespace Unilend\Service;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Class ClientSettingsManager
 * @package Unilend\Service
 */
class ClientSettingsManager extends DataService
{
    const CACHE_KEY_GET_SETTING = 'UNILEND_SERVICE_CLIENTSETTINGSMANAGER_GETSETTING';

    private $oCachePool;

    public function __construct(CacheItemPoolInterface $oCachePool)
    {
        $this->oCachePool = $oCachePool;
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
        /** @var \client_settings $oClientSettings */
        $oClientSettings = $this->loadData('client_settings');
        $oCachedItem     = $this->oCachePool->get(self::CACHE_KEY_GET_SETTING . '_' . $oClient->id_client . '_' . $iSettingType);

        if (false === $oCachedItem->isHit()) {
            $mValue = $oClientSettings->getSetting($oClient->id_client, $iSettingType);
            $oCachedItem->set($mValue)
                        ->expiresAfter(1800);
            $this->oCachePool->save($oCachedItem);
        } else {
            $mValue = $oCachedItem->get();
        }

        return $mValue;
    }

    private function flushSettingCache(\clients $oClient, $iSettingType)
    {
        $this->oCachePool->deleteItem(self::CACHE_KEY_GET_SETTING . '_' . $oClient->id_client . '_' . $iSettingType);
    }
}
