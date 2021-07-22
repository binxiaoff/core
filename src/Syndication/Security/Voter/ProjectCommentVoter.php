<?php

declare(strict_types=1);

namespace Unilend\Syndication\Security\Voter;

use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\ProjectComment;

class ProjectCommentVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_EDIT = 'edit';

    protected function canEdit(ProjectComment $projectComment, User $user): bool
    {
        return $projectComment->getUser() === $user;
    }
}
