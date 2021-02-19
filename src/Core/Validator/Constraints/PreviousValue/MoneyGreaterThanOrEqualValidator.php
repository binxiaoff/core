<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints\PreviousValue;

use Unilend\Core\Entity\Interfaces\MoneyInterface;
use Unilend\Core\Service\MoneyCalculator;
use Unilend\Core\Validator\Constraints\AbstractMoneyPreviousValueComparisonValidator;

class MoneyGreaterThanOrEqualValidator extends AbstractMoneyPreviousValueComparisonValidator
{
    /**
     * @inheritDoc
     */
    protected function compareValues(MoneyInterface $value, MoneyInterface $previousValue): bool
    {
        return -1 !== MoneyCalculator::compare($value, $previousValue);
    }
}
