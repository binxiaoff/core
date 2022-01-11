<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\Reporting;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ReportingVoter extends AbstractEntityVoter
{
    private StaffPermissionManager $staffPermissionManager;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        StaffPermissionManager $staffPermissionManager
    ) {
        parent::__construct($authorizationChecker);
        $this->staffPermissionManager = $staffPermissionManager;
    }

    /**
     * @param Reporting $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        $staff   = $user->getCurrentStaff();
        $program = $subject->getReportingTemplate()->getProgram();

        return $this->authorizationChecker->isGranted(ProgramVoter::ATTRIBUTE_REPORTING, $program)
            && $program->isPaused()
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_READ_PROGRAM)
            && $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_REPORTING);
    }
}
