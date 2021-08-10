<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Security\Voter;

use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\Entity\ProgramEligibility;

class ProgramEligibilityVoter extends AbstractEntityVoter
{
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
}
