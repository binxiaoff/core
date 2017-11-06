<?php

namespace Unilend\Bundle\FrontBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        // We redirect only the request in case of NotFoundHttpException, as how it has worked in the former version.
        // We cannot always redirect the request, because, in the "redirections" table we have circular references
        // and we have the route defined in the controllers as the from_slug (in these case, we must display the page
        // once it exists)
        return [
            KernelEvents::EXCEPTION => ['handleRedirection', 100]

        ];
    }

    public function handleRedirection(GetResponseForExceptionEvent $event)
    {
        if ($event->getException() instanceof NotFoundHttpException) {
            $response = $this->redirectionHandler->handle($event->getRequest());

            if ($event->isMasterRequest() && $response instanceof RedirectResponse) {
                $event->setResponse($response);
            }
        }
    }
}
