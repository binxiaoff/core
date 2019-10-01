<?php

declare(strict_types=1);

namespace Unilend\Message\ProjectParticipation;

use Unilend\Entity\ProjectParticipation;

class ProjectParticipantInvited
{
    /** @var int */
    private $projectParticipationId;

    /**
     * @param ProjectParticipation $projectParticipation
     */
    public function __construct(ProjectParticipation $projectParticipation)
    {
        $this->projectParticipationId = $projectParticipation->getId();
    }

    /**
     * @return int
     */
    public function getProjectParticipationId(): int
    {
        return $this->projectParticipationId;
    }
}
