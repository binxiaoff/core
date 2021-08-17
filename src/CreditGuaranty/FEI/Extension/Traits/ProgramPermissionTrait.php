<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Extension\Traits;

use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;

/**
 * The conditions in this trait should be the same as those in KLS\CreditGuaranty\FEI\Security\Voter\ProgramRoleVoter.
 */
trait ProgramPermissionTrait
{
    use StaffCompanyGroupTagTrait;

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
}
