<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Message\Client\ClientCreated;
use Unilend\Repository\ClientsRepository;
use Unilend\Service\MailerManager;
use Unilend\Service\NotificationManager;

class ClientCreatedHandler implements MessageHandlerInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var NotificationManager */
    private $notificationManager;
    /**
     * @var MailerManager
     */
    private $mailerManager;

    /**
     * @param ClientsRepository   $clientsRepository
     * @param NotificationManager $notificationManager
     * @param MailerManager       $mailerManager
     */
    public function __construct(
        ClientsRepository $clientsRepository,
        NotificationManager $notificationManager,
        MailerManager $mailerManager
    ) {
        $this->clientsRepository   = $clientsRepository;
        $this->notificationManager = $notificationManager;
        $this->mailerManager       = $mailerManager;
    }

    /**
     * @param ClientCreated $clientCreated
     */
    public function __invoke(ClientCreated $clientCreated)
    {
        $client = $this->clientsRepository->find($clientCreated->getClientId());

        if ($client) {
            $this->mailerManager->sendAccountCreated($client);
            $this->notificationManager->createAccountCreated($client);
        }
    }
}
