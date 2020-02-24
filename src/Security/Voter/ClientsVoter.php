<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\Clients;

class ClientsVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';
    public const ATTRIBUTE_EDIT = 'edit';

    /**
     * @param Clients $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function isGrantedAll($subject, Clients $user): bool
    {
        return $subject->getId() === $user->getId();
    }
}
