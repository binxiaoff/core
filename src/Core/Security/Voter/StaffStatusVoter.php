<?php

declare(strict_types=1);

namespace KLS\Core\Security\Voter;

use KLS\Core\Entity\StaffStatus;
use KLS\Core\Entity\User;

class StaffStatusVoter extends AbstractEntityVoter
{
    /**
     * @param StaffStatus $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(StaffVoter::ATTRIBUTE_EDIT, $subject->getStaff());
    }
}
