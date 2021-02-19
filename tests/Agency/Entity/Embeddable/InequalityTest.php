<?php

declare(strict_types=1);

namespace Unilend\Test\Agency\Entity\Embeddable;

use PHPUnit\Framework\TestCase;
use Unilend\Agency\Entity\Embeddable\Inequality;

/**
 * @coversDefaultClass \Unilend\Agency\Entity\Embeddable\Inequality
 */
class InequalityTest extends TestCase
{
    /**
     * @param Inequality $inequality
     * @param string     $evaluatedValue
     * @param bool       $expected
     *
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
            Inequality::OPERATOR_EQUAL . ' success natural integer' => [new Inequality(Inequality::OPERATOR_EQUAL, '50'), '50', true],
            Inequality::OPERATOR_EQUAL . ' success float' => [new Inequality(Inequality::OPERATOR_EQUAL, '50.50'), '50.50', true],
            Inequality::OPERATOR_EQUAL . ' fail natural integer' =>  [new Inequality(Inequality::OPERATOR_EQUAL, '49'), '50', false],
            Inequality::OPERATOR_EQUAL . ' fail float' =>  [new Inequality(Inequality::OPERATOR_EQUAL, '50.50'), '50.49', false],
        ];
    }

    /**
     * @return array
     */
    public function providerInferiorOperator(): array
    {
        return [
            Inequality::OPERATOR_INFERIOR . ' success natural integer' => [new Inequality(Inequality::OPERATOR_INFERIOR, '50'), '49', true],
            Inequality::OPERATOR_INFERIOR . ' success float' => [new Inequality(Inequality::OPERATOR_INFERIOR, '50.50'), '50.4999', true],
            Inequality::OPERATOR_INFERIOR . ' fail equal natural integer' => [new Inequality(Inequality::OPERATOR_INFERIOR, '50'), '50', false],
            Inequality::OPERATOR_INFERIOR . ' fail equal float' => [new Inequality(Inequality::OPERATOR_INFERIOR, '50.4999'), '50.4999', false],
            Inequality::OPERATOR_INFERIOR . ' fail natural integer' =>  [new Inequality(Inequality::OPERATOR_INFERIOR, '49'), '50', false],
            Inequality::OPERATOR_INFERIOR . ' fail float' =>  [new Inequality(Inequality::OPERATOR_INFERIOR, '50.48'), '50.49', false],
        ];
    }

    /**
     * @return array
     */
    public function providerInferiorEqualOperator(): array
    {
        return [
            Inequality::OPERATOR_INFERIOR_OR_EQUAL . ' success natural integer' => [new Inequality(Inequality::OPERATOR_INFERIOR_OR_EQUAL, '50'), '49', true],
            Inequality::OPERATOR_INFERIOR_OR_EQUAL . ' success float' => [new Inequality(Inequality::OPERATOR_INFERIOR_OR_EQUAL, '50.50'), '50.4999', true],
            Inequality::OPERATOR_INFERIOR_OR_EQUAL . ' success equal natural integer' => [new Inequality(Inequality::OPERATOR_INFERIOR_OR_EQUAL, '50'), '50', true],
            Inequality::OPERATOR_INFERIOR_OR_EQUAL . ' success equal float' => [new Inequality(Inequality::OPERATOR_INFERIOR_OR_EQUAL, '50.4999'), '50.4999', true],
            Inequality::OPERATOR_INFERIOR_OR_EQUAL . ' fail natural integer' =>  [new Inequality(Inequality::OPERATOR_INFERIOR_OR_EQUAL, '49'), '50', false],
            Inequality::OPERATOR_INFERIOR_OR_EQUAL . ' fail float' =>  [new Inequality(Inequality::OPERATOR_INFERIOR_OR_EQUAL, '50.48'), '50.49', false],
        ];
    }

    /**
     * @return array
     */
    public function providerSuperiorOperator(): array
    {
        return [
            Inequality::OPERATOR_SUPERIOR . ' success natural integer' => [new Inequality(Inequality::OPERATOR_SUPERIOR, '49'), '50', true],
            Inequality::OPERATOR_SUPERIOR . ' success float' => [new Inequality(Inequality::OPERATOR_SUPERIOR, '50.4998'), '50.50', true],
            Inequality::OPERATOR_SUPERIOR . ' fail equal natural integer' => [new Inequality(Inequality::OPERATOR_SUPERIOR, '50'), '50', false],
            Inequality::OPERATOR_SUPERIOR . ' fail equal float' => [new Inequality(Inequality::OPERATOR_SUPERIOR, '50.4999'), '50.4999', false],
            Inequality::OPERATOR_SUPERIOR . ' fail natural integer' =>  [new Inequality(Inequality::OPERATOR_SUPERIOR, '51'), '50', false],
            Inequality::OPERATOR_SUPERIOR . ' fail float' =>  [new Inequality(Inequality::OPERATOR_SUPERIOR, '50.50'), '48.3', false],
        ];
    }

