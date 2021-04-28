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
        $staffPermission = $this->staffPermissionRepository->findOneBy(['staff' => $staff]);
        if ($staffPermission) {
            return $staffPermission->getPermissions()->has($permissions);
        }

        return false;
    }
}
