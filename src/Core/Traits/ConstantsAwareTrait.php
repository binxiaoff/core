<?php

declare(strict_types=1);

namespace Unilend\Core\Traits;

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
            $self = new ReflectionClass(static::class);
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
}
