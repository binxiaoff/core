<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity;

use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Entity\ProjectParticipation;
use Unilend\Message\ProjectParticipation\ProjectParticipantInvited;

class ProjectParticipationInvitedListener
{
    /** @var MessageBusInterface */
    private $messageBus;

    /**
     * @param MessageBusInterface $messageBus
     */
    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     */
    public function dispatchParticipantInvitedEvent(ProjectParticipation $projectParticipation): void
    {
        if ($projectParticipation->isParticipant()) {
            $this->messageBus->dispatch(new ProjectParticipantInvited($projectParticipation));
        }
    }
}
