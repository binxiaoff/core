<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use DateTime;
use Exception;
use Unilend\Agency\Entity\Term;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class TermVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';

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
        return $this->authorizationChecker->isGranted(CovenantVoter::ATTRIBUTE_VIEW, $term->getCovenant())
            && $term->getStartDate() >= $this->getToday();
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
        if (false === ($term->getStartDate() <= $this->getToday())) {
            return false;
        }

        if ($term->isArchived()) {
            return false;
        }

        if ($term->isShared()) {
            return false;
        }

        // TODO Update for borrowers

        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $term->getCovenant()->getProject());
    }

    /**
     * @param Term $term
     * @param User $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canDelete(Term $term, User $user): bool
    {
        return $this->authorizationChecker->isGranted(CovenantVoter::ATTRIBUTE_EDIT, $term->getCovenant())
            && false === $term->isArchived()
            && $term->isShared()
            && $term->getStartDate() >= $this->getToday();
    }

    /**
     * @return DateTime|false
     */
    private function getToday()
    {
        return (new DateTime())->setTime(0, 0);
    }
}
