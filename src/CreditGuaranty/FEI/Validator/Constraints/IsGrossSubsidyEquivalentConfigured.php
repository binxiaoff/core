<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsGrossSubsidyEquivalentConfigured extends Constraint
{
    public string $message = 'The program cannot be validated because some configurations related '
    . 'to gross subsidy equivalent calculation are missing.';
}
