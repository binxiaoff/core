<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Exception;
use Unilend\Agency\Entity\Covenant;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class CovenantVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_DELETE = 'delete';

    /**
     * @throws Exception
     */
    protected function canView(Covenant $covenant, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $covenant->getProject());
    }

    protected function canCreate(Covenant $covenant, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $covenant->getProject())
            && false === $covenant->getProject()->isEditable();
    }

    /**
     * @throws Exception
     */
    protected function canEdit(Covenant $covenant, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $covenant->getProject())
            && false === $covenant->isPublished()
            && false === $covenant->isArchived()
            && false === $covenant->getProject()->isEditable();
    }

    /**
     * @return bool
     */
    protected function canDelete(Covenant $covenant, User $user)
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $covenant->getProject())
            && false === $covenant->getProject()->isEditable();
    }
}
