<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\Clients;
use Unilend\Core\Entity\{ClientStatus};

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
