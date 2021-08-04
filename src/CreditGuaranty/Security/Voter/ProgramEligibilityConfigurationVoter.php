<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;

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
