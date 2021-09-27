<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplateField;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ReportingTemplateFieldVoter extends AbstractEntityVoter
{
    private StaffPermissionManager $staffPermissionManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, StaffPermissionManager $staffPermissionManager)
    {
        parent::__construct($authorizationChecker);
        $this->staffPermissionManager = $staffPermissionManager;
    }

    /**
     * @param ReportingTemplateField $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $subject->getReportingTemplate()->getProgram())
            && $this->staffPermissionManager->hasPermissions($user->getCurrentStaff(), StaffPermission::PERMISSION_READ_PROGRAM)
            && $this->staffPermissionManager->hasPermissions($user->getCurrentStaff(), StaffPermission::PERMISSION_REPORTING);
    }
}
