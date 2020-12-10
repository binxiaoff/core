<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\Clients;
use Unilend\Security\Voter\ProjectParticipationVoter;

class MessageThreadVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';

    /**
     * @param mixed   $messageThread
     * @param Clients $user
     *
     * @return bool
     */
    protected function isGrantedAll($messageThread, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $messageThread->getProjectParticipation());
    }
}
