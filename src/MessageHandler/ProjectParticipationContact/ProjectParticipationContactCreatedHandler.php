<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\ProjectParticipationContact;

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
     * @param ProjectParticipationContactCreated $clientCreated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(ProjectParticipationContactCreated $clientCreated)
    {
        $projectParticipationContact = $this->projectParticipationContactRepository->find($clientCreated->getProjectParticipationContactId());
        $arranger                    = $projectParticipationContact->getProjectParticipation()->getProject()->getArranger();

        if (
            $projectParticipationContact
            && $arranger
            && $arranger->getCompany() !== $projectParticipationContact->getProjectParticipation()->getCompany()
        ) {
            $this->projectParticipationContactNotifier->sendInvitation($projectParticipationContact);
        }
    }
}
