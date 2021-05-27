<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\FinancingObject;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\CreditGuaranty\Service\StaffPermissionManager;

class FinancingObjectVoter extends AbstractEntityVoter
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

    protected function canCreate(FinancingObject $financingObject, User $user): bool
    {
        $staff       = $user->getCurrentStaff();
        $reservation = $financingObject->getReservation();
        $program     = $reservation->getProgram();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_CREATE_RESERVATION)
            && $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_PARTICIPANT, $program)
        ;
    }

    protected function canView(FinancingObject $financingObject, User $user): bool
    {
        $staff       = $user->getCurrentStaff();
        $reservation = $financingObject->getReservation();
        $program     = $reservation->getProgram();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_READ_RESERVATION)
            && (
                $this->authorizationChecker->isGranted(ReservationRoleVoter::ROLE_MANAGER, $reservation)
                || $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $program)
            )
        ;
    }

    protected function canEdit(FinancingObject $financingObject, User $user): bool
    {
        $staff       = $user->getCurrentStaff();
        $reservation = $financingObject->getReservation();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_EDIT_RESERVATION)
            && $this->authorizationChecker->isGranted(ReservationRoleVoter::ROLE_MANAGER, $reservation)
        ;
    }

    protected function canDelete(FinancingObject $financingObject, User $user): bool
    {
        return $this->canCreate($financingObject, $user);
    }
}
