<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Service\Jwt;

use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Core\EventSubscriber\Jwt\PermissionProviderInterface;

class PermissionProvider implements PermissionProviderInterface
{
    public function getProductName(): string
    {
        return 'syndication';
    }

    public function getServiceName(): string
    {
        return 'agency';
    }

    public function getPermissions(?User $user = null, ?Staff $staff = null): int
    {
        return 1;
    }

    public function getGrantPermissions(?User $user = null, ?Staff $staff = null): int
    {
        return 0;
    }
}
