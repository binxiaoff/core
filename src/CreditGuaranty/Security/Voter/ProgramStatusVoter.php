<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\Entity\ProgramStatus;

class ProgramStatusVoter extends AbstractEntityVoter
{
    /**
     * @param ProgramStatus $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProgramVoter::ATTRIBUTE_EDIT, $subject->getProgram());
    }
}
