<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\MessageThread;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Security\Voter\ProjectParticipationVoter;

class MessageThreadVoter extends AbstractEntityVoter
{
    protected function canView(MessageThread $messageThread, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $messageThread->getProjectParticipation());
    }
}
