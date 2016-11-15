<?php

namespace Unilend\Bundle\CommandBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Unilend\Bundle\CommandBundle\DependencyInjection\Compiler\OverrideNewRelicSettingsPass;

class UnilendCommandBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideNewRelicSettingsPass());
    }
}
