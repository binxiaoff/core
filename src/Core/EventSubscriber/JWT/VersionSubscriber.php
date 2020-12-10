<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber\JWT;

use Lexik\Bundle\JWTAuthenticationBundle\Event\{JWTCreatedEvent, JWTDecodedEvent};
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VersionSubscriber implements EventSubscriberInterface
{
    public const JWT_VERSION = '2020-07-23';

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            JwtEvents::JWT_DECODED => 'validateVersion',
            JwtEvents::JWT_CREATED => 'addVersion',
        ];
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function addVersion(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();

        $payload['version'] = static::JWT_VERSION;

        $event->setData($payload);
    }

    /**
     * @param JWTDecodedEvent $event
     */
    public function validateVersion(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        if (false === isset($payload['version']) || static::JWT_VERSION !== $payload['version']) {
            $event->markAsInvalid();
        }
    }
}
