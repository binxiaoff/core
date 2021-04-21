<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber\Jwt;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Core\Entity\User;

class UserSubscriber implements EventSubscriberInterface
{
    private IriConverterInterface $iriConverter;

    public function __construct(IriConverterInterface $iriConverter)
    {
        $this->iriConverter = $iriConverter;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            JwtEvents::JWT_CREATED => 'addPayload',
            JwtEvents::JWT_DECODED => 'validatePayload',
        ];
    }

    public function addPayload(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();

        $payload['user'] = $this->iriConverter->getIriFromItem($event->getUser());

        $event->setData($payload);
    }

    public function validatePayload(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        $userIri = $payload['user'] ?? null;

        if (null === $userIri) {
            $event->markAsInvalid();

            return;
        }

        try {
            /** @var User $user */
            $user = $this->iriConverter->getItemFromIri($userIri, [AbstractNormalizer::GROUPS => []]);
        } catch (ItemNotFoundException $exception) {
            $event->markAsInvalid();

            return;
        }

        if ((false === ($user instanceof User)) || (false === $user->isGrantedLogin())) {
            $event->markAsInvalid();
        }
    }
}
