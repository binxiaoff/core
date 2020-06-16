<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, ProjectParticipation, ProjectParticipationStatus, ProjectParticipationTranche};
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationTrancheVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_EDIT   = 'edit';

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

        return $this->projectParticipationManager->canEdit($projectParticipation, $client);
    }
}
