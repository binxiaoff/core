<?php

declare(strict_types=1);

namespace Unilend\Test\Agency\Unit\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JsonException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Unilend\Agency\Entity\AbstractProjectMember;
use Unilend\Agency\Entity\AgentMember;
use Unilend\Agency\Entity\BorrowerMember;
use Unilend\Agency\Entity\ParticipationMember;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Message\ProjectMemberCreated;
use Unilend\Agency\MessageHandler\ProjectMemberCreatedHandler;
use Unilend\Agency\Notifier\ProjectMemberNotifier;

/**
 * @coversDefaultClass \Unilend\Agency\MessageHandler\ProjectMemberCreatedHandler
 *
 * @internal
 */
class ProjectMemberCreatedHandlerTest extends TestCase
{
    /**
     * @var EntityManagerInterface|ObjectProphecy
     */
    private $managerRegistry;
    /**
     * @var ProjectMemberNotifier|ObjectProphecy
     */
    private $projectMemberNotifier;
    /**
     * @var EntityManagerInterface|ObjectProphecy
     */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->managerRegistry       = $this->prophesize(ManagerRegistry::class);
        $this->entityManager         = $this->prophesize(EntityManagerInterface::class);
        $this->projectMemberNotifier = $this->prophesize(ProjectMemberNotifier::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->managerRegistry       = null;
        $this->entityManager         = null;
        $this->projectMemberNotifier = null;
    }

    /**
     * @throws JsonException
     *
     * @covers ::__invoke
     *
     * @dataProvider providerUnknownProjectMember
     */
    public function testUnknownProjectMember(AbstractProjectMember $projectMember)
    {
        $message = new ProjectMemberCreated($projectMember);

        $this->entityManager->find($message->getProjectMemberClass(), $message->getProjectMemberId())->willReturn(null);
        $this->managerRegistry->getManagerForClass($message->getProjectMemberClass())->willReturn($this->entityManager->reveal());

        $projectMemberCreatedHandler = new ProjectMemberCreatedHandler(
            $this->managerRegistry->reveal(),
            $this->projectMemberNotifier->reveal()
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/{$message->getProjectMemberClass()}/");
        $this->expectExceptionMessageMatches("/{$message->getProjectMemberId()}/");

        $projectMemberCreatedHandler($message);

        $this->entityManager->find($message->getProjectMemberClass(), $message->getProjectMemberId())->shouldHaveBeenCalledOnce();
        $this->managerRegistry->getManagerForClass($message->getProjectMemberClass())->shouldHaveBeenCalledOnce();
        $this->projectMemberNotifier->notifyProjectPublication(Argument::any())->shouldNotHaveBeenCalled();
    }

    public function providerUnknownProjectMember(): iterable
    {
        $prefix = 'It should correctly handle an unknown ';

        foreach ($this->getAbstractProjectMemberClasses() as $class) {
            /** @var AbstractProjectMember|ObjectProphecy $projectMember */
            $projectMember = $this->prophesize($class);
            $projectMember->getId()->willReturn(1);

            yield $prefix . $class => [$projectMember->reveal()];
        }
    }

    /**
     * @throws JsonException
     *
     * @covers ::__invoke
     *
     * @dataProvider providerWrongStatusProject
     */
    public function testWrongStatusProject(AbstractProjectMember $projectMember)
    {
        $message = new ProjectMemberCreated($projectMember);

        $this->entityManager->find($message->getProjectMemberClass(), $message->getProjectMemberId())->willReturn($projectMember);
        $this->managerRegistry->getManagerForClass($message->getProjectMemberClass())->willReturn($this->entityManager->reveal());

        $projectMemberCreatedHandler = new ProjectMemberCreatedHandler(
            $this->managerRegistry->reveal(),
            $this->projectMemberNotifier->reveal()
        );

        $projectMemberCreatedHandler($message);

        $this->entityManager->find($message->getProjectMemberClass(), $message->getProjectMemberId())->shouldHaveBeenCalledOnce();
        $this->managerRegistry->getManagerForClass($message->getProjectMemberClass())->shouldHaveBeenCalledOnce();
        $this->projectMemberNotifier->notifyProjectPublication(Argument::any())->shouldNotHaveBeenCalled();
    }

    public function providerWrongStatusProject(): iterable
    {
        foreach (array_filter(Project::getAvailableStatuses(), static fn ($status) => Project::STATUS_PUBLISHED !== $status) as $status) {
            $project = $this->prophesize(Project::class);
            $project->getCurrentStatus()->willReturn($status);
            $project->isPublished()->willReturn(false);

            foreach ($this->getAbstractProjectMemberClasses() as $class) {
                /** @var AbstractProjectMember|ObjectProphecy $projectMember */
                $projectMember = $this->prophesize($class);
                $projectMember->getId()->willReturn(1);
                $projectMember->getProject()->willReturn($project);

                yield sprintf('It should not notify with status %d and class %s', $status, $class) => [$projectMember->reveal()];
            }
        }
    }

    /**
     * @throws JsonException
     *
     * @dataProvider providerSuccess
     */
    public function testSuccess(AbstractProjectMember $projectMember)
    {
        $message = new ProjectMemberCreated($projectMember);

        $this->entityManager->find($message->getProjectMemberClass(), $message->getProjectMemberId())->willReturn($projectMember);
        $this->managerRegistry->getManagerForClass($message->getProjectMemberClass())->willReturn($this->entityManager->reveal());

        $projectMemberCreatedHandler = new ProjectMemberCreatedHandler(
            $this->managerRegistry->reveal(),
            $this->projectMemberNotifier->reveal()
        );

        $projectMemberCreatedHandler($message);

        $this->entityManager->find($message->getProjectMemberClass(), $message->getProjectMemberId())->shouldHaveBeenCalledOnce();
        $this->managerRegistry->getManagerForClass($message->getProjectMemberClass())->shouldHaveBeenCalledOnce();
        $this->projectMemberNotifier->notifyProjectPublication($projectMember)->shouldHaveBeenCalledOnce();
    }

    public function providerSuccess(): iterable
    {
        $project = $this->prophesize(Project::class);
        $project->getCurrentStatus()->willReturn(Project::STATUS_PUBLISHED);
        $project->isPublished()->willReturn(true);

        foreach ($this->getAbstractProjectMemberClasses() as $class) {
            /** @var AbstractProjectMember|ObjectProphecy $projectMember */
            $projectMember = $this->prophesize($class);
            $projectMember->getId()->willReturn(1);
            $projectMember->getProject()->willReturn($project);

            yield sprintf('It should notify with class %s', $class) => [$projectMember->reveal()];
        }
    }

    private function getAbstractProjectMemberClasses(): iterable
    {
        yield BorrowerMember::class;
        yield AgentMember::class;
        yield ParticipationMember::class;
    }
}
