<?php

declare(strict_types=1);

namespace Unilend\Core\Service;

use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\{NullableMoney};
use Unilend\Core\Entity\Interfaces\MoneyInterface;
use Unilend\Core\Exception\Money\DifferentCurrencyException;

class MoneyCalculator
{
    public static function add(MoneyInterface $leftAddend, MoneyInterface $rightAddend): MoneyInterface
    {
        if (static::isDifferentCurrency($leftAddend, $rightAddend)) {
            throw new DifferentCurrencyException($leftAddend, $rightAddend);
        }

        if (static::isBothNullMoney($leftAddend, $rightAddend)) {
            return new NullableMoney();
        }

        return new Money(
            $leftAddend->getCurrency() ?? $rightAddend->getCurrency(),
            \bcadd((string) $leftAddend->getAmount(), (string) $rightAddend->getAmount(), 2)
        );
    }

    public static function subtract(MoneyInterface $minuend, MoneyInterface $subtrahend): MoneyInterface
    {
        if (static::isDifferentCurrency($minuend, $subtrahend)) {
            throw new DifferentCurrencyException($minuend, $subtrahend);
        }

        if (static::isBothNullMoney($minuend, $subtrahend)) {
            return new NullableMoney();
        }

        return new Money(
            $minuend->getCurrency() ?? $subtrahend->getCurrency(),
            \bcsub((string) $minuend->getAmount(), (string) $subtrahend->getAmount(), 2)
        );
    }

    public static function multiply(MoneyInterface $multiplicand, float $multiplier): MoneyInterface
    {
        if (null === $multiplicand->getAmount()) {
            return new NullableMoney();
        }

        return new Money(
            $multiplicand->getCurrency(),
            static::round(\bcmul($multiplicand->getAmount(), (string) $multiplier, 4))
        );
    }

    public static function divide(MoneyInterface $dividend, float $divisor): MoneyInterface
    {
        if (null === $dividend->getAmount()) {
            return new NullableMoney();
        }

        return new Money(
            $dividend->getCurrency(),
            static::round(\bcdiv($dividend->getAmount(), (string) $divisor, 4))
        );
    }

    public static function ratio(MoneyInterface $fraction, MoneyInterface $denominator): float
    {
        if (static::isDifferentCurrency($fraction, $denominator)) {
            throw new DifferentCurrencyException($fraction, $denominator);
        }

        if (static::isBothNullMoney($fraction, $denominator)) {
            return 0;
        }

        return (float) \bcdiv((string) $fraction->getAmount(), (string) $denominator->getAmount(), 4);
    }

    /**
     * @return int 1 if a > b | 0 if a === b | -1 if a < b
     */
    public static function compare(MoneyInterface $a, MoneyInterface $b): int
    {
        if (static::isDifferentCurrency($a, $b)) {
            throw new DifferentCurrencyException($a, $b);
        }
        // cast them to string, since amount can be null.
        return \bccomp((string) $a->getAmount(), (string) $b->getAmount(), 2);
    }

    public static function max(MoneyInterface $a, MoneyInterface $b): MoneyInterface
    {
        $comparison = self::compare($a, $b);

        return (-1 === $comparison) ? $b : $a;
    }

    public static function isDifferentCurrency(MoneyInterface $leftOperand, MoneyInterface $rightOperand): bool
    {
        $leftCurrency  = \mb_strtoupper($leftOperand->getCurrency());
        $rightCurrency = \mb_strtoupper($rightOperand->getCurrency());

        return false === empty($leftCurrency) && false === empty($rightCurrency) && $leftCurrency !== $rightCurrency;
    }

    private static function round(string $number): string
    {
        return (string) \round((float) $number, 2);
    }

    private static function isBothNullMoney(MoneyInterface $leftOperand, MoneyInterface $rightOperand): bool
    {
        return null === $leftOperand->getAmount() && null === $rightOperand->getAmount();
    }
}
