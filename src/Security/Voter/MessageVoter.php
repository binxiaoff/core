<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\Clients;
use Unilend\Security\Voter\ProjectParticipationVoter;

class MessageVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';

    /**
     * @param mixed   $message
     * @param Clients $user
     *
     * @return bool
     */
    protected function isGrantedAll($message, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $message->getMessageThread()->getProjectParticipation());
    }
}
