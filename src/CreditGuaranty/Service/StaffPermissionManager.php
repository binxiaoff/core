<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Service;

use Unilend\Core\Entity\Staff;
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
        //todo: use the new getInheritedRightStaff() method
        $staffToCheck = $staff->isManager() ? $staff->getManagedStaff() : [$staff];

        foreach ($staffToCheck as $managedStaff) {
            $staffPermission = $this->staffPermissionRepository->findOneBy(['staff' => $managedStaff]);
            if ($staffPermission && $staffPermission->getPermissions()->has($permissions)) {
                return true;
            }
        }

        return false;
    }
}
