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
use Unilend\Entity\Staff;
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
            $payload['publicId']  = $user->getPublicId();
            $payload['firstName'] = $user->getFirstName();
            $payload['lastName']  = $user->getLastName();

            /** @var Staff $staff */
            $staff = $user->getStaff()->first();

            if ($staff) {
                $payload['marketSegments'] = $staff->getMarketSegments()->map(function (MarketSegment $marketSegment) {
                    return $this->iriConverter->getIriFromItem($marketSegment);
                })->toArray();
                $payload['roles']                  = $staff->getRoles();
                $company                           = $staff->getCompany();
                $payload['company']['@id']         = $this->iriConverter->getIriFromItem($company);
                $payload['company']['publicId']    = $company->getPublicId();
                $payload['company']['name']        = $company->getName();
                $payload['company']['shortCode']   = $company->getShortCode();
                $payload['company']['emailDomain'] = $company->getEmailDomain();
            }
            $event->setData($payload);
        }
    }
}
