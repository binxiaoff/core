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

    public function canView(Participation $participation, User $user)
    {
        // TODO
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $participation->getProject());
    }

    public function canEdit(Participation $participation, User $user): bool
    {
        if ($this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $participation->getProject())) {
            return true;
        }

        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_PARTICIPANT, $participation->getProject()) && $staff->getCompany();
    }

    public function canCreate(Participation $participation, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $participation->getProject());
    }

    protected function canDelete(Participation $participation, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $participation->getProject()) && false === $participation->isAgent();
    }
}
