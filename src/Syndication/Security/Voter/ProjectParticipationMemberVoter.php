<?php

declare(strict_types=1);

namespace Unilend\Syndication\Security\Voter;

use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\{ProjectParticipationMember};

class ProjectParticipationMemberVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_EDIT   = 'edit';

    /**
     * @param ProjectParticipationMember $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $subject->getProjectParticipation());
    }
}
