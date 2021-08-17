<?php

declare(strict_types=1);

namespace KLS\Core\EventSubscriber\Jwt;

use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;

interface PermissionProviderInterface
{
    public function getProductName(): string;

    public function getServiceName(): string;

    public function getPermissions(?User $user = null, ?Staff $staff = null): int;

    public function getGrantPermissions(?User $user = null, ?Staff $staff = null): int;
}
