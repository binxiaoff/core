<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Extension\Traits;

use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\Entity\StaffPermission;
use KLS\CreditGuaranty\Service\StaffPermissionManager;

/**
 * The conditions in this trait should be the same as those in KLS\CreditGuaranty\Security\Voter\ProgramRoleVoter.
 */
trait ProgramPermissionTrait
{
    private StaffPermissionManager $staffPermissionManager;

    private function applyProgramManagerFilter(?Staff $staff, QueryBuilder $queryBuilder, string $programAlias): void
    {
        $this->addCommonFilter($staff, $queryBuilder, $programAlias);

        $queryBuilder
            ->andWhere("{$programAlias}.managingCompany = :staffCompany")
            ->setParameter('staffCompany', $staff->getCompany())
        ;
    }

    private function applyProgramManagerOrParticipantFilter(?Staff $staff, QueryBuilder $queryBuilder, string $programAlias, string $participationAlias): void
    {
        $this->addCommonFilter($staff, $queryBuilder, $programAlias);

        $queryBuilder
            ->andWhere($queryBuilder->expr()->orX(
                "{$programAlias}.managingCompany = :staffCompany",
                "{$participationAlias}.participant = :staffCompany"
            ))
            ->setParameter('staffCompany', $staff->getCompany())
        ;
    }

    private function addCommonFilter(?Staff $staff, QueryBuilder $queryBuilder, string $programAlias): void
    {
        if (null === $staff || false === $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_READ_PROGRAM)) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        if (false === $staff->isAdmin()) {
            $queryBuilder
                ->andWhere("{$programAlias}.companyGroupTag in (:companyGroupTags)")
                ->setParameter('companyGroupTags', $staff->getCompanyGroupTags())
            ;
        }
    }
}
