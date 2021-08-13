<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\ProgramBorrowerTypeAllocation;

class ProgramBorrowerTypeAllocationVoter extends AbstractEntityVoter
{
    /**
     * @param ProgramBorrowerTypeAllocation $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProgramVoter::ATTRIBUTE_EDIT, $subject->getProgram());
    }
}
