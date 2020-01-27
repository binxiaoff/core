<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\Client;

use Unilend\Entity\Clients;
use Unilend\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Message\Client\ClientCreated;

class ClientCreatedListener
{
    use MessageDispatcherTrait;

    /**
     * @param Clients $clients
     */
    public function postPersist(Clients $clients): void
    {
        $this->messageBus->dispatch(new ClientCreated($clients));
    }
}
