<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\Unit\Service;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;
use Unilend\Core\Model\Bitmask;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\CreditGuaranty\Repository\StaffPermissionRepository;
use Unilend\CreditGuaranty\Service\StaffLoginChecker;

/**
 * @coversDefaultClass \Unilend\CreditGuaranty\Service\StaffLoginChecker
 *
 * @internal
 */
class StaffLoginCheckerTest extends TestCase
{
    /** @var StaffPermissionRepository|ObjectProphecy */
    private $staffPermissionRepository;

    protected function setUp(): void
    {
        $this->staffPermissionRepository = $this->prophesize(StaffPermissionRepository::class);
    }

    protected function tearDown(): void
    {
        $this->staffPermissionRepository = null;
    }

    /**
     * @covers ::isGrantedLogin
     */
    public function testIsGrantedLogin(): void
    {
        $staff           = $this->createStaff();
        $staffPermission = new StaffPermission(
            $staff,
            new Bitmask(StaffPermission::PERMISSION_CREATE_PROGRAM)
        );

        $this->staffPermissionRepository->findOneBy(['staff' => $staff])->shouldBeCalledOnce()->willReturn($staffPermission);

        static::assertTrue($this->createTestObject()->isGrantedLogin($staff));
    }

    /**
     * @covers ::isGrantedLogin
     */
    public function testIsNotGrantedLogin(): void
    {
        $staff = $this->createStaff();

        $this->staffPermissionRepository->findOneBy(['staff' => $staff])->shouldBeCalledOnce()->willReturn(null);

        static::assertFalse($this->createTestObject()->isGrantedLogin($staff));
    }

    private function createStaff(): Staff
    {
        $teamRoot = Team::createRootTeam(new Company('Company', 'Company', ''));
        $team     = Team::createTeam('Team', $teamRoot);

        return new Staff(new User('user@mail.com'), $team);
    }

    private function createTestObject(): StaffLoginChecker
    {
        return new StaffLoginChecker(
            $this->staffPermissionRepository->reveal()
        );
    }
}
