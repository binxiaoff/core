<?php

declare(strict_types=1);

namespace Unilend\Service\FrontRouting;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class FrontRouterDecorator implements RouterInterface
{
    public const FRONT_ROUTE_PREFIX = 'front';

    /** @var RouterInterface */
    private $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        if (0 === mb_strpos($name, static::FRONT_ROUTE_PREFIX)) {
            $referenceType = static::ABSOLUTE_URL;
        }

        return $this->router->generate($name, $parameters, $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        return $this->router->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->router->getContext();
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        return $this->router->getRouteCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        return $this->router->match($pathinfo);
    }
}
