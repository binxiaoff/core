<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\Clients;

class MessageFileVoter
{
    public const ATTRIBUTE_VIEW = 'view';

    /**
     * @param         $messageFile
     * @param Clients $user
     *
     * @return bool
     */
    protected function isGrantedAll($messageFile, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $messageFile->getMessage()->getMessageThread()->getProjectParticipation());
    }
}
