<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Bundle\CoreBusinessBundle\Entity\Redirections;
use Unilend\Bundle\CoreBusinessBundle\Entity\Tree;
use Unilend\librairies\CacheKeys;

/**
 * Class RedirectionHandler
 * @package Unilend\Bundle\FrontBundle\Service
 */
class RedirectionHandler
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var CacheItemPoolInterface */
    private $cacheItemPool;

    /**
     * RedirectionHandler constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param CacheItemPoolInterface $cacheItemPool
     */
    public function __construct(EntityManagerInterface $entityManager, CacheItemPoolInterface $cacheItemPool)
    {
        $this->entityManager = $entityManager;
        $this->cacheItemPool = $cacheItemPool;
    }

    /**
     * @param Request $request
     *
     * @return null|RedirectResponse
     */
    public function handle(Request $request)
    {
        $requestedPath = $request->getPathInfo();
        $newPath       = null;
        $cachedItem    = $this->cacheItemPool->getItem(md5('redirection_handler.handle.' . $requestedPath));
        if (false === $cachedItem->isHit()) {
            $redirection = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Redirections')->findOneBy([
                'fromSlug' => $requestedPath,
                'status'   => Redirections::STATUS_ENABLED
            ]);

            if ($redirection) {
                $newPath = ['slug' => $redirection->getToSlug(), 'code' => $redirection->getType()];
            } else {
                $newPath = [];
            }
            $cachedItem->set($newPath)
                ->expiresAfter(CacheKeys::MEDIUM_TIME);
            $this->cacheItemPool->save($cachedItem);
        } else {
            $newPath = $cachedItem->get();
        }

        if ($newPath) {
            return new RedirectResponse($newPath['slug'], $newPath['code']);
        }

        return null;
    }
}
