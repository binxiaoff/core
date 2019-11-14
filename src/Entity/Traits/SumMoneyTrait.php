<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Exception;
use Unilend\Entity\Embeddable\Money;

trait SumMoneyTrait
{
    /**
     * @param Money[] $moneyCollection
     * @param string  $currency
     *
     * @throws Exception
     *
     * @return Money
     */
    private function sumMoney(array $moneyCollection, string $currency): Money
    {
        $sum = 0;

        foreach ($moneyCollection as $money) {
            if ($money->getCurrency() !== $currency) {
                throw new Exception('This method doesn\'t support multiple currencies.');
            }

            $sum = round(bcadd((string) $sum, (string) $money->getAmount(), 3), 2);
        }

        return new Money((string) $sum, $currency);
    }
}
