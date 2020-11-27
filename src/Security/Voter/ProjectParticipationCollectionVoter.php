<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\Request\ProjectParticipationCollection;

class ProjectParticipationCollectionVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    protected const UNILEND_ENTITY_NAMESPACE = 'Unilend\\Syndication\\Entity\\Request\\';

    /**
     * @param ProjectParticipationCollection $projectParticipationCollection
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipationCollection $projectParticipationCollection): bool
    {
        return $this->authorizationChecker->isGranted('edit', $projectParticipationCollection->getProject());
    }
}
