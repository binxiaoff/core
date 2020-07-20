<?php

declare(strict_types=1);

namespace Unilend\Exception\Authentication;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

class RecaptchaChallengeFailedException extends AuthenticationException
{
    /**
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = 'The captcha challenge has failed', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getMessageKey(): string
    {
        return 'The captcha challenge has failed';
    }
}
