<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity;

use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Entity\Clients;
use Unilend\Message\Client\ClientCreated as Message;

class ClientCreated
{
    /** @var MessageBusInterface */
    private $messageBus;

    /**
     * @param MessageBusInterface $messageBus
     */
    public function __construct(
        MessageBusInterface $messageBus
    ) {
        $this->messageBus = $messageBus;
    }

    /**
     * @param Clients $clients
     */
    public function postPersist(Clients $clients): void
    {
        $this->messageBus->dispatch(new Message($clients));
    }
}
