<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityCondition;

class ProgramEligibilityConditionVoter extends AbstractEntityVoter
{
    /**
     * @param ProgramEligibilityCondition $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProgramVoter::ATTRIBUTE_EDIT, $subject->getProgramEligibilityConfiguration()->getProgramEligibility()->getProgram());
    }
}
