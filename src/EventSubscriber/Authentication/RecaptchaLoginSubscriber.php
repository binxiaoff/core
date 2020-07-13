<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\Authentication;

use JsonException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Unilend\Exception\Authentication\RecaptchaChallengeFailedException;
use Unilend\Service\GoogleRecaptchaManager;

class RecaptchaLoginSubscriber implements EventSubscriberInterface
{
    private const CAPTCHA_VALUE_REQUEST_KEY = 'captchaValue';
    private GoogleRecaptchaManager $recaptchaManager;

    /**
     * @param GoogleRecaptchaManager $recaptchaManager
     */
    public function __construct(GoogleRecaptchaManager $recaptchaManager)
    {
        $this->recaptchaManager = $recaptchaManager;
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function verifyCaptcha(InteractiveLoginEvent $event): void
    {
        $request      = $event->getRequest();
        $captchaValue = json_decode($request->getContent(), true)[static::CAPTCHA_VALUE_REQUEST_KEY] ?? null;

        try {
            if (false === $this->recaptchaManager->isValid($captchaValue)) {
                throw new RecaptchaChallengeFailedException('The captcha challenge has failed');
            }
        } catch (JsonException $exception) {
            throw new RecaptchaChallengeFailedException('The captcha challenge has failed', 0, $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'verifyCaptcha',
        ];
    }
}
