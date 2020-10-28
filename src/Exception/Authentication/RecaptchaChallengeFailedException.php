<?php

declare(strict_types=1);

namespace Unilend\Exception\Authentication;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Unilend\DTO\GoogleRecaptchaResult;

class RecaptchaChallengeFailedException extends AuthenticationException
{
    private GoogleRecaptchaResult $result;

    /**
     * @param GoogleRecaptchaResult $result
     */
    public function __construct(GoogleRecaptchaResult $result)
    {
        parent::__construct('The captcha challenge has failed');
        $this->result = $result;
    }

    /**
     * @return string
     */
    public function getMessageKey(): string
    {
        return 'The captcha challenge has failed';
    }

    /**
     * @return GoogleRecaptchaResult
     */
    public function getResult(): GoogleRecaptchaResult
    {
        return $this->result;
    }
}
