<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;

class FinancingObjectVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CHECK_ELIGIBILITY = 'check_eligibility';

    protected function canCreate(FinancingObject $financingObject): bool
    {
        $reservation = $financingObject->getReservation();

        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $reservation);
    }

    protected function canView(FinancingObject $financingObject): bool
    {
        $reservation = $financingObject->getReservation();

        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_VIEW, $reservation);
    }

    protected function canEdit(FinancingObject $financingObject): bool
    {
        $reservation = $financingObject->getReservation();

        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $reservation)
            && ($reservation->isInDraft() || $reservation->isFormalized())
        ;
    }

    protected function canCheckEligibility(FinancingObject $financingObject): bool
    {
        $reservation = $financingObject->getReservation();

        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_CHECK_ELIGIBILITY, $reservation);
    }

    protected function canDelete(FinancingObject $financingObject): bool
    {
        $reservation = $financingObject->getReservation();

        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $reservation);
    }
}
