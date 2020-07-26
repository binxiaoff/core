<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\{Clients, ProjectParticipationCollection};

class ProjectParticipationCollectionVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param ProjectParticipationCollection $projectParticipationCollection
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipationCollection $projectParticipationCollection): bool
    {
        return $this->authorizationChecker->isGranted('edit', $projectParticipationCollection->getProject());
    }
}
