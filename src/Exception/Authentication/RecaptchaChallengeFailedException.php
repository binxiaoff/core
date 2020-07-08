<?php

declare(strict_types=1);

namespace Unilend\Exception\Authentication;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class RecaptchaChallengeFailedException extends AuthenticationException
{
    /**
     * @return string
     */
    public function getMessageKey(): string
    {
        return 'The captcha challenge has failed';
    }
}
