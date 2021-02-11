<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\Covenant;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class CovenantVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param Covenant $covenant
     * @param User     $user
     *
     * @return bool
     */
    protected function isGrantedAll($covenant, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $covenant->getProject());
    }
}
