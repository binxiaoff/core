<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Service\User\SlackNotifier;

use KLS\Core\Entity\Company;
use KLS\Core\Service\User\SlackNotifier\UserCreatedNotifier;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use Nexy\Slack\Attachment;
use Nexy\Slack\Client as Slack;
use Nexy\Slack\MessageInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass  \KLS\Core\Service\User\SlackNotifier\UserCreatedNotifier
 *
 * @internal
 */
class UserCreatedNotifierTest extends TestCase
{
    use UserStaffTrait;
    use ProphecyTrait;

    /** @var Slack|ObjectProphecy */
    private $slack;

    protected function setUp(): void
    {
        $this->slack = $this->prophesize(Slack::class);
    }

    protected function tearDown(): void
    {
        $this->slack = null;
    }

    /**
     * @covers ::notify
     */
    public function testNotify(): void
    {
        $userStaff = $this->createUserWithStaff();

        $messageInterface = $this->prophesize(MessageInterface::class);

        $company = $this->prophesize(Company::class);
        $company->getLegalName()->shouldNotBeCalled();

        $this->slack->sendMessage(Argument::type(MessageInterface::class))->shouldBeCalledOnce();
        $messageInterface->setText(Argument::type('string'))->shouldBeCalledOnce()->willReturn($messageInterface);
        $messageInterface->enableMarkdown()->shouldBeCalledOnce()->willReturn($messageInterface);
        $messageInterface->attach(Argument::type(Attachment::class))->shouldBeCalled(2)->willReturn($messageInterface);
        $this->slack->createMessage()->shouldBeCalledOnce()->willReturn($messageInterface->reveal());

        $this->createTestObject()->notify($userStaff);
    }

    /**
     * @covers ::notify
     */
    public function testNotifyWithAdminStaff(): void
    {
        $user = $this->createUserWithStaff();
        $this->defineAsCompanyAdmin($user, $user->getCompany());

        $messageInterface = $this->prophesize(MessageInterface::class);

        $this->slack->sendMessage(Argument::type(MessageInterface::class))->shouldBeCalledOnce();
        $messageInterface->enableMarkdown()->shouldBeCalledOnce()->willReturn($messageInterface);
        $messageInterface->setText(Argument::type('string'))->shouldBeCalledOnce()->willReturn($messageInterface);
        $messageInterface->attach(Argument::type(Attachment::class))->shouldBeCalled(2)->willReturn($messageInterface);
        $this->slack->createMessage()->shouldBeCalledOnce()->willReturn($messageInterface->reveal());

        $this->createTestObject()->notify($user);
    }

    private function createTestObject(): UserCreatedNotifier
    {
        return new UserCreatedNotifier(
            $this->slack->reveal()
        );
    }
}
