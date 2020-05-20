<?php

declare(strict_types=1);

namespace Unilend\Message\Client;

use Unilend\Entity\Clients;
use Unilend\Message\AsyncMessageInterface;

class ClientUpdated implements AsyncMessageInterface
{
    /** @var int */
    private $clientId;
    /** @var array */
    private $changeSet;

    /**
     * @param Clients $client
     * @param array   $changeSet
     */
    public function __construct(Clients $client, array $changeSet)
    {
        $this->clientId  = $client->getId();
        $this->changeSet = $changeSet;
    }

    /**
     * @return array
     */
    public function getChangeSet(): array
    {
        return $this->changeSet;
    }

    /**
     * @return int
     */
    public function getClientId(): int
    {
        return $this->clientId;
    }
}
