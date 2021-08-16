<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service\Jwt;

use Exception;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Core\Model\Bitmask;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Repository\StaffPermissionRepository;
use KLS\CreditGuaranty\FEI\Service\Jwt\PermissionProvider;
use PHPUnit\Framework\TestCase;
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

    public function providerGetGrantPermissions()
    {
        $staff = $this->createStaff();

        yield from $this->commonProviderPermission();

        yield 'staff with cg permissions (no grant permission)' => [
            $staff->getUser(),
            $staff,
            0,
            new StaffPermission($staff, new Bitmask(StaffPermission::MANAGING_COMPANY_ADMIN_PERMISSIONS)),
        ];
        yield 'staff with cg permissions and same grant_permissions' => [
            $staff->getUser(),
            $staff,
            StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS,
            (new StaffPermission($staff, new Bitmask(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS)))
                ->setGrantPermissions(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS),
        ];
        yield 'staff with cg permissions and different grant permission' => [
            $staff->getUser(),
            $staff,
            StaffPermission::PERMISSION_GRANT_EDIT_PROGRAM,
            (new StaffPermission($staff, new Bitmask(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS)))
                ->setGrantPermissions(StaffPermission::PERMISSION_GRANT_EDIT_PROGRAM),
        ];
    }

    public function providerGetPermissions(): iterable
    {
        $staff = $this->createStaff();

        yield from $this->commonProviderPermission();

        yield 'staff with cg permissions (no grant permission)' => [
            $staff->getUser(),
            $staff,
            StaffPermission::MANAGING_COMPANY_ADMIN_PERMISSIONS,
            new StaffPermission($staff, new Bitmask(StaffPermission::MANAGING_COMPANY_ADMIN_PERMISSIONS)),
        ];
        yield 'staff with cg permissions and grant_permissions' => [
            $staff->getUser(),
            $staff,
            StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS,
            (new StaffPermission($staff, new Bitmask(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS)))
                ->setGrantPermissions(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS),
        ];
        yield 'staff with cg permissions and different grant permission' => [
            $staff->getUser(),
            $staff,
            StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS,
            (new StaffPermission($staff, new Bitmask(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS)))
                ->setGrantPermissions(StaffPermission::PERMISSION_GRANT_EDIT_PROGRAM),
        ];
    }

    /**
     * @covers ::getProductName
     */
    public function testGetProductName(): void
    {
        static::assertSame('credit_guaranty', $this->createTestObject()->getProductName());
    }

    /**
     * @covers ::getServiceName
     */
    public function testGetServiceName(): void
    {
        static::assertSame('fei', $this->createTestObject()->getServiceName());
    }

    /**
     * @covers ::getPermissions
     *
     * @dataProvider providerGetPermissions
     */
    public function testGetPermissions(?User $user, ?Staff $staff, int $expected, ?StaffPermission $staffPermission): void
    {
        $this->staffPermissionRepository->findOneBy(['staff' => $staff])->willReturn($staffPermission);

        $permissionProvider = $this->createTestObject();

        static::assertSame($expected, $permissionProvider->getPermissions($user, $staff));
    }

    /**
     * @covers ::getPermissions
     *
     * @dataProvider providerGetGrantPermissions
     *
     * @param mixed $expected
     */
    public function testGetGrantPermissions(?User $user, ?Staff $staff, $expected, ?StaffPermission $staffPermission)
    {
        $this->staffPermissionRepository->findOneBy(['staff' => $staff])->willReturn($staffPermission);

        $permissionProvider = $this->createTestObject();

        static::assertSame($expected, $permissionProvider->getGrantPermission($user, $staff));
    }

    private function commonProviderPermission(): iterable
    {
        $staff = $this->createStaff();

        yield 'no staff and no user' => [
            new User('test@test.com'),
            null,
            0,
            null,
        ];
        yield 'no user and staff' => [
            null,
            $staff,
            0,
            null,
        ];
        yield 'user and no staff' => [
            $staff->getUser(),
            null,
            0,
            null,
        ];
        yield 'staff without cg permissions' => [
            $staff->getUser(),
            $staff,
            0,
            null,
        ];
    }

    /**
     * @throws Exception
     */
    private function createStaff(): Staff
    {
        $company = new Company('Company', 'Company', '');

        return new Staff(new User('user@mail.com'), $company->getRootTeam());
    }

    private function createTestObject(): PermissionProvider
    {
        return new PermissionProvider($this->staffPermissionRepository->reveal());
    }
}
