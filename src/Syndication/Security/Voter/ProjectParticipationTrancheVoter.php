<?php

declare(strict_types=1);

namespace Unilend\Syndication\Security\Voter;

use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\{ProjectParticipationStatus, ProjectParticipationTranche};

class ProjectParticipationTrancheVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE           = 'create';
    public const ATTRIBUTE_EDIT             = 'edit';
    public const ATTRIBUTE_SENSITIVE_VIEW   = 'sensitive_view';

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     * @param User                        $user
     *
     * @return bool
     */
    protected function canSensitiveView(ProjectParticipationTranche $projectParticipationTranche, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $projectParticipationTranche->getProjectParticipation());
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     * @param User                        $user
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipationTranche $projectParticipationTranche, User $user): bool
    {
        $projectParticipation = $projectParticipationTranche->getProjectParticipation();

        if (
            false === $projectParticipation->isActive()
            && $projectParticipation->getCurrentStatus()->getStatus() >= ProjectParticipationStatus::STATUS_COMMITTEE_PENDED
        ) {
            return false;
        }

        return $user->getCompany() === $projectParticipation->getProject()->getArranger() &&
            $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $projectParticipation);
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     *
     * @return bool
     */
    protected function canEdit(ProjectParticipationTranche $projectParticipationTranche): bool
    {
        $projectParticipation = $projectParticipationTranche->getProjectParticipation();

        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $projectParticipation);
    }
}
