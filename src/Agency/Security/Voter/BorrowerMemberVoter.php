<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\BorrowerMember;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class BorrowerMemberVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param BorrowerMember $borrowerMember
     */
    protected function isGrantedAll($borrowerMember, User $user): bool
    {
        return $this->authorizationChecker->isGranted(BorrowerVoter::ATTRIBUTE_EDIT, $borrowerMember->getBorrower());
    }
}
