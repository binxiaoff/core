<?php

declare(strict_types=1);

namespace Unilend\Service\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Swift_RfcComplianceException;
use Unilend\Entity\Project;
use Unilend\Service\{NotificationManager, ProjectParticipation\ProjectParticipationNotifier};

class ProjectNotifier
{
    /** @var ProjectParticipationNotifier */
    private $projectParticipationNotifier;
    private $notificationManager;

    /**
     * @param ProjectParticipationNotifier $projectParticipationNotifier
     * @param NotificationManager          $notificationManager
     */
    public function __construct(ProjectParticipationNotifier $projectParticipationNotifier, NotificationManager $notificationManager)
    {
        $this->projectParticipationNotifier = $projectParticipationNotifier;
        $this->notificationManager          = $notificationManager;
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
        $participants = $project->getProjectParticipations();
        $sent         = 0;
        foreach ($participants as $participant) {
            $sent += $this->projectParticipationNotifier->notifyParticipantInvited($participant);
            $this->notificationManager->createProjectPublication($participant);
        }

        return $sent;
    }
}
