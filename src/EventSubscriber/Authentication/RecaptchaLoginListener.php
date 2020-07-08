<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\Authentication;

use JsonException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Unilend\Exception\Authentication\RecaptchaChallengeFailedException;
use Unilend\Service\GoogleRecaptchaManager;

class RecaptchaLoginListener implements EventSubscriberInterface
{
    private const CAPTCHA_VALUE_REQUEST_KEY = 'captchaValue';
    private GoogleRecaptchaManager $recaptchaManager;
    private string $environment;

    /**
     * @param GoogleRecaptchaManager $recaptchaManager
     * @param string                 $environment
     */
    public function __construct(
        GoogleRecaptchaManager $recaptchaManager,
        string $environment
    ) {
        $this->recaptchaManager = $recaptchaManager;
        $this->environment      = $environment;
    }

    /**
     * @param InteractiveLoginEvent $event
     *
     * @return bool
     */
    public function verifyCaptcha(InteractiveLoginEvent $event)
    {
        // Condition to allow to test in development environment with postman without having to have a captcha token
        if ('development' === $this->environment) {
            return true;
        }

        $request      = $event->getRequest();
        $captchaValue = $request->get(static::CAPTCHA_VALUE_REQUEST_KEY);

        try {
            if (false === $this->recaptchaManager->isValid($captchaValue)) {
                throw new RecaptchaChallengeFailedException('The captcha challenge has failed');
            }
        } catch (JsonException $exception) {
            throw new RecaptchaChallengeFailedException('The captcha challenge has failed', 0, $exception);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'verifyCaptcha',
        ];
    }
}
