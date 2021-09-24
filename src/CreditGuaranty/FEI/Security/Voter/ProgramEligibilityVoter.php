<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;

class ProgramEligibilityVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_MANAGER = 'manager';

    protected function canCreate(ProgramEligibility $programEligibility): bool
    {
        return $this->authorizationChecker->isGranted(ProgramVoter::ATTRIBUTE_EDIT, $programEligibility->getProgram());
    }

    protected function canView(ProgramEligibility $programEligibility): bool
    {
        return $this->authorizationChecker->isGranted(ProgramVoter::ATTRIBUTE_VIEW, $programEligibility->getProgram());
    }

    protected function canDelete(ProgramEligibility $programEligibility): bool
    {
        return $this->authorizationChecker->isGranted(ProgramVoter::ATTRIBUTE_EDIT, $programEligibility->getProgram());
    }

    protected function canManager(ProgramEligibility $programEligibility): bool
    {
        return $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $programEligibility->getProgram());
    }
}
