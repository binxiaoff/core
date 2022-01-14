<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service;

use KLS\Core\Entity\Staff;
use KLS\Core\Model\Bitmask;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Repository\StaffPermissionRepository;

class StaffPermissionManager
{
    private StaffPermissionRepository $staffPermissionRepository;

    public function __construct(StaffPermissionRepository $staffPermissionRepository)
    {
        $this->staffPermissionRepository = $staffPermissionRepository;
    }

    public function hasPermissions(Staff $staff, int $permissions): bool
    {
        $staffToCheck = $staff->getManagedStaff();

        foreach ($staffToCheck as $managedStaff) {
            $staffPermission = $this->staffPermissionRepository->findOneBy(['staff' => $managedStaff]);
            if ($staffPermission && $staffPermission->getPermissions()->has($permissions)) {
                return true;
            }
        }

        return false;
    }

    public function canGrant(Staff $staff, Bitmask $permissions): bool
    {
        $staffPermission = $this->staffPermissionRepository->findOneBy(['staff' => $staff]);

        // Since the "grant permissions" have the same positon as "permissions" that a user can grant,
        // we can check it by using has()
        return $staffPermission && $staffPermission->getGrantPermissions()->has($permissions);
    }

    public function checkCompanyGroupTag(Program $program, Staff $staff): bool
    {
        return $staff->isAdmin() || \in_array($program->getCompanyGroupTag(), $staff->getCompanyGroupTags(), true);
    }
}
