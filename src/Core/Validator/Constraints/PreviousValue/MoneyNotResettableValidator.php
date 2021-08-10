<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints\PreviousValue;

use KLS\Core\Validator\Constraints\AbstractMoneyPreviousValueComparisonValidator;

class MoneyNotResettableValidator extends AbstractMoneyPreviousValueComparisonValidator
{
    protected function compareValues($value, $previousValue): bool
    {
        return null !== $value->getAmount() || null === $previousValue->getAmount();
    }
}
