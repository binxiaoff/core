<?php

declare(strict_types=1);

namespace KLS\Syndication\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Entity\ProjectStatus;

class ProjectStatusVoter extends AbstractEntityVoter
{
    /**
     * @param ProjectStatus $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $subject->getProject());
    }
}
