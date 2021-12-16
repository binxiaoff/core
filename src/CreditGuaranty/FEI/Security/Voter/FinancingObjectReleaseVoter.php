<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\FinancingObjectRelease;

class FinancingObjectReleaseVoter extends AbstractEntityVoter
{
    protected function canCreate(FinancingObjectRelease $financingObjectRelease): bool
    {
        $reservation = $financingObjectRelease->getReservation();

        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $reservation)
            && $reservation->isFormalized()
        ;
    }

    protected function canView(FinancingObjectRelease $financingObjectRelease): bool
    {
        $reservation = $financingObjectRelease->getReservation();

        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_VIEW, $reservation)
            && $reservation->isFormalized()
        ;
    }

    protected function canEdit(FinancingObjectRelease $financingObjectRelease): bool
    {
        $reservation = $financingObjectRelease->getReservation();

        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $reservation)
            && $reservation->isFormalized()
        ;
    }
}
