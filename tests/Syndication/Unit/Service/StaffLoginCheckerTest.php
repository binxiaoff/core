<?php

declare(strict_types=1);

namespace Unilend\Test\Syndication\Unit\Service;

use PHPUnit\Framework\TestCase;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\CompanyGroup;
use Unilend\Core\Entity\CompanyStatus;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Service\StaffLoginChecker;

/**
 * @coversDefaultClass \Unilend\Syndication\Service\StaffLoginChecker
 *
 * @internal
 */
class StaffLoginCheckerTest extends TestCase
{
    /**
     * @covers ::isGrantedLogin
     *
     * @dataProvider grantedProvider
     */
    public function testIsGrantedLogin(Staff $staff): void
    {
        $checker = new StaffLoginChecker();
        static::assertTrue($checker->isGrantedLogin($staff));
    }

    public function grantedProvider(): iterable
    {
        yield 'staff CA member and signed' => [$this->createStaff(null, CompanyStatus::STATUS_SIGNED)];
        yield 'staff not CA member and signed' => [$this->createStaff(new CompanyGroup('Company Group')), CompanyStatus::STATUS_SIGNED];
    }

    /**
     * @covers ::isGrantedLogin
     */
    public function testIsNotGrantedLogin(): void
    {
        $staff   = $this->createStaff();
        $checker = new StaffLoginChecker();
        static::assertFalse($checker->isGrantedLogin($staff));
    }

    private function createStaff(?CompanyGroup $companyGroup = null, ?int $companyStatus = null): Staff
    {
        $company = new Company('Company', 'Company', '');
        $company->setCompanyGroup($companyGroup ?? new CompanyGroup(CompanyGroup::COMPANY_GROUP_CA));

        if (null !== $companyStatus) {
            $company->setCurrentStatus(new CompanyStatus($company, $companyStatus));
        }

        $teamRoot = Team::createRootTeam($company);
        $team     = Team::createTeam('Team', $teamRoot);

        return new Staff(new User('user@mail.com'), $team);
    }
}
