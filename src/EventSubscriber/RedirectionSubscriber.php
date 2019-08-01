<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\{Event\ExceptionEvent, Exception\NotFoundHttpException, KernelEvents};
use Unilend\Service\ContentManagementSystem\RedirectionHandler;

class RedirectionSubscriber implements EventSubscriberInterface
{
    /** @var RedirectionHandler */
    private $redirectionHandler;

    /**
     * @param RedirectionHandler $redirectionHandler
     */
    public function __construct(RedirectionHandler $redirectionHandler)
    {
        $this->redirectionHandler = $redirectionHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        // We redirect only the request in case of NotFoundHttpException, as how it has worked in the former version.
        // We cannot always redirect the request, because, in the "redirections" table we have circular references
        // and we have the route defined in the controllers as the from_slug (in these case, we must display the page
        // once it exists)
        return [
            KernelEvents::EXCEPTION => ['handleRedirection', 100],
        ];
    }

    /**
     * @param ExceptionEvent $event
     *
     * @throws InvalidArgumentException
     */
    public function handleRedirection(ExceptionEvent $event)
    {
        if ($event->getException() instanceof NotFoundHttpException) {
            $response = $this->redirectionHandler->handle($event->getRequest());

            if ($event->isMasterRequest() && $response instanceof RedirectResponse) {
                $event->setResponse($response);
            }
        }
    }
}
