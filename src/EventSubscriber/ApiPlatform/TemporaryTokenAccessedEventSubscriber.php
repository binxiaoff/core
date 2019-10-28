<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\ApiPlatform;

use ApiPlatform\Core\EventListener\EventPriorities;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\{Event\RequestEvent, KernelEvents};
use Unilend\Entity\TemporaryToken;
use Unilend\Repository\TemporaryTokenRepository;

class TemporaryTokenAccessedEventSubscriber implements EventSubscriberInterface
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
    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => ['accessTemporaryToken', EventPriorities::POST_READ]];
    }

    /**
     * @param RequestEvent $event
     *
     * @throws Exception
     */
    public function accessTemporaryToken(RequestEvent $event): void
    {
        /** @var TemporaryToken $temporaryToken */
        $temporaryToken = $event->getRequest()->attributes->get('data');

        if (false === $temporaryToken instanceof TemporaryToken) {
            return;
        }

        $temporaryToken->setAccessed();
        $this->temporaryTokenRepository->save($temporaryToken);
    }
}
