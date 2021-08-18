<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Traits;

use KLS\Core\Entity\Company;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Team;
use KLS\Core\Entity\User;

trait StaffTrait
{
    private function createStaff(): Staff
    {
        $teamRoot = Team::createRootTeam(new Company('Company', 'Company', ''));
        $team     = Team::createTeam('Team', $teamRoot);

        return new Staff(new User('user@mail.com'), $team);
    }
}
