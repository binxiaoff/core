<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints\PreviousValue;

use KLS\Core\Validator\Constraints\AbstractPreviousValueComparison;

/**
 * @Annotation
 */
class MoneyNotAssignable extends AbstractPreviousValueComparison
{
    public string $message = 'The money amount is locked to null.';
}
