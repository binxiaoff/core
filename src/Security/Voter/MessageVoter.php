<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\Clients;

class MessageVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';

    public const ATTRIBUTE_CREATE = 'create';

    public const ATTRIBUTE_ATTACH_FILE = 'attach_file';

    /**
     * @param mixed   $message
     * @param Clients $user
     *
     * @return bool
     */
    protected function isGrantedAll($message, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted(MessageThreadVoter::ATTRIBUTE_VIEW, $message->getMessageThread());
    }
}
