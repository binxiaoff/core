<?php

declare(strict_types=1);

namespace KLS\Core\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Entity\UserStatus;

class UserStatusVoter extends AbstractEntityVoter
{
    protected function canView(UserStatus $userStatus, User $user): bool
    {
        return $user === $userStatus->getUser();
    }
}
