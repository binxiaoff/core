<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\{MessageFile,User};

class MessageFileVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';

    /**
     * @param MessageFile $messageFile
     * @param User  $user
     *
     * @return bool
     */
    protected function isGrantedAll($messageFile, User $user): bool
    {
        return $this->authorizationChecker->isGranted(MessageThreadVoter::ATTRIBUTE_VIEW, $messageFile->getMessage()->getMessageThread());
    }
}
