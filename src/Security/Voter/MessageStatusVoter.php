<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\{Clients, MessageStatus};

class MessageStatusVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';

    /**
     * @param MessageStatus $messageStatus
     * @param Clients       $user
     *
     * @return bool
     */
    protected function canView(MessageStatus $messageStatus, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted(MessageVoter::ATTRIBUTE_VIEW, $messageStatus->getMessage());
    }
}
