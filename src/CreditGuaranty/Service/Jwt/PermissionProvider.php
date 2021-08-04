<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Service\Jwt;

use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\EventSubscriber\Jwt\PermissionProviderInterface;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\CreditGuaranty\Repository\StaffPermissionRepository;

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
