<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints\PreviousValue;

use KLS\Core\Validator\Constraints\AbstractPreviousValueComparison;

/**
 * @Annotation
 */
class NumericGreaterThanOrEqual extends AbstractPreviousValueComparison
{
    public string $message = 'The money amount is less than the previous one.';
    public int $scale      = 2;
}
