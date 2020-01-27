<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\ProjectParticipationContact;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\ProjectParticipationContact\ProjectParticipationContactCreated;
use Unilend\Repository\ProjectParticipationContactRepository;
use Unilend\Service\{Client\ClientNotifier, MailerManager};

class ProjectParticipationContactCreatedHandler implements MessageHandlerInterface
{
    /** @var MailerManager */
    private $mailerManager;
    /** @var ProjectParticipationContactRepository */
    private $projectParticipationContactRepository;
    /** @var ClientNotifier */
    private $clientNotifier;

    /**
     * @param MailerManager                         $mailerManager
     * @param ClientNotifier                        $clientNotifier
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     */
    public function __construct(
        MailerManager $mailerManager,
        ClientNotifier $clientNotifier,
        ProjectParticipationContactRepository $projectParticipationContactRepository
    ) {
        $this->mailerManager                         = $mailerManager;
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
        $this->clientNotifier                        = $clientNotifier;
    }

    /**
     * @param ProjectParticipationContactCreated $clientCreated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function __invoke(ProjectParticipationContactCreated $clientCreated)
    {
        $projectParticipationContact = $this->projectParticipationContactRepository->find($clientCreated->getProjectParticipationContactId());

        if ($projectParticipationContact) {
            $this->mailerManager->sendRequestToAssignRights($projectParticipationContact);
            $this->clientNotifier->notifyInvited(
                $projectParticipationContact->getAddedBy(),
                $projectParticipationContact->getClient(),
                $projectParticipationContact->getProjectParticipation()->getProject()
            );
        }
    }
}
