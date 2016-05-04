<?php

namespace Unilend\Bundle\KernelBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Unilend\Bundle\KernelBundle\DependencyInjection\Compiler\AddDbalCacheConfigurationPass;

/**
 * Bundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class KernelBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddDbalCacheConfigurationPass());
    }
}
