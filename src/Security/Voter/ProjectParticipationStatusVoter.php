<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, ProjectParticipationStatus};
use Unilend\Service\ProjectOrganizer\ProjectOrganizerManager;
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationStatusVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /** @var ProjectParticipationManager */
    private $projectParticipationManager;
    /** @var ProjectOrganizerManager */
    private $projectOrganizerManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ProjectParticipationManager   $projectParticipationManager
     * @param ProjectOrganizerManager       $projectOrganizerManager
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ProjectParticipationManager $projectParticipationManager,
        ProjectOrganizerManager $projectOrganizerManager
    ) {
        $this->projectParticipationManager = $projectParticipationManager;
        $this->projectOrganizerManager     = $projectOrganizerManager;
        parent::__construct($authorizationChecker);
    }

    /**
     * @param ProjectParticipationStatus $projectParticipationStatus
     * @param Clients                    $client
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    public function canCreate(ProjectParticipationStatus $projectParticipationStatus, Clients $client): bool
    {
        $project = $projectParticipationStatus->getProjectParticipation()->getProject();
        $staff   = $client->getCurrentStaff();

        return $staff
            && ((ProjectParticipationStatus::STATUS_ARCHIVED === $projectParticipationStatus->getStatus() && $this->projectOrganizerManager->isArranger($staff, $project))
            || (ProjectParticipationStatus::STATUS_DECLINED === $projectParticipationStatus->getStatus() && $this->projectParticipationManager->isParticipant($staff, $project)));
    }
}
