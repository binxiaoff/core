<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\Participation;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class ParticipationVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_DELETE = 'delete';

    protected function canDelete(Participation $participation, User $user)
    {
        $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $participation->getProject()) && false === $participation->isAgent();
    }

    /**
     * @param Participation $participation
     */
    protected function isGrantedAll($participation, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $participation->getProject());
    }
}
