<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints\PreviousValue;

use KLS\Core\Validator\Constraints\AbstractScalarPreviousValueComparisonValidator;

class NumericGreaterThanOrEqualValidator extends AbstractScalarPreviousValueComparisonValidator
{
    protected function compareValues($value, $previousValue): bool
    {
        return $value >= $previousValue;
    }
}
