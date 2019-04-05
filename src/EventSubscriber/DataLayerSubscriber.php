<?php
namespace Unilend\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Unilend\Service\Front\DataLayerCollector;

class DataLayerSubscriber implements EventSubscriberInterface
{
    /** @var DataLayerCollector */
    private $dataLayerCollector;

    public function __construct(DataLayerCollector $dataLayerCollector)
    {
        $this->dataLayerCollector = $dataLayerCollector;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'setDataLayer'
        ];
    }

    public function setDataLayer()
    {
        $this->dataLayerCollector->collect();
    }
}
