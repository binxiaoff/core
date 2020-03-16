<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\JWT;

use Lexik\Bundle\JWTAuthenticationBundle\Event\{JWTCreatedEvent, JWTDecodedEvent};
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VersionSubscriber implements EventSubscriberInterface
{
    public const JWT_VERSION = '2020-02-28';

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            JwtEvents::JWT_DECODED => 'validateVersion',
            JwtEvents::JWT_CREATED => 'addVersion',
        ];
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function addVersion(JWTCreatedEvent $event)
    {
        $payload = $event->getData();

        $payload['version'] = static::JWT_VERSION;

        $event->setData($payload);
    }

    /**
     * @param JWTDecodedEvent $event
     */
    public function validateVersion(JWTDecodedEvent $event)
    {
        $payload = $event->getPayload();

        if (false === isset($payload['version']) || static::JWT_VERSION !== $payload['version']) {
            $event->markAsInvalid();
        }
    }
}
