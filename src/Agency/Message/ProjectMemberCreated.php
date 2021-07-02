<?php

declare(strict_types=1);

namespace Unilend\Agency\Message;

use Unilend\Agency\Entity\AbstractProjectMember;
use Unilend\Core\Message\AsyncMessageInterface;

class ProjectMemberCreated implements AsyncMessageInterface
{
    private int $projectMemberId;

    // Class must inherit of AbstractProjectMember (AgentMember, ParticipationMember and BorrowerMember)
    private string $projectMemberClass;

    public function __construct(AbstractProjectMember $projectMember)
    {
        $this->projectMemberId    = $projectMember->getId();
        $this->projectMemberClass = \get_class($projectMember);
    }

    public function getProjectMemberId(): ?int
    {
        return $this->projectMemberId;
    }

    public function getProjectMemberClass(): string
    {
        return $this->projectMemberClass;
    }
}
