<?php

declare(strict_types=1);

namespace Unilend\Service\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Swift_RfcComplianceException;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectStatus;
use Unilend\Service\ProjectParticipation\ProjectParticipationNotifier;

class ProjectNotifier
{
    /** @var ProjectParticipationNotifier */
    private $projectParticipationNotifier;

    /**
     * @param ProjectParticipationNotifier $projectParticipationNotifier
     */
    public function __construct(ProjectParticipationNotifier $projectParticipationNotifier)
    {
        $this->projectParticipationNotifier = $projectParticipationNotifier;
    }

    /**
     * @param Project $project
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Swift_RfcComplianceException
     * @throws Exception
     *
     * @return int
     */
    public function notifyProjectPublished(Project $project): int
    {
        $sent = 0;
        if (ProjectStatus::STATUS_PUBLISHED === $project->getCurrentStatus()->getStatus()) {
            $participants = $project->getProjectParticipations();
            foreach ($participants as $participant) {
                $sent += $this->projectParticipationNotifier->notifyParticipantInvited($participant);
            }
        }

        return $sent;
    }
}
