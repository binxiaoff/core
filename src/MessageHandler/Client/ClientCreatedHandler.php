<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\Client\ClientCreated;
use Unilend\Repository\ClientsRepository;
use Unilend\Service\{Client\ClientNotifier, NotificationManager};

class ClientCreatedHandler implements MessageHandlerInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var NotificationManager */
    private $notificationManager;
    /** @var ClientNotifier */
    private $clientNotifier;

    /**
     * @param ClientsRepository   $clientsRepository
     * @param NotificationManager $notificationManager
     * @param ClientNotifier      $clientNotifier
     */
    public function __construct(ClientsRepository $clientsRepository, NotificationManager $notificationManager, ClientNotifier $clientNotifier)
    {
        $this->clientsRepository   = $clientsRepository;
        $this->notificationManager = $notificationManager;
        $this->clientNotifier      = $clientNotifier;
    }

    /**
     * @param ClientCreated $clientCreated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(ClientCreated $clientCreated)
    {
        $client = $this->clientsRepository->find($clientCreated->getClientId());

        if ($client) {
            $this->clientNotifier->sendAccountCreated($client);
            $this->notificationManager->createAccountCreated($client);
        }
    }
}
