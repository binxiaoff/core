<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ReservationEligible extends Constraint
{
    public string $message = 'The reservation is ineligible.';

    public function validatedBy(): string
    {
        return self::class . 'Validator';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
