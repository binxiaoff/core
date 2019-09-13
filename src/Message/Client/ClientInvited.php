<?php

declare(strict_types=1);

namespace Unilend\Message\Client;

class ClientInvited
{
    /** @var int */
    private $inviterId;

    /** @var int */
    private $guestId;

    /** @var int */
    private $projectId;

    /**
     * @param int $inviterId
     * @param int $guestId
     * @param int $projectId
     */
    public function __construct(int $inviterId, int $guestId, int $projectId)
    {
        $this->inviterId = $inviterId;
        $this->guestId   = $guestId;
        $this->projectId = $projectId;
    }

    /**
     * @return int
     */
    public function getProjectId(): int
    {
        return $this->projectId;
    }

    /**
     * @return int
     */
    public function getInviterId(): int
    {
        return $this->inviterId;
    }

    /**
     * @return int
     */
    public function getGuestId(): int
    {
        return $this->guestId;
    }
}
