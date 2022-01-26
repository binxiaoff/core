<?php

declare(strict_types=1);

namespace KLS\Core\Service;

use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Interfaces\MoneyInterface;
use KLS\Core\Exception\Money\DifferentCurrencyException;

class MoneyCalculator
{
    public static function add(MoneyInterface $leftAddend, MoneyInterface $rightAddend): MoneyInterface
    {
        if (static::isBothNullMoney($leftAddend, $rightAddend)) {
            return new NullableMoney();
        }

        if (static::isDifferentCurrency($leftAddend, $rightAddend)) {
            throw new DifferentCurrencyException($leftAddend, $rightAddend);
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

        // TODO handle 0 case

        // TODO handle negative case

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

        // TODO handle 0 case

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

        return (1 === $comparison) ? $a : $b;
    }

    public static function min(MoneyInterface $a, MoneyInterface $b): MoneyInterface
    {
        $comparison = self::compare($a, $b);

        return (-1 === $comparison) ? $a : $b;
    }

    public static function isDifferentCurrency(MoneyInterface $leftOperand, MoneyInterface $rightOperand): bool
    {
        $leftCurrency  = null !== $leftOperand->getCurrency() ? \mb_strtoupper($leftOperand->getCurrency()) : false;
        $rightCurrency = null !== $rightOperand->getCurrency() ? \mb_strtoupper($rightOperand->getCurrency()) : false;

        return false === empty($leftCurrency) && false === empty($rightCurrency) && $leftCurrency !== $rightCurrency;
    }

    /**
     * @param array|MoneyInterface[] $addedums
     */
    public static function sum(array $addedums = []): MoneyInterface
    {
        $accumulator = new NullableMoney();

        return \array_reduce(
            $addedums,
            static fn (MoneyInterface $carry, MoneyInterface $item) => static::add($carry, $item),
            $accumulator
        );
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
