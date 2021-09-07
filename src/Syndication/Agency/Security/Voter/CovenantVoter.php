<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Security\Voter;

use Exception;
use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Agency\Entity\Covenant;

class CovenantVoter extends AbstractEntityVoter
{
    protected function canCreate(Covenant $covenant, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $covenant->getProject())
            && $covenant->getProject()->isEditable();
    }

    /**
     * @throws Exception
     */
    protected function canView(Covenant $covenant, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $covenant->getProject())
            && ($covenant->isPublished() || $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $covenant->getProject()));
    }

    /**
     * @throws Exception
     */
    protected function canEdit(Covenant $covenant, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $covenant->getProject())
            && false === $covenant->isPublished()
            && false === $covenant->isArchived();
    }

    protected function canDelete(Covenant $covenant, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $covenant->getProject());
    }
}
