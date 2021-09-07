<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\Unit\Entity\Embeddable;

use KLS\Core\Entity\Constant\MathOperator;
use KLS\Syndication\Agency\Entity\Embeddable\Inequality;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \KLS\Syndication\Agency\Entity\Embeddable\Inequality
 *
 * @internal
 */
class InequalityTest extends TestCase
{
    /**
     * @covers ::isConform
     *
     * @dataProvider providerEqualityOperator
     * @dataProvider providerInferiorOperator
     * @dataProvider providerInferiorEqualOperator
     * @dataProvider providerSuperiorOperator
     * @dataProvider providerSuperiorEqualOperator
     * @dataProvider providerBetweenOperator
     */
    public function testIsConform(Inequality $inequality, string $evaluatedValue, bool $expected)
    {
        static::assertSame($expected, $inequality->isConform($evaluatedValue));
    }

    /**
     * @return array[]
     */
    public function providerEqualityOperator(): array
    {
        return [
            MathOperator::EQUAL . ' success natural integer' => [new Inequality(MathOperator::EQUAL, '50'), '50', true],
            MathOperator::EQUAL . ' success float'           => [new Inequality(MathOperator::EQUAL, '50.50'), '50.50', true],
            MathOperator::EQUAL . ' fail natural integer'    => [new Inequality(MathOperator::EQUAL, '49'), '50', false],
            MathOperator::EQUAL . ' fail float'              => [new Inequality(MathOperator::EQUAL, '50.50'), '50.49', false],
        ];
    }

    public function providerInferiorOperator(): array
    {
        return [
            MathOperator::INFERIOR . ' success natural integer'    => [new Inequality(MathOperator::INFERIOR, '50'), '49', true],
            MathOperator::INFERIOR . ' success float'              => [new Inequality(MathOperator::INFERIOR, '50.50'), '50.4999', true],
            MathOperator::INFERIOR . ' fail equal natural integer' => [new Inequality(MathOperator::INFERIOR, '50'), '50', false],
            MathOperator::INFERIOR . ' fail equal float'           => [new Inequality(MathOperator::INFERIOR, '50.4999'), '50.4999', false],
            MathOperator::INFERIOR . ' fail natural integer'       => [new Inequality(MathOperator::INFERIOR, '49'), '50', false],
            MathOperator::INFERIOR . ' fail float'                 => [new Inequality(MathOperator::INFERIOR, '50.48'), '50.49', false],
        ];
    }

    public function providerInferiorEqualOperator(): array
    {
        return [
            MathOperator::INFERIOR_OR_EQUAL . ' success natural integer'       => [new Inequality(MathOperator::INFERIOR_OR_EQUAL, '50'), '49', true],
            MathOperator::INFERIOR_OR_EQUAL . ' success float'                 => [new Inequality(MathOperator::INFERIOR_OR_EQUAL, '50.50'), '50.4999', true],
            MathOperator::INFERIOR_OR_EQUAL . ' success equal natural integer' => [new Inequality(MathOperator::INFERIOR_OR_EQUAL, '50'), '50', true],
            MathOperator::INFERIOR_OR_EQUAL . ' success equal float'           => [new Inequality(MathOperator::INFERIOR_OR_EQUAL, '50.4999'), '50.4999', true],
            MathOperator::INFERIOR_OR_EQUAL . ' fail natural integer'          => [new Inequality(MathOperator::INFERIOR_OR_EQUAL, '49'), '50', false],
            MathOperator::INFERIOR_OR_EQUAL . ' fail float'                    => [new Inequality(MathOperator::INFERIOR_OR_EQUAL, '50.48'), '50.49', false],
        ];
    }

