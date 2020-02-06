<?php

declare(strict_types=1);

namespace Unilend\Service\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectStatus;
use Unilend\Service\ProjectParticipation\ProjectParticipationNotifier;

class ProjectNotifier
{
    /** @var ProjectParticipationNotifier */
    private $projectParticipationNotifier;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param ProjectParticipationNotifier $projectParticipationNotifier
     * @param LoggerInterface              $logger
     */
    public function __construct(
        ProjectParticipationNotifier $projectParticipationNotifier,
        LoggerInterface $logger
    ) {
        $this->projectParticipationNotifier = $projectParticipationNotifier;
        $this->logger                       = $logger;
    }

    /**
     * @param Project $project
     *
     * @throws ORMException
     * @throws OptimisticLockException
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

    /**
     * @param Project $project
     */
    public function notifyProjectCreated(Project $project)
    {
        $title = $project->getTitle();

        $this->logger->log(LogLevel::INFO, "Le projet {$title} viens d'être créé", [
            'utilisateur' => $project->getSubmitterClient()->getEmail(),
            'entité'      => $project->getSubmitterCompany()->getName(),
        ]);
    }
}
