<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\Project;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use HTMLPurifier;
use Unilend\Entity\Project;

class ProjectUpdatedListener
{
    /** @var HTMLPurifier */
    private $htmlPurifier;

    /**
     * @param HTMLPurifier $htmlPurifier
     */
    public function __construct(HTMLPurifier $htmlPurifier)
    {
        $this->htmlPurifier = $htmlPurifier;
    }

    /**
     * @param Project            $project
     * @param PreUpdateEventArgs $args
     */
    public function purify(Project $project, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField(Project::FIELD_DESCRIPTION) && $args->getNewValue(Project::FIELD_DESCRIPTION)) {
            $args->setNewValue(Project::FIELD_DESCRIPTION, $this->htmlPurifier->purify($args->getNewValue(Project::FIELD_DESCRIPTION)));
        }
    }
}
