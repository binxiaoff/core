<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\ProjectParticipationContact;

use InvalidArgumentException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\ProjectParticipationContact\ProjectParticipationContactCreated;
use Unilend\Repository\ProjectParticipationContactRepository;
use Unilend\Service\{ProjectParticipationContact\ProjectParticipationContactNotifier};

class ProjectParticipationContactCreatedHandler implements MessageHandlerInterface
{
    /** @var ProjectParticipationContactRepository */
    private $projectParticipationContactRepository;
    /** @var ProjectParticipationContactNotifier */
    private $projectParticipationContactNotifier;

    /**
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     * @param ProjectParticipationContactNotifier   $projectParticipationContactNotifier
     */
    public function __construct(
        ProjectParticipationContactRepository $projectParticipationContactRepository,
        ProjectParticipationContactNotifier $projectParticipationContactNotifier
    ) {
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
        $this->projectParticipationContactNotifier   = $projectParticipationContactNotifier;
    }

    /**
     * @param ProjectParticipationContactCreated $participationContactCreated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(ProjectParticipationContactCreated $participationContactCreated)
    {
        $projectParticipationContactId = $participationContactCreated->getProjectParticipationContactId();
        $projectParticipationContact   = $this->projectParticipationContactRepository->find($projectParticipationContactId);

        if (!$projectParticipationContact) {
            throw new InvalidArgumentException(sprintf("The participationContact with id %d doesn't exist anymore", $projectParticipationContactId));
        }

        $this->projectParticipationContactNotifier->notifyContactAdded($projectParticipationContact, true);
    }
}
