<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Unilend\Core\Entity\Interfaces\MoneyInterface;
use Unilend\Core\Service\MoneyCalculator;

class MoneyLessThanOrEqualValidator extends AbstractMoneyPreviousValueComparisonValidator
{
    /**
     * @inheritDoc
     */
    protected function compareValues(MoneyInterface $value, MoneyInterface $previousValue): bool
    {
        return 1 !== MoneyCalculator::compare($value, $previousValue);
    }
}
