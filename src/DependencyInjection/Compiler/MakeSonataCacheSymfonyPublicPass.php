<?php

namespace Unilend\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\{Compiler\CompilerPassInterface, ContainerBuilder};

class MakeSonataCacheSymfonyPublicPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $id = 'sonata.cache.symfony';

        if ($container->hasDefinition($id)) {
            $container
                ->getDefinition($id)
                ->setPublic(true);
        }
    }
}
