<?php

declare(strict_types=1);

namespace KLS\Core\EventSubscriber\Jwt;

use ApiPlatform\Core\Api\IriConverterInterface;
use KLS\Core\Entity\Staff;
use KLS\Core\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class PermissionSubscriber implements EventSubscriberInterface
{
    /** @var iterable|PermissionProviderInterface[] */
    private iterable $permissionProviders;
    private IriConverterInterface $iriConverter;
    private UserRepository $userRepository;

    public function __construct(
        iterable $permissionProviders,
        IriConverterInterface $iriConverter,
        UserRepository $userRepository
    ) {
        $this->permissionProviders = $permissionProviders;
        $this->iriConverter        = $iriConverter;
        $this->userRepository      = $userRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            JwtEvents::JWT_CREATED => 'addPayload',
            JwtEvents::JWT_DECODED => 'validatePayload',
        ];
    }

    public function addPayload(JWTCreatedEvent $event): void
    {
        $user     = $this->userRepository->findOneBy(['email' => $event->getUser()->getUsername()]);
        $payload  = $event->getData();
        $staffIri = $payload['staff'] ?? null;

        /** @var Staff|null $staff */
        $staff = null !== $staffIri ? $this->iriConverter->getItemFromIri($staffIri, [AbstractNormalizer::GROUPS => []]) : null;

        $permissions = [];

        foreach ($this->permissionProviders as $permissionProvider) {
            $permissions[$permissionProvider->getName()] = $permissionProvider->provide($user, $staff);
        }

        $payload['permissions'] = $permissions;

        $event->setData($payload);
    }

    public function validatePayload(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        if (false === isset($payload['permissions'])) {
            $event->markAsInvalid();
        }
    }
}
