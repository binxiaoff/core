<?php

declare(strict_types=1);

namespace KLS\Agency\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use KLS\Core\Entity\CompanyModule;
use KLS\Core\Entity\Staff;

class AgencyModuleActivatedListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em            = $args->getEntityManager();
        $uow           = $em->getUnitOfWork();
        $classMetadata = $em->getClassMetadata(Staff::class);

        foreach ($uow->getScheduledEntityUpdates() as $companyModule) {
            if (
                $companyModule instanceof CompanyModule
                && \array_key_exists(CompanyModule::PROPERTY_ACTIVATED, $uow->getEntityChangeSet($companyModule))
                && CompanyModule::MODULE_AGENCY === $companyModule->getCode()
                && $companyModule->isActivated()
            ) {
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
