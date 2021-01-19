<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Interfaces;

use DateTimeImmutable;

/**
 * Interface StatusInterface.
 */
interface StatusInterface
{
    /**
     * @return int
     */
    public function getStatus(): int;

    /**
     * @return DateTimeImmutable
     */
    public function getAdded(): DateTimeImmutable;

    /**
     * @return array|string[]
     */
    public static function getPossibleStatuses(): array;

    /**
     * @return TraceableStatusAwareInterface
     */
    public function getAttachedObject();

    /**
     * @return int|null
     */
    public function getId(): ?int;
}
