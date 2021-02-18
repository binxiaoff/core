<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

/**
 * @Annotation
 */
class MoneyNotAssignable extends AbstractMoneyPreviousValueComparison
{
    public string $message = 'The money amount is locked to null.';
}
