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
     * @covers ::provide
     *
     * @dataProvider staffProvider
     */
    public function testProvide(Staff $staff, ?StaffPermission $staffPermission = null, array $expected): void
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
            null,
            ['credit_guaranty' => []],
        ];
        yield 'staff with cg permissions' => [
            $staff,
            new StaffPermission($staff, new Bitmask(StaffPermission::MANAGING_COMPANY_ADMIN_PERMISSIONS)),
            ['credit_guaranty' => ['permissions' => 15, 'grant_permissions' => 0]],
        ];
        yield 'staff with cg permissions and grant_permissions' => [
            $staff,
            (new StaffPermission($staff, new Bitmask(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS)))
                ->setGrantPermissions(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS),
            ['credit_guaranty' => ['permissions' => 57, 'grant_permissions' => 57]],
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

        static::assertSame(['credit_guaranty' => []], $result);
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
