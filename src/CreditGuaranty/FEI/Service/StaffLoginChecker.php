<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service;

use KLS\Core\Entity\Staff;
use KLS\Core\Service\Staff\StaffLoginInterface;
use KLS\CreditGuaranty\FEI\Repository\StaffPermissionRepository;

/**
 * @internal should only be used in KLS\Core\Service\Staff\StaffLoginChecker
 */
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

        return $staffPermission && 0 !== $staffPermission->getPermissions()->get();
    }
}
