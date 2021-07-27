<?php

declare(strict_types=1);

namespace Unilend\Syndication\Security\Voter;

use Exception;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\ProjectMessage;

class ProjectMessageVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @throws Exception
     */
    protected function canCreate(ProjectMessage $subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $subject->getParticipation()->getProject());
    }

    protected function canEdit(ProjectMessage $subject, User $user): bool
    {
        return $user->getCurrentStaff() === $subject->getAddedBy();
    }

    protected function canDelete(ProjectMessage $subject, User $user): bool
    {
        return $user->getCurrentStaff() === $subject->getAddedBy();
    }
}
