<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Security\Voter;

use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\CreditGuaranty\FEI\Entity\Project;

class ProjectVoter extends AbstractEntityVoter
{
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
