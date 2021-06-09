<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures\Companies;

use Exception;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Team;

class FooCompanyFixtures extends AbstractCompanyFixtures
{
    protected function getName(): string
    {
        return 'foo';
    }

    protected function getTeams(Team $companyRootTeam): array
    {
        $teams = array_map($this->getTeamFactory($companyRootTeam), ['A' => 'A', 'B' => 'B']);
        $teams += array_map($this->getTeamFactory($teams['A']), ['1' => '1']);

        return $teams;
    }

    protected function getAdmins(Company $company): array
    {
        return [];
    }

    /**
     * @throws Exception
     */
    protected function getStaff(Team $team): array
    {
        switch ($team->getName()) {
            case 'A':
                return [
                    $this->createManager($this->getReference('user-a'), $team),
                ];

            case 'B':
                return [
                    $this->createManager($this->getReference('user-e'), $team),
                    $this->createStaff($this->getReference('user-c'), $team),
                ];

            case '1':
                return [
                    $this->createStaff($this->getReference('user-b'), $team)->setAgencyProjectCreationPermission(true),
                    $this->createStaff($this->getReference('user-d'), $team),
                ];
        }

        return [];
    }
}
