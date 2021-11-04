<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ReportingTemplateVoter extends AbstractEntityVoter
{
    private StaffPermissionManager $staffPermissionManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, StaffPermissionManager $staffPermissionManager)
    {
        parent::__construct($authorizationChecker);
        $this->staffPermissionManager = $staffPermissionManager;
    }

    /**
     * @param ReportingTemplate $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        $staff   = $user->getCurrentStaff();
        $program = $subject->getProgram();

        return $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $program)
            && $program->isPaused()
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_READ_PROGRAM)
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_REPORTING);
    }
}
