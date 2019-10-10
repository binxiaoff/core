<?php

declare(strict_types=1);

namespace Unilend\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PasswordConstraintValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof PasswordConstraint) {
            throw new UnexpectedTypeException($constraint, PasswordConstraint::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (false === preg_match('/^(?=.*[a-z])(?=.*[A-Z]).{' . PasswordConstraint::MIN_PASSWORD_LENGTH . ',}$/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation()
            ;
        }
    }
}
