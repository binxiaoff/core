<?php

namespace Unilend\Service\Front;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Unilend\CacheKeys;
use Unilend\Entity\Tree;
use Unilend\Repository\TreeRepository;

class RouteProvider implements RouteProviderInterface
{
    /** @var TreeRepository */
    private $treeRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var CacheItemPoolInterface */
    private $cacheItemPool;

    /**
     * @param TreeRepository         $treeRepository
     * @param LoggerInterface        $logger
     * @param CacheItemPoolInterface $cacheItemPool
     */
    public function __construct(TreeRepository $treeRepository, LoggerInterface $logger, CacheItemPoolInterface $cacheItemPool)
    {
        $this->treeRepository = $treeRepository;
        $this->logger         = $logger;
        $this->cacheItemPool  = $cacheItemPool;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        return $this->getRouteCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteByName($name)
    {
        $cachedItem = $this->cacheItemPool->getItem('RouteProvider_getRouteByName' . $name);

        if ($cachedItem->isHit()) {
            $path = $cachedItem->get();
        } else {
            $tree = $this->treeRepository->findOneBy([
                'slug'   => $name,
                'status' => Tree::STATUS_ONLINE,
                'prive'  => Tree::VISIBILITY_PUBLIC,
            ]);

            if ($tree) {
                $path = $tree->getSlug();
                $cachedItem->set($path)->expiresAfter(CacheKeys::MEDIUM_TIME);
                $this->cacheItemPool->save($cachedItem);
            } else {
                $this->logger->warning('No CMS page found for path ' . $name);
                $path = $name;
            }
        }

        return new Route($path, [], [], ['utf8' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutesByNames($names)
    {
        if (null === $names) {
            return $this->getRouteCollection();
        }

        $routes = [];
        foreach ($names as $name) {
            $routes[] = $this->getRouteByName($name);
        }

        return $routes;
    }

    /**
     * @return RouteCollection
     */
    private function getRouteCollection()
    {
        $routeCollection = new RouteCollection();
        $trees           = $this->treeRepository->findBy(['status' => Tree::STATUS_ONLINE, 'prive' => Tree::VISIBILITY_PUBLIC]);

        foreach ($trees as $tree) {
            $routeCollection->add($tree->getSlug(), new Route($tree->getSlug()));
        }

        return $routeCollection;
    }
}
