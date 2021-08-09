<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\Entity\ProgramEligibilityConfiguration;

class ProgramEligibilityConfigurationVoter extends AbstractEntityVoter
{
    /**
     * @param ProgramEligibilityConfiguration $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProgramVoter::ATTRIBUTE_EDIT, $subject->getProgramEligibility()->getProgram());
    }
}
