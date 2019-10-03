<?php

declare(strict_types=1);

namespace Unilend\Validator\Constraints;

use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Validator\Constraint;

class PasswordConstraint extends Constraint
{
    public const MAX_PASSWORD_LENGTH = BasePasswordEncoder::MAX_PASSWORD_LENGTH;
    public const MIN_PASSWORD_LENGTH = 6;

    public $message = 'reset.password.wrong';
}
