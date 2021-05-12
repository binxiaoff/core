<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\Staff;
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
            && $this->checkCompanyGroupTag($program, $staff);
    }

    protected function canView(Program $program, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_READ_PROGRAM)
            && (
                $staff->getCompany() === $program->getManagingCompany()
                || $program->hasParticipant($staff->getCompany())
            )
            && $this->checkCompanyGroupTag($program, $staff)
        ;
    }

    protected function canEdit(Program $program, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $staff->getCompany() === $program->getManagingCompany()
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_EDIT_PROGRAM)
            && $this->checkCompanyGroupTag($program, $staff)
            && ($program->isInDraft() || $program->isPaused());
    }

    protected function canDelete(Program $program, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff && $this->canEdit($program, $staff->getUser());
    }

    private function checkCompanyGroupTag(Program $program, Staff $staff): bool
    {
        return $staff->isAdmin() || in_array($program->getCompanyGroupTag(), $staff->getCompanyGroupTags(), true);
    }
}
