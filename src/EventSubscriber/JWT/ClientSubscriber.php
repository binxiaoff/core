<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\JWT;

use ApiPlatform\Core\Api\IriConverterInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\{Event\AuthenticationSuccessEvent, Event\JWTAuthenticatedEvent, Event\JWTCreatedEvent, Event\JWTDecodedEvent, Events as JwtEvents,
    Services\JWTTokenManagerInterface};
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Entity\{Staff};
use Unilend\Repository\ClientsRepository;

class ClientSubscriber implements EventSubscriberInterface
{
    /** @var ClientsRepository */
    private ClientsRepository $clientsRepository;
    /** @var IriConverterInterface */
    private IriConverterInterface $iriConverter;
    /** @var JWTTokenManagerInterface */
    private JWTTokenManagerInterface $jwtManager;

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
            JwtEvents::JWT_CREATED            => ['addUserPayload'],
            JwtEvents::JWT_DECODED            => ['validateToken'],
            JwtEvents::AUTHENTICATION_SUCCESS => ['createTokens'],
            JwtEvents::JWT_AUTHENTICATED      => ['setCurrentStaff'],
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
            $staff = $this->iriConverter->getItemFromIri($payload['staff'], [AbstractNormalizer::GROUPS => []]);
        } catch (Exception $exception) {
            $staff = null;
        }

        if (null === $staff || false === $staff->isActive()) {
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
            if ($staffEntry->isGrantedLogin()) {
                $user->setCurrentStaff($staffEntry);
                $data['tokens'][] = $this->jwtManager->create($user);
            }
        }

        $event->setData($data);
    }

    /**
     * @param JWTAuthenticatedEvent $event
     */
    public function setCurrentStaff(JWTAuthenticatedEvent $event): void
    {
        $payload = $event->getPayload();
        $token   = $event->getToken();

        /** @var Staff $currentStaff */
        $currentStaff = $this->iriConverter->getItemFromIri($payload['staff'], [AbstractNormalizer::GROUPS => []]);

        $token->getUser()->setCurrentStaff($currentStaff);
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function addUserPayload(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        $user    = $event->getUser();

        if ($user instanceof UserInterface && false === $user instanceof Clients) {
            $user = $this->clientsRepository->findOneBy(['email' => $user->getUsername()]);
        }

        if ($user instanceof Clients) {
            $payload['user'] = $this->iriConverter->getIriFromItem($user);
            $currentStaff = $user->getCurrentStaff();
            if ($currentStaff) {
                $payload['staff'] = $this->iriConverter->getIriFromItem($currentStaff);
            }
        }

        $event->setData($payload);
    }
}
