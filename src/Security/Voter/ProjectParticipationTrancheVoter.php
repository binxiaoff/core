<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, CompanyModule, Project, ProjectParticipationStatus, ProjectParticipationTranche};
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationTrancheVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE                   = 'create';
    public const ATTRIBUTE_EDIT                     = 'edit';
    public const ATTRIBUTE_SENSITIVE_VIEW           = 'sensitive_view';
    public const ATTRIBUTE_ARRANGER_EDIT            = 'arranger_edit';
    public const ATTRIBUTE_PARTICIPATION_OWNER_EDIT = 'participation_owner_edit';

    /** @var ProjectParticipationManager */
    private ProjectParticipationManager $projectParticipationManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ProjectParticipationManager   $projectParticipationManager
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ProjectParticipationManager $projectParticipationManager)
    {
        $this->projectParticipationManager = $projectParticipationManager;
        parent::__construct($authorizationChecker);
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     * @param Clients                     $client
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipationTranche $projectParticipationTranche, Clients $client): bool
    {
        $projectParticipation = $projectParticipationTranche->getProjectParticipation();

        return $projectParticipation->getProject()->getSubmitterCompany() === $client->getCompany()
            && $projectParticipation->isActive()
            && $projectParticipation->getCurrentStatus()->getStatus() < ProjectParticipationStatus::STATUS_COMMITTEE_PENDED;
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     * @param Clients                     $client
     *
     * @return bool
     */
    protected function canEdit(ProjectParticipationTranche $projectParticipationTranche, Clients $client): bool
    {
        $projectParticipation = $projectParticipationTranche->getProjectParticipation();

        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $projectParticipation);
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     * @param Clients                     $client
     *
     * @return bool
     */
    protected function canArrangerEdit(ProjectParticipationTranche $projectParticipationTranche, Clients $client): bool
    {
        $project = $projectParticipationTranche->getProjectParticipation()->getProject();

        return $project->isInAllocationStep() && $project->getSubmitterCompany() === $client->getCompany();
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     * @param Clients                     $client
     *
     * @return bool
     */
    protected function canParticipationOwnerEdit(ProjectParticipationTranche $projectParticipationTranche, Clients $client): bool
    {
        $projectParticipation = $projectParticipationTranche->getProjectParticipation();

        return $projectParticipation->getProject()->isInOfferNegotiationStep()
            && $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_PARTICIPATION_OWNER_EDIT, $projectParticipation);
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     * @param Clients                     $client
     *
     * @return bool
     */
    protected function canSensitiveView(ProjectParticipationTranche $projectParticipationTranche, Clients $client): bool
    {
        $project = $projectParticipationTranche->getProjectParticipation()->getProject();

        return Project::OFFER_VISIBILITY_PUBLIC === $project->getOfferVisibility()
            || $this->projectParticipationManager->isParticipationOwner($client->getCurrentStaff(), $projectParticipationTranche->getProjectParticipation())
            || $project->getSubmitterCompany() === $client->getCompany();
    }
}
