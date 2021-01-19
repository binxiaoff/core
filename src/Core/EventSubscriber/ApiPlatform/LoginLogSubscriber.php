<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber\ApiPlatform;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Gesdinet\JWTRefreshTokenBundle\Event\RefreshEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\{AuthenticationFailureEvent, JWTCreatedEvent};
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Unilend\Core\Entity\User;
use Unilend\Core\Entity\{UserSuccessfulLogin};
use Unilend\Core\Event\TemporaryToken\{TemporaryTokenAuthenticationEvents, TemporaryTokenAuthenticationFailureEvent, TemporaryTokenAuthenticationSuccessEvent};
use Unilend\Core\Repository\{UserFailedLoginRepository, UserSuccessfulLoginRepository, UserRepository};
use Unilend\Core\Service\User\UserLoginFactory;

class LoginLogSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserLoginFactory
     */
    private UserLoginFactory $userLoginHistoryFactory;
    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;
    /**
     * @var UserSuccessfulLoginRepository
     */
    private UserSuccessfulLoginRepository $userSuccessfulLoginRepository;
    /**
     * @var UserFailedLoginRepository
     */
    private UserFailedLoginRepository $userFailedLoginRepository;
    /**
     * @var bool
     */
    private bool $alreadyLogged;

    /**
     * LoginLogSubscriber constructor.
     *
     * @param UserLoginFactory              $userLoginHistoryFactory
     * @param UserRepository                $userRepository
     * @param UserSuccessfulLoginRepository $userSuccessfulLoginRepository
     * @param UserFailedLoginRepository     $userFailedLoginRepository
     */
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

    /**
     * {@inheritdoc}
     */
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
     * @param JWTCreatedEvent $event
     *
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
     * @param RefreshEvent $event
     *
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
     * @param AuthenticationFailureEvent $event
     *
     * @throws Exception
     */
    public function onLoginFailure(AuthenticationFailureEvent $event): void
    {
        $authenticationException = $event->getException();

        $failedLogin = $this->userLoginHistoryFactory->createUserLoginFailure($authenticationException, $this->getFailedLoginUsername($authenticationException));
        $this->userFailedLoginRepository->save($failedLogin);
    }

    /**
     * @param TemporaryTokenAuthenticationSuccessEvent $event
     *
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
     * @param TemporaryTokenAuthenticationFailureEvent $event
     *
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

    /**
     * @param AuthenticationException $authenticationException
     *
     * @return string|null
     */
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
