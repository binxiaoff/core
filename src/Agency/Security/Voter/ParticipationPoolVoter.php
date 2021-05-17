<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\ParticipationPool;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class ParticipationPoolVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_EDIT = 'edit';
    public const ATTRIBUTE_VIEW = 'view';

    protected function canEdit(ParticipationPool $participationPool): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $participationPool->getProject())
            && false === $participationPool->getProject()->isArchived();
    }

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
            && $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_PRIMARY_PARTICIPANT, $participationPool->getProject())
        ) {
            return true;
        }

        return false;
    }
}
