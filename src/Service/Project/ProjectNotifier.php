<?php

declare(strict_types=1);

namespace Unilend\Service\Project;

use Psr\Log\{LogLevel, LoggerInterface};
use Unilend\Entity\{Project};

class ProjectNotifier
{
    /** @var LoggerInterface */
    private $supportLogger;

    /**
     * @param LoggerInterface $supportLogger
     */
    public function __construct(LoggerInterface $supportLogger)
    {
        $this->supportLogger = $supportLogger;
    }

    /**
     * @param Project $project
     */
    public function notifyProjectCreated(Project $project)
    {
        $title = $project->getTitle();

        $this->supportLogger->log(LogLevel::INFO, "Le dossier {$title} vient d'être créé", [
            'utilisateur' => $project->getSubmitterClient()->getEmail(),
            'entité'      => $project->getSubmitterCompany()->getName(),
        ]);
    }
}
