<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Interfaces;

interface MoneyInterface
{
    public function getAmount(): ?string;

    public function getCurrency(): ?string;
}
