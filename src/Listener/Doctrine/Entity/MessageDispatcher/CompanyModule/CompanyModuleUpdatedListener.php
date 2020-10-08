<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\CompanyModule;

use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Entity\CompanyModule;
use Unilend\Message\CompanyModule\CompanyModuleUpdated;

class CompanyModuleUpdatedListener
{
    private MessageBusInterface $bus;

    /**
     * @param MessageBusInterface $bus
     */
    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    /**
     * @param CompanyModule $companyModule
     */
    public function postUpdate(CompanyModule $companyModule)
    {
        $this->bus->dispatch(new CompanyModuleUpdated($companyModule));
    }
}
