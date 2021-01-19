<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\MarketSegment;

class Program
{
    // General information
    private string $name;

    private string $description;

    private MarketSegment $marketSegment;

    private Money $cappedAt;

    // Distribution conditions
    private Money $funds;

    private \DateTimeImmutable $distributionStartAt;

    private \DateTimeImmutable $distributionDeadline;

    private array $distributionProcess;

    // Guaranty conditions
    // in month
    private int $guarantyDuration;

    private string $guarantyCoverage;

    private string $guarantyCost;
}
