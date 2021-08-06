<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints\PreviousValue;

use Unilend\Core\Validator\Constraints\AbstractMoneyPreviousValueComparisonValidator;

class MoneyNotAssignableValidator extends AbstractMoneyPreviousValueComparisonValidator
{
    protected function compareValues($value, $previousValue): bool
    {
        return null === $value->getAmount() || null !== $previousValue->getAmount();
    }
}
