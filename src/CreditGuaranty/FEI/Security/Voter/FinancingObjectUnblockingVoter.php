<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\FinancingObjectUnblocking;

class FinancingObjectUnblockingVoter extends AbstractEntityVoter
{
    protected function canCreate(FinancingObjectUnblocking $financingObjectUnblocking): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $financingObjectUnblocking->getReservation());
    }

    protected function canView(FinancingObjectUnblocking $financingObjectUnblocking): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_VIEW, $financingObjectUnblocking->getReservation());
    }

    protected function canEdit(FinancingObjectUnblocking $financingObjectUnblocking): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $financingObjectUnblocking->getReservation());
    }
}
