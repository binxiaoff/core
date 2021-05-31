<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\FinancingObject;

class FinancingObjectVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';

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
