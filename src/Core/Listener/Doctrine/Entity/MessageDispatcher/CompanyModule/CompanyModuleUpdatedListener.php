<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\CompanyModule;

use KLS\Core\Entity\CompanyModule;
use KLS\Core\Message\CompanyModule\CompanyModuleUpdated;
use Symfony\Component\Messenger\MessageBusInterface;

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
