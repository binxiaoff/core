<?php

namespace Unilend\Bundle\FrontBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;

class ClientAuthorizationAccessSubscriber implements EventSubscriberInterface
{
    /** @var ClientManager $clientManager */
    private $clientManager;

    /**
     * ClientAuthorizationAccessSubscriber constructor.
     * @param ClientManager $clientManager
     */
    public function __construct(ClientManager $clientManager)
    {
        $this->clientManager = $clientManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'checkClientSubscriptionStep'
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function checkClientSubscriptionStep(GetResponseEvent $event)
    {
        $response = $this->clientManager->checkProgressAndRedirect($event->getRequest());

        if ($event->isMasterRequest() && $response instanceof RedirectResponse) {
            $event->setResponse($response->setStatusCode(RedirectResponse::HTTP_OK));
        }
    }
}
