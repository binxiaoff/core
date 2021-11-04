<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\FinancingObjectRelease;

class FinancingObjectReleaseVoter extends AbstractEntityVoter
{
    protected function canCreate(FinancingObjectRelease $financingObjectRelease): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $financingObjectRelease->getReservation());
    }

    protected function canView(FinancingObjectRelease $financingObjectRelease): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_VIEW, $financingObjectRelease->getReservation());
    }

    protected function canEdit(FinancingObjectRelease $financingObjectRelease): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $financingObjectRelease->getReservation());
    }
}
