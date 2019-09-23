<?php

declare(strict_types=1);

namespace Unilend\Message\Client;

class ClientUpdated
{
    /** @var int */
    private $clientId;
    /** @var array */
    private $changeSet;

    /**
     * @param int   $clientId
     * @param array $changeSet
     */
    public function __construct(int $clientId, array $changeSet)
    {
        $this->clientId  = $clientId;
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
