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
        $hasChangedValue = $args->hasChangedField('currentStatus');
        $oldValue        = $args->getOldValue('currentStatus');
        $newValue        = $args->getNewValue('currentStatus');

        if ($hasChangedValue) {
            $this->messageBus->dispatch(
                new CompanyStatusUpdated($company, $oldValue, $newValue)
            );
        }
    }
}
