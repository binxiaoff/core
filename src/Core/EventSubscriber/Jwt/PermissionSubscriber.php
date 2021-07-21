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
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\UserRepository;

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
        $user     = $event->getUser();
        $user     = $this->userRepository->findOneBy(['email' => $user->getUsername()]);
        $payload  = $event->getData();
        $staffIri = $payload['staff'] ?? null;
        $staff    = null !== $staffIri ? $this->iriConverter->getItemFromIri($staffIri, [AbstractNormalizer::GROUPS => []]) : null;

        $permissions = [];

        foreach ($this->permissionProviders as $permissionProvider) {
            /** @var Staff|null $staff */
            $permissions = \array_merge($permissions, $permissionProvider->provide($user, $staff));
        }

        $payload['permissions'] = $permissions;

        $event->setData($payload);
    }

    public function validatePayload(JWTDecodedEvent $event): void
    {
        $payload     = $event->getPayload();
        $permissions = $payload['permissions'] ?? null;

        if (null === $permissions) {
            $event->markAsInvalid();

            return;
        }

        $userIri = $payload['user'] ?? null;

        if (null === $userIri) {
            return;
        }

        try {
            /** @var User $user */
            $user = $this->iriConverter->getItemFromIri($userIri, [AbstractNormalizer::GROUPS => []]);
        } catch (ItemNotFoundException $exception) {
            $event->markAsInvalid();

            return;
        }

        $staffIri = $payload['staff'] ?? null;
        $staff    = null !== $staffIri ? $this->iriConverter->getItemFromIri($staffIri, [AbstractNormalizer::GROUPS => []]) : null;
        $temp     = [];

        foreach ($this->permissionProviders as $permissionProvider) {
            /** @var Staff|null $staff */
            $temp = \array_merge($temp, $permissionProvider->provide($user, $staff));
        }

        \array_walk_recursive($permissions, function (&$value) {
            $value = (array) $value;
        });

        if (false === ((array) $permissions === $temp)) {
            $event->markAsInvalid();
        }
    }
}
