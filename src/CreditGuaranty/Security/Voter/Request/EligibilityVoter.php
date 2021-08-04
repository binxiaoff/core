<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter\Request;

use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\Request\Eligibility;
use Unilend\CreditGuaranty\Security\Voter\ReservationVoter;

class EligibilityVoter extends AbstractEntityVoter
{
    protected function canCreate(Eligibility $eligibility): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $eligibility->reservation);
    }
}
