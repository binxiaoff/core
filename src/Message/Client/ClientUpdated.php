<?php

declare(strict_types=1);

namespace Unilend\Message\Client;

class ClientUpdated
{
    /** @var int */
    private $clientId;
    /** @var string */
    private $content;
    /** @var string */
    private $changeFields;

    /**
     * @param int    $clientId
     * @param string $content
     * @param string $changeFields
     */
    public function __construct(int $clientId, string $content, string $changeFields)
    {
        $this->clientId     = $clientId;
        $this->content      = $content;
        $this->changeFields = $changeFields;
    }

    /**
     * @return int
     */
    public function getClientId(): int
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getChangeFields(): string
    {
        return $this->changeFields;
    }
}
