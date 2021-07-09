<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Unit\Service;

use PHPUnit\Framework\TestCase;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Exception\Money\DifferentCurrencyException;
use Unilend\Core\Service\MoneyCalculator;

/**
 * @coversDefaultClass \Unilend\Core\Service\MoneyCalculator
 *
 * @internal
 */
class MoneyCalculatorTest extends TestCase
{
    public function testAdd(): void
    {
        $moneyA   = new Money('EUR', '26');
        $moneyB   = new Money('EUR', '16');
        $expected = new Money('EUR', '42.00');

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::add($moneyA, $moneyB);

        static::assertSame($expected->getAmount(), $result->getAmount());
        static::assertSame($expected->getCurrency(), $result->getCurrency());
    }

    public function testAddWithBothNullMoney(): void
    {
        $moneyA = new NullableMoney('EUR');
        $moneyB = new NullableMoney('EUR');

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::add($moneyA, $moneyB);

        static::assertInstanceOf(NullableMoney::class, $result);
    }

    public function testAddExceptionWithDifferentCurrency(): void
    {
        $moneyA = new NullableMoney('US', '0');
        $moneyB = new Money('EUR', '42');

        static::expectException(DifferentCurrencyException::class);

        $moneyCalculator = new MoneyCalculator();
        $moneyCalculator::add($moneyA, $moneyB);
    }

    public function testSubtract(): void
    {
        $moneyA   = new Money('EUR', '85');
        $moneyB   = new Money('EUR', '43');
        $expected = new Money('EUR', '42.00');

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::subtract($moneyA, $moneyB);

        static::assertSame($expected->getAmount(), $result->getAmount());
        static::assertSame($expected->getCurrency(), $result->getCurrency());
    }

    public function testSubtractWithBothNullMoney(): void
    {
        $moneyA = new NullableMoney('EUR');
        $moneyB = new NullableMoney('EUR');

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::subtract($moneyA, $moneyB);

        static::assertInstanceOf(NullableMoney::class, $result);
    }

    public function testSubtractExceptionWithDifferentCurrency(): void
    {
        $moneyA = new NullableMoney('JYP', '0');
        $moneyB = new Money('US', '42');

        static::expectException(DifferentCurrencyException::class);

        $moneyCalculator = new MoneyCalculator();
        $moneyCalculator::subtract($moneyA, $moneyB);
    }

    public function testMultiply(): void
    {
        $moneyA   = new Money('EUR', '7');
        $expected = new Money('EUR', '42');

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::multiply($moneyA, 6);

        static::assertSame($expected->getAmount(), $result->getAmount());
        static::assertSame($expected->getCurrency(), $result->getCurrency());
    }

    public function testMultiplyWithNullAmount(): void
    {
        $moneyA = new NullableMoney();

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::multiply($moneyA, 42);

        static::assertInstanceOf(NullableMoney::class, $result);
    }

    public function testDivide(): void
    {
        $moneyA   = new Money('EUR', '168');
        $expected = new Money('EUR', '42');

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::divide($moneyA, 4);

        static::assertSame($expected->getAmount(), $result->getAmount());
        static::assertSame($expected->getCurrency(), $result->getCurrency());
    }

    public function testDivideWithNullAmount(): void
    {
        $moneyA = new NullableMoney();

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::divide($moneyA, 0);

        static::assertInstanceOf(NullableMoney::class, $result);
    }

    public function testRatio(): void
    {
        $moneyA = new Money('US', '84');
        $moneyB = new Money('US', '2');

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::ratio($moneyA, $moneyB);

        static::assertSame(42.0, $result);
    }

    public function testRatioWithBothNullMoney(): void
    {
        $moneyA = new NullableMoney('EUR');
        $moneyB = new NullableMoney('EUR');

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::ratio($moneyA, $moneyB);

        static::assertSame(0.0, $result);
    }

    public function testRatioExceptionWithDifferentCurrency(): void
    {
        $moneyA = new NullableMoney('EUR');
        $moneyB = new NullableMoney('USD');

        static::expectException(DifferentCurrencyException::class);

        $moneyCalculator = new MoneyCalculator();
        $moneyCalculator::ratio($moneyA, $moneyB);
    }

    public function comparisonProvider(): iterable
    {
        yield 'inferior' => ['27', '36', -1];
        yield 'equal' => ['62', '62.00', 0];
        yield 'superior' => ['88', '80', 1];
    }

    /**
     * @dataProvider comparisonProvider
     */
    public function testCompare(string $amountA, string $amountB, int $expected): void
    {
        $moneyA = new Money('EUR', $amountA);
        $moneyB = new Money('EUR', $amountB);

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::compare($moneyA, $moneyB);

        static::assertSame($expected, $result);
    }

    public function testCompareExceptionWithDifferentCurrency(): void
    {
        $moneyA = new Money('EUR', '42');
        $moneyB = new Money('KR', '42');

        static::expectException(DifferentCurrencyException::class);

        $moneyCalculator = new MoneyCalculator();
        $moneyCalculator::compare($moneyA, $moneyB);
    }

    public function testMax(): void
    {
        $moneyA   = new Money('EUR', '94');
        $moneyB   = new Money('EUR', '85');
        $expected = $moneyA;

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::max($moneyA, $moneyB);

        static::assertSame($expected->getAmount(), $result->getAmount());
        static::assertSame($expected->getCurrency(), $result->getCurrency());
    }

    public function testMaxWithBothNullMoney(): void
    {
        $moneyA = new NullableMoney('EUR');
        $moneyB = new NullableMoney('EUR');

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::max($moneyA, $moneyB);

        static::assertSame($moneyA->getAmount(), $result->getAmount());
        static::assertSame($moneyA->getCurrency(), $result->getCurrency());
    }

    public function testMaxExceptionWithDifferentCurrency(): void
    {
        $moneyA = new Money('KR', '88');
        $moneyB = new Money('JYP', '92');

        static::expectException(DifferentCurrencyException::class);

        $moneyCalculator = new MoneyCalculator();
        $moneyCalculator::max($moneyA, $moneyB);
    }

    public function differentCurrencyProvider(): iterable
    {
        yield 'EUR - EUR' => ['EUR', 'EUR', false];
        yield 'EUR - eur' => ['EUR', 'eur', false];
        yield 'EUR - USD' => ['EUR', 'USD', true];
        yield 'US - USD' => ['US', 'USD', true];
    }

    /**
     * @dataProvider differentCurrencyProvider
     */
    public function testIsDifferentCurrency(string $currencyA, string $currencyB, bool $expected): void
    {
        $moneyA = new Money($currencyA, '42');
        $moneyB = new Money($currencyB, '42');

        $moneyCalculator = new MoneyCalculator();
        $result          = $moneyCalculator::isDifferentCurrency($moneyA, $moneyB);

        static::assertSame($expected, $result);
    }
}
