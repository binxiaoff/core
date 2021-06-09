<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\AgentMember;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class AgentMemberVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_EDIT   = 'edit';

    protected function canCreate(AgentMember $agentMember, User $user): bool
    {
        $project = $agentMember->getProject();

        return $project->isEditable()
            && ($this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $project)
            || $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project));
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
                && $project->isEditable()
                && false === $agentMember->isArchived()
                && false === $agentMember->isSignatory()
                && false === $agentMember->isReferent()
                && false === count($agentMember->getAgent()->getMembers()) > 1 // Forbid last member deletion
                && $agentMember->getUser() !== $user; // Forbid autodelete
    }
}
