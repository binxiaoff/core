<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\Borrower;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class BorrowerVoter extends AbstractEntityVoter
{
    public function canCreate(Borrower $borrower, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $borrower->getProject())
            && $borrower->getProject()->isEditable();
    }

    public function canView(Borrower $borrower, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $borrower->getProject());
    }

    public function canEdit(Borrower $borrower, User $user): bool
    {
        return (
                $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $borrower->getProject())
                || $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $borrower->getProject())
            )
            && $borrower->getProject()->isEditable();
    }

    public function canDelete(Borrower $borrower, User $user): bool
    {
        $project = $borrower->getProject();

        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $borrower->getProject())
            && $project->isDraft();
    }
}
