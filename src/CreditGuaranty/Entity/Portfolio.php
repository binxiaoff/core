<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Unilend\Core\Entity\Embeddable\Money;

class Portfolio
{
    private string $name;

    private Money $funds;

    private string $gradeType;
}
