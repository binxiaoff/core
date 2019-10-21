<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\ProjectParticipationContact;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Repository\ProjectParticipationContactRepository;
use Unilend\Service\MailerManager;

class ProjectParticipationContactCreatedHandler implements MessageHandlerInterface
{
    /** @var MailerManager */
    private $mailerManager;
    /** @var ProjectParticipationContactRepository */
    private $projectParticipationContactRepository;

    /**
     * @param MailerManager                         $mailerManager
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     */
    public function __construct(
        MailerManager $mailerManager,
        ProjectParticipationContactRepository $projectParticipationContactRepository
    ) {
        $this->mailerManager                         = $mailerManager;
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
    }

    /**
     * @param \Unilend\Message\ProjectParticipationContact\ProjectParticipationContactCreated $clientCreated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(ProjectParticipationContactCreatedHandler $clientCreated)
    {
        $projectParticipationContact = $this->projectParticipationContactRepository->find($clientCreated->getProjectParticipationContactId());

        if ($projectParticipationContact) {
            $this->mailerManager->sendRequestToAssignRights($projectParticipationContact);
        }
    }
}
