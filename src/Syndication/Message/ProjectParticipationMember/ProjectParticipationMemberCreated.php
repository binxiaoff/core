<?php

declare(strict_types=1);

namespace KLS\Syndication\Message\ProjectParticipationMember;

use KLS\Core\Message\AsyncMessageInterface;
use KLS\Syndication\Entity\ProjectParticipationMember;

class ProjectParticipationMemberCreated implements AsyncMessageInterface
{
    private ?int $projectParticipationMemberId;

    public function __construct(ProjectParticipationMember $projectParticipationMember)
    {
        $this->projectParticipationMemberId = $projectParticipationMember->getId();
    }

    public function getProjectParticipationMemberId(): int
    {
        return $this->projectParticipationMemberId;
    }
}
