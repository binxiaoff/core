<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Core\Entity\Clients;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\ProjectStatus;

class ProjectStatusVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param ProjectStatus $projectStatus
     * @param Clients       $user
     *
     * @return bool
     */
    protected function isGrantedAll($projectStatus, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $projectStatus->getProject());
    }
}
