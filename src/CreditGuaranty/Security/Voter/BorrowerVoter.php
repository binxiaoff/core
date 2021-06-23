<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\Borrower;

class BorrowerVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';

    protected function canCreate(Borrower $borrower): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $borrower->getReservation());
    }

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
