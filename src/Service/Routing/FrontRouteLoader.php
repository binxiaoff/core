<?php

declare(strict_types=1);

namespace Unilend\Service\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

class FrontRouteLoader extends Loader
{
    private $host;

    /**
     * @param string $host
     */
    public function __construct(string $host)
    {
        $this->host = parse_url($host, PHP_URL_HOST) ?? $host;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $routes = new RouteCollection();

        $importedRoutes = $this->import($resource);

        if ($importedRoutes instanceof RouteCollection) {
            $importedRoutes->setHost($this->host);
            $importedRoutes->setMethods([Request::METHOD_GET]);
            $importedRoutes->setSchemes(['https']);

            $routes->addCollection($importedRoutes);
        }

        return $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null): bool
    {
        return 'front' === $type;
    }
}
