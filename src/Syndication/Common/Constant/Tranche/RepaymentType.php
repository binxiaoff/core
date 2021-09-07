<?php

declare(strict_types=1);

namespace KLS\Syndication\Common\Constant\Tranche;

use KLS\Core\Entity\Constant\AbstractEnum;

final class RepaymentType extends AbstractEnum
{
    public const ATYPICAL         = 'atypical';
    public const IN_FINE          = 'in_fine';
    public const CONSTANT_CAPITAL = 'constant_capital';
    public const FIXED            = 'repayment_fixed';

    /**
     * @return string[]|array
     */
    public static function getAmortizableRepaymentTypes(): array
    {
        return [
            self::CONSTANT_CAPITAL,
            self::FIXED,
        ];
    }
}
