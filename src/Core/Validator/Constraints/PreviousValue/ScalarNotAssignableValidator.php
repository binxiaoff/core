<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints\PreviousValue;

use Unilend\Core\Validator\Constraints\AbstractScalarPreviousValueComparisonValidator;

class ScalarNotAssignableValidator extends AbstractScalarPreviousValueComparisonValidator
{
    /**
     * {@inheritDoc}
     */
    protected function compareValues($value, $previousValue): bool
    {
        return null === $value || null !== $previousValue;
    }
}
