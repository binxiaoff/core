<?php

declare(strict_types=1);

namespace KLS\Core\Exception\Authentication;

use KLS\Core\DTO\GoogleRecaptchaResult;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class RecaptchaChallengeFailedException extends AuthenticationException
{
    private GoogleRecaptchaResult $result;

    public function __construct(GoogleRecaptchaResult $result)
    {
        parent::__construct('The captcha challenge has failed');
        $this->result = $result;
    }

    public function getMessageKey(): string
    {
        return 'The captcha challenge has failed';
    }

    public function getResult(): GoogleRecaptchaResult
    {
        return $this->result;
    }
}
