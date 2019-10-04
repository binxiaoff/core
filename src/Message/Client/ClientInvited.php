<?php

declare(strict_types=1);

namespace Unilend\Message\Client;

use Unilend\Entity\ProjectParticipationContact;

class ClientInvited
{
    private $projectParticipationContactId;

    /**
     * @param ProjectParticipationContact $projectParticipationContact
     */
    public function __construct(ProjectParticipationContact $projectParticipationContact)
    {
        $this->projectParticipationContactId = $projectParticipationContact->getId();
    }

    /**
     * @return int
     */
    public function getProjectParticipationContactId(): int
    {
        return $this->projectParticipationContactId;
    }
}
