<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ProgramDistributed extends Constraint
{
    public string $message = 'Impossible to distribute the program.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
