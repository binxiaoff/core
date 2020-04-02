<?php

declare(strict_types=1);

namespace Unilend\Listener\ApiPlatform;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RemoveTagsOnOriginalDeserializeListener implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $container
            ->findDefinition('api_platform.listener.request.deserialize')
            ->clearTags()
        ;
    }
}
