<?php
namespace Unilend\Bundle\FrontBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Unilend\Bundle\FrontBundle\Service\SeoManager;

class SonataSeoDataSubscriber implements EventSubscriberInterface
{
    private $seoManager;

    public function __construct(SeoManager $seoManager)
    {
        $this->seoManager = $seoManager;
    }

    /**
     * @inheritdoc
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'setSeoData'
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function setSeoData(GetResponseEvent $event)
    {
        $this->seoManager->setSeoData($event->getRequest());
    }
}