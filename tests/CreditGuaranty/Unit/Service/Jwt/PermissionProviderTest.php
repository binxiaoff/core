<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\Unit\Service\Jwt;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;
use Unilend\Core\Model\Bitmask;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\CreditGuaranty\Repository\StaffPermissionRepository;
use Unilend\CreditGuaranty\Service\Jwt\PermissionProvider;

/**
 * @coversDefaultClass \Unilend\CreditGuaranty\Service\Jwt\PermissionProvider
 *
 * @internal
 */
class PermissionProviderTest extends TestCase
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
     * @covers ::getName
     */
    public function testGetName(): void
    {
        static::assertSame('credit_guaranty', $this->createTestObject()->getName());
    }

    /**
     * @covers ::provide
     *
     * @dataProvider staffProvider
     */
    public function testProvide(Staff $staff, array $expected, ?StaffPermission $staffPermission = null): void
    {
        $this->staffPermissionRepository->findOneBy(['staff' => $staff])->shouldBeCalledOnce()->willReturn($staffPermission);

        $permissionProvider = $this->createTestObject();
        $result             = $permissionProvider->provide($staff->getUser(), $staff);

        static::assertSame($expected, $result);
    }

    public function staffProvider(): iterable
    {
        $staff = $this->createStaff();

        yield 'staff without cg permissions' => [
            $staff,
            ['permissions' => 0, 'grant_permissions' => 0],
            null,
        ];
        yield 'staff with cg permissions' => [
            $staff,
            ['permissions' => StaffPermission::MANAGING_COMPANY_ADMIN_PERMISSIONS, 'grant_permissions' => 0],
            new StaffPermission($staff, new Bitmask(StaffPermission::MANAGING_COMPANY_ADMIN_PERMISSIONS)),
        ];
        yield 'staff with cg permissions and grant_permissions' => [
            $staff,
            ['permissions' => StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS, 'grant_permissions' => StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS],
            (new StaffPermission($staff, new Bitmask(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS)))
                ->setGrantPermissions(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS),
        ];
    }

    /**
     * @covers ::provide
     */
    public function testProvideWithoutStaff(): void
    {
        $user = new User('user@mail.com');

        $this->staffPermissionRepository->findOneBy(['staff' => Argument::any()])->shouldNotBeCalled();

        $permissionProvider = $this->createTestObject();
        $result             = $permissionProvider->provide($user, null);

        static::assertSame(['permissions' => 0, 'grant_permissions' => 0], $result);
    }

    /**
     * @covers ::provide
     */
    public function testProvideWithoutStaffPermission(): void
    {
        $staff = $this->createStaff();

        $this->staffPermissionRepository->findOneBy(['staff' => $staff])->shouldBeCalledOnce()->willReturn(null);

        $permissionProvider = $this->createTestObject();
        $result             = $permissionProvider->provide($staff->getUser(), $staff);

        static::assertSame(['permissions' => 0, 'grant_permissions' => 0], $result);
    }

    private function createStaff(): Staff
    {
        $teamRoot = Team::createRootTeam(new Company('Company', 'Company', ''));
        $team     = Team::createTeam('Team', $teamRoot);

        return new Staff(new User('user@mail.com'), $team);
    }

    private function createTestObject(): PermissionProvider
    {
        return new PermissionProvider($this->staffPermissionRepository->reveal());
    }
}
