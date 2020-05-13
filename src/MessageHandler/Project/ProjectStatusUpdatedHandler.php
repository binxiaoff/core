<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Project;

use Doctrine\ORM\{NoResultException, NonUniqueResultException};
use Http\Client\Exception;
use Nexy\Slack\Exception\SlackApiException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\ProjectStatus;
use Unilend\Message\Project\ProjectStatusUpdated;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\Project\ProjectNotifier;
use Unilend\Service\ProjectParticipationContact\ProjectParticipationContactNotifier;

class ProjectStatusUpdatedHandler implements MessageHandlerInterface
{
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var ProjectParticipationContactNotifier */
    private $projectParticipationContactNotifier;
    /** @var ProjectNotifier */
    private $projectNotifier;

    /**
     * @param ProjectRepository                   $projectRepository
     * @param ProjectNotifier                     $projectNotifier
     * @param ProjectParticipationContactNotifier $projectParticipationContactNotifier
     */
    public function __construct(
        ProjectRepository $projectRepository,
        ProjectNotifier $projectNotifier,
        ProjectParticipationContactNotifier $projectParticipationContactNotifier
    ) {
        $this->projectRepository                   = $projectRepository;
        $this->projectParticipationContactNotifier = $projectParticipationContactNotifier;
        $this->projectNotifier                     = $projectNotifier;
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

        if (\in_array($projectStatusUpdated->getNewStatus(), [ProjectStatus::STATUS_PUBLISHED, ProjectStatus::STATUS_INTERESTS_COLLECTED], true)) {
            foreach ($project->getProjectParticipations() as $projectParticipation) {
                foreach ($projectParticipation->getProjectParticipationContacts() as $contact) {
                    $this->projectParticipationContactNotifier->notifyContactAdded($contact, false);
                }
            }
        }

        $this->projectNotifier->notifyProjectStatusChanged($project);
    }
}
