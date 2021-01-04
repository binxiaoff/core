<?php

declare(strict_types=1);

namespace Unilend\Core\ApiPlatform\Operation;

use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;
use Doctrine\Common\Inflector\Inflector;

class PathSegmentNameWithDomainGenerator implements PathSegmentNameGeneratorInterface
{
    /**
     * @inheritDoc
     */
    public function getSegmentName(string $name, bool $collection = true): string
    {
        $fragments = explode('_', $name, 2);

        foreach ($fragments as $key => &$fragment) {
            $fragment = Inflector::tableize($fragment);
            $fragment = $collection && array_key_last($fragments) === $key ? Inflector::pluralize($fragment) : $fragment;
        }

        return implode('/', $fragments);
    }
}
