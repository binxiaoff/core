<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Model;

use InvalidArgumentException;
use KLS\Core\Model\Bitmask;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \KLS\Core\Model\Bitmask
 *
 * @internal
 */
class BitmaskTest extends TestCase
{
    /**
     * @param $addendum
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
    public function providerAdd(): iterable
    {
        return [
            '(addendum is int) It should return an equivalent object when addendum is 0' => [new Bitmask(9), 0, new Bitmask(9)],
            '(addendum is int) It should return an object with the correct result 1'     => [new Bitmask(3), 10, new Bitmask(11)],
            '(addendum is int) It should return an object with the correct result 2'     => [new Bitmask(3), 1 | 2 | 3 | 5, new Bitmask(7)],
            '(addendum is int) It should return an object with the correct result 3'     => [new Bitmask(6), 11, new Bitmask(15)],

            '(addendum is numeric string) It should return an equivalent object when addendum is 0' => [new Bitmask(9), '0', new Bitmask(9)],
            '(addendum is numeric string) It should return an object with the correct result 1'     => [new Bitmask(3), '10', new Bitmask(11)],
            '(addendum is numeric string) It should return an object with the correct result 2'     => [new Bitmask(3), (string) (1 | 2 | 3 | 5), new Bitmask(7)],
            '(addendum is numeric string) It should return an object with the correct result 3'     => [new Bitmask(6), '11', new Bitmask(15)],

            '(addendum is bitmask) It should return an equivalent object when addendum is 0' => [new Bitmask(9), new Bitmask(0), new Bitmask(9)],
            '(addendum is bitmask) It should return an object with the correct result 1'     => [new Bitmask(3), new Bitmask(10), new Bitmask(11)],
            '(addendum is bitmask) It should return an object with the correct result 2'     => [new Bitmask(3), new Bitmask(1 | 2 | 3 | 5), new Bitmask(7)],
            '(addendum is bitmask) It should return an object with the correct result 3'     => [new Bitmask(6), new Bitmask(11), new Bitmask(15)],
        ];
    }

    /**
     * @param $subtract
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

    public function providerRemove(): array
    {
        return [
            '(substract is int) It should return an equal object when same when subtract is 0' => [new Bitmask(9), 0, new Bitmask(9)],
            '(substract is int) It should return an object with the correct result 1'          => [new Bitmask(10), 3, new Bitmask(8)],
            '(substract is int) It should return an object with the correct result 2'          => [new Bitmask(3), 10, new Bitmask(1)],
            '(substract is int) It should return an object with the correct result 3'          => [new Bitmask(15), 2 | 4 | 8, new Bitmask(1)],

            '(substract is string) It should return an equal object when same when subtract is 0' => [new Bitmask(9), '0', new Bitmask(9)],
            '(substract is string) It should return an object with the correct result 1'          => [new Bitmask(10), '3', new Bitmask(8)],
            '(substract is string) It should return an object with the correct result 2'          => [new Bitmask(3), '10', new Bitmask(1)],
            '(substract is string) It should return an object with the correct result 3'          => [new Bitmask(15), (string) (2 | 4 | 8), new Bitmask(1)],

            '(substract is Bitmask) It should return an equal object when same when subtract is 0' => [new Bitmask(9), new Bitmask(0), new Bitmask(9)],
            '(substract is Bitmask) It should return an object with the correct result 1'          => [new Bitmask(10), new Bitmask(3), new Bitmask(8)],
            '(substract is Bitmask) It should return an object with the correct result 2'          => [new Bitmask(3), new Bitmask(10), new Bitmask(1)],
            '(substract is Bitmask) It should return an object with the correct result 3'          => [new Bitmask(15), new Bitmask(2 | 4 | 8), new Bitmask(1)],
        ];
    }

    /**
     * @covers ::get
     */
    public function testGet()
    {
        $value  = 3;
        $tested = new Bitmask($value);

        $expected = $tested->get();

        static::assertSame($expected, $value);
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
     * @param $query
     */
    public function testHas(Bitmask $tested, $query, bool $expected)
    {
        static::assertSame($tested->has($query), $expected);
    }

    /**
     * @return array[]
     */
    public function providerHas(): array
    {
        return [
            '(query is int) It should return true 1'  => [new Bitmask(9), 8, true],
            '(query is int) It should return true 2'  => [new Bitmask(34), 32, true],
            '(query is int) It should return true 3'  => [new Bitmask(79), 1 | 64 | 6 | 8, true],
            '(query is int) It should return false 1' => [new Bitmask(28), 9, false],
            '(query is int) It should return false 2' => [new Bitmask(68), 1, false],
            '(query is int) It should return false 3' => [new Bitmask(78), 1 | 32, false],

            '(query is string) It should return true 1'  => [new Bitmask(9), '8', true],
            '(query is string) It should return true 2'  => [new Bitmask(34), '32', true],
            '(query is string) It should return true 3'  => [new Bitmask(79), (string) (1 | 64 | 6 | 8), true],
            '(query is string) It should return false 1' => [new Bitmask(28), '9', false],
            '(query is string) It should return false 2' => [new Bitmask(68), '1', false],
            '(query is string) It should return false 3' => [new Bitmask(78), (string) (1 | 32), false],

            '(query is Bitmask) It should return true 1'  => [new Bitmask(9), new Bitmask(8), true],
            '(query is Bitmask) It should return true 2'  => [new Bitmask(34), new Bitmask(32), true],
            '(query is Bitmask) It should return true 3'  => [new Bitmask(79), new Bitmask(1 | 64 | 6 | 8), true],
            '(query is Bitmask) It should return false 1' => [new Bitmask(28), new Bitmask(9), false],
            '(query is Bitmask) It should return false 2' => [new Bitmask(68), new Bitmask(1), false],
            '(query is Bitmask) It should return false 3' => [new Bitmask(78), new Bitmask(1 | 32), false],
        ];
    }

    /**
     * @param $method
     * @param $argument
     *
     * @dataProvider providerInvalidTypes
     */
    public function testException($method, $argument)
    {
        $this->expectException(InvalidArgumentException::class);

        (new Bitmask(3))->{$method}($argument);
    }

    public function providerInvalidTypes(): iterable
    {
        $types = [
            'array'  => [],
            'string' => 'failure',
            'null'   => null,
        ];

        $methods = [
            'add',
            'remove',
            'has',
        ];

        foreach ($types as $type => $value) {
            foreach ($methods as $method) {
                yield 'Incorrect type ' . $type . ' with method ' . $method => [$method, $value];
            }
        }
    }
}
