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
            'It should return an equal object when addendum is 0' => [new Bitmask(9), 0, new Bitmask(9)],
            'It should return an object with the correct result 1' => [new Bitmask(3), 10, new Bitmask(11)],
            'It should return an object with the correct result 2' => [new Bitmask(3), 1 | 2 | 3 | 5, new Bitmask(7)],
            'It should return an object with the correct result 3' => [new Bitmask(6), 11, new Bitmask(15)],
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
            'It should return an equal object when same when subtract is 0' => [new Bitmask(9), 0, new Bitmask(9)],
            'It should return an object with the correct result 1' => [new Bitmask(10), 3, new Bitmask(8)],
            'It should return an object with the correct result 2' => [new Bitmask(3), 10, new Bitmask(1)],
            'It should return an object with the correct result 3' => [new Bitmask(15), 2 | 4 | 8, new Bitmask(1)],
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
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $value = 4;

        static::assertEquals(new Bitmask(4), new Bitmask($value));
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
            'It should return true 1' => [new Bitmask(9), 8, true],
            'It should return true 2' => [new Bitmask(34), 32, true],
            'It should return true 3' => [new Bitmask(79), 1 | 64 | 6 | 8, true],
            'It should return false 1' => [new Bitmask(28), 9, false],
            'It should return false 2' => [new Bitmask(68), 1, false],
            'It should return false 3' => [new Bitmask(78), 1 | 32, false],
        ];
    }
}
