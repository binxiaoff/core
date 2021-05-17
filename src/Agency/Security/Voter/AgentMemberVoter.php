<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\AgentMember;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class AgentMemberVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param AgentMember $agentMember
     */
    protected function isGrantedAll($agentMember, User $user): bool
    {
        return $this->authorizationChecker->isGranted(BorrowerVoter::ATTRIBUTE_EDIT, $agentMember->getAgent());
    }
}
