<?php
namespace Unilend\Bundle\TranslationBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideSonataSymfonyCacheClassPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('sonata.cache.symfony')->setClass('Unilend\Bundle\TranslationBundle\Service\SymfonyCache');;
    }
}
