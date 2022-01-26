<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\MessageHandler\User;

use KLS\Core\Entity\Request\ResetPassword;
use KLS\Core\MessageHandler\User\ResetPasswordHandler;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Service\User\UserNotifier;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass  \KLS\Core\MessageHandler\User\ResetPasswordHandler
 *
 * @internal
 */
class ResetPasswordHandlerTest extends TestCase
{
    use ProphecyTrait;
    use UserStaffTrait;

    /** @var UserRepository|ObjectProphecy */
    private $userRepository;
    /** @var UserNotifier|ObjectProphecy */
    private $notifier;

    protected function setUp(): void
    {
        $this->userRepository = $this->prophesize(UserRepository::class);
        $this->notifier       = $this->prophesize(UserNotifier::class);
    }

    protected function tearDown(): void
    {
        $this->userRepository = null;
        $this->notifier       = null;
    }

    /**
     * @covers ::__invoke
     *
     * @throws \Exception
     */
    public function testInvoke(): void
    {
        $user = $this->createUserWithStaff();
        $this->userRepository->findOneBy(['email' => 'user@mail.com'])->shouldBeCalledOnce()
            ->willReturn($user)
        ;

        $this->notifier->notifyPasswordRequest($user)->shouldBeCalledOnce();

        $resetPassword        = new ResetPassword();
        $resetPassword->email = 'user@mail.com';
        $this->createTestObject()($resetPassword);
    }

    /**
     * @covers ::__invoke
     *
     * @throws \Exception
     */
    public function testInvokeWithNoUserFound(): void
    {
        $user = $this->createUserWithStaff();
        $this->userRepository->findOneBy(['email' => 'user@mail.com'])->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $this->notifier->notifyPasswordRequest($user)->shouldNotBeCalled();

        $resetPassword        = new ResetPassword();
        $resetPassword->email = 'user@mail.com';
        $this->createTestObject()($resetPassword);
    }

    private function createTestObject(): ResetPasswordHandler
    {
        return new ResetPasswordHandler(
            $this->userRepository->reveal(),
            $this->notifier->reveal()
        );
    }
}
