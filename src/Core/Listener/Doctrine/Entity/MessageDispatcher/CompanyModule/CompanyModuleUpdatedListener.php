<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\CompanyModule;

use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Core\Entity\CompanyModule;
use Unilend\Core\Message\CompanyModule\CompanyModuleUpdated;

class CompanyModuleUpdatedListener
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public function postUpdate(CompanyModule $companyModule)
    {
        $this->bus->dispatch(new CompanyModuleUpdated($companyModule));
    }
}
