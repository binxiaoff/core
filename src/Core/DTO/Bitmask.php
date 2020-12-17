<?php

declare(strict_types=1);

namespace Unilend\Core\DTO;

class Bitmask
{
    /** @var int */
    private int $bitmask;

    /**
     * @param int $bitmask
     */
    public function __construct(int $bitmask)
    {
        $this->bitmask = $bitmask;
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
