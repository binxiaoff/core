<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Agency\Entity\AgentMember;

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
            && ($project->isDraft() || $project->isPublished())
            && $project->getAgent()->getMembers()->count() > 1
            && false === $agentMember->getUser()->isEqualTo($user);
    }
}
