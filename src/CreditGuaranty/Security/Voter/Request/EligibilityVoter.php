<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter\Request;

use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\Request\Eligibility;
use Unilend\CreditGuaranty\Security\Voter\ReservationVoter;

class EligibilityVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    protected function canEdit(Eligibility $eligibility): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $eligibility->reservation);
    }
}
