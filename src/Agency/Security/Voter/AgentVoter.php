<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\Agent;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class AgentVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_EDIT = 'edit';
    public const ATTRIBUTE_VIEW = 'view';

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
