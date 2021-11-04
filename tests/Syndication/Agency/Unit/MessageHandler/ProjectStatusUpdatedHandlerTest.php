<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\Unit\MessageHandler;

use InvalidArgumentException;
use KLS\Syndication\Agency\Entity\AbstractProjectMember;
use KLS\Syndication\Agency\Entity\AgentMember;
use KLS\Syndication\Agency\Entity\BorrowerMember;
use KLS\Syndication\Agency\Entity\ParticipationMember;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Message\ProjectStatusUpdated;
use KLS\Syndication\Agency\MessageHandler\ProjectStatusUpdatedHandler;
use KLS\Syndication\Agency\Notifier\ProjectClosedNotifier;
use KLS\Syndication\Agency\Notifier\ProjectMemberNotifier;
use KLS\Syndication\Agency\Notifier\ProjectPublishedNotifier;
use KLS\Syndication\Agency\Repository\ProjectRepository;
use KLS\Test\Syndication\Agency\Unit\Traits\ProjectMemberSetTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\Syndication\Agency\MessageHandler\ProjectStatusUpdatedHandler
 *
 * @internal
 */
class ProjectStatusUpdatedHandlerTest extends TestCase
{
    use ProjectMemberSetTrait;
    use ProphecyTrait;

    /** @var ProjectRepository|ObjectProphecy */
    private $projectRepository;

    /** @var ProjectMemberNotifier|ObjectProphecy */
    private $projectMemberNotifier;

    /** @var ProjectPublishedNotifier|ObjectProphecy */
    private $projectPublishedNotifier;

    /** @var ProjectClosedNotifier|ObjectProphecy */
    private $projectClosedNotifier;

    protected function setUp(): void
    {
        $this->projectRepository        = $this->prophesize(ProjectRepository::class);
        $this->projectMemberNotifier    = $this->prophesize(ProjectMemberNotifier::class);
        $this->projectPublishedNotifier = $this->prophesize(ProjectPublishedNotifier::class);
        $this->projectClosedNotifier    = $this->prophesize(ProjectClosedNotifier::class);
    }

    protected function tearDown(): void
    {
        $this->projectMemberNotifier    = null;
        $this->projectRepository        = null;
        $this->projectPublishedNotifier = null;
        $this->projectClosedNotifier    = null;
    }

    /**
     * @covers ::__invoke
     *
     * @dataProvider projectMembersProvider
     */
    public function testInvoke(Project $project, int $expectedMembersNb): void
    {
        $this->forcePropertyValue($project, 'id', 42);
        $message = new ProjectStatusUpdated(42, Project::STATUS_DRAFT, Project::STATUS_PUBLISHED);

        $this->projectRepository->find(42)->shouldBeCalledOnce()->willReturn($project);
        $this->projectMemberNotifier->notifyProjectPublication(Argument::type(AbstractProjectMember::class))
            ->shouldBeCalledTimes($expectedMembersNb)
        ;

        $this->projectPublishedNotifier->notify(Argument::type(Project::class))->shouldBeCalledOnce();
        $this->projectClosedNotifier->notify(Argument::type(Project::class))->shouldNotBeCalled();

        $this->createTestObject()($message);
    }

