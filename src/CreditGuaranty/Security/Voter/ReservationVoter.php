<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\CreditGuaranty\Service\StaffPermissionManager;

class ReservationVoter extends AbstractEntityVoter
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

    protected function canCreate(Reservation $reservation, User $user): bool
    {
        $staff   = $user->getCurrentStaff();
        $program = $reservation->getProgram();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_CREATE_RESERVATION)
            && $program->hasParticipant($staff->getCompany())
            && $this->staffPermissionManager->checkCompanyGroupTag($program, $staff)
        ;
    }

    protected function canView(Reservation $reservation, User $user): bool
    {
        $staff   = $user->getCurrentStaff();
        $program = $reservation->getProgram();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_READ_RESERVATION)
            && ($reservation->getManagingCompany() === $staff->getCompany() || $program->getManagingCompany() === $staff->getCompany())
            && $this->staffPermissionManager->checkCompanyGroupTag($program, $staff)
        ;
    }

    protected function canEdit(Reservation $reservation, User $user): bool
    {
        $staff   = $user->getCurrentStaff();
        $program = $reservation->getProgram();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_EDIT_RESERVATION)
            && $program->hasParticipant($staff->getCompany())
            && $reservation->getManagingCompany() === $staff->getCompany()
            && $this->staffPermissionManager->checkCompanyGroupTag($program, $staff)
        ;
    }

    protected function canDelete(Reservation $reservation, User $user): bool
    {
        return $this->canCreate($reservation, $user);
    }
}
