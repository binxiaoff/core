<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\User;

class UserVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';
    public const ATTRIBUTE_EDIT = 'edit';

    /**
     * @param User $subject
     * @param User $user
     *
     * @return bool
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $subject->getId() === $user->getId();
    }
}
