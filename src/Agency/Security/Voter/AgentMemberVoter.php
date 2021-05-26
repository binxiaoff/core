<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\AgentMember;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class AgentMemberVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    protected function canCreate(AgentMember $agentMember, User $user): bool
    {
        $project = $agentMember->getProject();

        if (!$project->isEditable()) {
            return false;
        }

        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $project)
            || $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project);
    }
}
