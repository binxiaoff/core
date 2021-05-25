<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\ReservationStatus;

class ReservationStatusVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param ReservationStatus $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_MANAGER, $subject->getReservation()->getProgram())
            || $this->authorizationChecker->isGranted(ProgramRoleVoter::ROLE_PARTICIPANT, $subject->getReservation()->getProgram());
    }
}
