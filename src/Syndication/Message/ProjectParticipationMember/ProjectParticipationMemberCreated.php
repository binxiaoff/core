<?php

declare(strict_types=1);

namespace Unilend\Syndication\Message\ProjectParticipationMember;

use Unilend\Core\Message\AsyncMessageInterface;
use Unilend\Syndication\Entity\ProjectParticipationMember;

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
