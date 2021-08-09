<?php

declare(strict_types=1);

namespace KLS\Agency\Security\Voter;

use KLS\Agency\Entity\ParticipationPool;
use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;

class ParticipationPoolVoter extends AbstractEntityVoter
{
    protected function canView(ParticipationPool $participationPool, User $user): bool
    {
        if ($this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $participationPool->getProject())) {
            return true;
        }

        if (
            $participationPool->isSecondary()
            && $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_SECONDARY_PARTICIPANT, $participationPool->getProject())
        ) {
            return true;
        }

        if (
            $participationPool->isPrimary()
            && (
                $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_PRIMARY_PARTICIPANT, $participationPool->getProject())
                || $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $participationPool->getProject())
            )
        ) {
            return true;
        }

        return false;
    }

    protected function canEdit(ParticipationPool $participationPool): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $participationPool->getProject())
            && $participationPool->getProject()->isEditable();
    }
}
