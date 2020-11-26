<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber\ApiPlatform;

use ApiPlatform\Core\EventListener\EventPriorities;
use Exception;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\{EventDispatcher\EventSubscriberInterface, HttpFoundation\Request, HttpKernel\KernelEvents};
use Unilend\Core\Entity\Clients;
use Unilend\Repository\TemporaryTokenRepository;

class ClientProfileUpdatedEventSubscriber implements EventSubscriberInterface
{
    /** @var TemporaryTokenRepository */
    private $temporaryTokenRepository;
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /**
     * @param TemporaryTokenRepository $temporaryTokenRepository
     * @param TokenStorageInterface    $tokenStorage
     */
    public function __construct(TemporaryTokenRepository $temporaryTokenRepository, TokenStorageInterface $tokenStorage)
    {
        $this->temporaryTokenRepository = $temporaryTokenRepository;
        $this->tokenStorage             = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => ['expireTemporaryToken', EventPriorities::POST_WRITE]];
    }

    /**
     * @param ViewEvent $event
     *
     * @throws Exception
     */
    public function expireTemporaryToken(ViewEvent $event): void
    {
        /** @var Clients $client */
        $client = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (
            false === $client instanceof Clients
            || false === in_array($method, [Request::METHOD_PUT, Request::METHOD_PATCH], true)
            || (
                null !== $this->tokenStorage->getToken()
                && $this->tokenStorage->getToken()->getUsername() !== $client->getUsername()
            )
        ) {
            return;
        }

        $this->temporaryTokenRepository->expireTemporaryTokens($client);
    }
}
