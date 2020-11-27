<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Project;

use Doctrine\ORM\{NoResultException, NonUniqueResultException};
use Http\Client\Exception;
use Nexy\Slack\Exception\SlackApiException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\Project\ProjectStatusUpdated;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\Project\ProjectNotifier;
use Unilend\Service\ProjectParticipationMember\ProjectParticipationMemberNotifier;
use Unilend\Syndication\Entity\ProjectStatus;

class ProjectStatusUpdatedHandler implements MessageHandlerInterface
{
    /** @var ProjectRepository */
    private ProjectRepository $projectRepository;
    /** @var ProjectParticipationMemberNotifier */
    private ProjectParticipationMemberNotifier $projectParticipationMemberNotifier;
    /** @var ProjectNotifier */
    private ProjectNotifier $projectNotifier;

    /**
     * @param ProjectRepository                  $projectRepository
     * @param ProjectNotifier                    $projectNotifier
     * @param ProjectParticipationMemberNotifier $projectParticipationMemberNotifier
     */
    public function __construct(
        ProjectRepository $projectRepository,
        ProjectNotifier $projectNotifier,
        ProjectParticipationMemberNotifier $projectParticipationMemberNotifier
    ) {
        $this->projectRepository                  = $projectRepository;
        $this->projectParticipationMemberNotifier = $projectParticipationMemberNotifier;
        $this->projectNotifier                    = $projectNotifier;
    }

    /**
     * @param ProjectStatusUpdated $projectStatusUpdated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws Exception
     * @throws SlackApiException
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

        $this->projectNotifier->notifyProjectStatusChanged($project);
    }
}
