<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Service;

use Unilend\Core\Entity\Staff;
use Unilend\Core\Service\Staff\StaffLoginInterface;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\CreditGuaranty\Repository\StaffPermissionRepository;

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

        if (false === ($staffPermission instanceof StaffPermission)) {
            return false;
        }

        return true;
    }
}
