<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter\Request;

use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\Request\Eligibility;
use KLS\CreditGuaranty\FEI\Security\Voter\ReservationVoter;

class EligibilityVoter extends AbstractEntityVoter
{
    protected function canCreate(Eligibility $eligibility): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $eligibility->reservation);
    }
}
