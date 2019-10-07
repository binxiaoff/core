<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Message\Client\ClientCreated;
use Unilend\Repository\ProjectParticipationContactRepository;
use Unilend\Service\MailerManager;
use Unilend\Service\NotificationManager;

class ClientCreatedHandler implements MessageHandlerInterface
{
    /** @var NotificationManager */
    private $notificationManager;
    /** @var MailerManager */
    private $mailerManager;
    /** @var ProjectParticipationContactRepository */
    private $projectParticipationContactRepository;

    /**
     * @param NotificationManager                   $notificationManager
     * @param MailerManager                         $mailerManager
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     */
    public function __construct(
        NotificationManager $notificationManager,
        MailerManager $mailerManager,
        ProjectParticipationContactRepository $projectParticipationContactRepository
    ) {
        $this->notificationManager                   = $notificationManager;
        $this->mailerManager                         = $mailerManager;
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
    }

    /**
     * @param ClientCreated $clientCreated
     */
    public function __invoke(ClientCreated $clientCreated)
    {
        $projectParticipationContact = $this->projectParticipationContactRepository->find($clientCreated->getProjectParticipationContactId());

        if ($projectParticipationContact) {
            $client = $projectParticipationContact->getClient();

            $this->mailerManager->sendAccountCreated($client);
            $this->mailerManager->sendRequestToAssignRights($projectParticipationContact);
            $this->notificationManager->createAccountCreated($client);
        }
    }
}
