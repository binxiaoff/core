<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\{MessageThread, User};
use Unilend\Syndication\Security\Voter\ProjectParticipationVoter;

class MessageThreadVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';

    /**
     * @param MessageThread $messageThread
     * @param User          $user
     *
     * @return bool
     */
    protected function isGrantedAll($messageThread, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $messageThread->getProjectParticipation());
    }
}
