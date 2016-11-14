<?php
namespace Unilend\Bundle\FrontBundle\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\librairies\CacheKeys;

class RouteProvider implements RouteProviderInterface
{
    /** List of routes available through CMS */
    const ROUTE_PROJECT_REQUEST_LANDING_PAGE = 'lp-depot-de-dossier';
    const ROUTE_LENDER_FAQ                   = 'faq-preteur';
    const ROUTE_BORROWER_FAQ                 = 'faq-emprunteur';
    const ROUTE_OUR_ETHICS                   = 'charte-de-deontologie';

    /** @var EntityManager */
    private $entityManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var CacheItemPoolInterface */
    private $cacheItemPool;

    public function __construct(EntityManager $entityManager, LoggerInterface $logger, CacheItemPoolInterface $cacheItemPool)
    {
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
        $this->cacheItemPool = $cacheItemPool;
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
        $cachedItem = $this->cacheItemPool->getItem('RouteProvider_getRouteByName' . $name);

        if ($cachedItem->isHit()) {
            $path = $cachedItem->get();
        } else {
            /** @var \tree $tree */
            $tree = $this->entityManager->getRepository('tree');
            if ($tree->get(['slug' => $name, 'status' => 1, 'prive' => 0])) {
                $path = $tree->slug;
                $cachedItem->set($path)->expiresAfter(CacheKeys::MEDIUM_TIME);
                $this->cacheItemPool->save($cachedItem);
            } else {
                $this->logger->warning('No CMS page found for path ' . $name);
                $path = $name;
            }
        }

        return new Route($path);
    }

    /**
     * @see RouteProviderInterface
     */
    public function getRoutesByNames($names)
    {
        if (is_null($names)) {
            return $this->getRouteCollection();
        }

        $routes = [];
        foreach ($names as $name) {
            $routes[] = $this->getRouteByName($name);
        }

        return $routes;
    }
}
