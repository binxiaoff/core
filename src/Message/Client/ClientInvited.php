<?php

declare(strict_types=1);

namespace Unilend\Message\Client;

class ClientInvited
{
    /** @var int */
    private $inviterId;
    /** @var int */
    private $guestId;

    /**
     * @param int $inviterId
     * @param int $guestId
     */
    public function __construct(int $inviterId, int $guestId)
    {
        $this->inviterId = $inviterId;
        $this->guestId   = $guestId;
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
