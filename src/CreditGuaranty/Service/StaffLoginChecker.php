<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Service;

use KLS\Core\Entity\Staff;
use KLS\Core\Service\Staff\StaffLoginInterface;
use KLS\CreditGuaranty\Entity\StaffPermission;
use KLS\CreditGuaranty\Repository\StaffPermissionRepository;

class StaffLoginChecker implements StaffLoginInterface
{
    private StaffPermissionRepository $staffPermissionRepository;

    public function __construct(StaffPermissionRepository $staffPermissionRepository)
    {
        $this->staffPermissionRepository = $staffPermissionRepository;
    }

    public function isGrantedLogin(Staff $staff): bool
    {
        $staffPermission = $this->staffPermissionRepository->findOneBy(['staff' => $staff]);

        if (false === ($staffPermission instanceof StaffPermission) || 0 === $staffPermission->getPermissions()->get()) {
            return false;
        }

        return true;
    }
}
