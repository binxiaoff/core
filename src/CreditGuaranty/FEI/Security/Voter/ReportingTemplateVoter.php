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

    protected function canCreate(ReportingTemplate $reportingTemplate, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $reportingTemplate->getProgram())
            && $this->staffPermissionManager->hasPermissions($user->getCurrentStaff(), StaffPermission::PERMISSION_CREATE_PROGRAM);
    }

    protected function canView(ReportingTemplate $reportingTemplate, User $user): bool
    {
        return $this->canCreate($reportingTemplate, $user);
    }

    protected function canEdit(ReportingTemplate $reportingTemplate, User $user): bool
    {
        return $this->canCreate($reportingTemplate, $user);
    }

    protected function canDelete(ReportingTemplate $reportingTemplate, User $user): bool
    {
        return $this->canCreate($reportingTemplate, $user);
    }
}
