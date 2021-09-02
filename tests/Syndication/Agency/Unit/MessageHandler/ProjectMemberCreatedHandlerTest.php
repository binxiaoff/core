<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\Unit\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use KLS\Syndication\Agency\Entity\AbstractProjectMember;
use KLS\Syndication\Agency\Entity\AgentMember;
use KLS\Syndication\Agency\Entity\BorrowerMember;
use KLS\Syndication\Agency\Entity\ParticipationMember;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Message\ProjectMemberCreated;
use KLS\Syndication\Agency\MessageHandler\ProjectMemberCreatedHandler;
use KLS\Syndication\Agency\Notifier\ProjectMemberNotifier;
use KLS\Test\Syndication\Agency\Unit\Traits\ProjectMemberSetTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\Syndication\Agency\MessageHandler\ProjectMemberCreatedHandler
 *
 * @internal
 */
class ProjectMemberCreatedHandlerTest extends TestCase
{
    use ProjectMemberSetTrait;

    /** @var ManagerRegistry|ObjectProphecy */
    private $managerRegistry;

    /** @var ProjectMemberNotifier|ObjectProphecy */
    private $projectMemberNotifier;

    /** @var EntityManagerInterface|ObjectProphecy */
    private $entityManager;

    protected function setUp(): void
    {
        $this->managerRegistry       = $this->prophesize(ManagerRegistry::class);
        $this->projectMemberNotifier = $this->prophesize(ProjectMemberNotifier::class);
        $this->entityManager         = $this->prophesize(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        $this->managerRegistry       = null;
        $this->projectMemberNotifier = null;
        $this->entityManager         = null;
    }

    /**
     * @covers ::__invoke
     *
     * @dataProvider projectMembersProvider
     */
    public function testInvoke(AbstractProjectMember $projectMember, string $projectMemberClass): void
    {
        $projectMember->getProject()->setCurrentStatus(Project::STATUS_PUBLISHED);
        $message = new ProjectMemberCreated($projectMember);

        $this->managerRegistry->getManagerForClass($projectMemberClass)->shouldBeCalledOnce()->willReturn($this->entityManager);
        $this->entityManager->find($message->getProjectMemberClass(), $message->getProjectMemberId())->shouldBeCalledOnce()->willReturn($projectMember);
        $this->projectMemberNotifier->notifyProjectPublication($projectMember)->shouldBeCalledOnce();

        $this->createTestObject()($message);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeExceptionWithoutManager(): void
    {
        $projectMember = $this->createAgentMember();
        $message       = new ProjectMemberCreated($projectMember);

        $this->managerRegistry->getManagerForClass($message->getProjectMemberClass())->shouldBeCalledOnce()->willReturn(null);
        $this->entityManager->find(Argument::cetera())->shouldNotBeCalled();
        $this->projectMemberNotifier->notifyProjectPublication(Argument::any())->shouldNotBeCalled();

        static::expectException(InvalidArgumentException::class);

        $this->createTestObject()($message);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeExceptionWithProjectMemberNotFound(): void
    {
        $projectMember = $this->createAgentMember();
        $message       = new ProjectMemberCreated($projectMember);

        $this->managerRegistry->getManagerForClass($message->getProjectMemberClass())->shouldBeCalledOnce()->willReturn($this->entityManager);
        $this->entityManager->find($message->getProjectMemberClass(), $message->getProjectMemberId())->shouldBeCalledOnce()->willReturn(null);
        $this->projectMemberNotifier->notifyProjectPublication(Argument::any())->shouldNotBeCalled();

        static::expectException(InvalidArgumentException::class);

        $this->createTestObject()($message);
    }

    /**
     * @covers ::__invoke
     *
     * @dataProvider projectMembersProvider
     */
    public function testInvokeWithProjectMemberNotPublished(AbstractProjectMember $projectMember, string $projectMemberClass): void
    {
        $message = new ProjectMemberCreated($projectMember);

        $this->managerRegistry->getManagerForClass($projectMemberClass)->shouldBeCalledOnce()->willReturn($this->entityManager);
        $this->entityManager->find($message->getProjectMemberClass(), $message->getProjectMemberId())->shouldBeCalledOnce()->willReturn($projectMember);
        $this->projectMemberNotifier->notifyProjectPublication(Argument::any())->shouldNotBeCalled();

        $this->createTestObject()($message);
    }

    public function projectMembersProvider(): iterable
    {
        yield 'AgentMember' => [$this->createAgentMember(), AgentMember::class];
        yield 'BorrowerMember' => [$this->createBorrowerMember(), BorrowerMember::class];
        yield 'ParticipationMember' => [$this->createParticipationMember(), ParticipationMember::class];
    }

    private function createTestObject(): ProjectMemberCreatedHandler
    {
        return new ProjectMemberCreatedHandler(
            $this->managerRegistry->reveal(),
            $this->projectMemberNotifier->reveal()
        );
    }
}
