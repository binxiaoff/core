<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints\PreviousValue;

use Unilend\Core\Validator\Constraints\AbstractPreviousValueComparison;

/**
 * @Annotation
 */
class MoneyGreaterThanOrEqual extends AbstractPreviousValueComparison
{
    public string $message = 'The money amount is less than the previous one.';
}
