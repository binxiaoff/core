<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\Borrower;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class BorrowerVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_VIEW   = 'view';

    public function canView(Borrower $borrower, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $borrower->getProject());
    }

    public function canEdit(Borrower $borrower, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $borrower->getProject())
            || $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $borrower->getProject());
    }

    public function canDelete(Borrower $borrower, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $borrower->getProject());
    }

    public function canCreate(Borrower $borrower, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $borrower->getProject());
    }
}
