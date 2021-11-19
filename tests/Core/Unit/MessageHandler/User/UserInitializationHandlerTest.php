<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\MessageHandler\User;

use InvalidArgumentException;
use KLS\Core\Entity\Request\UserInitialization;
use KLS\Core\Entity\User;
use KLS\Core\Entity\UserStatus;
use KLS\Core\MessageHandler\User\UserInitializationHandler;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Service\User\UserInitializationNotifier;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass  \KLS\Core\MessageHandler\User\UserInitializationHandler
 *
 * @internal
 */
class UserInitializationHandlerTest extends TestCase
{
    use UserStaffTrait;
    use ProphecyTrait;

    /** @var UserRepository|ObjectProphecy */
    private $userRepository;
    /** @var UserInitializationNotifier|ObjectProphecy */
    private $userInitializationNotifier;

    protected function setUp(): void
    {
        $this->userRepository             = $this->prophesize(UserRepository::class);
        $this->userInitializationNotifier = $this->prophesize(UserInitializationNotifier::class);
    }

    protected function tearDown(): void
    {
        $this->userRepository             = null;
        $this->userInitializationNotifier = null;
    }

    /**
     * @covers ::__invoke
     *
     * @dataProvider userProvider
     *
     * @param mixed $expectedResponse
     */
    public function testInvoke(
        UserInitialization $userInitialization,
        ?User $user
    ): void {
        $this->userRepository->findOneBy(['email' => $userInitialization->email])->shouldBeCalledOnce()
            ->willReturn($user)
        ;

        if (null === $user) {
            $this->userInitializationNotifier->notifyUserInitialization(Argument::type(User::class))
                ->shouldNotBeCalled()
            ;
        } else {
            $this->userInitializationNotifier->notifyUserInitialization(Argument::type(User::class))
                ->shouldBeCalledOnce()->willReturn(1);
        }

        $this->createTestObject()($userInitialization);
    }

    public function userProvider(): iterable
    {
        $userInitialization        = new UserInitialization();
        $userInitialization->email = 'user@mail.com';

        $user       = $this->createUserWithStaff(1);
        $userStatus = new UserStatus($user, UserStatus::STATUS_INVITED);
        $user->setCurrentStatus($userStatus);

        yield 'userNotFound' => [$userInitialization, null, InvalidArgumentException::class];
        yield 'userFoundAndEmailSent' => [$userInitialization, $user, null];
    }

    private function createTestObject(): UserInitializationHandler
    {
        return new UserInitializationHandler(
            $this->userRepository->reveal(),
            $this->userInitializationNotifier->reveal()
        );
    }
}
