<?php

declare(strict_types=1);

namespace Unilend\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PasswordConstraint extends Constraint
{
    public const MIN_PASSWORD_LENGTH = 6;

    public $message = 'reset.password.wrong';
}
