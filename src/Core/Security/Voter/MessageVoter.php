<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\{Message, User};

class MessageVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';

    public const ATTRIBUTE_CREATE = 'create';

    public const ATTRIBUTE_ATTACH_FILE = 'attach_file';

    /**
     * @param Message $message
     * @param User    $user
     *
     * @return bool
     */
    protected function canView($message, User $user): bool
    {
        return $this->authorizationChecker->isGranted(MessageThreadVoter::ATTRIBUTE_VIEW, $message->getMessageThread());
    }

    /**
     * @param Message $message
     * @param User    $user
     *
     * @return bool
     */
    protected function canCreate($message, User $user): bool
    {
        return $this->authorizationChecker->isGranted(MessageThreadVoter::ATTRIBUTE_VIEW, $message->getMessageThread());
    }

    /**
     * @param Message $message
     * @param User    $user
     *
     * @return bool
     */
    protected function canAttachFile($message, User $user): bool
    {
        return $message->getSender()->getUser() === $user;
    }
}
