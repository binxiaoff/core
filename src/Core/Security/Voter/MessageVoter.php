<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\Message;
use Unilend\Core\Entity\User;

class MessageVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_ATTACH_FILE = 'attach_file';

    protected function canCreate(Message $message, User $user): bool
    {
        return $this->authorizationChecker->isGranted(MessageThreadVoter::ATTRIBUTE_VIEW, $message->getMessageThread());
    }

    protected function canView(Message $message, User $user): bool
    {
        return $this->authorizationChecker->isGranted(MessageThreadVoter::ATTRIBUTE_VIEW, $message->getMessageThread());
    }

    protected function canAttachFile(Message $message, User $user): bool
    {
        return $message->getSender()->getUser() === $user;
    }
}
