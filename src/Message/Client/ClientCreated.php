<?php

declare(strict_types=1);

namespace Unilend\Message\Client;

use Unilend\Entity\Clients;

class ClientCreated
{
    /** @var int */
    private $clientId;

    /**
     * @param Clients $client
     */
    public function __construct(Clients $client)
    {
        $this->clientId = $client->getIdClient();
    }

    /**
     * @return int
     */
    public function getClientId(): int
    {
        return $this->clientId;
    }
}
