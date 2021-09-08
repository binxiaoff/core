<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\Company;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyStatus;
use KLS\Core\Service\Notifier\CompanyStatus\CompanyStatusNotifier;

class CompanyStatusUpdatedListener
{
    private CompanyStatusNotifier $companyStatusNotifier;

    public function __construct(CompanyStatusNotifier $companyStatusNotifier)
    {
        $this->companyStatusNotifier = $companyStatusNotifier;
    }

    public function preUpdate(Company $company, PreUpdateEventArgs $args): void
    {
        $hasChangedValue = $args->hasChangedField('currentStatus');
        $oldValue        = $args->getOldValue('currentStatus');
        $newValue        = $args->getNewValue('currentStatus');

        if (
            $hasChangedValue
            && CompanyStatus::STATUS_PROSPECT === $oldValue->getStatus()
            && CompanyStatus::STATUS_SIGNED === $newValue->getStatus()
        ) {
            $this->companyStatusNotifier->notify($company);
        }
    }
}
