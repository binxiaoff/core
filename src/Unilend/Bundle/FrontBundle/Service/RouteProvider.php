<?php
namespace Unilend\Bundle\FrontBundle\Service;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class RouteProvider implements RouteProviderInterface
{
    /** @var EntityManager */
    private $entityManager;
    /** @var MemcacheCachePool */
    private $cachePool;

    public function __construct(EntityManager $entityManager, MemcacheCachePool $cachePool)
    {
        $this->entityManager = $entityManager;
        $this->cachePool     = $cachePool;
    }

    /**
     * @see RouteProviderInterface
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        return $this->getRouteCollection();
    }

    /**
     * @return RouteCollection
     */
    private function getRouteCollection()
    {
        $routeCollection = new RouteCollection();

        /** @var \tree $trees */
        $trees = $this->entityManager->getRepository('tree');

        foreach ($trees->select('status = 1 AND prive = 0') as $tree) {
            $routeCollection->add($tree['slug'], new Route($tree['slug']));
        }

        return $routeCollection;
    }

    /**
     * @see RouteProviderInterface
     */
    public function getRouteByName($name)
    {
        /** @var \tree $tree */
        $tree = $this->entityManager->getRepository('tree');

        if (false === $tree->get(['slug' => $name, 'status' => 1, 'prive' => 0])) {
            throw new RouteNotFoundException("No route found for path '$name'");
        }

        return new Route($tree->slug);
    }

    /**
     * @see RouteProviderInterface
     */
    public function getRoutesByNames($names)
    {
        if (is_null($names)) {
            return $this->getRouteCollection();
        }

        /** @var \tree $trees */
        $trees  = $this->entityManager->getRepository('tree');
        $routes = [];

        foreach ($trees->select('status = 1 AND prive = 0') as $tree) {
            $routes[$tree['slug']] = new Route($tree['slug']);
        }

        return $routes;
    }
}
