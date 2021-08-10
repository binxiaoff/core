<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints\PreviousValue;

use KLS\Core\Validator\Constraints\AbstractPreviousValueComparison;

/**
 * @Annotation
 */
class MoneyLessThanOrEqual extends AbstractPreviousValueComparison
{
    public string $message = 'The money amount is greater than the previous one.';
}
