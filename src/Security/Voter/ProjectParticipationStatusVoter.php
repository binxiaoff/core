<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, ProjectParticipationStatus};
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationStatusVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /** @var ProjectParticipationManager */
    private $projectParticipationManager;

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
     * @param ProjectParticipationStatus $projectParticipationStatus
     * @param Clients                    $client
     *
     * @return bool
     */
    public function canCreate(ProjectParticipationStatus $projectParticipationStatus, Clients $client): bool
    {
        $project = $projectParticipationStatus->getProjectParticipation()->getProject();
        $staff   = $client->getCurrentStaff();

        return $staff && (
            (
                ProjectParticipationStatus::STATUS_ARCHIVED_BY_ARRANGER === $projectParticipationStatus->getStatus()
                && $project->getSubmitterCompany() === $staff->getCompany()
            )
            || (
                ProjectParticipationStatus::STATUS_ARCHIVED_BY_PARTICIPANT === $projectParticipationStatus->getStatus()
                && $this->projectParticipationManager->isParticipationOwner($staff, $projectParticipationStatus->getProjectParticipation())
            )
        );
    }
}
