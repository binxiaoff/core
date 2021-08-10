<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Security\Voter;

use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\Entity\StaffPermission;
use KLS\CreditGuaranty\Service\StaffPermissionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class StaffPermissionVoter extends AbstractEntityVoter
{
    private StaffPermissionManager $staffPermissionManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, StaffPermissionManager $staffPermissionManager)
    {
        parent::__construct($authorizationChecker);
        $this->staffPermissionManager = $staffPermissionManager;
    }

    protected function canCreate(StaffPermission $staffPermission, User $user): bool
    {
        $staff = $user->getCurrentStaff();
        if (false === $staff instanceof Staff) {
            return false;
        }

        return $staff->getCompany() === $staffPermission->getStaff()->getCompany() && $this->staffPermissionManager->canGrant($staff, $staffPermission->getPermissions());
    }

    protected function canView(StaffPermission $staffPermission, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            return false;
        }

        return $staff->getCompany() === $staffPermission->getStaff()->getCompany();
    }

    protected function canEdit(StaffPermission $staffPermission, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            return false;
        }

        // To simplify the code, we only verify the permissions already set. As a company can only be a participant or a manager (not both), the code works.
        return $staff->getCompany() === $staffPermission->getStaff()->getCompany() && $this->staffPermissionManager->canGrant($staff, $staffPermission->getPermissions());
    }

    protected function canDelete(StaffPermission $staffPermission, User $user): bool
    {
        return $this->canCreate($staffPermission, $user);
    }
}
