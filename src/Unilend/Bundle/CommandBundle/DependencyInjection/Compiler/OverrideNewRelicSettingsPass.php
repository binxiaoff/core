<?php

namespace Unilend\Bundle\CommandBundle\DependencyInjection\Compiler;

use Ekino\Bundle\NewRelicBundle\NewRelic\NewRelic;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideNewRelicSettingsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $newRelic        = $container->findDefinition('ekino.new_relic');
        $newRelicConsole = $container->register('ekino.new_relic.console', NewRelic::class);

        $newRelicConsole->setArguments([
            $container->getParameter('new_relic.console_app_name'),
            $newRelic->getArgument(1),
            $newRelic->getArgument(2),
            $newRelic->getArgument(3),
            $newRelic->getArgument(4)
        ]);

        $container->getDefinition('ekino.new_relic.command_listener')->replaceArgument(0, $newRelicConsole);
    }
}
