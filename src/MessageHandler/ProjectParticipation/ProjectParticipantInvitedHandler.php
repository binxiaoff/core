<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\ProjectParticipation;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\ProjectParticipation\ProjectParticipantInvited;
use Unilend\Repository\ProjectParticipationRepository;
use Unilend\Service\ProjectParticipation\ProjectParticipationNotifier;

class ProjectParticipantInvitedHandler implements MessageHandlerInterface
{
    /** @var ProjectParticipationRepository */
    private $projectParticipationRepository;
    /** @var ProjectParticipationNotifier */
    private $projectParticipationNotifier;

    /**
     * @param ProjectParticipationRepository $projectParticipationRepository
     * @param ProjectParticipationNotifier   $projectParticipationNotifier
     */
    public function __construct(ProjectParticipationRepository $projectParticipationRepository, ProjectParticipationNotifier $projectParticipationNotifier)
    {
        $this->projectParticipationRepository = $projectParticipationRepository;
        $this->projectParticipationNotifier   = $projectParticipationNotifier;
    }

    /**
     * @param ProjectParticipantInvited $projectParticipantInvited
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(ProjectParticipantInvited $projectParticipantInvited)
    {
        $projectParticipation = $this->projectParticipationRepository->find($projectParticipantInvited->getProjectParticipationId());

        if ($projectParticipation) {
            $this->projectParticipationNotifier->notifyParticipantInvited($projectParticipation);
        }
    }
}
