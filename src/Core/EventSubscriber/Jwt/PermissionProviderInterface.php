<?php

declare(strict_types=1);

namespace KLS\Core\EventSubscriber\Jwt;

use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;

interface PermissionProviderInterface
{
    public function getName(): string;

    public function provide(User $user, ?Staff $staff = null): array;
}
