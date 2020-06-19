<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\{Clients, ProjectParticipationStatus};

class ProjectParticipationStatusVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param ProjectParticipationStatus $projectParticipationStatus
     * @param Clients                    $user
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipationStatus $projectParticipationStatus, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $projectParticipationStatus->getProjectParticipation());
    }
}
