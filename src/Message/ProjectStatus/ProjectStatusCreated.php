<?php

declare(strict_types=1);

namespace Unilend\Message\ProjectStatus;

use Unilend\Entity\ProjectStatus;

class ProjectStatusCreated
{
    /** @var int */
    private $projectStatusId;

    /**
     * @param ProjectStatus $projectStatus
     */
    public function __construct(ProjectStatus $projectStatus)
    {
        $this->projectStatusId = $projectStatus->getId();
    }

    /**
     * @return int
     */
    public function getProjectStatusId(): int
    {
        return $this->projectStatusId;
    }
}
