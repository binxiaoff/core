<?php

declare(strict_types=1);

namespace KLS\Syndication\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Entity\ProjectFile;

class ProjectFileVoter extends AbstractEntityVoter
{
    /**
     * @param ProjectFile $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $subject->getProject());
    }
}
