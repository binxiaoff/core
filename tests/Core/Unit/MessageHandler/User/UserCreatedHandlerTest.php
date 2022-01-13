<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\MessageHandler\User;

use KLS\Core\Entity\User;
use KLS\Core\Entity\UserStatus;
use KLS\Core\Message\Message\User\UserCreated;
use KLS\Core\MessageHandler\User\UserCreatedHandler;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Service\User\SlackNotifier\UserCreatedNotifier;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass  \KLS\Core\MessageHandler\User\UserCreatedHandler
 *
 * @internal
 */
class UserCreatedHandlerTest extends TestCase
{
    use UserStaffTrait;
    use PropertyValueTrait;
    use ProphecyTrait;

    /** @var UserCreatedNotifier|ObjectProphecy */
    private $userCreatedNotifier;

    /** @var UserRepository|ObjectProphecy */
    private $userRepository;

    protected function setUp(): void
    {
        $this->userCreatedNotifier = $this->prophesize(UserCreatedNotifier::class);
        $this->userRepository      = $this->prophesize(UserRepository::class);
    }

    protected function tearDown(): void
    {
        $this->userCreatedNotifier = null;
        $this->userRepository      = null;
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeWithoutUserFound(): void
    {
        $user = $this->createUserWithStaff();

        $this->forcePropertyValue($user, 'id', 1);

        $userCreated = new UserCreated($user, UserStatus::STATUS_INVITED, UserStatus::STATUS_CREATED);

        $this->userRepository->find(1)->shouldBeCalledOnce()->willReturn(null);

        $this->userCreatedNotifier->notify(Argument::type(User::class))->shouldNotBeCalled();

        $this->createTestObject()($userCreated);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeWithWrongStatuses(): void
    {
        $user = $this->createUserWithStaff();

        $this->forcePropertyValue($user, 'id', 1);

        $userCreated = new UserCreated($user, UserStatus::STATUS_INVITED, UserStatus::STATUS_INVITED);

        $this->userRepository->find(1)->shouldBeCalledOnce()->willReturn(Argument::type(User::class));

        $this->userCreatedNotifier->notify(Argument::type(User::class))->shouldNotBeCalled();

        $this->createTestObject()($userCreated);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeWithUserIsAdmin(): void
    {
        $user = $this->createUserWithStaff();
        $this->defineAsCompanyAdmin($user, $user->getCompany());

        $this->forcePropertyValue($user, 'id', 1);

        $userCreated = new UserCreated($user, UserStatus::STATUS_INVITED, UserStatus::STATUS_CREATED);

        $this->userRepository->find(1)->shouldBeCalledOnce()->willReturn($user);

        $this->userCreatedNotifier->notify(Argument::type(User::class))->shouldBeCalledOnce();

        $this->createTestObject()($userCreated);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeWithUserIsNotAdmin(): void
    {
        $user = $this->createUserWithStaff();

        $this->forcePropertyValue($user, 'id', 1);

        $userCreated = new UserCreated($user, UserStatus::STATUS_INVITED, UserStatus::STATUS_CREATED);

        $this->userRepository->find(1)->shouldBeCalledOnce()->willReturn($user);

        $this->userCreatedNotifier->notify(Argument::type(User::class))->shouldNotBeCalled();

        $this->createTestObject()($userCreated);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        $user = $this->createUserWithStaff();

        $this->forcePropertyValue($user, 'id', 1);

        $userCreated = new UserCreated($user, UserStatus::STATUS_INVITED, UserStatus::STATUS_CREATED);

        $user = $this->prophesize(User::class);
        $user->getStaff()->shouldBeCalled()->willReturn([]);

        $this->userRepository->find(1)->shouldBeCalledOnce()->willReturn($user->reveal());

        $this->createTestObject()($userCreated);
    }

    private function createTestObject(): UserCreatedHandler
    {
        return new UserCreatedHandler(
            $this->userCreatedNotifier->reveal(),
            $this->userRepository->reveal()
        );
    }
}
