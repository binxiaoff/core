<?php

declare(strict_types=1);

namespace Unilend\Syndication\Message\Project;

use Unilend\Core\Message\AsyncMessageInterface;
use Unilend\Syndication\Entity\Project;

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
