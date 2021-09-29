<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\Company;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use KLS\Core\Entity\Company;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Core\Message\Company\CompanyStatusUpdated;

class CompanyStatusUpdatedListener
{
    use MessageDispatcherTrait;

    public function preUpdate(Company $company, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('currentStatus')) {
            $this->messageBus->dispatch(
                new CompanyStatusUpdated($company, $args->getOldValue('currentStatus'), $args->getNewValue('currentStatus'))
            );
        }
    }
}
