<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Password extends Constraint
{
    public const MIN_PASSWORD_LENGTH = 12;

    public string $message = 'The password must have at least 12 characters including a minuscule, a capital letter and 
    a digit';
}
