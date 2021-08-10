<?php

declare(strict_types=1);

namespace KLS\Syndication\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Entity\ProjectComment;

class ProjectCommentVoter extends AbstractEntityVoter
{
    protected function canEdit(ProjectComment $projectComment, User $user): bool
    {
        return $projectComment->getUser() === $user;
    }
}
