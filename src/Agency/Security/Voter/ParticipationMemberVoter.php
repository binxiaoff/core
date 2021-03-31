<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\ParticipationMember;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class ParticipationMemberVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param ParticipationMember $participationMember
     * @param User                $user
     *
     * @return bool
     */
    protected function isGrantedAll($participationMember, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ParticipationVoter::ATTRIBUTE_EDIT, $participationMember->getParticipation())
            || $participationMember->getParticipation()->findMemberByUser($user);
    }
}
