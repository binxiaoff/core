<?php
namespace Unilend\Bundle\Doctrine\DBAL\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages the configuration
 *
 */
class DoctrineExtension extends Extension
{
    /**
     * @var string
     */
    private $defaultConnection;

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config        = $this->processConfiguration($configuration, $configs);

        if (! empty($config['dbal'])) {
            $this->dbalLoad($config['dbal'], $container);
        }
    }


    public function dbalLoad(array $config, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources//config'));
        $loader->load('dbal.xml');

        if (empty($config['default_connection'])) {
            $keys                         = array_keys($config['connections']);
            $config['default_connection'] = reset($keys);
        }

        $this->defaultConnection = $config['default_connection'];

        $connections = array();
        foreach ($config['connections'] as $name => $connectionConfig) {
            $connections[$name] = sprintf('database.%s_connection', $name);
        }

        $container->setParameter('database.connections', $connections);
        $container->setParameter('database.default_connection', $this->defaultConnection);

        $def = $container->getDefinition('unilend.dbal.connection');
        $def->setFactory(array(new Reference('unilend.dbal.connection_factory'), 'createConnection'));

        foreach ($config['connections'] as $name => $connection) {
            $this->loadDbalConnection($name, $connection, $container);
        }
    }

    /**
     * Loads a configured DBAL connection.
     *
     * @param string           $name       The name of the connection
     * @param array            $connection A dbal connection configuration.
     * @param ContainerBuilder $container  A ContainerBuilder instance
     */
    protected function loadDbalConnection($name, array $connection, ContainerBuilder $container)
    {
        $container
            ->setDefinition(sprintf('unilend.dbal.%s_connection', $name), new DefinitionDecorator('unilend.dbal.connection'))
            ->setArguments(array($connection));
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }
}
