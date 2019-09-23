<?php

declare(strict_types=1);

namespace Unilend\Message\Client;

class ClientInvited
{
    private $projectInvitationId;

    /**
     * @param int $invitationId
     */
    public function __construct(int $invitationId)
    {
        $this->projectInvitationId = $invitationId;
    }

    /**
     * @return int
     */
    public function getProjectInvitationId(): int
    {
        return $this->projectInvitationId;
    }
}
