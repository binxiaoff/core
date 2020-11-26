<?php

declare(strict_types=1);

namespace Unilend\DTO;

class Bitmask
{
    /** @var int */
    private int $bitmask;

    /**
     * @param int|Bitmask|array $bitmask
     */
    public function __construct($bitmask)
    {
        $this->bitmask = $this->normalize($bitmask);
    }

    /**
     * @param int|Bitmask|array $addendum
     *
     * @return $this
     */
    public function add($addendum): Bitmask
    {
        $this->bitmask |= $this->normalize($addendum);

        return $this;
    }

    /**
     * @param int|Bitmask|array $subtract
     *
     * @return $this
     */
    public function remove($subtract): Bitmask
    {
        $this->bitmask &= ~$this->normalize($subtract);

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
    public function has($query): bool
    {
        return ($this->bitmask & $this->normalize($query)) === $this->normalize($query);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->bitmask;
    }

    /**
     * @param $bitmask
     *
     * @return int
     */
    private function normalize($bitmask): int
    {
        if (is_iterable($bitmask)) {
            $tmp = new Bitmask(0);
            foreach ($bitmask as $bit) {
                $tmp->add($this->normalize($bit));
            }
            $bitmask = $tmp;
        }

        if ($bitmask instanceof self) {
            return $bitmask->bitmask;
        }

        return (int) $bitmask;
    }
}
