<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Service;

use Unilend\Core\Entity\Staff;
use Unilend\Core\Model\Bitmask;
use Unilend\CreditGuaranty\Repository\StaffPermissionRepository;

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
        // Since the "grant permissions" have the same positon as "permissions" that an user can grant, we can check it by using has().
        return $staffPermission && $staffPermission->getGrantPermissions()->has($permissions);
    }
}
