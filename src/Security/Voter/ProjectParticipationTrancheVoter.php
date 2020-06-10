<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, ProjectParticipationStatus};
use Unilend\Service\ProjectOrganizer\ProjectOrganizerManager;

class ProjectParticipationTrancheVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /** @var ProjectOrganizerManager */
    private $projectOrganizerManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ProjectOrganizerManager       $projectOrganizerManager
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ProjectOrganizerManager $projectOrganizerManager)
    {
        $this->projectOrganizerManager = $projectOrganizerManager;
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
        return $this->projectOrganizerManager->isArranger($client->getCurrentStaff(), $projectParticipationStatus->getProjectParticipation()->getProject());
    }
}
