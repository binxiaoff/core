<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\StaffStatus;
use Unilend\Core\Entity\User;

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
