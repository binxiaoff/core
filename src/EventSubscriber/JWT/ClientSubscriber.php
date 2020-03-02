<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\JWT;

use ApiPlatform\Core\Api\IriConverterInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\Clients;
use Unilend\Repository\ClientsRepository;

class ClientSubscriber implements EventSubscriberInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var IriConverterInterface */
    private $iriConverter;

    /**
     * @param ClientsRepository     $clientsRepository
     * @param IriConverterInterface $iriConverter
     */
    public function __construct(ClientsRepository $clientsRepository, IriConverterInterface $iriConverter)
    {
        $this->clientsRepository = $clientsRepository;
        $this->iriConverter      = $iriConverter;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            JwtEvents::JWT_CREATED => ['addClientData'],
        ];
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function addClientData(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        $user    = $event->getUser();

        if ($user instanceof UserInterface && false === $user instanceof Clients) {
            $user = $this->clientsRepository->findOneBy(['email' => $user->getUsername()]);
        }

        if ($user instanceof Clients) {
            $payload['@id']       = $this->iriConverter->getIriFromItem($user);
            $payload['publicId']  = $user->getPublicId();
            $payload['firstName'] = $user->getFirstName();
            $payload['lastName']  = $user->getLastName();
        }

        $event->setData($payload);
    }
}
