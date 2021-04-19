<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber\JWT;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\UserRepository;

class StaffSubscriber implements EventSubscriberInterface
{
    private UserRepository $userRepository;

    private JWTTokenManagerInterface $jwtManager;

    private IriConverterInterface $iriConverter;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        IriConverterInterface $iriConverter
    ) {
        $this->jwtManager   = $jwtManager;
        $this->iriConverter = $iriConverter;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            JwtEvents::JWT_DECODED            => 'validateToken',
            JwtEvents::JWT_AUTHENTICATED      => 'updateSecurityToken',
            JwtEvents::AUTHENTICATION_SUCCESS => 'addStaffJwtTokens',
            JwtEvents::JWT_CREATED => 'addPayload',
        ];
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function addPayload(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();

        if (!array_key_exists('staff', $payload)) {
            return;
        }

        if (!array_key_exists('@scope', $payload)) {
            $payload['@scope'] = [];
        }

        $payload['@scope'][] = 'staff';

        $event->setData($payload);
    }

    /**
     * To handle case where the staff is disabled whereas user is still connected
     * This will disconnect him the next time the user attempts to access the api after its token has been disabled.
     */
    public function validateToken(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        if (isset($payload['staff'])) {
            try {
                /** @var Staff $staff */
                $staff = $this->iriConverter->getItemFromIri($payload['staff'], [AbstractNormalizer::GROUPS => []]);
            } catch (ItemNotFoundException $exception) {
                $event->markAsInvalid();
            }

            if (false === ($staff instanceof Staff && $staff->isGrantedLogin())) {
                $event->markAsInvalid();
            }
        }
    }

    public function addStaffJwtTokens(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof UserInterface && false === $user instanceof User) {
            $user = $this->userRepository->findOneBy(['email' => $user->getUsername()]);
        }

        if (null === $user) {
            return;
        }

        $data = $event->getData();

        $data['staffTokens'] = [];

        foreach ($user->getStaff() as $staff) {
            if ($staff->isGrantedLogin()) {
                $data['staffTokens'][] = $this->jwtManager->createFromPayload($user, ['staff' => $this->iriConverter->getIriFromItem($staff)]);
            }
        }

        $event->setData($data);
    }

    public function updateSecurityToken(JWTAuthenticatedEvent $event): void
    {
        $payload = $event->getPayload();
        $token   = $event->getToken();

        if (isset($payload['staff'])) {
            /** @var Staff $currentStaff */
            $currentStaff = $this->iriConverter->getItemFromIri($payload['staff'], [AbstractNormalizer::GROUPS => []]);

            // Legacy way of storing current staff for security
            $token->getUser()->setCurrentStaff($currentStaff);

            $token->setAttribute('staff', $currentStaff);
            $token->setAttribute('company', $currentStaff->getCompany());
        }
    }
}
