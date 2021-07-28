<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;

class TeamVoter extends AbstractEntityVoter
{
    public function canCreate(Team $team, User $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        if (null === $submitterStaff) {
            return false;
        }

        if (false === $submitterStaff->isManager()) {
            return false;
        }

        $parent = $team->getParent();

        if (null === $parent) {
            return false;
        }

        return $submitterStaff->getTeam() === $parent || \in_array($submitterStaff->getTeam(), $parent->getAncestors(), true);
    }

    public function canEdit(Team $team, User $user): bool
    {
        $submitterStaff = $user->getCurrentStaff();

        if (null === $submitterStaff) {
            return false;
        }

        if (false === $submitterStaff->isManager()) {
            return false;
        }

        return $submitterStaff->getTeam() === $team || \in_array($submitterStaff->getTeam(), $team->getAncestors(), true);
    }
}
