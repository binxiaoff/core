<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\JWT;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\Clients;
use Unilend\Repository\ClientsRepository;

class JWTCreatedSubscriber implements EventSubscriberInterface
{
    /**
     * @var ClientsRepository
     */
    private $clientsRepository;

    /**
     * @param ClientsRepository $clientsRepository
     */
    public function __construct(ClientsRepository $clientsRepository)
    {
        $this->clientsRepository = $clientsRepository;
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            JwtEvents::JWT_CREATED => 'addClientHashOnCreated',
        ];
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function addClientHashOnCreated(JWTCreatedEvent $event)
    {
        $payload = $event->getData();
        $user    = $event->getUser();

        if ($user instanceof UserInterface && false === $user instanceof Clients) {
            $user = $this->clientsRepository->findOneBy(['email' => $user->getUsername()]);
        }

        if ($user instanceof Clients) {
            $payload['hash'] = $user->getHash();
            $event->setData($payload);
        }
    }
}
