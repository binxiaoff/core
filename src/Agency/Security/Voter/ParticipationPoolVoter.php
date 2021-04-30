<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Unilend\Agency\Entity\ParticipationPool;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class ParticipationPoolVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_EDIT = 'edit';
    public const ATTRIBUTE_VIEW = 'view';

    public function canEdit(ParticipationPool $participationPool): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $participationPool->getProject());
    }

    public function canView(ParticipationPool $participationPool): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $participationPool->getProject());
    }
}
