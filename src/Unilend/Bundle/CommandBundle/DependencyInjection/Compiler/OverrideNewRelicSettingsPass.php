<?php
namespace Unilend\Bundle\CommandBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideNewRelicSettingsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('ekino.new_relic')->replaceArgument(0, $container->getParameter('new_relic.console_app_name'));
    }
}
