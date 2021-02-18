<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Team;

class TeamTest extends TestCase
{
    /**
     * @return void
     */
    public function testCreateTeam()
    {
        $company = new Company('company', 'company');

        $name = 'test';
        $createdTeam = Team::createTeam($name, $company->getRootTeam());

        static::assertSame($company->getRootTeam(), $createdTeam->getParent());
        static::assertSame($name, $createdTeam->getName());
        static::assertSame($company->getRootTeam(), $createdTeam->getRoot());
        static::assertSame($company->getRootTeam(), $createdTeam->getParent());
        static::assertCount(0, $createdTeam->getChildren());
        static::assertCount(1, $company->getRootTeam()->getChildren());
    }
}
