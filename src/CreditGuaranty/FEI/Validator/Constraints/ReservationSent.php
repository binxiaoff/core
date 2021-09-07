<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ReservationSent extends Constraint
{
    public string $message = 'The reservation is ineligible.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
