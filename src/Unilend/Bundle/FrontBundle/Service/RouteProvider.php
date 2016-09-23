<?php
namespace Unilend\Bundle\FrontBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class RouteProvider implements RouteProviderInterface
{
    /** List of routes available through CMS */
    const ROUTE_PROJECT_REQUEST_LANDING_PAGE = 'lp-depot-de-dossier';
    const ROUTE_LENDER_FAQ                   = 'faq-preteur';
    const ROUTE_BORROWER_FAQ                 = 'faq-emprunteur';
    const ROUTE_OUR_ETHICS                   = 'charte-de-deontologie';
    const ROUTE_UNILEND_RATING               = 'comprendre-la-note-unilend';

    /** @var EntityManager */
    private $entityManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(EntityManager $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger     = $logger;
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
            $this->logger->warning('No CMS page found for path ' . $name);
            return new Route($name);
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
