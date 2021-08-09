<?php

declare(strict_types=1);

namespace KLS\Core\Exception\TemporaryToken;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class InvalidTemporaryTokenException extends AuthenticationException
{
}
