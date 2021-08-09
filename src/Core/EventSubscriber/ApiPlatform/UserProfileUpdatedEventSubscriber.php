<?php

declare(strict_types=1);

namespace KLS\Core\EventSubscriber\ApiPlatform;

use ApiPlatform\Core\EventListener\EventPriorities;
use Exception;
use KLS\Core\Entity\User;
use KLS\Core\Repository\TemporaryTokenRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserProfileUpdatedEventSubscriber implements EventSubscriberInterface
{
    /** @var TemporaryTokenRepository */
    private $temporaryTokenRepository;
    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(TemporaryTokenRepository $temporaryTokenRepository, TokenStorageInterface $tokenStorage)
    {
        $this->temporaryTokenRepository = $temporaryTokenRepository;
        $this->tokenStorage             = $tokenStorage;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => ['expireTemporaryToken', EventPriorities::POST_WRITE]];
    }

    /**
     * @throws Exception
     */
    public function expireTemporaryToken(ViewEvent $event): void
    {
        /** @var User $user */
        $user   = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (
            false === $user instanceof User
            || false === \in_array($method, [Request::METHOD_PUT, Request::METHOD_PATCH], true)
            || (
                null !== $this->tokenStorage->getToken()
                && $this->tokenStorage->getToken()->getUsername() !== $user->getUsername()
            )
        ) {
            return;
        }

        $this->temporaryTokenRepository->expireTemporaryTokens($user);
    }
}
