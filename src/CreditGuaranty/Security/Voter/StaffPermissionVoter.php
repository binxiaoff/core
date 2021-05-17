<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\CreditGuaranty\Service\StaffPermissionManager;

class StaffPermissionVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';

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
