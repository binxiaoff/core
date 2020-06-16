<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\{Clients, ProjectParticipationTranche};

class ProjectParticipationTrancheVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_EDIT   = 'edit';

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     * @param Clients                     $client
     *
     * @return bool
     */
    public function canCreate(ProjectParticipationTranche $projectParticipationTranche, Clients $client): bool
    {
        return $projectParticipationTranche->getProjectParticipation()->getProject()->getSubmitterCompany() === $client->getCompany()
            && $projectParticipationTranche->getProjectParticipation()->isActive();
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     * @param Clients                     $client
     *
     * @return bool
     */
    public function canEdit(ProjectParticipationTranche $projectParticipationTranche, Clients $client): bool
    {
        $projectParticipation = $projectParticipationTranche->getProjectParticipation();

        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $projectParticipation);
    }
}
