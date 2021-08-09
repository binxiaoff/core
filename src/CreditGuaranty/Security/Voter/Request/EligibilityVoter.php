<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Security\Voter\Request;

use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\Entity\Request\Eligibility;
use KLS\CreditGuaranty\Security\Voter\ReservationVoter;

class EligibilityVoter extends AbstractEntityVoter
{
    protected function canCreate(Eligibility $eligibility): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $eligibility->reservation);
    }
}
