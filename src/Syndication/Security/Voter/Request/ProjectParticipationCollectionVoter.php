<?php

declare(strict_types=1);

namespace Unilend\Syndication\Security\Voter\Request;

use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\Request\ProjectParticipationCollection;
use Unilend\Syndication\Security\Voter\ProjectVoter;

class ProjectParticipationCollectionVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    protected function canCreate(ProjectParticipationCollection $projectParticipationCollection): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $projectParticipationCollection->getProject());
    }
}
