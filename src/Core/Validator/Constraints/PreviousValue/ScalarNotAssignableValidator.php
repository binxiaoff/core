<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints\PreviousValue;

use KLS\Core\Validator\Constraints\AbstractScalarPreviousValueComparisonValidator;

class ScalarNotAssignableValidator extends AbstractScalarPreviousValueComparisonValidator
{
    protected function compareValues($value, $previousValue): bool
    {
        return null === $value || null !== $previousValue;
    }
}
