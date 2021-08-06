<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints\PreviousValue;

use Unilend\Core\Service\MoneyCalculator;
use Unilend\Core\Validator\Constraints\AbstractMoneyPreviousValueComparisonValidator;

class MoneyLessThanOrEqualValidator extends AbstractMoneyPreviousValueComparisonValidator
{
    protected function compareValues($value, $previousValue): bool
    {
        return 1 !== MoneyCalculator::compare($value, $previousValue);
    }
}
