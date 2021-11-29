<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\Unit\Service;

use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyGroup;
use KLS\Core\Entity\CompanyStatus;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Team;
use KLS\Core\Entity\User;
use KLS\Syndication\Arrangement\Service\StaffLoginChecker;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \KLS\Syndication\Arrangement\Service\StaffLoginChecker
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
        yield 'staff CA member and signed' => [
            $this->createStaff(null, CompanyStatus::STATUS_SIGNED),
        ];
        yield 'staff not CA member and signed' => [
            $this->createStaff(new CompanyGroup('Company Group'), CompanyStatus::STATUS_SIGNED),
        ];
        yield 'staff CA member and not signed' => [
            $this->createStaff(null, CompanyStatus::STATUS_PROSPECT),
        ];
    }

    private function createStaff(?CompanyGroup $companyGroup = null, ?int $companyStatus = null): Staff
    {
        $company = new Company('Company', '');
        $company->setCompanyGroup($companyGroup ?? new CompanyGroup(CompanyGroup::COMPANY_GROUP_CA));

        if (null !== $companyStatus) {
            $company->setCurrentStatus(new CompanyStatus($company, $companyStatus));
        }

        $teamRoot = Team::createRootTeam($company);
        $team     = Team::createTeam('Team', $teamRoot);

        return new Staff(new User('user@mail.com'), $team);
    }
}
