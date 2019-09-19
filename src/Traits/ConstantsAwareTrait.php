<?php

declare(strict_types=1);

namespace Unilend\Traits;

use ReflectionClass;
use ReflectionException;

trait ConstantsAwareTrait
{
    /**
     * @param string|null $prefix
     *
     * @return array
     */
    private static function getConstants(?string $prefix = null): array
    {
        try {
            $self = new ReflectionClass(__CLASS__);
        } catch (ReflectionException $exception) {
            return [];
        }

        $constants = $self->getConstants();

        if ($constants && null !== $prefix) {
            $constants = array_filter(
                $constants,
                static function ($key) use ($prefix) {
                    return 0 === mb_strpos($key, $prefix);
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        return $constants;
    }

    /**
     * @param mixed       $value
     * @param string|null $prefix
     *
     * @return false|string
     */
    private static function getConstantKey($value, ?string $prefix = null)
    {
        $constants = self::getConstants($prefix);

        return array_search($value, $constants, true);
    }
}
