<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\DTO;

use PHPUnit\Framework\TestCase;
use Unilend\DTO\Bitmask;

/**
 * @coversDefaultClass \Unilend\DTO\Bitmask
 */
class BitmaskTest extends TestCase
{
    /**
     * @param Bitmask $initial
     * @param $addendum
     * @param Bitmask $expected
     *
     * @covers ::add
     *
     * @dataProvider providerAdd
     */
    public function testAdd(Bitmask $initial, $addendum, Bitmask $expected)
    {
        $result = $initial->add($addendum);

        static::assertEquals($result, $expected);
    }

    /**
     * @return array[]
     */
    public function providerAdd(): array
    {
        return [
            'addendum is 0' => [new Bitmask(9), 0, new Bitmask(9)],
            'addendum is int' => [new Bitmask(3), 10, new Bitmask(11)],
            'addendum is array' => [new Bitmask(3), [1, 2, 3, 5], new Bitmask(7)],
            'addendum is Bitmask' => [new Bitmask(6), new Bitmask(11), new Bitmask(15)],
        ];
    }

    /**
     * @param Bitmask $initial
     * @param $subtract
     * @param Bitmask $expected
     *
     * @covers ::remove
     *
     * @dataProvider providerRemove
     */
    public function testRemove(Bitmask $initial, $subtract, Bitmask $expected)
    {
        $result = $initial->remove($subtract);

        static::assertEquals($result, $expected);
    }

    /**
     * @return array
     */
    public function providerRemove(): array
    {
        return [
            'subtract is 0' => [new Bitmask(9), 0, new Bitmask(9)],
            'subtract is int' => [new Bitmask(10), 3, new Bitmask(8)],
            'subtract is higher than subtracted' => [new Bitmask(3), 10, new Bitmask(1)],
            'subtract is array' => [new Bitmask(15), [2, 4, 8], new Bitmask(1)],
            'subtract is Bitmask' => [new Bitmask(6), new Bitmask(11), new Bitmask(4)],
        ];
    }

    /**
     * @covers ::get
     */
    public function testGet()
    {
        $value = 3;
        $tested = new Bitmask($value);

        $expected = $tested->get();


        static::assertEquals($expected, $value);
    }

    /**
     * @param $value
     * @param $expected
     *
     * @covers ::__construct
     *
     * @dataProvider providerConstructor
     */
    public function testConstructor($value, $expected)
    {
        static::assertEquals(new Bitmask($value), $expected);
    }

    /**
     * @return array
     */
    public function providerConstructor(): array
    {
        return [
            'int' => [4, new Bitmask(4)],
            'bitmask' => [new Bitmask(4), new Bitmask(4)],
            'array' => [[6, 5, 15, new Bitmask(9), new Bitmask(17)], new Bitmask(31)],
        ];
    }

    /**
     * @covers ::has
     *
     * @dataProvider providerHas
     *
     * @param Bitmask $tested
     * @param $query
     * @param bool    $expected
     */
    public function testHas(Bitmask $tested, $query, bool $expected)
    {
        static::assertEquals($tested->has($query), $expected);
    }

    /**
     * @return array[]
     */
    public function providerHas(): array
    {
        return [
            'int success' => [new Bitmask(9), 8, true],
            'int failure' => [new Bitmask(28), 9, false],
            'bitmask success' => [new Bitmask(34), new Bitmask(32), true],
            'bitmask failure' => [new Bitmask(68), new Bitmask(1), false],
            'array success' => [new Bitmask(79), [1, 64, 8, 6], true],
            'array failure' => [new Bitmask(78), [1, 32], false],
        ];
    }
}
