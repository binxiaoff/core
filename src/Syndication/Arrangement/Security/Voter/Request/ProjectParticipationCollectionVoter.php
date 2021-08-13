<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Security\Voter\Request;

use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Arrangement\Entity\Request\ProjectParticipationCollection;
use KLS\Syndication\Arrangement\Security\Voter\ProjectVoter;

class ProjectParticipationCollectionVoter extends AbstractEntityVoter
{
    protected function canCreate(ProjectParticipationCollection $projectParticipationCollection): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $projectParticipationCollection->getProject());
    }
}