    public function projectMembersProvider(): iterable
    {
        yield '0 AgentMember + 0 BorrowerMember + 0 ParticipationMember' => [
            $this->createProjectWithMembers([]),
            0,
        ];
        yield '3 AgentMember + 0 BorrowerMember + 0 ParticipationMember' => [
            $this->createProjectWithMembers([AgentMember::class => 3]),
            3,
        ];
        yield '0 AgentMember + 1 BorrowerMember + 0 ParticipationMember' => [
            $this->createProjectWithMembers([BorrowerMember::class => 1]),
            1,
        ];
        yield '0 AgentMember + 0 BorrowerMember + 2 ParticipationMember' => [
            $this->createProjectWithMembers([ParticipationMember::class => 2]),
            2,
        ];
        yield '3 AgentMember + 1 BorrowerMember + 0 ParticipationMember' => [
            $this->createProjectWithMembers([AgentMember::class => 3, BorrowerMember::class => 1]),
            4,
        ];
        yield '0 AgentMember + 1 BorrowerMember + 2 ParticipationMember' => [
            $this->createProjectWithMembers([BorrowerMember::class => 1, ParticipationMember::class => 2]),
            3,
        ];
        yield '3 AgentMember + 0 BorrowerMember + 2 ParticipationMember' => [
            $this->createProjectWithMembers([AgentMember::class => 3, ParticipationMember::class => 2]),
            5,
        ];
        yield '3 AgentMember + 1 BorrowerMember + 2 ParticipationMember' => [
            $this->createProjectWithMembers([
                AgentMember::class         => 3,
                BorrowerMember::class      => 1,
                ParticipationMember::class => 2,
            ]),
            6,
        ];
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeExceptionWithProjectNotFound(): void
    {
        $project = $this->createAgencyProject($this->createStaff());
        $this->forcePropertyValue($project, 'id', 42);
        $message = new ProjectStatusUpdated(
            42,
            Project::STATUS_DRAFT,
            Project::STATUS_PUBLISHED
        );

        $this->projectRepository->find(42)->shouldBeCalledOnce()->willReturn(null);
        $this->projectMemberNotifier->notifyProjectPublication(Argument::any())->shouldNotBeCalled();

        $this->projectPublishedNotifier->notify(Argument::type(Project::class))->shouldNotBeCalled();
        $this->projectClosedNotifier->notify(Argument::type(Project::class))->shouldNotBeCalled();

        static::expectException(InvalidArgumentException::class);

        $this->createTestObject()($message);
    }

    /**
     * @covers ::__invoke
     *
     * @dataProvider invalidStatusesProvider
     */
    public function testInvokeWithInvalidStatuses(int $previousStatus, int $nextStatus): void
    {
        $project = $this->createAgencyProject($this->createStaff());
        $this->forcePropertyValue($project, 'id', 42);
        $message = new ProjectStatusUpdated(42, $previousStatus, $nextStatus);

        $this->projectRepository->find(42)->shouldBeCalledOnce()->willReturn($project);
        $this->projectMemberNotifier->notifyProjectPublication(Argument::any())->shouldNotBeCalled();

        $this->projectPublishedNotifier->notify(Argument::type(Project::class))->shouldNotBeCalled();
        $this->projectClosedNotifier->notify(Argument::type(Project::class))->shouldNotBeCalled();

        $this->createTestObject()($message);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeWithProjectClosed(): void
    {
        $project = $this->createAgencyProject($this->createStaff());
        $this->forcePropertyValue($project, 'id', 42);
        $message = new ProjectStatusUpdated(
            42,
            Project::STATUS_PUBLISHED,
            Project::STATUS_ARCHIVED
        );

        $this->projectRepository->find(42)->shouldBeCalledOnce()->willReturn($project);
        $this->projectMemberNotifier->notifyProjectPublication(Argument::type(AbstractProjectMember::class))
            ->shouldNotBeCalled()
        ;

        $this->projectPublishedNotifier->notify(Argument::type(Project::class))->shouldNotBeCalled();
        $this->projectClosedNotifier->notify(Argument::type(Project::class))->shouldBeCalledOnce();

        $this->createTestObject()($message);
    }

    public function invalidStatusesProvider(): iterable
    {
        yield 'status draft and draft' => [Project::STATUS_DRAFT, Project::STATUS_DRAFT];
        yield 'status published and published' => [Project::STATUS_PUBLISHED, Project::STATUS_PUBLISHED];
        yield 'status archived and published' => [Project::STATUS_ARCHIVED, Project::STATUS_PUBLISHED];
        yield 'status finished and published' => [Project::STATUS_FINISHED, Project::STATUS_PUBLISHED];
    }

    private function createTestObject(): ProjectStatusUpdatedHandler
    {
        return new ProjectStatusUpdatedHandler(
            $this->projectMemberNotifier->reveal(),
            $this->projectRepository->reveal(),
            $this->projectPublishedNotifier->reveal(),
            $this->projectClosedNotifier->reveal()
        );
    }
}
