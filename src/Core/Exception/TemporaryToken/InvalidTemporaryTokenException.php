<?php

declare(strict_types=1);

namespace Unilend\Core\Exception\TemporaryToken;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class InvalidTemporaryTokenException extends AuthenticationException
{
}
