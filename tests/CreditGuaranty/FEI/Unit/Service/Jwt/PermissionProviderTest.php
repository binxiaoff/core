<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service\Jwt;

use Exception;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Team;
use KLS\Core\Entity\User;
use KLS\Core\Model\Bitmask;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Repository\StaffPermissionRepository;
use KLS\CreditGuaranty\FEI\Service\Jwt\PermissionProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\Jwt\PermissionProvider
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
     * @covers ::getProductName
     */
    public function testGetName(): void
    {
        static::assertSame('credit_guaranty', $this->createTestObject()->getProductName());
    }

    /**
     * @covers ::provide
     *
     * @dataProvider staffProvider
     */
    public function testProvide(Staff $staff, array $expected, ?StaffPermission $staffPermission = null): void
    {
        $this->staffPermissionRepository->findOneBy(['staff' => $staff])->shouldBeCalledTimes(2)->willReturn($staffPermission);

        $permissionProvider = $this->createTestObject();
        $result             = [
            $permissionProvider->getServiceName() => [
                'permissions'       => $permissionProvider->getPermissions($staff->getUser(), $staff),
                'grant_permissions' => $permissionProvider->getGrantPermission($staff->getUser(), $staff),
            ],
        ];

        static::assertSame($expected, $result);
    }

    /**
     * @throws Exception
     */
    public function staffProvider(): iterable
    {
        $staff = $this->createStaff();

        yield 'staff without cg permissions' => [
            $staff,
            ['fei' => ['permissions' => 0, 'grant_permissions' => 0]],
            null,
        ];
        yield 'staff with cg permissions' => [
            $staff,
            ['fei' => ['permissions' => StaffPermission::MANAGING_COMPANY_ADMIN_PERMISSIONS, 'grant_permissions' => 0]],
            new StaffPermission($staff, new Bitmask(StaffPermission::MANAGING_COMPANY_ADMIN_PERMISSIONS)),
        ];
        yield 'staff with cg permissions and grant_permissions' => [
            $staff,
            ['fei' => ['permissions' => StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS, 'grant_permissions' => StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS]],
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
        $result             = [
            $permissionProvider->getServiceName() => [
                'permissions'       => $permissionProvider->getPermissions($user, null),
                'grant_permissions' => $permissionProvider->getGrantPermission($user, null),
            ],
        ];

        static::assertSame(['fei' => ['permissions' => 0, 'grant_permissions' => 0]], $result);
    }

    /**
     * @covers ::provide
     *
     * @throws Exception
     */
    public function testProvideWithoutStaffPermission(): void
    {
        $staff = $this->createStaff();

        $this->staffPermissionRepository->findOneBy(['staff' => $staff])->shouldBeCalledTimes(2)->willReturn(null);

        $permissionProvider = $this->createTestObject();
        $result             = [
            $permissionProvider->getServiceName() => [
                'permissions'       => $permissionProvider->getPermissions($staff->getUser(), $staff),
                'grant_permissions' => $permissionProvider->getGrantPermission($staff->getUser(), $staff),
            ],
        ];

        static::assertSame(['fei' => ['permissions' => 0, 'grant_permissions' => 0]], $result);
    }

    /**
     * @throws Exception
     */
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
