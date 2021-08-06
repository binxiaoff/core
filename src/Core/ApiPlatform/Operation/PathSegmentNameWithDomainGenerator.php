<?php

declare(strict_types=1);

namespace Unilend\Core\ApiPlatform\Operation;

use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;
use Doctrine\Inflector\InflectorFactory;

class PathSegmentNameWithDomainGenerator implements PathSegmentNameGeneratorInterface
{
    private const API_DOMAINS = ['core', 'syndication', 'agency', 'credit_guaranty'];

    public function getSegmentName(string $name, bool $collection = true): string
    {
        $inflector = InflectorFactory::create()->build();
        $domain    = null;
        // The segment names with domain.
        if (1 === \preg_match(\sprintf('/^(%s)_(.+)/', \implode('|', self::API_DOMAINS)), $name, $matches)) {
            [, $domain, $name] = $matches;
        }
        // It exists also the segment names without domain (ex. sub-resource).
        $name = $inflector->tableize($name);
        $name = $collection ? $inflector->pluralize($name) : $name;

        return $domain ? $domain . '/' . $name : $name;
    }
}
