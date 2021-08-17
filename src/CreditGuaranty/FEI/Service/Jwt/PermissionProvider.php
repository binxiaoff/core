<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\Jwt;

use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Core\EventSubscriber\Jwt\PermissionProviderInterface;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Repository\StaffPermissionRepository;

class PermissionProvider implements PermissionProviderInterface
{
    private const PRODUCT_NAME = 'credit_guaranty';
    private const SERVICE_NAME = 'fei';

    private StaffPermissionRepository $staffPermissionRepository;

    public function __construct(StaffPermissionRepository $staffPermissionRepository)
    {
        $this->staffPermissionRepository = $staffPermissionRepository;
    }

    public function getProductName(): string
    {
        return static::PRODUCT_NAME;
    }

    public function getServiceName(): string
    {
        return static::SERVICE_NAME;
    }

    public function getPermissions(?User $user = null, ?Staff $staff = null): int
    {
        $staffPermission = $this->getStaffPermission($staff);

        return $staffPermission ? $staffPermission->getPermissions()->get() : 0;
    }

    public function getGrantPermissions(?User $user = null, ?Staff $staff = null): int
    {
        $staffPermission = $this->getStaffPermission($staff);

        return $staffPermission ? $staffPermission->getGrantPermissions()->get() : 0;
    }

    private function getStaffPermission(?Staff $staff): ?StaffPermission
    {
        if (false === ($staff instanceof Staff)) {
            return null;
        }

        return $this->staffPermissionRepository->findOneBy(['staff' => $staff]);
    }
}
