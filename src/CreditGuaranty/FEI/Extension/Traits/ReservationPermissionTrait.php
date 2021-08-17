<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Extension\Traits;

use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;

/**
 * The conditions in this trait should be the same as those in KLS\CreditGuaranty\FEI\Security\Voter\ReservationRoleVoter.
 */
trait ReservationPermissionTrait
{
    use StaffCompanyGroupTagTrait;

    private StaffPermissionManager $staffPermissionManager;

    private function applyReservationManagerOrParticipantFilter(?Staff $staff, QueryBuilder $queryBuilder, string $reservationAlias, string $programAlias): void
    {
        $this->addCommonFilter($staff, $queryBuilder, $programAlias);

        $queryBuilder
            ->andWhere($queryBuilder->expr()->orX(
                "{$reservationAlias}.managingCompany = :staffCompany",
                "{$programAlias}.managingCompany = :staffCompany"
            ))
            ->setParameter('staffCompany', $staff->getCompany())
        ;
    }
}
