<?php

declare(strict_types=1);

namespace Unilend\Syndication\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\{Project, ProjectParticipationStatus, ProjectParticipationTranche};
use Unilend\Syndication\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationTrancheVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE           = 'create';
    public const ATTRIBUTE_EDIT             = 'edit';
    public const ATTRIBUTE_SENSITIVE_VIEW   = 'sensitive_view';

    /** @var ProjectParticipationManager */
    private ProjectParticipationManager $projectParticipationManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ProjectParticipationManager   $projectManager
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ProjectParticipationManager $projectManager)
    {
        $this->projectParticipationManager = $projectManager;
        parent::__construct($authorizationChecker);
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     * @param User                        $user
     *
     * @return bool
     *
     * @throws NonUniqueResultException
     */
    protected function canSensitiveView(ProjectParticipationTranche $projectParticipationTranche, User $user): bool
    {
        $project = $projectParticipationTranche->getProjectParticipation()->getProject();

        return Project::OFFER_VISIBILITY_PUBLIC === $project->getOfferVisibility()
            || $this->projectParticipationManager->isMember($projectParticipationTranche->getProjectParticipation(), $user->getCurrentStaff())
            || $project->getSubmitterCompany() === $user->getCompany();
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

        return $projectParticipation->getProject()->getSubmitterCompany() === $user->getCompany()
            && $projectParticipation->isActive()
            && $projectParticipation->getCurrentStatus()->getStatus() < ProjectParticipationStatus::STATUS_COMMITTEE_PENDED;
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
