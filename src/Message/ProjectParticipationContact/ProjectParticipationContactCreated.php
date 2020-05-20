<?php

declare(strict_types=1);

namespace Unilend\Message\ProjectParticipationContact;

use Unilend\Entity\ProjectParticipationContact;
use Unilend\Message\AsyncMessageInterface;

class ProjectParticipationContactCreated implements AsyncMessageInterface
{
    /** @var int */
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
