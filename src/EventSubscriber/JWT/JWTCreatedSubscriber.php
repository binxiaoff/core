<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\JWT;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Unilend\Entity\Clients;
use Unilend\Repository\ClientsRepository;

class JWTCreatedSubscriber implements EventSubscriberInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var SerializerInterface */
    private $serializer;

    /**
     * @param ClientsRepository   $clientsRepository
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ClientsRepository $clientsRepository,
        SerializerInterface $serializer
    ) {
        $this->clientsRepository = $clientsRepository;
        $this->serializer        = $serializer;
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
            $payload['hash']      = $user->getHash();
            $payload['firstName'] = $user->getFirstName();
            $payload['lastName']  = $user->getLastName();
            $payload['company']   = $this->serializer->normalize($user->getStaff()->getCompany());
            $event->setData($payload);
        }
    }
}
