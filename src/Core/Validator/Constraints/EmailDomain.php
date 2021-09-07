<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class EmailDomain extends Constraint
{
    public $message = 'This email domain is not one of our client companies';
}
