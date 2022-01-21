<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;

class ReportingTemplateVoter extends AbstractEntityVoter
{
    /**
     * @param ReportingTemplate $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        $program = $subject->getProgram();

        return $this->authorizationChecker->isGranted(ProgramVoter::ATTRIBUTE_REPORTING, $program)
            && $program->isPaused();
    }
}
