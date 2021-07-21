<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber\Jwt;

use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;

interface PermissionProviderInterface
{
    public function provide(User $user, ?Staff $staff = null): array;
}
