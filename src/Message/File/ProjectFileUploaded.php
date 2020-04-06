<?php

declare(strict_types=1);

namespace Unilend\Message\File;

use Unilend\Entity\{File, Project};

class ProjectFileUploaded
{
    /** @var int */
    private $fileId;
    /** @var int */
    private $projectId;

    /**
     * @param File    $file
     * @param Project $project
     */
    public function __construct(File $file, Project $project)
    {
        $this->fileId    = $file->getId();
        $this->projectId = $project->getId();
    }

    /**
     * @return int
     */
    public function getFileVersionId(): int
    {
        return $this->fileId;
    }

    /**
     * @return int
     */
    public function getProjectId(): int
    {
        return $this->projectId;
    }
}
