<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\User;
use Unilend\Core\Entity\UserStatus;

class UserStatusVoter extends AbstractEntityVoter
{
    protected function canView(UserStatus $userStatus, User $user): bool
    {
        return $user === $userStatus->getUser();
    }
}
