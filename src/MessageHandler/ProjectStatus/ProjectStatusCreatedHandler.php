<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\ProjectStatus;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\ProjectStatus;
use Unilend\Message\ProjectStatus\ProjectStatusCreated;
use Unilend\Repository\ProjectStatusRepository;
use Unilend\Service\ProjectParticipationContact\ProjectParticipationContactNotifier;

class ProjectStatusCreatedHandler implements MessageHandlerInterface
{
    /** @var ProjectStatusRepository */
    private $projectStatusRepository;
    /** @var ProjectParticipationContactNotifier */
    private $projectParticipationContactNotifier;

    /**
     * @param ProjectStatusRepository             $projectStatusRepository
     * @param ProjectParticipationContactNotifier $projectParticipationContactNotifier
     */
    public function __construct(ProjectStatusRepository $projectStatusRepository, ProjectParticipationContactNotifier $projectParticipationContactNotifier)
    {
        $this->projectStatusRepository             = $projectStatusRepository;
        $this->projectParticipationContactNotifier = $projectParticipationContactNotifier;
    }

    /**
     * @param ProjectStatusCreated $projectStatusCreated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(ProjectStatusCreated $projectStatusCreated)
    {
        $projectStatus = $this->projectStatusRepository->find($projectStatusCreated->getProjectStatusId());

        if ($projectStatus && \in_array($projectStatus->getStatus(), [ProjectStatus::STATUS_PUBLISHED, ProjectStatus::STATUS_INTERESTS_COLLECTED], true)) {
            $project = $projectStatus->getProject();
            foreach ($project->getProjectParticipations() as $projectParticipation) {
                foreach ($projectParticipation->getProjectParticipationContacts() as $contact) {
                    $this->projectParticipationContactNotifier->sendInvitation($contact);
                }
            }
        }
    }
}
