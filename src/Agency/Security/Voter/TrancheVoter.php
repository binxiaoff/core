<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\Tranche;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

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
