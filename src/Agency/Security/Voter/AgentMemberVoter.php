<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\AgentMember;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class AgentMemberVoter extends AbstractEntityVoter
{
    protected function canCreate(AgentMember $agentMember, User $user): bool
    {
        $project = $agentMember->getProject();

        return $project->isEditable()
            && (
                $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $project)
                || $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project)
            );
    }

    protected function canEdit(AgentMember $agentMember, User $user): bool
    {
        $project = $agentMember->getProject();

        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project)
            && $project->isEditable()
            && false === $agentMember->isArchived();
    }

    protected function canDelete(AgentMember $agentMember, User $user): bool
    {
        $project = $agentMember->getProject();

        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project)
            && $project->isDraft()
            && $project->getAgent()->getMembers()->count() > 1
            && false === $agentMember->getUser()->isEqualTo($user);
    }
}
