<?php

declare(strict_types=1);

namespace Unilend\Core\Exception\Authentication;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Unilend\Core\DTO\GoogleRecaptchaResult;

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
