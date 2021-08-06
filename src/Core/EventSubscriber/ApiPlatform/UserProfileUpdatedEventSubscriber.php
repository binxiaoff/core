<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber\ApiPlatform;

use ApiPlatform\Core\EventListener\EventPriorities;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\TemporaryTokenRepository;

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

    /**
     * {@inheritdoc}
     */
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
