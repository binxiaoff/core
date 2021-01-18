<?php

declare(strict_types=1);

namespace Unilend\Core\Model;

use InvalidArgumentException;

class Bitmask
{
    /** @var int */
    private int $bitmask;

    /**
     * @param mixed $bitmask
     */
    public function __construct(int $bitmask)
    {
        $this->bitmask = $this->normalize($bitmask);
    }

    /**
     * @param $addendum
     *
     * @return Bitmask
     */
    public function add($addendum): Bitmask
    {
        $this->bitmask |= $this->normalize($addendum);

        return $this;
    }

    /**
     * @param $subtract
     *
     * @return Bitmask
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
        if (\is_int($bitmask)) {
            return $bitmask;
        }

        if ($bitmask instanceof static) {
            return $bitmask->bitmask;
        }

        if (\is_string($bitmask) && \is_numeric($bitmask)) {
            return (int) $bitmask;
        }

        throw new InvalidArgumentException(
            sprintf(
                'Argument of %s %s given; %s, int, or numeric string expected',
                \is_object($bitmask) ? 'class' : 'type',
                \is_object($bitmask) ? \get_class($bitmask) : gettype($bitmask),
                __CLASS__
            )
        );
    }
}
