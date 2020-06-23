<?php

declare(strict_types=1);

namespace Unilend\Entity\Interfaces;

interface MoneyInterface
{
    /**
     * @return string|null
     */
    public function getAmount(): ?string;

    /**
     * @return string|null
     */
    public function getCurrency(): ?string;
}
