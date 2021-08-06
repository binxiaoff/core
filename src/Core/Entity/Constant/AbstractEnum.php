<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Constant;

use Unilend\Core\Traits\ConstantsAwareTrait;

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
