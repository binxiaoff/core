<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Agency\Entity\Agent;

class AgentVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_AGENT = 'agent';

    protected function canView(Agent $agent, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $agent->getProject());
    }

    protected function canEdit(Agent $agent, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $agent->getProject());
    }

    protected function canAgent(Agent $agent, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $agent->getProject());
    }
}
