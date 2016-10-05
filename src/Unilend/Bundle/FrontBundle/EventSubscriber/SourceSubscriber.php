<?php
namespace Unilend\Bundle\FrontBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Unilend\Bundle\FrontBundle\Service\SourceManager;

class SourceSubscriber implements EventSubscriberInterface
{
    /** @var SourceManager */
    private $sourceManager;

    public function __construct(SourceManager $sourceManager)
    {
        $this->sourceManager = $sourceManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'setSource'
        ];
    }

    public function setSource()
    {
        $this->sourceManager->handle();
    }
}
