<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\BorrowerMember;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class BorrowerMemberVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_CREATE = 'create';

    protected function canCreate(BorrowerMember $borrowerMember, User $user): bool
    {
        $project = $borrowerMember->getProject();

        if ($project->isArchived()) {
            return false;
        }

        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $project)
            || $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project);
    }

    protected function canView(BorrowerMember $borrowerMember, User $user)
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $borrowerMember);
    }
}
