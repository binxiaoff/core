<?php
namespace Unilend\Bundle\FrontBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;

class TermsOfServiceSubscriber implements EventSubscriberInterface
{
    /** @var ClientManager */
    private $clientManager;

    public function __construct(ClientManager $clientManager)
    {
        $this->clientManager = $clientManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'checkLegalDoc'
        ];
    }

    public function checkLegalDoc()
    {
        $this->clientManager->checkLastTOSAccepted();
    }
}