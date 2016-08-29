<?php
namespace Unilend\Bundle\FrontBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Unilend\Bundle\FrontBundle\Service\DataLayerCollector;

class DataLayerSubscriber implements EventSubscriberInterface
{
    const SESSION_KEY_LENDER_CLIENT_ID   = 'datalayer_lender_client_id';
    const SESSION_KEY_BORROWER_CLIENT_ID = 'datalayer_borrower_client_id';
    const SESSION_KEY_CLIENT_EMAIL       = 'datalayer_client_email';

    /**
     * @var DataLayerCollector
     */
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
