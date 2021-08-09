<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\MessageHandler\ProjectParticipation;

use KLS\Syndication\Arrangement\Entity\ProjectParticipationStatus;
use KLS\Syndication\Arrangement\Message\ProjectParticipation\ProjectParticipationStatusUpdated;
use KLS\Syndication\Arrangement\Repository\ProjectParticipationRepository;
use KLS\Syndication\Arrangement\Service\ProjectParticipation\ProjectParticipationNotifier;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ProjectParticipationStatusUpdatedHandler implements MessageHandlerInterface
{
    private ProjectParticipationRepository $projectParticipationRepository;

    private ProjectParticipationNotifier $projectParticipationNotifier;

    public function __construct(ProjectParticipationRepository $projectParticipationRepository, ProjectParticipationNotifier $projectParticipationNotifier)
    {
        $this->projectParticipationRepository = $projectParticipationRepository;
        $this->projectParticipationNotifier   = $projectParticipationNotifier;
    }

    /**
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
