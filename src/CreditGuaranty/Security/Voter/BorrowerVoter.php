<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\Borrower;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\CreditGuaranty\Service\StaffPermissionManager;

class BorrowerVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';

    private StaffPermissionManager $staffPermissionManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, StaffPermissionManager $staffPermissionManager)
    {
        parent::__construct($authorizationChecker);
        $this->staffPermissionManager = $staffPermissionManager;
    }

    protected function canView(Borrower $borrower, User $user): bool
    {
        $staff       = $user->getCurrentStaff();
        $reservation = $borrower->getReservation();
        $program     = $reservation->getProgram();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_READ_RESERVATION)
            && (
                $this->authorizationChecker->isGranted(ReservationRoleVoter::ROLE_MANAGER, $reservation)
                || $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $program)
            )
        ;
    }

    protected function canEdit(Borrower $borrower, User $user): bool
    {
        $staff       = $user->getCurrentStaff();
        $reservation = $borrower->getReservation();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_EDIT_RESERVATION)
            && $this->authorizationChecker->isGranted(ReservationRoleVoter::ROLE_MANAGER, $reservation)
        ;
    }

    protected function canDelete(Borrower $borrower, User $user): bool
    {
        $staff       = $user->getCurrentStaff();
        $reservation = $borrower->getReservation();
        $program     = $reservation->getProgram();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_CREATE_RESERVATION)
            && $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_PARTICIPANT, $program)
        ;
    }
}
