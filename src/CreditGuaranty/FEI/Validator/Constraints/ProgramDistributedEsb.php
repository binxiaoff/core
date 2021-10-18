<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ProgramDistributedEsb extends Constraint
{
    public string $message = 'The program cannot be distributed.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
