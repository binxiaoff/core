<?php

declare(strict_types=1);

namespace Unilend\Test\unitTest\Service\Project;

use PHPUnit\Framework\TestCase;
use Prophecy\{Argument, Prophecy\ObjectProphecy};
use Psr\Log\LoggerInterface;
use Unilend\Entity\{Clients, Project, ProjectStatusHistory};
use Unilend\Repository\ProjectRepository;
use Unilend\Service\{Project\ProjectStatusManager, User\RealUserFinder};

/**
 * @internal
 *
 * @coversDefaultClass  \Unilend\Service\Project\ProjectStatusManager
 */
class ProjectStatusManagerTest extends TestCase
{
    /** @var ObjectProphecy */
    private $projectRepository;
    /** @var ObjectProphecy */
    private $realUserFinder;
    /** @var ProjectStatusManager */
    private $projectStatusManager;
    /** @var Clients */
    private $realUser;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $logger                  = $this->prophesize(LoggerInterface::class);
        $this->projectRepository = $this->prophesize(ProjectRepository::class);
        $this->realUserFinder    = $this->prophesize(RealUserFinder::class);
        $this->realUser          = new Clients();
        $this->realUserFinder->__invoke()->willReturn($this->realUser);

        $this->projectStatusManager = new ProjectStatusManager($logger->reveal(), $this->projectRepository->reveal(), $this->realUserFinder->reveal());
    }

    /**
     * @covers ::addProjectStatus
     */
    public function testAddProjectStatus(): void
    {
        $project = new Project();
        $status  = ProjectStatusHistory::STATUS_PUBLISHED;
        $this->projectStatusManager->addProjectStatus($status, $project);

        $this->projectRepository->save(Argument::exact($project))->shouldHaveBeenCalled();
        static::assertSame($project->getCurrentProjectStatusHistory()->getStatus(), $status);
        static::assertSame($project->getCurrentProjectStatusHistory()->getAddedBy(), $this->realUser);
    }
}
