<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints\PreviousValue;

use Unilend\Core\Validator\Constraints\AbstractPreviousValueComparison;

/**
 * @Annotation
 */
class MoneyNotAssignable extends AbstractPreviousValueComparison
{
    public string $message = 'The money amount is locked to null.';
}
