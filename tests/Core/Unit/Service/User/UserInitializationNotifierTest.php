<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Service\User;

use KLS\Core\Entity\TemporaryToken;
use KLS\Core\Entity\User;
use KLS\Core\Entity\UserStatus;
use KLS\Core\Mailer\MailjetMessage;
use KLS\Core\Service\TemporaryTokenGenerator;
use KLS\Core\Service\User\UserInitializationNotifier;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @coversDefaultClass  \KLS\Core\Service\User\UserInitializationNotifier
 *
 * @internal
 */
class UserInitializationNotifierTest extends TestCase
{
    use UserStaffTrait;
    use ProphecyTrait;

    /** @var RouterInterface|ObjectProphecy */
    private $router;
    /** @var TemporaryTokenGenerator|ObjectProphecy */
    private $temporaryTokenGenerator;
    /** @var ObjectProphecy|MailerInterface */
    private $mailer;
    /** @var ObjectProphecy|LoggerInterface */
    private $logger;

    protected function setUp(): void
    {
        $this->router                  = $this->prophesize(RouterInterface::class);
        $this->temporaryTokenGenerator = $this->prophesize(TemporaryTokenGenerator::class);
        $this->mailer                  = $this->prophesize(MailerInterface::class);
        $this->logger                  = $this->prophesize(LoggerInterface::class);
    }

    protected function tearDown(): void
    {
        $this->router                  = null;
        $this->temporaryTokenGenerator = null;
        $this->mailer                  = null;
    }

    /**
     * @covers ::notifyUserInitialization
     *
     * @dataProvider userProvider
     */
    public function testNotifyUserInitialization(User $user, int $expectedResponse): void
    {
        $temporaryToken = $this->prophesize(TemporaryToken::class);

        if (false === $user->isInitializationNeeded()) {
            $temporaryToken->getToken()->shouldNotBeCalled();
            $this->mailer->send(Argument::type(MailjetMessage::class))->shouldNotBeCalled();
            $this->router->generate(Argument::cetera())->shouldNotBeCalled();
        } else {
            $temporaryToken->getToken()->shouldBeCalledOnce()->willReturn(Argument::type('string'));
            $this->temporaryTokenGenerator->generateUltraLongToken($user)->willReturn($temporaryToken->reveal());
            $this->mailer->send(Argument::type(MailjetMessage::class))->shouldBeCalledOnce();
        }

        $response = $this->createTestObject()->notifyUserInitialization($user);
        static::assertSame($expectedResponse, $response);
    }

    public function userProvider(): iterable
    {
        $user = $this->createUserWithStaff(1);
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setPassword('password');
        $userStatus = new UserStatus($user, UserStatus::STATUS_CREATED);
        $user->setCurrentStatus($userStatus);

        $userWithInitializationNeeded = $this->createUserWithStaff(1);

        yield 'Initialization Email not sent' => [$user, 0];
        yield 'Initialization Email sent' => [$userWithInitializationNeeded, 1];
    }

    private function createTestObject(): UserInitializationNotifier
    {
        return new UserInitializationNotifier(
            $this->router->reveal(),
            $this->temporaryTokenGenerator->reveal(),
            $this->mailer->reveal(),
            $this->logger->reveal()
        );
    }
}
