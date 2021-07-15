<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\FinancingObjectUnblocking;

class FinancingObjectUnblockingVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';

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
