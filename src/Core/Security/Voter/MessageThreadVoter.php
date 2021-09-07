<?php

declare(strict_types=1);

namespace KLS\Core\Security\Voter;

use KLS\Core\Entity\MessageThread;
use KLS\Core\Entity\User;
use KLS\Syndication\Arrangement\Security\Voter\ProjectParticipationVoter;

class MessageThreadVoter extends AbstractEntityVoter
{
    protected function canView(MessageThread $messageThread, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $messageThread->getProjectParticipation());
    }
}
