<?php

declare(strict_types=1);

namespace KLS\Core\Security\Voter;

use KLS\Core\Entity\User;

class UserVoter extends AbstractEntityVoter
{
    /**
     * @param User $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $subject->getId() === $user->getId();
    }
}
