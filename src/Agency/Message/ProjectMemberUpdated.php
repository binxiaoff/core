<?php

declare(strict_types=1);

namespace Unilend\Agency\Message;

use Unilend\Agency\Entity\AbstractProjectMember;

class ProjectMemberUpdated
{
    private int $projectMemberId;

    private array $changeSet;

    private string $projectMemberClass;

    public function __construct(AbstractProjectMember $projectMember, array $changeSet = [])
    {
        $this->projectMemberId    = $projectMember->getId();
        $this->projectMemberClass = \get_class($projectMember);
        $this->changeSet          = $changeSet;
    }

    public function getProjectMemberId(): ?int
    {
        return $this->projectMemberId;
    }

    public function getChangeSet(): array
    {
        return $this->changeSet;
    }

    public function getProjectMemberClass(): string
    {
        return $this->projectMemberClass;
    }
}
