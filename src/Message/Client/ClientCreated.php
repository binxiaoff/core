<?php

declare(strict_types=1);

namespace Unilend\Message\Client;

class ClientCreated
{
    /** @var int */
    private $clientId;

    /**
     * @param int $clientId
     */
    public function __construct(int $clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return int
     */
    public function getClientId(): int
    {
        return $this->clientId;
    }
}
