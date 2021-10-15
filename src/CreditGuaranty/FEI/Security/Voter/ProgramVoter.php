<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ProgramVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_DATAROOM  = 'dataroom';
    public const ATTRIBUTE_REPORTING = 'reporting';

    private StaffPermissionManager $staffPermissionManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, StaffPermissionManager $staffPermissionManager)
    {
        parent::__construct($authorizationChecker);
        $this->staffPermissionManager = $staffPermissionManager;
    }

    protected function canCreate(Program $program, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $program)
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_CREATE_PROGRAM)
        ;
    }

    protected function canView(Program $program, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_READ_PROGRAM)
            && (
                $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $program)
                || $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_PARTICIPANT, $program)
            )
        ;
    }

    protected function canEdit(Program $program, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $program)
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_EDIT_PROGRAM)
            && ($program->isInDraft() || $program->isPaused());
    }

    protected function canDelete(Program $program, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff && $this->canEdit($program, $staff->getUser());
    }

    protected function canDataroom(Program $program, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $program)
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_EDIT_PROGRAM)
            && false === $program->isArchived();
    }

    protected function canReporting(Program $program, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $program)
            && $program->isPaused()
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_READ_PROGRAM)
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_REPORTING);
    }
}
