<?php

declare(strict_types=1);

namespace Unilend\Agency\Voter;

use Exception;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class ProjectVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW                 = 'view';
    public const ATTRIBUTE_EDIT                 = 'edit';
    public const ATTRIBUTE_CREATE               = 'create';

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canView(Project $project, Clients $user): bool
    {
        return true;
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return bool
     */
    protected function canCreate(Project $project, Clients $user): bool
    {
        return true;
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canEdit(Project $project, Clients $user): bool
    {
        return true;
    }
}
