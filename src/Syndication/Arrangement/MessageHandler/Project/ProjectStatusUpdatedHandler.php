<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\MessageHandler\Project;

use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use KLS\Syndication\Arrangement\Message\Project\ProjectStatusUpdated;
use KLS\Syndication\Arrangement\Repository\ProjectRepository;
use KLS\Syndication\Arrangement\Service\Project\SlackNotifier\ProjectUpdateNotifier;
use KLS\Syndication\Arrangement\Service\ProjectParticipationMember\ProjectParticipationMemberNotifier;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ProjectStatusUpdatedHandler implements MessageHandlerInterface
{
    private ProjectRepository $projectRepository;
    private ProjectParticipationMemberNotifier $projectParticipationMemberNotifier;
    private ProjectUpdateNotifier $projectUpdateNotifier;

    public function __construct(
        ProjectRepository $projectRepository,
        ProjectParticipationMemberNotifier $projectParticipationMemberNotifier,
        ProjectUpdateNotifier $projectUpdateNotifier
    ) {
        $this->projectRepository                  = $projectRepository;
        $this->projectParticipationMemberNotifier = $projectParticipationMemberNotifier;
        $this->projectUpdateNotifier              = $projectUpdateNotifier;
    }

    /**
     * @throws \Http\Client\Exception
     * @throws \Nexy\Slack\Exception\SlackApiException
     * @throws \Exception
     */
    public function __invoke(ProjectStatusUpdated $projectStatusUpdated)
    {
        $project = $this->projectRepository->find($projectStatusUpdated->getProjectId());

        if (null === $project) {
            return;
        }

        if (\in_array($projectStatusUpdated->getNewStatus(), [ProjectStatus::STATUS_INTEREST_EXPRESSION, ProjectStatus::STATUS_PARTICIPANT_REPLY], true)) {
            foreach ($project->getProjectParticipations() as $projectParticipation) {
                foreach ($projectParticipation->getActiveProjectParticipationMembers() as $activeProjectParticipationMember) {
                    $this->projectParticipationMemberNotifier->notifyMemberAdded($activeProjectParticipationMember);
                }
            }
        }

        $this->projectUpdateNotifier->notify($project);
    }
}
