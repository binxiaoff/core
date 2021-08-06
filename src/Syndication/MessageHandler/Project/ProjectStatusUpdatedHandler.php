<?php

declare(strict_types=1);

namespace Unilend\Syndication\MessageHandler\Project;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Http\Client\Exception;
use Nexy\Slack\Exception\SlackApiException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Syndication\Entity\ProjectStatus;
use Unilend\Syndication\Message\Project\ProjectStatusUpdated;
use Unilend\Syndication\Repository\ProjectRepository;
use Unilend\Syndication\Service\Project\ProjectNotifier;
use Unilend\Syndication\Service\ProjectParticipationMember\ProjectParticipationMemberNotifier;

class ProjectStatusUpdatedHandler implements MessageHandlerInterface
{
    private ProjectRepository $projectRepository;
    private ProjectParticipationMemberNotifier $projectParticipationMemberNotifier;
    private ProjectNotifier $projectNotifier;

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
