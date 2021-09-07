<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Password extends Constraint
{
    public const MIN_PASSWORD_LENGTH = 8;

    public $message = 'The password must have at least 8 characters including a minuscule, a capital letter and a digit';
}
