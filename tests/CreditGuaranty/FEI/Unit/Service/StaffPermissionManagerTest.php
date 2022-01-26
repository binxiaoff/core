<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service;

use Doctrine\Common\Collections\ArrayCollection;
use KLS\Core\Entity\CompanyAdmin;
use KLS\Core\Entity\Staff;
use KLS\Core\Model\Bitmask;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Repository\StaffPermissionRepository;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ProgramTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\StaffPermissionManager
 *
 * @internal
 */
class StaffPermissionManagerTest extends TestCase
{
    use ProphecyTrait;
    use ProgramTrait;

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
     * @covers ::hasPermissions
     *
     * @dataProvider staffPermissionProvider
     */
    public function testHasPermissions(
        Staff $staff,
        int $permissions,
        StaffPermission $staffPermission,
        bool $expected
    ): void {
        $this->staffPermissionRepository->findOneBy(['staff' => $staff])
            ->shouldBeCalledOnce()
            ->willReturn($staffPermission)
        ;

        $staffPermissionManager = $this->createTestObject();
        $result                 = $staffPermissionManager->hasPermissions($staff, $permissions);

        static::assertSame($expected, $result);
    }

    public function staffPermissionProvider(): iterable
    {
        $staffPermission = new StaffPermission(
            $this->createStaff(),
            new Bitmask(StaffPermission::MANAGING_COMPANY_ADMIN_PERMISSIONS)
        );

        yield 'managing company staff - true with read program permission' => [
            $staffPermission->getStaff(),
            StaffPermission::PERMISSION_READ_PROGRAM,
            $staffPermission,
            true,
        ];
        yield 'managing company staff - false with reporting permission' => [
            $staffPermission->getStaff(),
            StaffPermission::PERMISSION_REPORTING,
            $staffPermission,
            false,
        ];
    }

    /**
     * @covers ::hasPermissions
     */
    public function testHasPermissionsWithoutManagedStaff(): void
    {
        $staff = $this->createStaff();
        $staff->setManager(true);

        $this->staffPermissionRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        $staffPermissionManager = $this->createTestObject();
        $result                 = $staffPermissionManager->hasPermissions($staff, 0);

        static::assertFalse($result);
    }

    /**
     * @covers ::canGrant
     *
     * @dataProvider staffGrantPermissionProvider
     */
    public function testCanGrant(
        Staff $staff,
        Bitmask $permissions,
        StaffPermission $staffPermission,
        bool $expected
    ): void {
        $this->staffPermissionRepository->findOneBy(['staff' => $staff])
            ->shouldBeCalledOnce()
            ->willReturn($staffPermission)
        ;

        $staffPermissionManager = $this->createTestObject();
        $result                 = $staffPermissionManager->canGrant($staff, $permissions);

        static::assertSame($expected, $result);
    }

    public function staffGrantPermissionProvider(): iterable
    {
        $staffPermission = new StaffPermission(
            $this->createStaff(),
            new Bitmask(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS)
        );
        $staffPermission->setGrantPermissions(new Bitmask(StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS));

        yield 'participant admin staff - true with read program permission' => [
            $staffPermission->getStaff(),
            new Bitmask(StaffPermission::PERMISSION_READ_PROGRAM),
            $staffPermission,
            true,
        ];
        yield 'participant admin staff - false with create program permission' => [
            $staffPermission->getStaff(),
            new Bitmask(StaffPermission::PERMISSION_CREATE_PROGRAM),
            $staffPermission,
            false,
        ];
    }

    /**
     * @covers ::canGrant
     */
    public function testCanGrantWithStaffPermissionNotFound(): void
    {
        $staff = $this->createStaff();

        $this->staffPermissionRepository->findOneBy(['staff' => $staff])
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $staffPermissionManager = $this->createTestObject();
        $result                 = $staffPermissionManager->canGrant($staff, new Bitmask(0));

        static::assertFalse($result);
    }

    /**
     * @covers ::checkCompanyGroupTag
     *
     * @dataProvider companyGroupTagPermissionProvider
     */
    public function testCheckCompanyGroupTag(Program $program, Staff $staff, bool $expected): void
    {
        $staffPermissionManager = $this->createTestObject();
        $result                 = $staffPermissionManager->checkCompanyGroupTag($program, $staff);

        static::assertSame($expected, $result);
    }

    public function companyGroupTagPermissionProvider(): iterable
    {
        $staffAmin = $this->createStaff();
        $staff     = $this->createStaff();
        $staff2    = $this->createStaff();
        $program   = $this->createProgram();

        $this->forcePropertyValue($staffAmin->getCompany(), 'admins', new ArrayCollection([
            new CompanyAdmin($staffAmin->getUser(), $staffAmin->getCompany()),
        ]));
        $this->forcePropertyValue($staff, 'companyGroupTags', new ArrayCollection([$program->getCompanyGroupTag()]));

        yield 'staff admin' => [
            $program,
            $staffAmin,
            true,
        ];
        yield 'staff not admin and same companyGroupTag' => [
            $program,
            $staff,
            true,
        ];
        yield 'staff not admin and different companyGroupTag' => [
            $program,
            $staff2,
            false,
        ];
    }

    private function createTestObject(): StaffPermissionManager
    {
        return new StaffPermissionManager(
            $this->staffPermissionRepository->reveal()
        );
    }
}
