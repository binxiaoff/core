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
        $name =  preg_replace('~_~', '/', $name, 1);

        return $collection ? Inflector::pluralize($name) : $name;
    }
}
