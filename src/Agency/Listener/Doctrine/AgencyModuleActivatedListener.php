<?php

declare(strict_types=1);

namespace Unilend\Agency\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Unilend\Core\Entity\CompanyModule;
use Unilend\Core\Entity\Staff;

class AgencyModuleActivatedListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $companyModule) {
            if (
                $companyModule instanceof CompanyModule
                && array_key_exists(CompanyModule::PROPERTY_ACTIVATED, $uow->getEntityChangeSet($companyModule))
                && CompanyModule::MODULE_AGENCY === $companyModule->getCode()
                && $companyModule->isActivated()
            ) {
                // put it in foreach, because it will pass here only once in the most case and it's hard to meet the conditions of the above if.
                $classMetadata = $em->getClassMetadata(Staff::class);

                foreach ($companyModule->getCompany()->getRootTeam()->getStaff() as $staff) {
                    if (false === $staff->isManager()) {
                        continue;
                    }

                    $staff->setAgencyProjectCreationPermission(true);
                    $uow->computeChangeSet($classMetadata, $staff);
                }
            }
        }
    }
}
