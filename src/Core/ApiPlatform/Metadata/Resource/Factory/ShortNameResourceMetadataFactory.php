<?php

declare(strict_types=1);

namespace KLS\Core\ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Inflector\InflectorFactory;

final class ShortNameResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private const DOMAIN_CORE            = 'Core';
    private const DOMAIN_SYNDICATION     = 'Syndication';
    private const DOMAIN_AGENCY          = 'Agency';
    private const DOMAIN_CREDIT_GUARANTY = 'CreditGuaranty';

    private ResourceMetadataFactoryInterface $decorated;

    public function __construct(ResourceMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function create(string $resourceClass): ResourceMetadata
    {
        $inflector = InflectorFactory::create()->build();

        $resourceMetadata = $this->decorated->create($resourceClass);

        if (false !== \mb_strrpos($resourceClass, '\\')) {
            $exploded  = \explode('\\', $resourceClass);
            $domain    = $inflector->tableize($this->getDomain($resourceClass));
            $entity    = $inflector->tableize(\end($exploded));
            $shortName = $domain . '_' . $entity;

            return $resourceMetadata->withShortName($shortName);
        }

        return $resourceMetadata->withShortName($resourceClass);
    }

    private function getDomain(string $resourceClass): string
    {
        if (false !== \mb_stripos($resourceClass, self::DOMAIN_AGENCY)) {
            return self::DOMAIN_AGENCY;
        }

        if (false !== \mb_stripos($resourceClass, self::DOMAIN_SYNDICATION)) {
            return self::DOMAIN_SYNDICATION;
        }

        if (false !== \mb_stripos($resourceClass, self::DOMAIN_CREDIT_GUARANTY)) {
            return self::DOMAIN_CREDIT_GUARANTY;
        }

        return self::DOMAIN_CORE;
    }
}
