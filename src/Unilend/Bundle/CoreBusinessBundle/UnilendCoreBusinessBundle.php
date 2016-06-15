<?php

namespace Unilend\Bundle\CoreBusinessBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Unilend\Bundle\CoreBusinessBundle\DependencyInjection\Compiler\AddDbalCacheConfigurationPass;

/**
 * Bundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class UnilendCoreBusinessBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddDbalCacheConfigurationPass());
    }
}
