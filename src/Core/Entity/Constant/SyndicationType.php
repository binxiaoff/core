<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Constant;

use Unilend\Core\Traits\ConstantsAwareTrait;

class SyndicationType
{
    use ConstantsAwareTrait;

    public const PRIMARY   = 'primary';
    public const SECONDARY = 'secondary';

    /**
     * Is private to forbid instanciation
     */
    private function __construct()
    {
    }

    /**
     * @return string[]|array
     */
    public static function getSyndicationTypes(): array
    {
        return static::getConstants();
    }
}
