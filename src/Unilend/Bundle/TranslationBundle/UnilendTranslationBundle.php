<?php

namespace Unilend\Bundle\TranslationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Unilend\Bundle\TranslationBundle\DependencyInjection\Compiler\OverrideSonataSymfonyCacheClassPass;

class UnilendTranslationBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideSonataSymfonyCacheClassPass());
    }
}
