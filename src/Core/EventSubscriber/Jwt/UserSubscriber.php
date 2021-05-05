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
use Unilend\Core\Repository\UserRepository;

class UserSubscriber implements EventSubscriberInterface
{
    private IriConverterInterface $iriConverter;

    private UserRepository $userRepository;

    public function __construct(IriConverterInterface $iriConverter, UserRepository $repository)
    {
        $this->iriConverter   = $iriConverter;
        $this->userRepository = $repository;
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

        $user = $event->getUser();

        // Needed because on refresh token request the given user is of Symfony basic User class
        $user = $this->userRepository->findOneBy(['email' => $user->getUsername()]);

        $payload['user'] = $this->iriConverter->getIriFromItem($user);

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
