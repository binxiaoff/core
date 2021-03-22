<?php

declare(strict_types=1);

namespace Unilend\Core\ApiPlatform\Operation;

use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;
use Doctrine\Inflector\InflectorFactory;

class PathSegmentNameWithDomainGenerator implements PathSegmentNameGeneratorInterface
{
    private const API_DOMAINS = ['core', 'syndication'];

    /**
     * @inheritDoc
     */
    public function getSegmentName(string $name, bool $collection = true): string
    {

        if (1 === preg_match(sprintf('/^(%s)_(.+)/', implode('|', self::API_DOMAINS)), $name, $matches)) {
            $inflector    = InflectorFactory::create()->build();
            $resourceName = $inflector->tableize($matches[2]);
            $resourceName = $collection ? $inflector->pluralize($resourceName) : $resourceName;

            return $matches[1] . '/' . $resourceName;
        }

        // Some resources pass here without "domain" at the very first running when the Symfony cache is generating, we just let them bypass.
        return $name;
    }
}
