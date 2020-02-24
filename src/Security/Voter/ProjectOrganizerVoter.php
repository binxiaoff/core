<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\Clients;
use Unilend\Entity\ProjectOrganizer;

class ProjectOrganizerVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_EDIT   = 'edit';

    /**
     * @param ProjectOrganizer $subject
     * @param Clients          $user
     *
     * @return bool
     */
    protected function fulfillPreconditions($subject, Clients $user): bool
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
        return false === $subject->hasRole(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_ARRANGER);
    }

    /**
     * @param ProjectOrganizer $subject
     *
     * @return bool
     */
    protected function canEdit(ProjectOrganizer $subject): bool
    {
        return $subject->getCompany() !== $subject->getProject()->getSubmitterCompany();
    }
}
