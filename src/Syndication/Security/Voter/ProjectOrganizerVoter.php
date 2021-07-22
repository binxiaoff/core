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
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $subject->getProject());
    }
}
