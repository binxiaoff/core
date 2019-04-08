<?php

namespace Unilend\Service;

use Psr\Cache\{
    CacheItemPoolInterface, InvalidArgumentException
};
use Unilend\Entity\Clients;
use Unilend\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\CacheKeys;

/**
 * Class ClientSettingsManager
 * @package Unilend\Service
 */
class ClientSettingsManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var CacheItemPoolInterface */
    private $cachePool;

    /**
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(EntityManagerSimulator $entityManagerSimulator, CacheItemPoolInterface $cachePool)
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->cachePool              = $cachePool;
    }

    /**
     * @param Clients $client
     * @param int     $settingType
     * @param string  $value
     *
     * @return bool
     * @throws InvalidArgumentException
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
     * @return string|null
     */
    public function getSetting(Clients $client, int $settingType): ?string
    {
        /** @var \client_settings $clientSettings */
        $clientSettings = $this->entityManagerSimulator->getRepository('client_settings');

        try {
            $cachedItem = $this->cachePool->getItem(CacheKeys::GET_CLIENT_SETTING . '_' . $client->getIdClient() . '_' . $settingType);
        } catch (InvalidArgumentException $exception) {
            return null;
        }

        if ($cachedItem->isHit()) {
            return $cachedItem->get();
        }

        $value = $clientSettings->getSetting($client->getIdClient(), $settingType);
        $cachedItem
            ->set($value)
            ->expiresAfter(CacheKeys::MEDIUM_TIME);

        $this->cachePool->save($cachedItem);

        return $value;
    }

    /**
     * @param Clients $client
     * @param int     $settingType
     *
     * @throws InvalidArgumentException
     */
    private function flushSettingCache(Clients $client, int $settingType): void
    {
        $this->cachePool->deleteItem(CacheKeys::GET_CLIENT_SETTING . '_' . $client->getIdClient() . '_' . $settingType);
    }
}
