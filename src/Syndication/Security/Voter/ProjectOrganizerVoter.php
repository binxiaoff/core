<?php

declare(strict_types=1);

namespace Unilend\Syndication\Security\Voter;

use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\ProjectOrganizer;

class ProjectOrganizerVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_EDIT   = 'edit';

    /**
     * @param ProjectOrganizer $subject
     * @param User             $user
     *
     * @return bool
     */
    protected function fulfillPreconditions($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $subject->getProject());
    }

    /**
     * @param ProjectOrganizer $subject
     *
     * @return bool
     */
    protected function canCreate(ProjectOrganizer $subject)
    {
        return true;
    }

    /**
     * @param ProjectOrganizer $subject
     *
     * @return bool
     */
    protected function canDelete(ProjectOrganizer $subject): bool
    {
        return $subject->getCompany() !== $subject->getProject()->getSubmitterCompany();
    }

    /**
     * @param ProjectOrganizer $subject
     *
     * @return bool
     */
    protected function canEdit(ProjectOrganizer $subject): bool
    {
        return true;
    }
}
