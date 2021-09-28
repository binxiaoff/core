<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\Unit\Service\Notifier\Agency;

use KLS\Syndication\Agency\Service\Notifier\Agency\AgencyPublishedNotifier;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use KLS\Test\Syndication\Agency\Unit\Traits\AgencyProjectTrait;
use Nexy\Slack\Attachment;
use Nexy\Slack\Client as Slack;
use Nexy\Slack\MessageInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\Syndication\Agency\Service\Notifier\Agency\AgencyPublishedNotifier
 *
 * @internal
 */
class AgencyPublishedNotifierTest extends TestCase
{
    use AgencyProjectTrait;
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
    public function testNotifiy(): void
    {
        $staff            = $this->createStaff();
        $project          = $this->createAgencyProject($staff);
        $messageInterface = $this->prophesize(MessageInterface::class);

        $this->slack->sendMessage(Argument::type(MessageInterface::class))->shouldBeCalledOnce();
        $messageInterface->enableMarkdown()->shouldBeCalledOnce()->willReturn($messageInterface);
        $messageInterface->setText(Argument::type('string'))->shouldBeCalledOnce()->willReturn($messageInterface);
        $messageInterface->attach(Argument::type(Attachment::class))->shouldBeCalledOnce()->willReturn($messageInterface);
        $this->slack->createMessage()->shouldBeCalledOnce()->willReturn($messageInterface->reveal());

        $this->createTestObject()->notify($project);
    }

    private function createTestObject(): AgencyPublishedNotifier
    {
        return new AgencyPublishedNotifier(
            $this->slack->reveal()
        );
    }
}
