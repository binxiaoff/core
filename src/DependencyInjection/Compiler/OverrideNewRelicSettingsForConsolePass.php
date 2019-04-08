<?php

namespace Unilend\DependencyInjection\Compiler;

use Ekino\NewRelicBundle\Listener\CommandListener;
use Ekino\NewRelicBundle\NewRelic\Config;
use Symfony\Component\DependencyInjection\{Compiler\CompilerPassInterface, ContainerBuilder};

class OverrideNewRelicSettingsForConsolePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $newRelic        = $container->getDefinition(Config::class);
        $newRelicConsole = $container->register('ekino.new_relic.console', Config::class);
        $newRelicConsole->setArguments($newRelic->getArguments());

        $newRelicConsole->replaceArgument('$name', $container->getParameter('new_relic.console_app_name'));

        $container->getDefinition(CommandListener::class)->replaceArgument('$config', $newRelicConsole);
    }
}
