<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Traits;

use ReflectionClass;
use ReflectionException;

trait PropertyValueTrait
{
    /**
     * @param mixed $entity
     * @param mixed $value
     *
     * @throws ReflectionException
     */
    private function forcePropertyValue($entity, string $property, $value): void
    {
        $reflection         = new ReflectionClass(\get_class($entity));
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($entity, $value);
    }
}
