<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Message\Client\ClientUpdated;
use Unilend\Repository\ClientsRepository;
use Unilend\Service\MailerManager;

class ClientUpdatedHandler implements MessageHandlerInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var MailerManager */
    private $mailerManager;

    /**
     * @param ClientsRepository $clientsRepository
     * @param MailerManager     $mailerManager
     */
    public function __construct(ClientsRepository $clientsRepository, MailerManager $mailerManager)
    {
        $this->clientsRepository = $clientsRepository;
        $this->mailerManager     = $mailerManager;
    }

    /**
     * @param ClientUpdated $clientUpdated
     */
    public function __invoke(ClientUpdated $clientUpdated)
    {
        $client    = $this->clientsRepository->find($clientUpdated->getClientId());
        $changeSet = $clientUpdated->getChangeSet();

        if ($client && $changeSet) {
            $this->mailerManager->sendIdentityUpdated($client, array_keys($changeSet));
        }
    }
}
