<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

/**
 * @Annotation
 */
class MoneyNotResettable extends AbstractMoneyPreviousValueComparison
{
    public string $message = 'The money amount can not be reset to null.';
}
