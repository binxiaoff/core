<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\{User, UserStatus};

class UserStatusVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';

    /**
     * @param UserStatus $userStatus
     * @param User       $user
     *
     * @return bool
     */
    protected function canView(UserStatus $userStatus, User $user): bool
    {
        return $user === $userStatus->getUser();
    }
}
