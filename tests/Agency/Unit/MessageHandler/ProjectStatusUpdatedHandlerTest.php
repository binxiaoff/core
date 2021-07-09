<?php

declare(strict_types=1);

namespace Unilend\Test\Agency\Unit\MessageHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Unilend\Agency\Entity\AgentMember;
use Unilend\Agency\Entity\BorrowerMember;
use Unilend\Agency\Entity\ParticipationMember;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Message\ProjectStatusUpdated;
use Unilend\Agency\MessageHandler\ProjectStatusUpdatedHandler;
use Unilend\Agency\Notifier\ProjectMemberNotifier;
use Unilend\Agency\Repository\ProjectRepository;

/**
 * @internal
 *
 * @coversDefaultClass \Unilend\Agency\MessageHandler\ProjectStatusUpdatedHandler
 */
class ProjectStatusUpdatedHandlerTest extends TestCase
{
    /**
     * @var ProjectRepository|ObjectProphecy
     */
    private $projectRepository;
    /**
     * @var ProjectMemberNotifier|ObjectProphecy
     */
    private $projectMemberNotifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectMemberNotifier = $this->prophesize(ProjectMemberNotifier::class);
        $this->projectRepository     = $this->prophesize(ProjectRepository::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->projectRepository     = null;
        $this->projectMemberNotifier = null;
    }

    /**
     * @throws \JsonException
     *
     * @covers ::__invoke
     */
    public function testUnknownProject()
    {
        $projectId = 1;
        $this->projectRepository->find($projectId)->willReturn(null);

        $message = new ProjectStatusUpdated(1, 10, 20);

        $projectStatusHandler = new ProjectStatusUpdatedHandler(
            $this->projectMemberNotifier->reveal(),
            $this->projectRepository->reveal()
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/{$projectId}/");

        $projectStatusHandler($message);

        $this->projectRepository->find($projectId)->shouldHaveBeenCalled();

        $this->projectMemberNotifier->notifyProjectPublication(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @throws \JsonException
     *
     * @covers ::__invoke
     *
     * @dataProvider providerProjectPublicationNotification
     */
    public function testProjectPublicationNotification(int $previousStatus, int $nextStatus, array $projectMembers)
    {
        $projectId = 1;

        $project = $this->prophesize(Project::class);
        $project->getMembers()->willReturn($projectMembers);

        $this->projectRepository->find($projectId)->willReturn($project->reveal());

        $message = new ProjectStatusUpdated($projectId, $previousStatus, $nextStatus);

        $projectStatusHandler = new ProjectStatusUpdatedHandler(
            $this->projectMemberNotifier->reveal(),
            $this->projectRepository->reveal()
        );

        $projectStatusHandler($message);

        $this->projectRepository->find($projectId)->shouldHaveBeenCalled();

        // Each of these condition forbids the sending of the notification
        if (Project::STATUS_DRAFT !== $previousStatus || Project::STATUS_PUBLISHED !== $nextStatus || 0 === count($projectMembers)) {
            $this->projectMemberNotifier->notifyProjectPublication(Argument::any())->shouldNotHaveBeenCalled();

            return;
        }

        // Case where some notification should have been sent
        foreach ($projectMembers as $projectMember) {
            $this->projectMemberNotifier->notifyProjectPublication($projectMember)->shouldHaveBeenCalled();
        }
    }

    public function providerProjectPublicationNotification(): iterable
    {
        $transitions = [
            Project::STATUS_DRAFT     => [Project::STATUS_PUBLISHED],
            Project::STATUS_PUBLISHED => [Project::STATUS_FINISHED, Project::STATUS_ARCHIVED],
        ];

        foreach ($transitions as $previousStatus => $nextStatuses) {
            foreach ($nextStatuses as $nextStatus) {
                foreach ($this->getProjectMemberCollectionPossibilities() as $projectMemberCollectionPossibilityDescription => $possibility) {
                    yield sprintf(
                        'Test with transition %d to %d and %s',
                        $previousStatus,
                        $nextStatus,
                        $projectMemberCollectionPossibilityDescription
                    ) => [$previousStatus, $nextStatus, $possibility];
                }
            }
        }
    }

    private function getProjectMemberCollectionPossibilities()
    {
        $random = random_int(2, 10);

        // TODO Add more possible combination (e.g. 1 agent member and 0 participation member, 1 agent member and n participation, etc.)
        $classes = [AgentMember::class, BorrowerMember::class, ParticipationMember::class];

        foreach ($classes as $class) {
            $projectMember = $this->prophesize($class);

            $class = ltrim(mb_substr($class, mb_strrpos($class, '\\')), '\\');

            yield "with O {$class}" => [];
            yield "with 1 {$class}" => [$projectMember];
            yield "with {$random} {$class}" => array_fill(1, $random, $projectMember);
        }
    }
}
