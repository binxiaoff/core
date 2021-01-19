<?php

declare(strict_types=1);

namespace Unilend\Core\ApiPlatform\Operation;

use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;
use Doctrine\Inflector\InflectorFactory;

class PathSegmentNameWithDomainGenerator implements PathSegmentNameGeneratorInterface
{
    /**
     * @inheritDoc
     */
    public function getSegmentName(string $name, bool $collection = true): string
    {
        $inflector = InflectorFactory::create()->build();

        $fragments = explode('_', $name, 2);

        foreach ($fragments as $key => &$fragment) {
            $fragment = $inflector->tableize($fragment);
            $fragment = $collection && array_key_last($fragments) === $key ? $inflector->pluralize($fragment) : $fragment;
        }

        return implode('/', $fragments);
    }
}
