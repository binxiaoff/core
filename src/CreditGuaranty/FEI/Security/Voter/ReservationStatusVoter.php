<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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
