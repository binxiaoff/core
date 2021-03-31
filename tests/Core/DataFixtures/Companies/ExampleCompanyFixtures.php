<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures\Companies;

use Exception;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\CompanyAdmin;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;

class ExampleCompanyFixtures extends AbstractCompanyFixtures
{
    /**
     * @return string
     */
    protected function getName(): string
    {
        return 'example';
    }

    /**
     * @param Team $companyRootTeam
     *
     * @return array
     */
    protected function getTeams(Team $companyRootTeam): array
    {
        $teams  = array_map($this->getTeamFactory($companyRootTeam), ['Z' => 'Z', 'Y' => 'Y']);
        $teams += array_map($this->getTeamFactory($teams['Z']), ['9' => '9']);
        $teams += array_map($this->getTeamFactory($teams['9']), ['w' => 'w', 'x' => 'x']);

        return $teams;
    }

    /**
     * @inheritDoc
     */
    protected function getAdmins(Company $company): array
    {
        return array_map(static function (User $user) use ($company) {
            return new CompanyAdmin($user, $company);
        }, [$this->getReference('user:3'), $this->getReference('user:11'), $this->getReference('user:12')]);
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    protected function getStaff(Team $team): array
    {
        switch ($team->getName()) {
            case 'Z':
                return [
                    $this->createManager($this->getReference('user:15'), $team),
                    $this->createManager($this->getReference('user:16'), $team),
                ];
            case 'Y':
                return [
                    $this->createManager($this->getReference('user:17'), $team),
                    $this->createStaff($this->getReference('user:18'), $team),
                ];
            case '9':
                return [
                    $this->createManager($this->getReference('user:19'), $team),
                    $this->createStaff($this->getReference('user:20'), $team),
                ];
            case 'w':
                return [
                    $this->createManager($this->getReference('user:14'), $team),
                    $this->createStaff($this->getReference('user:10'), $team),
                    $this->createStaff($this->getReference('user:9'), $team),
                ];
        }

        return [];
    }
}
