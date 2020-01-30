<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Project;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Entity\ProjectStatus;
use Unilend\Message\Project\ProjectStatusUpdated;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\ProjectParticipationContact\ProjectParticipationContactNotifier;

class ProjectStatusUpdatedHandler implements MessageHandlerInterface
{
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var ProjectParticipationContactNotifier */
    private $projectParticipationContactNotifier;

    /**
     * @param ProjectRepository                   $projectRepository
     * @param ProjectParticipationContactNotifier $projectParticipationContactNotifier
     */
    public function __construct(ProjectRepository $projectRepository, ProjectParticipationContactNotifier $projectParticipationContactNotifier)
    {
        $this->projectRepository                   = $projectRepository;
        $this->projectParticipationContactNotifier = $projectParticipationContactNotifier;
    }

    /**
     * @param ProjectStatusUpdated $projectStatusUpdated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(ProjectStatusUpdated $projectStatusUpdated)
    {
        $project = $this->projectRepository->find($projectStatusUpdated->getProjectId());

        if ($project && \in_array($projectStatusUpdated->getNewStatus(), [ProjectStatus::STATUS_PUBLISHED, ProjectStatus::STATUS_INTERESTS_COLLECTED], true)) {
            foreach ($project->getProjectParticipations() as $projectParticipation) {
                foreach ($projectParticipation->getProjectParticipationContacts() as $contact) {
                    $this->projectParticipationContactNotifier->sendInvitation($contact);
                }
            }
        }
    }
}
