<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints\PreviousValue;

use Unilend\Core\Validator\Constraints\AbstractPreviousValueComparison;

/**
 * @Annotation
 */
class ScalarNotAssignable extends AbstractPreviousValueComparison
{
    public string $message = 'The value is locked to null.';
}
