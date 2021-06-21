<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures\Companies;

use Exception;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\CompanyAdmin;
use Unilend\Core\Entity\CompanyGroup;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;

class BasicCompanyFixtures extends AbstractCompanyFixtures
{
    protected function getName(): string
    {
        return 'basic';
    }

    protected function getTeams(Team $companyRootTeam): array
    {
        $teams = array_map($this->getTeamFactory($companyRootTeam), ['A' => 'A', 'B' => 'B']);
        $teams += array_map($this->getTeamFactory($teams['A']), ['1' => '1', '2' => '2']);
        $teams += array_map($this->getTeamFactory($teams['B']), ['3' => '3', '4' => '4']);
        $teams += array_map($this->getTeamFactory($teams['1']), ['c' => 'c', 'd' => 'd']);
        $teams += array_map($this->getTeamFactory($teams['c']), ['.' => '.', '%' => '%', '£' => '£']);

        return $teams;
    }

    protected function getAdmins(Company $company): array
    {
        return array_map(static function (User $user) use ($company) {
            return new CompanyAdmin($user, $company);
        }, [$this->getReference('user-1'), $this->getReference('user-11'), $this->getReference('user-12')]);
    }

    /**
     * @throws Exception
     */
    protected function getStaff(Team $team): array
    {
        switch ($team->getName()) {
            case 'A':
                return [
                    $this->createManager($this->getReference('user-1'), $team),
                    $this->createManager($this->getReference('user-5'), $team),
                    $this->createStaff($this->getReference('user-6'), $team),
                    $this->createStaff($this->getReference('user-7'), $team),
                ];

            case '1':
                return [
                    $this->createManager($this->getReference('user-2'), $team),
                    $this->createStaff($this->getReference('user-8'), $team),
                    $this->createStaff($this->getReference('user-9'), $team),
                ];

            case 'c':
                return [
                    $this->createManager($this->getReference('user-3'), $team),
                ];

            case '.':
                return [
                    $this->createManager($this->getReference('user-4'), $team),
                    $this->createStaff($this->getReference('user-10'), $team),
                ];

            case 'B':
                return [
                    $this->createManager($this->getReference('user-11'), $team),
                    $this->createStaff($this->getReference('user-12'), $team),
                ];
        }

        return [];
    }

    protected function getCompanyGroup(): ?CompanyGroup
    {
        return $this->getReference('companyGroup:foo');
    }
}
