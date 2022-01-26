<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Agency\Entity\AgentBankAccount;

class AgentBankAccountVoter extends AbstractEntityVoter
{
    public function canCreate(AgentBankAccount $agentBankAccount, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $agentBankAccount->getProject())
            && $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $agentBankAccount->getProject());
    }

    public function canView(AgentBankAccount $agentBankAccount, User $user): bool
    {
        if ($this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $agentBankAccount->getProject())) {
            return true;
        }

        foreach ($agentBankAccount->getParticipations() as $participation) {
            if ($this->authorizationChecker->isGranted(ParticipationVoter::ATTRIBUTE_VIEW, $participation)) {
                return true;
            }
        }

        foreach ($agentBankAccount->getBorrowers() as $borrower) {
            if ($this->authorizationChecker->isGranted(BorrowerVoter::ATTRIBUTE_VIEW, $borrower)) {
                return true;
            }
        }

        return false;
    }

    public function canDelete(AgentBankAccount $agentBankAccount, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $agentBankAccount->getProject())
            && $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $agentBankAccount->getProject());
    }

    public function canEdit(AgentBankAccount $agentBankAccount, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $agentBankAccount->getProject())
            && $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $agentBankAccount->getProject());
    }
}
