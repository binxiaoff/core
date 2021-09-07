<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Constant;

use KLS\Core\Traits\ConstantsAwareTrait;

abstract class AbstractEnum
{
    use ConstantsAwareTrait;

    /**
     * Is private to forbid instantiation.
     */
    final private function __construct()
    {
    }

    /**
     * @return string[]|array
     */
    final public static function getConstList(): array
    {
        return static::getConstants();
    }
}
