<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\MessageHandler\Staff;

use Doctrine\ORM\ORMException;
use Exception;
use KLS\Core\Message\Staff\StaffCreated;
use KLS\Core\MessageHandler\Staff\StaffCreatedHandler;
use KLS\Core\Repository\StaffRepository;
use KLS\Core\Service\Staff\StaffNotifier;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass  \KLS\Core\MessageHandler\Staff\StaffCreatedHandler
 *
 * @internal
 */
class StaffCreatedHandlerTest extends TestCase
{
    use ProphecyTrait;
    use UserStaffTrait;

    /** @var StaffRepository|ObjectProphecy */
    private $staffRepository;

    /** @var StaffNotifier|ObjectProphecy */
    private $staffNotifier;

    protected function setUp(): void
    {
        $this->staffRepository = $this->prophesize(StaffRepository::class);
        $this->staffNotifier   = $this->prophesize(StaffNotifier::class);
    }

    protected function tearDown(): void
    {
        $this->staffRepository = null;
        $this->staffNotifier   = null;
    }

    /**
     * @covers ::__invoke
     *
     * @throws ORMException
     * @throws Exception
     */
    public function testInvoke(): void
    {
        $staff = $this->createStaff();
        $this->forcePropertyValue($staff, 'id', 1);

        $this->staffRepository->find($staff->getId())->shouldBeCalledOnce()->willReturn($staff);

        $this->staffRepository->refresh($staff)->shouldBeCalledOnce();
        $this->staffNotifier->notifyUserInitialisation($staff)->shouldBeCalledOnce();

        $this->createTestObject()(new StaffCreated($staff));
    }

    /**
     * @covers ::__invoke
     *
     * @throws ORMException
     * @throws Exception
     */
    public function testInvokeWithNoStaffFound(): void
    {
        $staff = $this->createStaff();
        $this->forcePropertyValue($staff, 'id', 100);

        $this->staffRepository->find($staff->getId())->shouldBeCalledOnce()->willReturn(null);

        $this->staffRepository->refresh($staff)->shouldNotBeCalled();
        $this->staffNotifier->notifyUserInitialisation($staff)->shouldNotBeCalled();

        $this->createTestObject()(new StaffCreated($staff));
    }

    private function createTestObject(): StaffCreatedHandler
    {
        return new StaffCreatedHandler(
            $this->staffRepository->reveal(),
            $this->staffNotifier->reveal()
        );
    }
}
