<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;

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
