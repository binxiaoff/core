<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity\Interfaces;

use Closure;

interface EquivalenceCheckerInterface
{
    public function getEquivalenceChecker(): Closure;
}
