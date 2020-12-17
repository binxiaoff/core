<?php

declare(strict_types=1);

namespace Unilend\Core\ApiPlatform\Metadata\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

final class ShortNameResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $decorated;

    /**
     * @param ResourceMetadataFactoryInterface $decorated
     */
    public function __construct(ResourceMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);

        if (false !== $pos = strrpos($resourceClass, '\\')) {
            $exploded = explode('\\', $resourceClass);
            $domain = $exploded[1];
            $entity = end($exploded);
            $shortName = (strtolower(($domain . '_' . $entity)));

            return $resourceMetadata->withShortName($shortName);
        }

        return $resourceMetadata->withShortName($resourceClass);
    }
}
