<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Service\ServiceTerms\ServiceTermsManager;

class ServiceTermsSubscriber implements EventSubscriberInterface
{
    /** @var ServiceTermsManager */
    private $serviceTermsManager;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param ServiceTermsManager $serviceTermsManager
     * @param SessionInterface    $session
     * @param RouterInterface     $router
     */
    public function __construct(ServiceTermsManager $serviceTermsManager, SessionInterface $session, RouterInterface $router)
    {
        $this->serviceTermsManager = $serviceTermsManager;
        $this->session             = $session;
        $this->router              = $router;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'checkLegalDoc'];
    }

    /**
     * Check and save in the session if the Service Terms have been accepted.
     *
     * @param RequestEvent $requestEvent
     */
    public function checkLegalDoc(RequestEvent $requestEvent): void
    {
        $this->serviceTermsManager->checkCurrentVersionAccepted();

        $serviceTermsAcceptPath = $this->router->generate('service_terms_accept');

        $unprotectedUris = [
            $this->router->generate('service_terms'),
            $serviceTermsAcceptPath,
            $this->router->generate('logout'),
        ];

        if (
            $this->session->has(ServiceTermsManager::SESSION_KEY_SERVICE_TERMS_ACCEPTED) && !in_array($requestEvent->getRequest()->getRequestUri(), $unprotectedUris)
        ) {
            $requestEvent->setResponse(new RedirectResponse($serviceTermsAcceptPath));
        }
    }
}
