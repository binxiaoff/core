<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;

class ProgramChoiceOptionVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';

    /**
     * @param ProgramChoiceOption $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProgramVoter::ATTRIBUTE_EDIT, $subject->getProgram());
    }
}
