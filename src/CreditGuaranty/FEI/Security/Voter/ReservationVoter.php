<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ReservationVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CHECK_ELIGIBILITY = 'check_eligibility';

    private StaffPermissionManager $staffPermissionManager;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        StaffPermissionManager $staffPermissionManager
    ) {
        parent::__construct($authorizationChecker);
        $this->staffPermissionManager = $staffPermissionManager;
    }

    protected function canCreate(Reservation $reservation, User $user): bool
    {
        $staff   = $user->getCurrentStaff();
        $program = $reservation->getProgram();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_CREATE_RESERVATION)
            && $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_PARTICIPANT, $program)
        ;
    }

    protected function canView(Reservation $reservation, User $user): bool
    {
        $staff   = $user->getCurrentStaff();
        $program = $reservation->getProgram();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_READ_RESERVATION)
            && (
                $this->authorizationChecker->isGranted(ReservationRoleVoter::ROLE_MANAGER, $reservation)
                || (
                    $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $program)
                    && false === $reservation->isInDraft()
                )
            )
        ;
    }

    protected function canEdit(Reservation $reservation, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_EDIT_RESERVATION)
            && $this->authorizationChecker->isGranted(ReservationRoleVoter::ROLE_MANAGER, $reservation)
            && ($reservation->isInDraft() || $reservation->isAcceptedByManagingCompany())
        ;
    }

    protected function canCheckEligibility(Reservation $reservation, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_EDIT_RESERVATION)
            && $this->authorizationChecker->isGranted(ReservationRoleVoter::ROLE_MANAGER, $reservation)
        ;
    }

    protected function canDelete(Reservation $reservation, User $user): bool
    {
        return $this->canCreate($reservation, $user) && $reservation->getManagingCompany() === $user->getCompany();
    }
}
