<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints\PreviousValue;

use KLS\Core\Service\MoneyCalculator;
use KLS\Core\Validator\Constraints\AbstractMoneyPreviousValueComparisonValidator;

class MoneyLessThanOrEqualValidator extends AbstractMoneyPreviousValueComparisonValidator
{
    protected function compareValues($value, $previousValue): bool
    {
        return 1 !== MoneyCalculator::compare($value, $previousValue);
    }
}
