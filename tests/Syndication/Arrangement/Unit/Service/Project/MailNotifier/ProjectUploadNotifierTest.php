<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\Unit\Service\Project\MailNotifier;

use InvalidArgumentException;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Staff;
use KLS\Core\Mailer\MailjetMessage;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use KLS\Syndication\Arrangement\Repository\ProjectRepository;
use KLS\Syndication\Arrangement\Service\Project\MailNotifier\ProjectUploadNotifier;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @coversDefaultClass \KLS\Syndication\Arrangement\Service\Project\MailNotifier\ProjectUploadNotifier
 *
 * @internal
 */
class ProjectUploadNotifierTest extends TestCase
{
    use UserStaffTrait;
    use ProphecyTrait;

    /** @var MailerInterface|ObjectProphecy */
    private $mailer;
    /** @var RouterInterface|ObjectProphecy */
    private $router;
    /** @var ProjectRepository|ObjectProphecy */
    private $projectRepository;
    /** @var ProjectRepository|LoggerInterface */
    private $logger;

    protected function setUp(): void
    {
        $this->mailer            = $this->prophesize(MailerInterface::class);
        $this->router            = $this->prophesize(RouterInterface::class);
        $this->projectRepository = $this->prophesize(ProjectRepository::class);
        $this->logger            = $this->prophesize(LoggerInterface::class);
    }

    protected function tearDown(): void
    {
        $this->mailer            = null;
        $this->router            = null;
        $this->projectRepository = null;
    }

    /**
     * @covers ::notify
     */
    public function testNotify(): void
    {
        $staff                 = $this->createStaff();
        $project               = $this->createProject($staff, ProjectStatus::STATUS_PARTICIPANT_REPLY);
        $projectParticipation1 = $this->createProjectParticipation(
            $this->createStaff()->getCompany(),
            $staff,
            $project
        );
        $projectParticipation2 = $this->createProjectParticipation(
            $this->createStaff()->getCompany(),
            $staff,
            $project
        );
        $projectParticipation1->addProjectParticipationMember(
            new ProjectParticipationMember($projectParticipation1, $staff, $staff)
        );
        $projectParticipation2->addProjectParticipationMember(
            new ProjectParticipationMember($projectParticipation2, $staff, $staff)
        );
        $project->addProjectParticipation($projectParticipation1);
        $project->addProjectParticipation($projectParticipation2);

        $this->projectRepository->find(1)->shouldBeCalledOnce()->willReturn($project);
        $this->router->generate(
            'front_viewParticipation',
            ['projectParticipationPublicId' => $projectParticipation1->getPublicId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        )
            ->shouldBeCalledOnce()
            ->willReturn('/participation/' . $projectParticipation1->getPublicId())
        ;
        $this->router->generate(
            'front_viewParticipation',
            ['projectParticipationPublicId' => $projectParticipation2->getPublicId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        )
            ->shouldBeCalledOnce()
            ->willReturn('/participation/' . $projectParticipation2->getPublicId())
        ;
        $this->mailer->send(Argument::type(MailjetMessage::class))->shouldBeCalledTimes(2);

        $notifier = $this->createTestObject();
        $result   = $notifier->notify(['projectId' => 1]);

        static::assertSame(2, $result);
    }

    /**
     * @covers ::notify
     */
    public function testNotifyNotSupports(): void
    {
        $this->projectRepository->find(Argument::any())->shouldNotBeCalled();
        $this->router->generate(Argument::cetera())->shouldNotBeCalled();
        $this->mailer->send(Argument::any())->shouldNotBeCalled();

        $notifier = $this->createTestObject();
        $result   = $notifier->notify(['id' => 1]);

        static::assertSame(0, $result);
    }

    /**
     * @covers ::notify
     *
     * @dataProvider noMailSentProvider
     */
    public function testNotifyNoMailSent(Project $project): void
    {
        $this->projectRepository->find(1)->shouldBeCalledOnce()->willReturn($project);
        $this->router->generate(Argument::cetera())->shouldNotBeCalled();
        $this->mailer->send(Argument::any())->shouldNotBeCalled();

        $notifier = $this->createTestObject();
        $result   = $notifier->notify(['projectId' => 1]);

        static::assertSame(0, $result);
    }

    public function noMailSentProvider(): iterable
    {
        $staff   = $this->createStaff();
        $project = $this->createProject($staff, ProjectStatus::STATUS_PARTICIPANT_REPLY);
        $project->addProjectParticipation(
            $this->createProjectParticipation($this->createStaff()->getCompany(), $staff, $project)
        );
        $project->addProjectParticipation(
            $this->createProjectParticipation($this->createStaff()->getCompany(), $staff, $project)
        );

        yield 'no projectParticipationMember' => [$project];
        yield 'no projectParticipation' => [$this->createProject($staff, ProjectStatus::STATUS_PARTICIPANT_REPLY)];
        yield 'project in draft' => [$this->createProject($staff, ProjectStatus::STATUS_DRAFT)];
        yield 'project in interest' => [$this->createProject($staff, ProjectStatus::STATUS_INTEREST_EXPRESSION)];
    }

    /**
     * @covers ::notify
     */
    public function testNotifyExceptionWithProjectNotFound(): void
    {
        $this->projectRepository->find(42)->shouldBeCalledOnce()->willReturn(null);
        $this->router->generate(Argument::cetera())->shouldNotBeCalled();
        $this->mailer->send(Argument::any())->shouldNotBeCalled();

        static::expectException(InvalidArgumentException::class);

        $notifier = $this->createTestObject();
        $notifier->notify(['projectId' => 42]);
    }

    private function createProject(Staff $staff, ?int $status = null): Project
    {
        $project = new Project($staff, 'risk1', new Money('EUR', '42'));
        $project->setTitle('Project');

        if (null !== $status) {
            $project->setCurrentStatus(new ProjectStatus($project, $status, $staff));
        }

        return $project;
    }

    private function createProjectParticipation(Company $company, Staff $staff, Project $project): ProjectParticipation
    {
        $projectParticipation = new ProjectParticipation($company, $project, $staff);
        $projectParticipation->setPublicId();

        return $projectParticipation;
    }

    private function createTestObject(): ProjectUploadNotifier
    {
        return new ProjectUploadNotifier(
            $this->mailer->reveal(),
            $this->router->reveal(),
            $this->projectRepository->reveal(),
            $this->logger->reveal()
        );
    }
}
