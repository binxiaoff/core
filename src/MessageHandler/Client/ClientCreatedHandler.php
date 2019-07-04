<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Message\Client\ClientCreated;
use Unilend\Repository\ClientsRepository;
use Unilend\Service\NotificationManager;

class ClientCreatedHandler implements MessageHandlerInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var NotificationManager */
    private $notificationManager;

    /**
     * @param ClientsRepository   $clientsRepository
     * @param NotificationManager $notificationManager
     */
    public function __construct(ClientsRepository $clientsRepository, NotificationManager $notificationManager)
    {
        $this->clientsRepository   = $clientsRepository;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @param ClientCreated $clientCreated
     */
    public function __invoke(ClientCreated $clientCreated)
    {
        $client = $this->clientsRepository->find($clientCreated->getClientId());

        $this->notificationManager->createAccountCreated($client);
    }
}
