<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Listener\Doctrine\MessageDispatcher;

use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\PostFlushListener;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Message\ProjectCreated;

class ProjectCreatedListener
{
    private PostFlushListener $postFlushListener;

    public function __construct(PostFlushListener $postFlushListener)
    {
        $this->postFlushListener = $postFlushListener;
    }

    public function postPersist(Project $project): void
    {
        $this->postFlushListener->addMessage(new ProjectCreated($project));
    }
}
