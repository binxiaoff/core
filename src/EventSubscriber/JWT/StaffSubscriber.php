<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\JWT;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\IriConverter;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\Clients;
use Unilend\Entity\MarketSegment;
use Unilend\Entity\Staff;
use Unilend\Repository\ClientsRepository;

class StaffSubscriber implements EventSubscriberInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var IriConverter */
    private $iriConverter;
    /** @var JWTTokenManagerInterface */
    private $jwtManager;

    /**
     * @param ClientsRepository        $clientsRepository
     * @param IriConverterInterface    $iriConverter
     * @param JWTTokenManagerInterface $JWTManager
     */
    public function __construct(ClientsRepository $clientsRepository, IriConverterInterface $iriConverter, JWTTokenManagerInterface $JWTManager)
    {
        $this->clientsRepository = $clientsRepository;
        $this->iriConverter      = $iriConverter;
        $this->jwtManager        = $JWTManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            JwtEvents::JWT_CREATED            => ['addPayloadData'],
            JwtEvents::AUTHENTICATION_SUCCESS => ['createTokens'],
            JwtEvents::JWT_AUTHENTICATED      => ['addSecurityTokenData'],
        ];
    }

    /**
     * @param AuthenticationSuccessEvent $event
     */
    public function createTokens(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof UserInterface && false === $user instanceof Clients) {
            $user = $this->clientsRepository->findOneBy(['email' => $user->getUsername()]);
        }

        $staffCollection = $user->getStaff();

        $data = $event->getData();
        unset($data['token']);

        /** @var Staff $staffEntry */
        foreach ($staffCollection as $staffEntry) {
            $user->setCurrentStaff($staffEntry);
            $data['tokens'][] = $this->jwtManager->create($user);
        }

        $event->setData($data);
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function addPayloadData(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        $user    = $event->getUser();

        if ($user instanceof UserInterface && false === $user instanceof Clients) {
            $user = $this->clientsRepository->findOneBy(['email' => $user->getUsername()]);
        }

        if ($user instanceof Clients) {
            $currentStaff = $user->getCurrentStaff();
            if ($currentStaff) {
                $payload['staff'] = $this->iriConverter->getIriFromItem($currentStaff);

                $payload['marketSegments'] = $currentStaff->getMarketSegments()->map(function (MarketSegment $marketSegment) {
                    return $this->iriConverter->getIriFromItem($marketSegment);
                })->toArray()
                ;
                $payload['roles']                  = $currentStaff->getRoles();
                $company                           = $currentStaff->getCompany();
                $payload['company']['@id']         = $this->iriConverter->getIriFromItem($company);
                $payload['company']['publicId']    = $company->getPublicId();
                $payload['company']['name']        = $company->getName();
                $payload['company']['shortCode']   = $company->getShortCode();
                $payload['company']['emailDomain'] = $company->getEmailDomain();
            }
            $event->setData($payload);
        }
    }

    /**
     * @param JWTAuthenticatedEvent $event
     */
    public function addSecurityTokenData(JWTAuthenticatedEvent $event): void
    {
        $payload = $event->getPayload();
        $token   = $event->getToken();

        $currentStaff = $this->iriConverter->getItemFromIri($payload['staff']);

        $token->setAttribute('staff', $currentStaff);
        $token->getUser()->setCurrentStaff($currentStaff);
    }
}
