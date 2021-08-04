<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\ReservationStatus;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\CreditGuaranty\Service\StaffPermissionManager;

class ReservationStatusVoter extends AbstractEntityVoter
{
    private StaffPermissionManager $staffPermissionManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, StaffPermissionManager $staffPermissionManager)
    {
        parent::__construct($authorizationChecker);
        $this->staffPermissionManager = $staffPermissionManager;
    }

    /**
     * @param ReservationStatus $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            return false;
        }

        return (
            $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $subject->getReservation()->getProgram())
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_GRANT_EDIT_PROGRAM)
        )
        || (
            $this->authorizationChecker->isGranted(ReservationRoleVoter::ROLE_MANAGER, $subject->getReservation())
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_EDIT_RESERVATION)
        );
    }
}
