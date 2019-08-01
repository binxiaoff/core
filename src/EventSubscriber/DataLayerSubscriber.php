<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber;

use Symfony\Component\{EventDispatcher\EventSubscriberInterface, HttpKernel\KernelEvents};
use Unilend\Service\GoogleTagManager\DataLayerCollector;

class DataLayerSubscriber implements EventSubscriberInterface
{
    /** @var DataLayerCollector */
    private $dataLayerCollector;

    /**
     * @param DataLayerCollector $dataLayerCollector
     */
    public function __construct(DataLayerCollector $dataLayerCollector)
    {
        $this->dataLayerCollector = $dataLayerCollector;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'setDataLayer',
        ];
    }

    /**
     * Collect the data from session and set them to Google Tag Manager Data Layer.
     */
    public function setDataLayer()
    {
        $this->dataLayerCollector->collect();
    }
}
