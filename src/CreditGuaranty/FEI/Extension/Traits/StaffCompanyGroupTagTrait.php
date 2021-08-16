<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Extension\Traits;

use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;

trait StaffCompanyGroupTagTrait
{
    private StaffPermissionManager $staffPermissionManager;

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
