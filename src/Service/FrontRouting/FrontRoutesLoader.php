<?php

declare(strict_types=1);

namespace Unilend\Service\FrontRouting;

use ApiPlatform\Core\Action\NotFoundAction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class FrontRoutesLoader
{
    private const ROUTES = [
        'index'                                 => ['path' => '/'],
        'login'                                 => ['path' => '/login'],
        'privacy'                               => ['path' => '/politique-de-confidentialite'],
        'legal_notice'                          => ['path' => '/mentions-legales'],
        'tos'                                   => ['path' => '/cgu'],
        '404'                                   => ['path' => '/404'],
        '403'                                   => ['path' => '/403'],
        'password_change'                       => ['path' => '/password/change/{token}'],
        'password_request'                      => ['path' => '/password/request'],
        'participation_index'                   => ['path' => '/participation'],
        'participation_project_view'            => ['path' => '/participation/project/{hash}'],
        'participation_project_confidentiality' => ['path' => '/participation/project/{hash}/confidentiality'],
        'habilitation'                          => ['path' => '/habilitations'],
        'arrangement_kanban'                    => ['path' => '/arrangement'],
        'arrangement_project_view'              => ['path' => '/arrangement/project/{hash}'],
        'arrangement_project_publish'           => ['path' => '/arrangement/project/{hash}/publication'],
        'arrangement_project_syndicate'         => ['path' => '/arrangement/project/{hash}/syndication'],
        'arrangement_project_create'            => ['path' => '/arrangement/project'],
    ];

    /** @var string */
    private $frontUrl;

    /**
     * @param string $frontUrl
     */
    public function __construct(string $frontUrl)
    {
        $parsed         = parse_url($frontUrl);
        $this->frontUrl = $parsed['host'];
    }

    /**
     * @return RouteCollection
     */
    public function __invoke()
    {
        $routes = new RouteCollection();

        foreach (self::ROUTES as $name => $route) {
            $name = FrontRouterDecorator::FRONT_ROUTE_PREFIX . '_' . $name;

            $routes->add(
                $name,
                new Route(
                    $route['path'],
                    ['_controller' => NotFoundAction::class],
                    $route['requirements'] ?? [],
                    [],
                    $this->frontUrl,
                    ['https'],
                    [Request::METHOD_GET]
                )
            );
        }

        return $routes;
    }
}
