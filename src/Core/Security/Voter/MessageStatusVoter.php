<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\{MessageStatus, User};

class MessageStatusVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';

    /**
     * @param MessageStatus $messageStatus
     * @param User          $user
     *
     * @return bool
     */
    protected function canView(MessageStatus $messageStatus, User $user): bool
    {
        return $this->authorizationChecker->isGranted(MessageVoter::ATTRIBUTE_VIEW, $messageStatus->getMessage());
    }
}
