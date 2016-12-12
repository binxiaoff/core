<?php
namespace Unilend\Bundle\CommandBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideNewRelicSettingsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $newRelic = $container->getDefinition('ekino.new_relic');

        $newRelicConsole = $container->getDefinition('ekino.new_relic.console');
        $newRelicConsole->replaceArgument(0, $container->getParameter('new_relic.console_app_name'))
            ->replaceArgument(1, $newRelic->getArgument(1))
            ->replaceArgument(2, $newRelic->getArgument(2))
            ->replaceArgument(3, $newRelic->getArgument(3))
            ->replaceArgument(4, $newRelic->getArgument(4))
        ;

        $container->getDefinition('ekino.new_relic.command_listener')->replaceArgument(0, $newRelicConsole);
    }
}
