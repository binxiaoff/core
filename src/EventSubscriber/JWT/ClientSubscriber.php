<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\JWT;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\IriConverter;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\Clients;
use Unilend\Entity\MarketSegment;
use Unilend\Entity\Staff;
use Unilend\Repository\ClientsRepository;

class ClientSubscriber implements EventSubscriberInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var IriConverter */
    private $iriConverter;
    /** @var JWTTokenManagerInterface */
    private $jwtManager;
    /** @var EntityManagerInterface */
    private $em;

    /**
     * @param EntityManagerInterface   $em
     * @param ClientsRepository        $clientsRepository
     * @param IriConverterInterface    $iriConverter
     * @param JWTTokenManagerInterface $JWTManager
     */
    public function __construct(EntityManagerInterface $em, ClientsRepository $clientsRepository, IriConverterInterface $iriConverter, JWTTokenManagerInterface $JWTManager)
    {
        $this->clientsRepository = $clientsRepository;
        $this->iriConverter      = $iriConverter;
        $this->jwtManager        = $JWTManager;
        $this->em                = $em;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            JwtEvents::JWT_CREATED            => [['addStaffPayload'], ['addClientPayload']],
            JwtEvents::JWT_DECODED            => ['validateToken'],
            JwtEvents::AUTHENTICATION_SUCCESS => ['createTokens'],
            JwtEvents::JWT_AUTHENTICATED      => ['addSecurityTokenData'],
        ];
    }

    /**
     * To handle case where the staff is disabled whereas user is still connected
     * This will disconnect him the next time the user attempts to access the api after its token has been disabled.
     *
     * @param JWTDecodedEvent $event
     */
    public function validateToken(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        if (false === isset($payload['staff'])) {
            $event->markAsInvalid();
        }

        try {
            /** @var Staff $staff */
            $staff = $this->iriConverter->getItemFromIri($payload['staff']);
        } catch (\Exception $exception) {
            $staff = null;
        }

        if (null === $staff || false === $staff->isAvailable()) {
            $event->markAsInvalid();
        }
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
            if ($staffEntry->isAvailable() && $staffEntry->getCompany()->hasSigned()) {
                $user->setCurrentStaff($staffEntry);
                $data['tokens'][] = $this->jwtManager->create($user);
            }
        }

        $event->setData($data);
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function addStaffPayload(JWTCreatedEvent $event): void
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
                })->toArray();
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

        $token->getUser()->setCurrentStaff($currentStaff);
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function addClientPayload(JWTCreatedEvent $event): void
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
