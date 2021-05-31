<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Security\Voter;

use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\CreditGuaranty\Entity\Project;

class ProjectVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';

    protected function canCreate(Project $project): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $project->getReservation());
    }

    protected function canView(Project $project): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_VIEW, $project->getReservation());
    }

    protected function canEdit(Project $project): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $project->getReservation());
    }

    protected function canDelete(Project $project): bool
    {
        return $this->authorizationChecker->isGranted(ReservationVoter::ATTRIBUTE_EDIT, $project->getReservation());
    }
}
