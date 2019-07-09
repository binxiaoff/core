<?php

namespace Unilend\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Unilend\Service\ServiceTerms\ServiceTermsManager;

class ServiceTermsSubscriber implements EventSubscriberInterface
{
    /** @var ServiceTermsManager */
    private $serviceTermsManager;

    /**
     * @param ServiceTermsManager $serviceTermsManager
     */
    public function __construct(ServiceTermsManager $serviceTermsManager)
    {
        $this->serviceTermsManager = $serviceTermsManager;
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
     */
    public function checkLegalDoc(): void
    {
        $this->serviceTermsManager->checkCurrentVersionAccepted();
    }
}
