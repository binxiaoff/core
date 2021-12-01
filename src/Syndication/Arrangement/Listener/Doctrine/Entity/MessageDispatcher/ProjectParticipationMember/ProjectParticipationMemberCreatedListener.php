<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Listener\Doctrine\Entity\MessageDispatcher\ProjectParticipationMember;

use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\PostFlushListener;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;
use KLS\Syndication\Arrangement\Message\ProjectParticipationMember\ProjectParticipationMemberCreated;

class ProjectParticipationMemberCreatedListener
{
    private PostFlushListener $postFlushListener;

    public function __construct(PostFlushListener $postFlushListener)
    {
        $this->postFlushListener = $postFlushListener;
    }

    public function postPersist(ProjectParticipationMember $projectParticipationMember): void
    {
        $this->postFlushListener->addMessage(new ProjectParticipationMemberCreated($projectParticipationMember));
    }
}
