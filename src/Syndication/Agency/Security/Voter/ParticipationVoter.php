<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Agency\Entity\Participation;

class ParticipationVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_DATAROOM = 'dataroom';

    protected function canCreate(Participation $participation, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $participation->getProject())
            && $participation->getProject()->isEditable();
    }

    protected function canView(Participation $participation, User $user): bool
    {
        if (false === $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $participation->getProject())) {
            return false;
        }

        if ($this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $participation->getProject())) {
            return true;
        }

        if ($this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $participation->getProject()) && $participation->isPrimary()) {
            return true;
        }

        if ($this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_PRIMARY_PARTICIPANT, $participation->getProject()) && $participation->isPrimary()) {
            return true;
        }

        if ($this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_SECONDARY_PARTICIPANT, $participation->getProject()) && $participation->isSecondary()) {
            return true;
        }

        return false;
    }

    protected function canEdit(Participation $participation, User $user): bool
    {
        if (false === $participation->getProject()->isEditable()) {
            return false;
        }

        if ($participation->isArchived()) {
            return false;
        }

        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $participation->getProject())
            || (
                $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_PARTICIPANT, $participation->getProject())
                && $staff->getCompany() === $participation->getParticipant()
            );
    }

    protected function canDelete(Participation $participation, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $participation->getProject())
            && false === $participation->isAgent()
            && $participation->getProject()->isEditable();
    }

    protected function canDataroom(Participation $participation, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_PARTICIPANT, $participation->getProject())
            && $staff->getCompany() === $participation->getParticipant();
    }
}
