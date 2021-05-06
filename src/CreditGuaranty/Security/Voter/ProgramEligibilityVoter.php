<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;

class ProgramEligibilityVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_EDIT   = 'view';
    public const ATTRIBUTE_DELETE = 'delete';

    protected function canView(ProgramEligibility $programEligibility): bool
    {
        return $this->authorizationChecker->isGranted($programEligibility->getProgram(), ProgramVoter::ATTRIBUTE_VIEW);
    }

    protected function canCreate(ProgramEligibility $programEligibility): bool
    {
        return $this->authorizationChecker->isGranted($programEligibility->getProgram(), ProgramVoter::ATTRIBUTE_EDIT);
    }

    protected function canDelete(ProgramEligibility $programEligibility): bool
    {
        return $this->authorizationChecker->isGranted($programEligibility->getProgram(), ProgramVoter::ATTRIBUTE_EDIT);
    }
}
