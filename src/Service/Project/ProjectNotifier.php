<?php

declare(strict_types=1);

namespace Unilend\Service\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Psr\Log\{LogLevel, LoggerInterface};
use Unilend\Entity\{Project, ProjectStatus};
use Unilend\Service\ProjectParticipation\ProjectParticipationNotifier;

class ProjectNotifier
{
    /** @var ProjectParticipationNotifier */
    private $projectParticipationNotifier;
    /** @var LoggerInterface */
    private $supportLogger;

    /**
     * @param ProjectParticipationNotifier $projectParticipationNotifier
     * @param LoggerInterface              $supportLogger
     */
    public function __construct(ProjectParticipationNotifier $projectParticipationNotifier, LoggerInterface $supportLogger)
    {
        $this->projectParticipationNotifier = $projectParticipationNotifier;
        $this->supportLogger                = $supportLogger;
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

        $this->supportLogger->log(LogLevel::INFO, "Le dossier {$title} vient d'être créé", [
            'utilisateur' => $project->getSubmitterClient()->getEmail(),
            'entité'      => $project->getSubmitterCompany()->getName(),
        ]);
    }
}
