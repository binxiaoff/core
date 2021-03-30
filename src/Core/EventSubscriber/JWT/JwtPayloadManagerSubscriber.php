<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber\JWT;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\UserRepository;
use Unilend\Core\Service\JWT\JwtPayloadManagerInterface;

class JwtPayloadManagerSubscriber implements EventSubscriberInterface
{
    /** @var UserRepository  */
    private UserRepository $userRepository;

    /** @var array|JwtPayloadManagerInterface[] */
    private array $jwtPayloadManagers;

    /** @var JWTTokenManagerInterface */
    private JWTTokenManagerInterface $jwtManager;

    /**
     * @param UserRepository                      $userRepository
     * @param JWTTokenManagerInterface            $jwtManager
     * @param iterable|JwtPayloadManagerInterface $jwtPayloadManagers
     */
    public function __construct(UserRepository $userRepository, JWTTokenManagerInterface $jwtManager, iterable $jwtPayloadManagers = [])
    {
        $this->userRepository = $userRepository;
        $this->jwtManager = $jwtManager;

        foreach ($jwtPayloadManagers as $jwtPayloadManager) {
            $this->addJwtTokenManager($jwtPayloadManager);
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            JwtEvents::JWT_DECODED            => 'validateToken',
            JwtEvents::JWT_AUTHENTICATED      => 'updateSecurityToken',
            JwtEvents::AUTHENTICATION_SUCCESS => 'addGeneratedJwtTokens',
        ];
    }

    /**
     * To handle case where the staff is disabled whereas user is still connected
     * This will disconnect him the next time the user attempts to access the api after its token has been disabled.
     *
     * @param JWTDecodedEvent $event
     */
    public function validateToken(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        if (false === isset($payload['@type'])) {
            $event->markAsInvalid();
        }

        if (false === isset($this->jwtPayloadManagers[$payload['@type']])) {
            $event->markAsInvalid();
        }

        $validity = $this->jwtPayloadManagers[$payload['@type']]->isTokenPayloadValid($event->getPayload());

        if (false === $validity) {
            $event->markAsInvalid();
        }
    }

    /**
     * @param AuthenticationSuccessEvent $event
     */
    public function addGeneratedJwtTokens(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof UserInterface && false === $user instanceof User) {
            $user = $this->userRepository->findOneBy(['email' => $user->getUsername()]);
        }

        if (null === $user) {
            return;
        }

        $data = $event->getData();

        unset($data['token']);

        $data['tokens'] = [];

        foreach ($this->jwtPayloadManagers as $generator) {
            foreach ($generator->generatePayloads($user) as $payload) {
                $payload['@type'] = $generator->getType();
                $data['tokens'][] = $this->jwtManager->createFromPayload($user, $payload);
            }
        }

        $event->setData($data);
    }

    /**
     * @param JWTAuthenticatedEvent $event
     */
    public function updateSecurityToken(JWTAuthenticatedEvent $event): void
    {
        $payload = $event->getPayload();
        $token   = $event->getToken();

        $token->setAttribute('type', $payload['@type']);
        $this->jwtPayloadManagers[$payload['@type']]->updateSecurityToken($token, $payload);
    }

    /**
     * @param JwtPayloadManagerInterface $manager
     */
    private function addJwtTokenManager(JwtPayloadManagerInterface $manager)
    {
        $this->jwtPayloadManagers[$manager->getType()] = $manager;
    }
}
