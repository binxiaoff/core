<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\Participation;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class ParticipationVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_DELETE = 'delete';

    protected function canView(Participation $participation, User $user)
    {
        if (false === $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $participation->getProject())) {
            return false;
        }

        if ($this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $participation->getProject())) {
            return true;
        }

        if ($this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER) && $participation->isPrimary()) {
            return true;
        }

        if ($this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_PRIMARY_PARTICIPANT) && $participation->isPrimary()) {
            return true;
        }

        if ($this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_SECONDARY_PARTICIPANT) && $participation->isSecondary()) {
            return true;
        }

        return false;
    }

    protected function canEdit(Participation $participation, User $user): bool
    {
        if ($participation->getProject()->isArchived()) {
            return false;
        }

        if ($participation->isArchived()) {
            return false;
        }

        if ($this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $participation->getProject())) {
            return true;
        }

        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_PARTICIPANT, $participation->getProject())
            && $staff->getCompany() === $participation->getParticipant();
    }

    protected function canCreate(Participation $participation, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $participation->getProject())
            && false === $participation->getProject()->isArchived();
    }

    protected function canDelete(Participation $participation, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $participation->getProject())
            && false === $participation->isAgent()
            && false === $participation->getProject()->isArchived();
    }
}
