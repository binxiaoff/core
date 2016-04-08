<?php

namespace Unilend\core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * A simple implementation of ContainerAwareInterface.
 *
 */
class ContainerAware implements ContainerAwareInterface
{
    /**
     * @var ContainerBuilder
     *
     * @api
     */
    protected $container;

    public function __construct()
    {
        $container = new ContainerBuilder();
        $this->setContainer($container);
        $loader = new XmlFileLoader($this->container, new FileLocator(__DIR__ . '/../../Config'));
        $loader->load('services.xml');
    }

    /**
     * Sets the Container associated with this Controller.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
