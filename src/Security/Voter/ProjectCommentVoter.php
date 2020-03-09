<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\{Clients, ProjectComment};

class ProjectCommentVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_EDIT = 'edit';

    /**
     * @param ProjectComment $projectComment
     * @param Clients        $user
     *
     * @return bool
     */
    protected function canEdit(ProjectComment $projectComment, Clients $user): bool
    {
        return $projectComment->getClient() === $user;
    }
}
