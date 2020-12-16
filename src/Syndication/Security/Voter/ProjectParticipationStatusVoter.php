<?php

declare(strict_types=1);

namespace Unilend\Syndication\Security\Voter;

use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\{ProjectParticipationStatus};

class ProjectParticipationStatusVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param ProjectParticipationStatus $projectParticipationStatus
     * @param User                       $user
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipationStatus $projectParticipationStatus, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $projectParticipationStatus->getProjectParticipation());
    }
}
