<?php

declare(strict_types=1);

namespace KLS\Agency\Security\Voter;

use KLS\Agency\Entity\Tranche;
use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;

class TrancheVoter extends AbstractEntityVoter
{
    /**
     * @param Tranche $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        $project = $subject->getProject();

        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project)
            && $project->isEditable();
    }
}
