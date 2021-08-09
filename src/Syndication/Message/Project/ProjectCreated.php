<?php

declare(strict_types=1);

namespace KLS\Syndication\Message\Project;

use KLS\Core\Message\AsyncMessageInterface;
use KLS\Syndication\Entity\Project;

class ProjectCreated implements AsyncMessageInterface
{
    private $projectId;

    public function __construct(Project $project)
    {
        $this->projectId = $project->getId();
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }
}
