<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\Clients;
use Unilend\Entity\StaffStatus;

class StaffStatusVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param StaffStatus $staffStatus
     * @param Clients     $user
     *
     * @return bool
     */
    protected function isGrantedAll($staffStatus, Clients $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        return $submitterStaff && $submitterStaff->isAdmin() && $staffStatus->getStaff()->getCompany() === $submitterStaff->getCompany();
    }
}
