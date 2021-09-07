<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Interfaces;

use DateTimeImmutable;

/**
 * Interface StatusInterface.
 */
interface StatusInterface
{
    public function getStatus(): int;

    public function getAdded(): DateTimeImmutable;

    /**
     * @return array|string[]
     */
    public static function getPossibleStatuses(): array;

    /**
     * @return TraceableStatusAwareInterface
     */
    public function getAttachedObject();

    public function getId(): ?int;
}
