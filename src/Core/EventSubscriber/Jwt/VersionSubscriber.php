<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber\Jwt;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VersionSubscriber implements EventSubscriberInterface
{
    public const JWT_VERSION = '2021-08-05';

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            JwtEvents::JWT_DECODED => 'validatePayload',
            JwtEvents::JWT_CREATED => 'addPayload',
        ];
    }

    public function addPayload(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();

        $payload['version'] = static::JWT_VERSION;

        $event->setData($payload);
    }

    public function validatePayload(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        if (false === isset($payload['version']) || static::JWT_VERSION !== $payload['version']) {
            $event->markAsInvalid();
        }
    }
}
