<?php

namespace Unilend\Bundle\FrontBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Unilend\Bundle\FrontBundle\Service\RedirectionHandler;

class RedirectionSubscriber implements EventSubscriberInterface
{

    /** @var RedirectionHandler */
    private $redirectionHandler;

    public function __construct(RedirectionHandler $redirectionHandler)
    {
        $this->redirectionHandler = $redirectionHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['handleRedirection', 100]

        ];
    }

    public function handleRedirection(GetResponseEvent $event)
    {
        $response = $this->redirectionHandler->handle($event->getRequest());

        if ($event->isMasterRequest() && $response instanceof RedirectResponse) {
            $event->setResponse($response);
        }
    }
}
