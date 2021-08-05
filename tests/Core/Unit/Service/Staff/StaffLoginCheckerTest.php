<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Unit\Service\Staff;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\StaffStatus;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;
use Unilend\Core\Service\Staff\StaffLoginChecker;
use Unilend\Core\Service\Staff\StaffLoginInterface;

/**
 * @coversDefaultClass \Unilend\Core\Service\Staff\StaffLoginChecker
 *
 * @internal
 */
class StaffLoginCheckerTest extends TestCase
{
    /**
     * @covers ::isGrantedLogin
     */
    public function testIsGrantedLogin(): void
    {
        /** @var StaffLoginInterface|ObjectProphecy $checker1 */
        $checker1 = $this->prophesize(StaffLoginInterface::class);
        /** @var StaffLoginInterface|ObjectProphecy $checker2 */
        $checker2 = $this->prophesize(StaffLoginInterface::class);
        /** @var StaffLoginInterface|ObjectProphecy $checker3 */
        $checker3 = $this->prophesize(StaffLoginInterface::class);

        $staff = $this->createStaff();

        $checker1->isGrantedLogin($staff)->shouldBeCalledOnce()->willReturn(false);
        $checker2->isGrantedLogin($staff)->shouldBeCalledOnce()->willReturn(true);
        $checker3->isGrantedLogin($staff)->shouldNotBeCalled();

        $checker = new StaffLoginChecker([$checker1->reveal(), $checker2->reveal(), $checker3->reveal()]);
        static::assertTrue($checker->isGrantedLogin($staff));
    }

    /**
     * @covers ::isGrantedLogin
     */
    public function testIsNotGrantedLogin(): void
    {
        /** @var StaffLoginInterface|ObjectProphecy $checker1 */
        $checker1 = $this->prophesize(StaffLoginInterface::class);
        /** @var StaffLoginInterface|ObjectProphecy $checker2 */
        $checker2 = $this->prophesize(StaffLoginInterface::class);

        $staff = $this->createStaff();

        $checker1->isGrantedLogin($staff)->shouldBeCalledOnce()->willReturn(false);
        $checker2->isGrantedLogin($staff)->shouldBeCalledOnce()->willReturn(false);

        $checker = new StaffLoginChecker([$checker1->reveal(), $checker2->reveal()]);
        static::assertFalse($checker->isGrantedLogin($staff));
    }

    /**
     * @covers ::isGrantedLogin
     */
    public function testIsNotGrantedLoginWithInactiveStaff(): void
    {
        /** @var StaffLoginInterface|ObjectProphecy $checker1 */
        $checker1 = $this->prophesize(StaffLoginInterface::class);

        $staff = $this->createStaff();
        $staff->setCurrentStatus(new StaffStatus($staff, StaffStatus::STATUS_INACTIVE, $staff));

        $checker1->isGrantedLogin($staff)->shouldNotBeCalled();

        $checker = new StaffLoginChecker([$checker1->reveal()]);
        static::assertFalse($checker->isGrantedLogin($staff));
    }

    private function createStaff(): Staff
    {
        $teamRoot = Team::createRootTeam(new Company('Company', 'Company', ''));
        $team     = Team::createTeam('Team', $teamRoot);

        return new Staff(new User('user@mail.com'), $team);
    }
}
