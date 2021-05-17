<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\Tranche;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class TrancheVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param Tranche $tranche
     */
    protected function isGrantedAll($tranche, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $tranche->getProject())
            && false === $tranche->getProject()->isArchived();
    }
}
