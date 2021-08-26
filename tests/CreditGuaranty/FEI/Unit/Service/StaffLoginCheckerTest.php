<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service;

use KLS\Core\Model\Bitmask;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Repository\StaffPermissionRepository;
use KLS\CreditGuaranty\FEI\Service\StaffLoginChecker;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\StaffLoginChecker
 *
 * @internal
 */
class StaffLoginCheckerTest extends TestCase
{
    use UserStaffTrait;

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
    public function testIsNotGrantedLoginWithoutPermissions(): void
    {
        $staff = $this->createStaff();

        $this->staffPermissionRepository->findOneBy(['staff' => $staff])->shouldBeCalledOnce()->willReturn(null);

        static::assertFalse($this->createTestObject()->isGrantedLogin($staff));
    }

    /**
     * @covers ::isGrantedLogin
     */
    public function testIsNotGrantedLoginWithPermissions0(): void
    {
        $staff           = $this->createStaff();
        $staffPermission = new StaffPermission(
            $staff,
            new Bitmask(0)
        );

        $this->staffPermissionRepository->findOneBy(['staff' => $staff])->shouldBeCalledOnce()->willReturn($staffPermission);

        static::assertFalse($this->createTestObject()->isGrantedLogin($staff));
    }

    private function createTestObject(): StaffLoginChecker
    {
        return new StaffLoginChecker(
            $this->staffPermissionRepository->reveal()
        );
    }
}
