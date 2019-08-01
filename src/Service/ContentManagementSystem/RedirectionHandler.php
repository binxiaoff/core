<?php

declare(strict_types=1);

namespace Unilend\Service\ContentManagementSystem;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\{CacheItemPoolInterface, InvalidArgumentException};
use Symfony\Component\HttpFoundation\{RedirectResponse, Request};
use Unilend\CacheKeys;
use Unilend\Entity\Redirections;

/**
 * Class RedirectionHandler.
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
     * @throws InvalidArgumentException
     *
     * @return RedirectResponse|null
     */
    public function handle(Request $request)
    {
        $requestedPath = $request->getPathInfo();
        $newPath       = null;
        $cachedItem    = $this->cacheItemPool->getItem(md5('redirection_handler.handle.' . $requestedPath));
        if (false === $cachedItem->isHit()) {
            $redirection = $this->entityManager->getRepository(Redirections::class)->findOneBy([
                'fromSlug' => $requestedPath,
                'status'   => Redirections::STATUS_ENABLED,
            ]);

            if ($redirection) {
                $newPath = ['slug' => $redirection->getToSlug(), 'code' => $redirection->getType()];
            } else {
                $newPath = [];
            }
            $cachedItem->set($newPath)
                ->expiresAfter(CacheKeys::MEDIUM_TIME)
            ;
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
