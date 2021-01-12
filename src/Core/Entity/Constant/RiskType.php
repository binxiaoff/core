<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Constant;

use Unilend\Core\Traits\ConstantsAwareTrait;

class RiskType
{
    use ConstantsAwareTrait;

    public const RISK     = 'risk';
    public const RISK_TREASURY = 'risk_treasury';

    /**
     * Is private to forbid instanciation
     */
    private function __construct()
    {
    }

    /**
     * @return string[]|array
     */
    public static function getRiskTypes(): array
    {
        return static::getConstants();
    }
}
