<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\CreditGuaranty\Service\StaffPermissionManager;

class ProgramVoter extends AbstractEntityVoter
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

    protected function canCreate(Program $program, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_CREATE_PROGRAM)
            && $this->staffPermissionManager->checkCompanyGroupTag($program, $staff);
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
}
