<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints\PreviousValue;

use KLS\Core\Validator\Constraints\AbstractPreviousValueComparison;

/**
 * @Annotation
 */
class MoneyNotResettable extends AbstractPreviousValueComparison
{
    public string $message = 'The money amount can not be reset to null.';
}
