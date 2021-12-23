<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity\Interfaces;

use Closure;

interface EquivalenceCheckerInterface
{
    /**
     * The callable must respect unique constrains of entity.
     */
    public function getEquivalenceChecker(): Closure;
}
