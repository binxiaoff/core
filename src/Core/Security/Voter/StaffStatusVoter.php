<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\StaffStatus;
use Unilend\Core\Entity\User;

class StaffStatusVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param StaffStatus $staffStatus
     * @param User        $user
     *
     * @return bool
     */
    protected function isGrantedAll($staffStatus, User $user): bool
    {
        return $this->authorizationChecker->isGranted(StaffVoter::ATTRIBUTE_EDIT, $staffStatus->getStaff());
    }
}
