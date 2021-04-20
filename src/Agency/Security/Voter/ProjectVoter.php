<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Exception;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class ProjectVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @throws Exception
     */
    protected function canView(Project $project, User $user): bool
    {
        return true;
    }

    protected function canCreate(Project $project, User $user): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    protected function canEdit(Project $project, User $user): bool
    {
        return true;
    }
}