    /**
     * @return array
     */
    public function providerSuperiorEqualOperator(): array
    {
        return [
            Inequality::OPERATOR_SUPERIOR_OR_EQUAL . ' success natural integer' => [new Inequality(Inequality::OPERATOR_SUPERIOR_OR_EQUAL, '48'), '49', true],
            Inequality::OPERATOR_SUPERIOR_OR_EQUAL . ' success float' => [new Inequality(Inequality::OPERATOR_SUPERIOR_OR_EQUAL, '50.1'), '50.4999', true],
            Inequality::OPERATOR_SUPERIOR_OR_EQUAL . ' success equal natural integer' => [new Inequality(Inequality::OPERATOR_SUPERIOR_OR_EQUAL, '50'), '50', true],
            Inequality::OPERATOR_SUPERIOR_OR_EQUAL . ' success equal float' => [new Inequality(Inequality::OPERATOR_SUPERIOR_OR_EQUAL, '50.4999'), '50.4999', true],
            Inequality::OPERATOR_SUPERIOR_OR_EQUAL . ' fail natural integer' =>  [new Inequality(Inequality::OPERATOR_SUPERIOR_OR_EQUAL, '51'), '50', false],
            Inequality::OPERATOR_SUPERIOR_OR_EQUAL . ' fail float' =>  [new Inequality(Inequality::OPERATOR_SUPERIOR_OR_EQUAL, '50.50'), '48.49', false],
        ];
    }

    /**
     * @return array[]
     */
    public function providerBetweenOperator()
    {
        return [
            Inequality::OPERATOR_BETWEEN . ' success natural integer' => [new Inequality(Inequality::OPERATOR_BETWEEN, '48', '50'), '49', true],
            Inequality::OPERATOR_BETWEEN . ' success float' => [new Inequality(Inequality::OPERATOR_BETWEEN, '50.1', '50.50'), '50.4999', true],
            Inequality::OPERATOR_BETWEEN . ' success equal min natural integer' => [new Inequality(Inequality::OPERATOR_BETWEEN, '50', '75'), '50', true],
            Inequality::OPERATOR_BETWEEN . ' success equal min float' => [new Inequality(Inequality::OPERATOR_BETWEEN, '50.4999', '75.488'), '50.4999', true],
            Inequality::OPERATOR_BETWEEN . ' success equal same natural integer' => [new Inequality(Inequality::OPERATOR_BETWEEN, '50', '50'), '50', true],
            Inequality::OPERATOR_BETWEEN . ' success equal same float' => [new Inequality(Inequality::OPERATOR_BETWEEN, '50.4999', '50.4999'), '50.4999', true],
            Inequality::OPERATOR_BETWEEN . ' success equal max natural integer' => [new Inequality(Inequality::OPERATOR_BETWEEN, '42', '50'), '50', true],
            Inequality::OPERATOR_BETWEEN . ' success equal max float' => [new Inequality(Inequality::OPERATOR_BETWEEN, '51.4999', '56.40'), '56.40', true],
            Inequality::OPERATOR_BETWEEN . ' fail no max value' => [new Inequality(Inequality::OPERATOR_BETWEEN, '50.4999'), '50.4999', false],
            Inequality::OPERATOR_BETWEEN . ' fail above natural integer' =>  [new Inequality(Inequality::OPERATOR_BETWEEN, '51', '59'), '60', false],
            Inequality::OPERATOR_BETWEEN . ' fail above float' =>  [new Inequality(Inequality::OPERATOR_BETWEEN, '51.50', '56.75'), '59.49', false],
            Inequality::OPERATOR_BETWEEN . ' fail below natural integer' =>  [new Inequality(Inequality::OPERATOR_BETWEEN, '51', '60'), '50', false],
            Inequality::OPERATOR_BETWEEN . ' fail below float' =>  [new Inequality(Inequality::OPERATOR_BETWEEN, '50.50', '56.46'), '50.49', false],
            Inequality::OPERATOR_BETWEEN . ' fail inverted' => [new Inequality(Inequality::OPERATOR_BETWEEN, '56.46', '50.50'), '50.49', false],
        ];
    }
}
