<?php

declare(strict_types=1);

namespace Unilend\Test\Core\DataFixtures\Companies;

use Exception;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Team;

class FooCompanyFixtures extends AbstractCompanyFixtures
{
    /**
     * {@inheritDoc}
     */
    protected function getName(): string
    {
        return 'foo';
    }

    /**
     * {@inheritDoc}
     */
    protected function getTeams(Team $companyRootTeam)
    {
        $teams = array_map($this->getTeamFactory($companyRootTeam), ['A' => 'A']);
        $teams += array_map($this->getTeamFactory($teams['A']), ['1' => '1']);

        return $teams;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAdmins(Company $company): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    protected function getStaff(Team $team): array
    {
        switch ($team->getName()) {
            case 'A':
                return [
                    $this->createManager($this->getReference('user:a'), $team),
                ];

            case '1':
                return [
                    $this->createStaff($this->getReference('user:b'), $team),
                    $this->createStaff($this->getReference('user:c'), $team),
                ];
        }

        return [];
    }
}
