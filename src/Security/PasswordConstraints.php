<?php

declare(strict_types=1);

namespace Unilend\Security;

use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;

/**
 * @todo: Can be replaced with a real Symfony custom Validation Constraint
 */
class PasswordConstraints
{
    public const MAX_PASSWORD_LENGTH    = BasePasswordEncoder::MAX_PASSWORD_LENGTH;
    public const MIN_PASSWORD_LENGTH    = 6;
    public const PASSWORD_REGEX_PATTERN = '/^(?=.*[a-z])(?=.*[A-Z]).{' . self::MIN_PASSWORD_LENGTH . ',}$/';
}
