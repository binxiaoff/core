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
     * @param Project $project
     * @param User    $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canView(Project $project, User $user): bool
    {
        // TODO change after new habilitations are merged
        return true;
    }

    /**
     * @param Project $project
     * @param User    $user
     *
     * @return bool
     */
    protected function canCreate(Project $project, User $user): bool
    {
        // TODO change after new habilitations are merged
        return true;
    }

    /**
     * @param Project $project
     * @param User    $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canEdit(Project $project, User $user): bool
    {
        return $project->getAgent() === $user->getCompany();
    }
}
