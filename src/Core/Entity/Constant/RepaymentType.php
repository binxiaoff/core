<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Constant;

final class RepaymentType extends AbstractEnum
{
    protected const ATYPICAL         = 'atypical';
    protected const IN_FINE          = 'in_fine';
    protected const CONSTANT_CAPITAL = 'constant_capital';
    protected const FIXED            = 'repayment_fixed';

    /**
     * @return string[]|array
     */
    public static function getAmortizableRepaymentTypes()
    {
        return [
            self::CONSTANT_CAPITAL,
            self::FIXED,
        ];
    }
}
