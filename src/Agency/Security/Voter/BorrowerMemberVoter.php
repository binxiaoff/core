<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\BorrowerMember;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class BorrowerMemberVoter extends AbstractEntityVoter
{
    protected function canCreate(BorrowerMember $borrowerMember, User $user): bool
    {
        $project = $borrowerMember->getProject();

        return $project->isEditable()
            && (
                $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $project)
                || $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project)
            );
    }

    protected function canView(BorrowerMember $borrowerMember, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $borrowerMember);
    }

    protected function canEdit(BorrowerMember $borrowerMember, User $user): bool
    {
        $project = $borrowerMember->getProject();

        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $project)
            && $project->isEditable()
            && false === $borrowerMember->isArchived();
    }
}
