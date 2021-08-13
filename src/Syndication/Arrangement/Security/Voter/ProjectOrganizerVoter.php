<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Arrangement\Entity\ProjectOrganizer;

class ProjectOrganizerVoter extends AbstractEntityVoter
{
    /**
     * @param ProjectOrganizer $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $subject->getProject());
    }
}
