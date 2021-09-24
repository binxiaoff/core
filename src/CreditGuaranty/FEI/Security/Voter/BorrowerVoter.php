<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\Borrower;

class BorrowerVoter extends AbstractEntityVoter
{
    protected function canView(Borrower $borrower): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_VIEW, $borrower->getReservation());
    }

    protected function canEdit(Borrower $borrower): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $borrower->getReservation());
    }

    protected function canDelete(Borrower $borrower): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $borrower->getReservation());
    }
}
