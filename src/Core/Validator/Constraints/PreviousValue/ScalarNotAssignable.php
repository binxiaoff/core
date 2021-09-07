<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints\PreviousValue;

use KLS\Core\Validator\Constraints\AbstractPreviousValueComparison;

/**
 * @Annotation
 */
class ScalarNotAssignable extends AbstractPreviousValueComparison
{
    public string $message = 'The value is locked to null.';
}
