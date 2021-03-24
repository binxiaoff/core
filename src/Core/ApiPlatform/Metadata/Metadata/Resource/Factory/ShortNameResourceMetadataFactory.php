<?php

declare(strict_types=1);

namespace Unilend\Core\ApiPlatform\Metadata\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Inflector\InflectorFactory;

final class ShortNameResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private ResourceMetadataFactoryInterface $decorated;

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
        $inflector = InflectorFactory::create()->build();

        $resourceMetadata = $this->decorated->create($resourceClass);

        if (false !== strrpos($resourceClass, '\\')) {
            $exploded  = explode('\\', $resourceClass);
            $domain    = $inflector->tableize($exploded[1]);
            $entity    = $inflector->tableize(end($exploded));
            $shortName = $domain . '_' . $entity;

            return $resourceMetadata->withShortName($shortName);
        }

        return $resourceMetadata->withShortName($resourceClass);
    }
}
