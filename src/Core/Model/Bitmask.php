<?php

declare(strict_types=1);

namespace Unilend\Core\Model;

class Bitmask
{
    /** @var int */
    private int $bitmask;

    /**
     * @param mixed $bitmask
     */
    public function __construct($bitmask)
    {
        $this->bitmask = $bitmask instanceof static ? $bitmask->get() : $bitmask;
    }

    /**
     * @param int $addendum
     *
     * @return Bitmask
     */
    public function add(int $addendum): Bitmask
    {
        $this->bitmask |= $addendum;

        return $this;
    }

    /**
     * @param int $subtract
     *
     * @return Bitmask
     */
    public function remove(int $subtract): Bitmask
    {
        $this->bitmask &= ~$subtract;

        return $this;
    }

    /**
     * @return int
     */
    public function get(): int
    {
        return $this->bitmask;
    }

    /**
     * @param $query
     *
     * @return bool
     */
    public function has(int $query): bool
    {
        return ($this->bitmask & $query) === $query;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->bitmask;
    }
}
