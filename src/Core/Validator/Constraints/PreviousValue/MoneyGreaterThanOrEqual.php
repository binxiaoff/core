<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints\PreviousValue;

use Unilend\Core\Validator\Constraints\AbstractMoneyPreviousValueComparison;

/**
 * @Annotation
 */
class MoneyGreaterThanOrEqual extends AbstractMoneyPreviousValueComparison
{
    public string $message = 'The money amount is less than the previous one.';
}
