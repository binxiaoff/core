<?php


namespace Unilend\Bundle\FrontBundle\Service;


use Cache\Adapter\Memcache\MemcacheCachePool;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class StaticContentManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var MemcacheCachePool  */
    private $cachePool;

    public function __construct(EntityManager $entityManager, MemcacheCachePool $cachePool)
    {
        $this->entityManager = $entityManager;
        $this->cachePool     = $cachePool;
    }

    public function getFacebookLink()
    {
        $cachedItem = $this->cachePool->getItem('Facebook');
        if (false === $cachedItem->isHit()) {
            /** @var \settings $settings */
            $settings = $this->entityManager->getRepository('settings');
            $settings->get('Facebook', 'type');
            $facebookUrl = $settings->value;

            $cachedItem->set($facebookUrl)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);
            return $facebookUrl;
        } else {
            return $cachedItem->get();
        }

    }

    public function getTwitterLink()
    {
        $cachedItem = $this->cachePool->getItem('Twitter');
        if (false === $cachedItem->isHit()) {
            /** @var \settings $settings */
            $settings = $this->entityManager->getRepository('settings');
            $settings->get('Twitter', 'type');
            $twitterUrl = $settings->value;

            $cachedItem->set($twitterUrl)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);
            return $twitterUrl;
        } else {
            return $cachedItem->get();
        }
    }


}