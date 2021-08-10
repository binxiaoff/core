<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;

class FinancingObjectVoter extends AbstractEntityVoter
{
    protected function canCreate(FinancingObject $financingObject): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $financingObject->getReservation());
    }

    protected function canView(FinancingObject $financingObject): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_VIEW, $financingObject->getReservation());
    }

    protected function canEdit(FinancingObject $financingObject): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $financingObject->getReservation());
    }

    protected function canDelete(FinancingObject $financingObject): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $financingObject->getReservation());
    }
}
