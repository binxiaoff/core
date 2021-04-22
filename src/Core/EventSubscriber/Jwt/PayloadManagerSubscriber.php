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
    private array $payloadManagers;

    private JWTTokenManagerInterface $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager, iterable $payloadManagers = [])
    {
        $this->payloadManagers = [];

        foreach ($payloadManagers as $jwtPayloadManager) {
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

        $payloadManager = $this->payloadManagers[$scope] ?? null;

        if (null === $payloadManager) {
            $event->markAsInvalid();
        }

        if (false === $payloadManager->isValid($payload)) {
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

        $payloadManager = $this->payloadManagers[$scope] ?? null;

        if (null === $payloadManager) {
            throw new \LogicException(sprintf('At this point there should be a corresponding payload generator for %s', $scope));
        }

        $payloadManager->updateSecurityToken($token, $payload);
    }

    public function updateSuccessResponse(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();

        // Remove generated token
        unset($data['token']);

        $data['tokens'] = [$this->jwtManager->create($event->getUser())];

        /** @var PayloadManagerInterface $jwtPayloadManager */
        foreach ($this->payloadManagers as $jwtPayloadManager) {
            foreach ($jwtPayloadManager->getPayloads($event->getUser()) as $payload) {
                $payload['@scope'] = $jwtPayloadManager::getScope();
                $data['tokens'][]  = $this->jwtManager->createFromPayload($event->getUser(), $payload);
            }
        }

        $event->setData($data);
    }

    public function addPayloadManager(PayloadManagerInterface $payloadManager)
    {
        if (isset($this->payloadManagers[$payloadManager::getScope()])) {
            throw new \InvalidArgumentException(sprintf(
                'This scope %s is already used by another jwt payload manager. Check the classes %s and %s',
                $payloadManager::getScope(),
                \get_class($payloadManager),
                \get_class($this->payloadManagers[$payloadManager::getScope()])
            ));
        }
        $this->payloadManagers[$payloadManager::getScope()] = $payloadManager;
    }
}
