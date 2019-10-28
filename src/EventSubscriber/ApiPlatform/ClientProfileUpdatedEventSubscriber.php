<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\ApiPlatform;

use ApiPlatform\Core\EventListener\EventPriorities;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Unilend\Entity\Clients;
use Unilend\Repository\TemporaryTokenRepository;

class ClientProfileUpdatedEventSubscriber implements EventSubscriberInterface
{
    /** @var TemporaryTokenRepository */
    private $temporaryTokenRepository;

    /**
     * @param TemporaryTokenRepository $temporaryTokenRepository
     */
    public function __construct(TemporaryTokenRepository $temporaryTokenRepository)
    {
        $this->temporaryTokenRepository = $temporaryTokenRepository;
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
        $client = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (
            false === $client instanceof Clients
            || false === in_array($method, [Request::METHOD_PUT, Request::METHOD_PATCH], true)
        ) {
            return;
        }

        $this->temporaryTokenRepository->expireTemporaryTokens($client);
    }
}
