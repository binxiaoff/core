<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\Unit\Notifier;

use KLS\Syndication\Agency\Notifier\ProjectCreatedNotifier;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use KLS\Test\Syndication\Agency\Unit\Traits\AgencyProjectTrait;
use Nexy\Slack\Attachment;
use Nexy\Slack\Client as Slack;
use Nexy\Slack\MessageInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\Syndication\Agency\Notifier\ProjectCreatedNotifier
 *
 * @internal
 */
class ProjectCreatedNotifierTest extends TestCase
{
    use AgencyProjectTrait;
    use UserStaffTrait;

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

    private function createTestObject(): ProjectCreatedNotifier
    {
        return new ProjectCreatedNotifier(
            $this->slack->reveal()
        );
    }
}
