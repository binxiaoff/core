<?php

declare(strict_types=1);

namespace KLS\Syndication\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Entity\ProjectParticipationMember;

class ProjectParticipationMemberVoter extends AbstractEntityVoter
{
    /**
     * @param ProjectParticipationMember $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $subject->getProjectParticipation());
    }
}