    public function providerSuperiorOperator(): array
    {
        return [
            MathOperator::SUPERIOR . ' success natural integer'    => [new Inequality(MathOperator::SUPERIOR, '49'), '50', true],
            MathOperator::SUPERIOR . ' success float'              => [new Inequality(MathOperator::SUPERIOR, '50.4998'), '50.50', true],
            MathOperator::SUPERIOR . ' fail equal natural integer' => [new Inequality(MathOperator::SUPERIOR, '50'), '50', false],
            MathOperator::SUPERIOR . ' fail equal float'           => [new Inequality(MathOperator::SUPERIOR, '50.4999'), '50.4999', false],
            MathOperator::SUPERIOR . ' fail natural integer'       => [new Inequality(MathOperator::SUPERIOR, '51'), '50', false],
            MathOperator::SUPERIOR . ' fail float'                 => [new Inequality(MathOperator::SUPERIOR, '50.50'), '48.3', false],
        ];
    }

    public function providerSuperiorEqualOperator(): array
    {
        return [
            MathOperator::SUPERIOR_OR_EQUAL . ' success natural integer'       => [new Inequality(MathOperator::SUPERIOR_OR_EQUAL, '48'), '49', true],
            MathOperator::SUPERIOR_OR_EQUAL . ' success float'                 => [new Inequality(MathOperator::SUPERIOR_OR_EQUAL, '50.1'), '50.4999', true],
            MathOperator::SUPERIOR_OR_EQUAL . ' success equal natural integer' => [new Inequality(MathOperator::SUPERIOR_OR_EQUAL, '50'), '50', true],
            MathOperator::SUPERIOR_OR_EQUAL . ' success equal float'           => [new Inequality(MathOperator::SUPERIOR_OR_EQUAL, '50.4999'), '50.4999', true],
            MathOperator::SUPERIOR_OR_EQUAL . ' fail natural integer'          => [new Inequality(MathOperator::SUPERIOR_OR_EQUAL, '51'), '50', false],
            MathOperator::SUPERIOR_OR_EQUAL . ' fail float'                    => [new Inequality(MathOperator::SUPERIOR_OR_EQUAL, '50.50'), '48.49', false],
        ];
    }

    /**
     * @return array[]
     */
    public function providerBetweenOperator()
    {
        return [
            MathOperator::BETWEEN . ' success natural integer'            => [new Inequality(MathOperator::BETWEEN, '48', '50'), '49', true],
            MathOperator::BETWEEN . ' success float'                      => [new Inequality(MathOperator::BETWEEN, '50.1', '50.50'), '50.4999', true],
            MathOperator::BETWEEN . ' success equal min natural integer'  => [new Inequality(MathOperator::BETWEEN, '50', '75'), '50', true],
            MathOperator::BETWEEN . ' success equal min float'            => [new Inequality(MathOperator::BETWEEN, '50.4999', '75.488'), '50.4999', true],
            MathOperator::BETWEEN . ' success equal same natural integer' => [new Inequality(MathOperator::BETWEEN, '50', '50'), '50', true],
            MathOperator::BETWEEN . ' success equal same float'           => [new Inequality(MathOperator::BETWEEN, '50.4999', '50.4999'), '50.4999', true],
            MathOperator::BETWEEN . ' success equal max natural integer'  => [new Inequality(MathOperator::BETWEEN, '42', '50'), '50', true],
            MathOperator::BETWEEN . ' success equal max float'            => [new Inequality(MathOperator::BETWEEN, '51.4999', '56.40'), '56.40', true],
            MathOperator::BETWEEN . ' fail no max value'                  => [new Inequality(MathOperator::BETWEEN, '50.4999'), '50.4999', false],
            MathOperator::BETWEEN . ' fail above natural integer'         => [new Inequality(MathOperator::BETWEEN, '51', '59'), '60', false],
            MathOperator::BETWEEN . ' fail above float'                   => [new Inequality(MathOperator::BETWEEN, '51.50', '56.75'), '59.49', false],
            MathOperator::BETWEEN . ' fail below natural integer'         => [new Inequality(MathOperator::BETWEEN, '51', '60'), '50', false],
            MathOperator::BETWEEN . ' fail below float'                   => [new Inequality(MathOperator::BETWEEN, '50.50', '56.46'), '50.49', false],
            MathOperator::BETWEEN . ' fail inverted'                      => [new Inequality(MathOperator::BETWEEN, '56.46', '50.50'), '50.49', false],
        ];
    }
}
