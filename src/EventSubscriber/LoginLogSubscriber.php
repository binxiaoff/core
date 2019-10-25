<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unilend\Entity\{ClientLogin, Clients};
use Unilend\Repository\ClientLoginRepository;
use Unilend\Repository\ClientsRepository;
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
     * @var ClientLoginRepository
     */
    private $clientLoginRepository;

    /**
     * LoginLogSubscriber constructor.
     *
     * @param ClientLoginFactory    $clientLoginHistoryFactory
     * @param ClientsRepository     $clientsRepository
     * @param ClientLoginRepository $clientLoginRepository
     */
    public function __construct(
        ClientLoginFactory $clientLoginHistoryFactory,
        ClientsRepository $clientsRepository,
        ClientLoginRepository $clientLoginRepository
    ) {
        $this->clientLoginHistoryFactory = $clientLoginHistoryFactory;
        $this->clientsRepository         = $clientsRepository;
        $this->clientLoginRepository     = $clientLoginRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            JwtEvents::JWT_CREATED            => 'onLoginSuccess',
            JwtEvents::AUTHENTICATION_FAILURE => 'onLoginFailure',
        ];
    }

    /**
     * @param JWTCreatedEvent $event
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function onLoginSuccess(JWTCreatedEvent $event): void
    {
        $this->logSuccess($event->getUser(), ClientLogin::ACTION_LOGIN);
    }

    /**
     * @param RefreshEvent $event
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function onLoginRefresh(RefreshEvent $event): void
    {
        $username = $event->getRefreshToken()->getUsername();

        /** @var Clients $client */
        $client = $this->clientsRepository->findOneBy(['email' => $username]);

        $this->logSuccess($client, ClientLogin::ACTION_REFRESH);
    }

    /**
     * @param AuthenticationFailureEvent $event
     *
     * @throws Exception
     */
    public function onLoginFailure(AuthenticationFailureEvent $event): void
    {
        $failure = $this->clientLoginHistoryFactory->createClientLoginFailure();

        if ($token = $event->getException()->getToken()) {
            $failure->setUsername($token->getUsername());
        }

        $failure->setError($event->getException()->getMessage());

        $this->manager->persist($failure);
        $this->manager->flush();
    }

    /**
     * @param Clients $client
     * @param string  $action
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    private function logSuccess(Clients $client, string $action): void
    {
        $entry = $this->clientLoginHistoryFactory->createClientLoginEntry($client, $action);
        $this->clientLoginRepository->save($entry);
    }
}
