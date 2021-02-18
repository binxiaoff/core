<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

/**
 * @Annotation
 */
class MoneyLessThanOrEqual extends AbstractMoneyPreviousValueComparison
{
    public string $message = 'The money amount is greater than the previous one.';
}
