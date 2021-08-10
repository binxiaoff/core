<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Service\Jwt;

use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Core\EventSubscriber\Jwt\PermissionProviderInterface;
use KLS\CreditGuaranty\Entity\StaffPermission;
use KLS\CreditGuaranty\Repository\StaffPermissionRepository;

class PermissionProvider implements PermissionProviderInterface
{
    private const NAME = 'credit_guaranty';

    private StaffPermissionRepository $staffPermissionRepository;

    public function __construct(StaffPermissionRepository $staffPermissionRepository)
    {
        $this->staffPermissionRepository = $staffPermissionRepository;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function provide(User $user, ?Staff $staff = null): array
    {
        $permissions = [
            'permissions'       => 0,
            'grant_permissions' => 0,
        ];

        if (false === ($staff instanceof Staff)) {
            return $permissions;
        }

        $staffPermission = $this->staffPermissionRepository->findOneBy(['staff' => $staff]);

        if (false === ($staffPermission instanceof StaffPermission)) {
            return $permissions;
        }

        $permissions['permissions']       = $staffPermission->getPermissions()->get();
        $permissions['grant_permissions'] = $staffPermission->getGrantPermissions()->get();

        return $permissions;
    }
}
