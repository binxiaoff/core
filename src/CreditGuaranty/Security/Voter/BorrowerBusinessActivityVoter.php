<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\BorrowerBusinessActivity;

class BorrowerBusinessActivityVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';

    protected function canCreate(BorrowerBusinessActivity $borrowerBusinessActivity): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $borrowerBusinessActivity->getReservation());
    }

    protected function canView(BorrowerBusinessActivity $borrowerBusinessActivity): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_VIEW, $borrowerBusinessActivity->getReservation());
    }

    protected function canEdit(BorrowerBusinessActivity $borrowerBusinessActivity): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $borrowerBusinessActivity->getReservation());
    }

    protected function canDelete(BorrowerBusinessActivity $borrowerBusinessActivity): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $borrowerBusinessActivity->getReservation());
    }
}
