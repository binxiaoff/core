<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber\Jwt;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as JwtEvents;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unilend\Core\Service\Jwt\PayloadManagerInterface;

class PayloadManagerSubscriber implements EventSubscriberInterface
{
    private array $jwtPayloadManagers;

    private JWTTokenManagerInterface $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager, iterable $jwtPayloadManagers = [])
    {
        $this->jwtPayloadManagers = [];

        foreach ($jwtPayloadManagers as $jwtPayloadManager) {
            $this->addPayloadManager($jwtPayloadManager);
        }

        $this->jwtManager = $jwtManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            JwtEvents::AUTHENTICATION_SUCCESS => 'updateSuccessResponse',
            JwtEvents::JWT_DECODED            => 'validatePayload',
            JwtEvents::JWT_AUTHENTICATED      => 'updateSecurityToken',
        ];
    }

    public function validatePayload(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        $scope = $payload['@scope'] ?? null;

        if (null === $scope) {
            return;
        }

        $payloadGenerator = $this->jwtPayloadManagers[$scope] ?? null;

        if (null === $payloadGenerator) {
            $event->markAsInvalid();
        }

        if (false === $payloadGenerator->isValid($payload)) {
            $event->markAsInvalid();
        }
    }

    public function updateSecurityToken(JWTAuthenticatedEvent $event): void
    {
        $payload = $event->getPayload();
        $token   = $event->getToken();

        $scope = $payload['@scope'] ?? null;

        if (null === $scope) {
            return;
        }

        $payloadGenerator = $this->jwtPayloadManagers[$scope] ?? null;

        if (null === $payloadGenerator) {
            throw new \LogicException(sprintf('At this point there should be a corresponding payload generator for %s', $scope));
        }

        $payloadGenerator->updateSecurityToken($token, $payload);
    }

    public function updateSuccessResponse(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();

        // Remove generated token
        unset($data['token']);

        $data['tokens'] = [$this->jwtManager->create($event->getUser())];

        /** @var PayloadManagerInterface $jwtPayloadManager */
        foreach ($this->jwtPayloadManagers as $jwtPayloadManager) {
            foreach ($jwtPayloadManager->getPayloads($event->getUser()) as $payload) {
                $payload['@scope'] = $jwtPayloadManager->getScope();
                $data['tokens'][]  = $this->jwtManager->createFromPayload($event->getUser(), $payload);
            }
        }

        $event->setData($data);
    }

    public function addPayloadManager(PayloadManagerInterface $jwtPayloadManager)
    {
        if (isset($this->jwtPayloadManagers[$jwtPayloadManager->getScope()])) {
            throw new \InvalidArgumentException(sprintf(
                'This scope %s is already used by another jwt payload manager. Check the classes %s and %s',
                $jwtPayloadManager->getScope(),
                \get_class($jwtPayloadManager),
                \get_class($this->jwtPayloadManagers[$jwtPayloadManager->getScope()])
            ));
        }
        $this->jwtPayloadManagers[$jwtPayloadManager->getScope()] = $jwtPayloadManager;
    }
}
