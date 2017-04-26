<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Psr\Cache\CacheItemPoolInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

/**
 * Class ClientSettingsManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class ClientSettingsManager
{
    const CACHE_KEY_GET_SETTING = 'UNILEND_SERVICE_CLIENTSETTINGSMANAGER_GETSETTING';

    /** @var CacheItemPoolInterface  */
    private $cachePool;
    /** @var EntityManagerSimulator  */
    private $entityManagerSimulator;

    /**
     * ClientSettingsManager constructor.
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(EntityManagerSimulator $entityManagerSimulator, CacheItemPoolInterface $cachePool)
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->cachePool              = $cachePool;
        $this->entityManagerSimulator->getRepository('client_setting_type'); //load for use of constants
    }

    /**
     * @param Clients $client
     * @param int     $settingType
     * @param string  $value
     *
     * @return bool
     */
    public function saveClientSetting(Clients $client, $settingType, $value)
    {
        /** @var \client_settings $clientSettings */
        $clientSettings = $this->entityManagerSimulator->getRepository('client_settings');

        if ($clientSettings->get($client->getIdClient(), 'id_type = ' . $settingType . ' AND id_client')) {
            if ($value != $clientSettings->value) {
                $clientSettings->value = $value;
                $clientSettings->update();
                $this->flushSettingCache($client, $settingType);

                return true;
            } else {
                return false;
            }
        } else {
            $clientSettings->unsetData();
            $clientSettings->id_client = $client->getIdClient();
            $clientSettings->id_type   = $settingType;
            $clientSettings->value     = $value;
            $clientSettings->create();
            $this->flushSettingCache($client, $settingType);

            return true;
        }
    }

    /**
     * @param Clients $client
     * @param int     $settingType
     *
     * @return string
     */
    public function getSetting(Clients $client, $settingType)
    {
        /** @var \client_settings $clientSettings */
        $clientSettings = $this->entityManagerSimulator->getRepository('client_settings');
        $cachedItem     = $this->cachePool->getItem(self::CACHE_KEY_GET_SETTING . '_' . $client->getIdClient() . '_' . $settingType);

        if (false === $cachedItem->isHit()) {
            $value = $clientSettings->getSetting($client->getIdClient(), $settingType);
            $cachedItem->set($value)
                        ->expiresAfter(1800);
            $this->cachePool->save($cachedItem);
        } else {
            $value = $cachedItem->get();
        }

        return $value;
    }

    /**
     * @param Clients $client
     * @param int     $settingType
     */
    private function flushSettingCache(Clients $client, $settingType)
    {
        $this->cachePool->deleteItem(self::CACHE_KEY_GET_SETTING . '_' . $client->getIdClient() . '_' . $settingType);
    }
}
