<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Security\Voter;

use DateTime;
use Exception;
use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Agency\Entity\Term;

class TermVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_AGENT = 'agent';

    /**
     * @throws Exception
     */
    protected function canView(Term $term, User $user): bool
    {
        return $this->authorizationChecker->isGranted(CovenantVoter::ATTRIBUTE_VIEW, $term->getCovenant())
            && $term->getStartDate() <= $this->getToday()
            && (
                $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $term->getProject())
                || $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $term->getProject())
                || $term->isShared()
            );
    }

    /**
     * @throws Exception
     */
    protected function canEdit(Term $term, User $user): bool
    {
        if (false === ($term->getStartDate() <= $this->getToday())) {
            return false;
        }

        if ($term->isArchived()) {
            return false;
        }

        if (false === $term->getProject()->isEditable()) {
            return false;
        }

        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $term->getProject())
            || ($this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $term->getProject()) && $term->isPendingBorrowerInput());
    }

    /**
     * @throws Exception
     */
    protected function canDelete(Term $term, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $term->getProject())
            && false === $term->isArchived()
            && $term->isShared()
            && $term->getStartDate() <= $this->getToday()
            && $term->getProject()->isEditable();
    }

    protected function canAgent(Term $term, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $term->getProject());
    }

    /**
     * @return DateTime|false
     */
    private function getToday()
    {
        return (new DateTime())->setTime(0, 0);
    }
}
