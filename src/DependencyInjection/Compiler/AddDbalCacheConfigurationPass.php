<?php

namespace Unilend\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\{Compiler\CompilerPassInterface, ContainerBuilder, Reference};

class AddDbalCacheConfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $id = 'doctrine.dbal.default_connection.configuration';

        if ($container->hasDefinition($id)) {
            $container
                ->getDefinition($id)
                ->addMethodCall('setResultCacheImpl', array(new Reference('doctrine.orm.default_result_cache')))
            ;
        }
    }
}
