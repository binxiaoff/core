<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber;

use Doctrine\Common\Persistence\ObjectManager;
use Gesdinet\JWTRefreshTokenBundle\Event\RefreshEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unilend\Entity\{ClientLogin, Clients};
use Unilend\Repository\ClientsRepository;
use Unilend\Service\User\ClientLoginFactory;

class LoginLogSubscriber implements EventSubscriberInterface
{
    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var ClientLoginFactory
     */
    private $clientLoginHistoryFactory;
    /**
     * @var ClientsRepository
     */
    private $clientsRepository;

    /**
     * LoginLogSubscriber constructor.
     *
     * @param ClientLoginFactory $clientLoginHistoryFactory
     * @param ClientsRepository  $clientsRepository
     * @param ObjectManager      $manager
     */
    public function __construct(
        ClientLoginFactory $clientLoginHistoryFactory,
        ClientsRepository $clientsRepository,
        ObjectManager $manager
    ) {
        $this->manager                   = $manager;
        $this->clientLoginHistoryFactory = $clientLoginHistoryFactory;
        $this->clientsRepository         = $clientsRepository;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * ['eventName' => 'methodName']
     *  * ['eventName' => ['methodName', $priority]]
     *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_CREATED      => 'onLoginSuccess',
            'gesdinet.refresh_token' => 'onLoginRefresh',
        ];
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function onLoginSuccess(JWTCreatedEvent $event): void
    {
        $this->log($event->getUser(), ClientLogin::ACTION_LOGIN);
    }

    /**
     * @param RefreshEvent $event
     */
    public function onLoginRefresh(RefreshEvent $event): void
    {
        $username = $event->getRefreshToken()->getUsername();

        /** @var Clients $client */
        $client = $this->clientsRepository->findOneBy(['email' => $username]);

        $this->log($client, ClientLogin::ACTION_REFRESH);
    }

    /**
     * @param Clients $client
     * @param string  $action
     */
    private function log(Clients $client, string $action): void
    {
        $entry = $this->clientLoginHistoryFactory->createClientLoginEntry($client, $action);
        $this->manager->persist($entry);
        $this->manager->flush();
    }
}
