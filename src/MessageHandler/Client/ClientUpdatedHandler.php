<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\Client\ClientUpdated;
use Unilend\Repository\ClientsRepository;
use Unilend\Service\Client\ClientNotifier;

class ClientUpdatedHandler implements MessageHandlerInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var ClientNotifier */
    private $clientNotifier;

    /**
     * @param ClientsRepository $clientsRepository
     * @param ClientNotifier    $clientNotifier
     */
    public function __construct(ClientsRepository $clientsRepository, ClientNotifier $clientNotifier)
    {
        $this->clientsRepository = $clientsRepository;
        $this->clientNotifier    = $clientNotifier;
    }

    /**
     * @param ClientUpdated $clientUpdated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(ClientUpdated $clientUpdated)
    {
        $client    = $this->clientsRepository->find($clientUpdated->getClientId());
        $changeSet = $clientUpdated->getChangeSet();

        if ($client && $changeSet) {
            //$this->clientNotifier->sendIdentityUpdated($client, array_keys($changeSet));
        }
    }
}
