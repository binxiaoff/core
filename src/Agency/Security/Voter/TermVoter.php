<?php

namespace Unilend\Agency\Security\Voter;

use Exception;
use Unilend\Agency\Entity\Covenant;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Entity\Term;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class TermVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW                 = 'view';
    public const ATTRIBUTE_EDIT                 = 'edit';

    /**
     * @param Term $term
     * @param User $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canView(Term $term, User $user): bool
    {
        return $this->authorizationChecker->isGranted(CovenantVoter::ATTRIBUTE_VIEW, $term->getCovenant());
    }

    /**
     * @param Term $term
     * @param User $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canEdit(Term $term, User $user): bool
    {
        return $this->authorizationChecker->isGranted(CovenantVoter::ATTRIBUTE_EDIT, $term->getCovenant());
    }
}
