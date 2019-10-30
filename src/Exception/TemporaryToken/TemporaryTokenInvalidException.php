<?php

declare(strict_types=1);

namespace Unilend\Exception\TemporaryToken;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class TemporaryTokenInvalidException extends AuthenticationException
{
    /**
     * {@inheritdoc}
     */
    public function getMessageKey(): string
    {
        return 'Temporary Token is not valid';
    }
}
