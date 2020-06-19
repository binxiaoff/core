<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\{ClientStatus, Clients};

class ClientStatusVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';

    /**
     * @param ClientStatus $clientStatus
     * @param Clients      $user
     *
     * @return bool
     */
    protected function canView(ClientStatus $clientStatus, Clients $user): bool
    {
        return $user === $clientStatus->getClient();
    }
}
