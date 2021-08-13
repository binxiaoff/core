<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationStatus;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationTranche;

class ProjectParticipationTrancheVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_SENSITIVE_VIEW = 'sensitive_view';

    protected function canCreate(ProjectParticipationTranche $projectParticipationTranche, User $user): bool
    {
        $projectParticipation = $projectParticipationTranche->getProjectParticipation();

        if (
            false === $projectParticipation->isActive()
            && $projectParticipation->getCurrentStatus()->getStatus() >= ProjectParticipationStatus::STATUS_COMMITTEE_PENDED
        ) {
            return false;
        }

        return $user->getCompany() === $projectParticipation->getProject()->getArranger()
            && $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $projectParticipation);
    }

    protected function canEdit(ProjectParticipationTranche $projectParticipationTranche): bool
    {
        $projectParticipation = $projectParticipationTranche->getProjectParticipation();

        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $projectParticipation);
    }

    protected function canSensitiveView(ProjectParticipationTranche $projectParticipationTranche, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $projectParticipationTranche->getProjectParticipation());
    }
}
