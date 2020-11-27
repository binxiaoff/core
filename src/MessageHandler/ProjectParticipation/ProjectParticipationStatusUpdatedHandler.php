<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\ProjectParticipation;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Message\ProjectParticipation\ProjectParticipationStatusUpdated;
use Unilend\Repository\ProjectParticipationRepository;
use Unilend\Service\ProjectParticipation\ProjectParticipationNotifier;
use Unilend\Syndication\Entity\ProjectParticipationStatus;

class ProjectParticipationStatusUpdatedHandler implements MessageHandlerInterface
{
    private ProjectParticipationRepository $projectParticipationRepository;

    private ProjectParticipationNotifier $projectParticipationNotifier;

    /**
     * @param ProjectParticipationRepository $projectParticipationRepository
     * @param ProjectParticipationNotifier   $projectParticipationNotifier
     */
    public function __construct(ProjectParticipationRepository $projectParticipationRepository, ProjectParticipationNotifier $projectParticipationNotifier)
    {
        $this->projectParticipationRepository = $projectParticipationRepository;
        $this->projectParticipationNotifier = $projectParticipationNotifier;
    }

    /**
     * @param ProjectParticipationStatusUpdated $projectParticipationStatusUpdated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(ProjectParticipationStatusUpdated $projectParticipationStatusUpdated)
    {
        $id = $projectParticipationStatusUpdated->getProjectParticipationId();

        $projectParticipation = $this->projectParticipationRepository->find($id);

        if (null === $projectParticipation) {
            return;
        }

        if (
            false === \in_array(
                $projectParticipationStatusUpdated->getNewStatus(),
                [ProjectParticipationStatus::STATUS_CREATED, ProjectParticipationStatus::STATUS_ARCHIVED_BY_ARRANGER],
                true
            )
        ) {
            $this->projectParticipationNotifier->notifyParticipantReply($projectParticipation);
        }
    }
}
