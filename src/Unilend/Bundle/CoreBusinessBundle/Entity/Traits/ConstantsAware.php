<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity\Traits;


trait ConstantsAware
{
    private static function getConstants($prefix = null)
    {
        try {
            $self      = new \ReflectionClass(__CLASS__);
            $constants = $self->getConstants();
        } catch (\ReflectionException $exception) {
            return [];
        }

        if ($constants && $prefix) {
            $constants = array_filter(
                $constants,
                function($key) use ($prefix) {
                    return $prefix === substr($key, 0, strlen($prefix));
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        return $constants;
    }
}
