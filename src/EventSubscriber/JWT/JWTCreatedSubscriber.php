<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\JWT;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\IriConverter;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Unilend\Entity\Clients;
use Unilend\Entity\MarketSegment;
use Unilend\Repository\ClientsRepository;

class JWTCreatedSubscriber implements EventSubscriberInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var SerializerInterface */
    private $serializer;
    /** @var IriConverter */
    private $iriConverter;

    /**
     * @param ClientsRepository     $clientsRepository
     * @param IriConverterInterface $iriConverter
     * @param SerializerInterface   $serializer
     */
    public function __construct(
        ClientsRepository $clientsRepository,
        IriConverterInterface $iriConverter,
        SerializerInterface $serializer
    ) {
        $this->clientsRepository = $clientsRepository;
        $this->serializer        = $serializer;
        $this->iriConverter      = $iriConverter;
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            JwtEvents::JWT_CREATED => ['addClientData'],
        ];
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function addClientData(JWTCreatedEvent $event)
    {
        $payload = $event->getData();
        $user    = $event->getUser();

        if ($user instanceof UserInterface && false === $user instanceof Clients) {
            $user = $this->clientsRepository->findOneBy(['email' => $user->getUsername()]);
        }

        if ($user instanceof Clients) {
            $payload['@id']       = $this->iriConverter->getIriFromItem($user);
            $payload['hash']      = $user->getPublicId();
            $payload['firstName'] = $user->getFirstName();
            $payload['lastName']  = $user->getLastName();

            $staff = $user->getStaff();

            if ($staff) {
                $payload['marketSegments'] = $staff->getMarketSegments()->map(function (MarketSegment $marketSegment) {
                    return  $this->iriConverter->getIriFromItem($marketSegment);
                })->toArray();
                $payload['roles'] = $staff->getRoles();
                //todo: put the exact fields
                $company                   = $staff->getCompany();
                $payload['company']        = $this->serializer->normalize($company, 'array', ['groups' => ['company:jwt:read', 'publicId:read']]);
                $payload['company']['@id'] = $this->iriConverter->getIriFromItem($company);
            }
            $event->setData($payload);
        }
    }
}
