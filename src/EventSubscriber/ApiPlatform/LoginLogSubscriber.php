<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\ApiPlatform;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Gesdinet\JWTRefreshTokenBundle\Event\RefreshEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\{AuthenticationFailureEvent, JWTCreatedEvent};
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unilend\Entity\{ClientSuccessfulLogin, Clients};
use Unilend\Event\TemporaryToken\{TemporaryTokenAuthenticationEvents, TemporaryTokenAuthenticationFailureEvent, TemporaryTokenAuthenticationSuccessEvent};
use Unilend\Repository\{ClientFailedLoginRepository, ClientSuccessfulLoginRepository, ClientsRepository};
use Unilend\Service\User\ClientLoginFactory;

class LoginLogSubscriber implements EventSubscriberInterface
{
    /**
     * @var ClientLoginFactory
     */
    private $clientLoginHistoryFactory;
    /**
     * @var ClientsRepository
     */
    private $clientsRepository;
    /**
     * @var ClientSuccessfulLoginRepository
     */
    private $clientSuccessfulLoginRepository;
    /**
     * @var ClientFailedLoginRepository
     */
    private $clientFailedLoginRepository;
    /**
     * @var bool
     */
    private $alreadyLogged;

    /**
     * LoginLogSubscriber constructor.
     *
     * @param ClientLoginFactory              $clientLoginHistoryFactory
     * @param ClientsRepository               $clientsRepository
     * @param ClientSuccessfulLoginRepository $clientSuccessfulLoginRepository
     * @param ClientFailedLoginRepository     $clientFailedLoginRepository
     */
    public function __construct(
        ClientLoginFactory $clientLoginHistoryFactory,
        ClientsRepository $clientsRepository,
        ClientSuccessfulLoginRepository $clientSuccessfulLoginRepository,
        ClientFailedLoginRepository $clientFailedLoginRepository
    ) {
        $this->clientsRepository               = $clientsRepository;
        $this->clientLoginHistoryFactory       = $clientLoginHistoryFactory;
        $this->clientSuccessfulLoginRepository = $clientSuccessfulLoginRepository;
        $this->clientFailedLoginRepository     = $clientFailedLoginRepository;
        $this->alreadyLogged                   = false;
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
        /** @var Clients $client */
        $client = $this->clientsRepository->findOneBy(['email' => $event->getUser()->getUsername()]);

        $successfulLogin = $this->clientLoginHistoryFactory->createClientLoginSuccess($client, ClientSuccessfulLogin::ACTION_JWT_LOGIN);
        $this->clientSuccessfulLoginRepository->save($successfulLogin);
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
        /** @var Clients $client */
        $client = $this->clientsRepository->findOneBy(['email' => $event->getRefreshToken()->getUsername()]);

        $successfulLogin = $this->clientLoginHistoryFactory->createClientLoginSuccess($client, ClientSuccessfulLogin::ACTION_JWT_REFRESH);
        $this->clientSuccessfulLoginRepository->save($successfulLogin);

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

        $username = ($token = $authenticationException->getToken()) ? $token->getUsername() : null;
        $message  = $authenticationException->getMessage();

        $failedLogin = $this->clientLoginHistoryFactory->createClientLoginFailure($message, $username);
        $this->clientFailedLoginRepository->save($failedLogin);
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
        /** @var Clients $client */
        $client = $this->clientsRepository->findOneBy(['email' => $event->getUser()->getUsername()]);

        $successfulLogin = $this->clientLoginHistoryFactory->createClientLoginSuccess($client, ClientSuccessfulLogin::ACTION_TEMPORARY_TOKEN);
        $this->clientSuccessfulLoginRepository->save($successfulLogin);
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

        $username = ($token = $authenticationException->getToken()) ? $token->getUsername() : null;
        $message  = $authenticationException->getMessage();

        $failedLogin = $this->clientLoginHistoryFactory->createClientLoginFailure($message, $username);
        $this->clientFailedLoginRepository->save($failedLogin);
    }
}
