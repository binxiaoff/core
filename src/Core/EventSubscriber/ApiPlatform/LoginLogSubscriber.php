<?php

declare(strict_types=1);

namespace KLS\Core\EventSubscriber\ApiPlatform;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Gesdinet\JWTRefreshTokenBundle\Event\RefreshEvent;
use KLS\Core\Entity\User;
use KLS\Core\Entity\UserSuccessfulLogin;
use KLS\Core\Event\TemporaryToken\TemporaryTokenAuthenticationEvents;
use KLS\Core\Event\TemporaryToken\TemporaryTokenAuthenticationFailureEvent;
use KLS\Core\Event\TemporaryToken\TemporaryTokenAuthenticationSuccessEvent;
use KLS\Core\Repository\UserFailedLoginRepository;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Repository\UserSuccessfulLoginRepository;
use KLS\Core\Service\User\UserLoginFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LoginLogSubscriber implements EventSubscriberInterface
{
    private UserLoginFactory $userLoginHistoryFactory;
    private UserRepository $userRepository;
    private UserSuccessfulLoginRepository $userSuccessfulLoginRepository;
    private UserFailedLoginRepository $userFailedLoginRepository;
    private bool $alreadyLogged;

    public function __construct(
        UserLoginFactory $userLoginHistoryFactory,
        UserRepository $userRepository,
        UserSuccessfulLoginRepository $userSuccessfulLoginRepository,
        UserFailedLoginRepository $userFailedLoginRepository
    ) {
        $this->userRepository                = $userRepository;
        $this->userLoginHistoryFactory       = $userLoginHistoryFactory;
        $this->userSuccessfulLoginRepository = $userSuccessfulLoginRepository;
        $this->userFailedLoginRepository     = $userFailedLoginRepository;
        $this->alreadyLogged                 = false;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            JwtEvents::JWT_CREATED                                     => 'onLoginSuccess',
            'gesdinet.refresh_token'                                   => 'onLoginRefresh',
            JwtEvents::AUTHENTICATION_FAILURE                          => 'onLoginFailure',
            TemporaryTokenAuthenticationEvents::AUTHENTICATION_SUCCESS => 'onTemporaryTokenLoginSuccess',
            TemporaryTokenAuthenticationEvents::AUTHENTICATION_FAILURE => 'onTemporaryTokenLoginFailure',
        ];
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function onLoginSuccess(JWTCreatedEvent $event): void
    {
        if ($this->alreadyLogged) {
            return;
        }
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => $event->getUser()->getUsername()]);

        $successfulLogin = $this->userLoginHistoryFactory->createUserLoginSuccess($user, UserSuccessfulLogin::ACTION_JWT_LOGIN);

        $this->userSuccessfulLoginRepository->save($successfulLogin);

        $this->alreadyLogged = true;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function onLoginRefresh(RefreshEvent $event): void
    {
        if ($this->alreadyLogged) {
            return;
        }
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => $event->getRefreshToken()->getUsername()]);

        $successfulLogin = $this->userLoginHistoryFactory->createUserLoginSuccess($user, UserSuccessfulLogin::ACTION_JWT_REFRESH);
        $this->userSuccessfulLoginRepository->save($successfulLogin);

        $this->alreadyLogged = true;
    }

    /**
     * @throws Exception
     */
    public function onLoginFailure(AuthenticationFailureEvent $event): void
    {
        $authenticationException = $event->getException();

        $failedLogin = $this->userLoginHistoryFactory->createUserLoginFailure($authenticationException, $this->getFailedLoginUsername($authenticationException));
        $this->userFailedLoginRepository->save($failedLogin);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function onTemporaryTokenLoginSuccess(TemporaryTokenAuthenticationSuccessEvent $event): void
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => $event->getUser()->getUsername()]);

        $successfulLogin = $this->userLoginHistoryFactory->createUserLoginSuccess($user, UserSuccessfulLogin::ACTION_TEMPORARY_TOKEN);
        $this->userSuccessfulLoginRepository->save($successfulLogin);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function onTemporaryTokenLoginFailure(TemporaryTokenAuthenticationFailureEvent $event): void
    {
        $authenticationException = $event->getException();

        $failedLogin = $this->userLoginHistoryFactory->createUserLoginFailure($authenticationException, $this->getFailedLoginUsername($authenticationException));
        $this->userFailedLoginRepository->save($failedLogin);
    }

    private function getFailedLoginUsername(AuthenticationException $authenticationException): ?string
    {
        if ($authenticationException instanceof AccountStatusException) {
            return $authenticationException->getUser()->getUsername();
        }

        $token = $authenticationException->getToken();

        $user = $token ? $token->getUser() : null;

        return $user ? $user->getUsername() : null;
    }
}
