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
    /** @var ProjectRepository|ObjectProphecy */
    private $projectRepository;
    /** @var RealUserFinder|ObjectProphecy */
    private $realUserFinder;
    /** @var ProjectStatusManager */
    private $projectStatusManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        /** @var LoggerInterface|ObjectProphecy $logger */
        $logger                  = $this->prophesize(LoggerInterface::class);
        $this->projectRepository = $this->prophesize(ProjectRepository::class);
        $this->realUserFinder    = $this->prophesize(RealUserFinder::class);
        $addedBy                 = $this->prophesize(Clients::class);

        $addedBy->getIdClient()->willReturn(1);
        $this->realUserFinder->__invoke()->willReturn($addedBy);

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

        $projectStatus = new ProjectStatusHistory();
        $projectStatus->setStatus($status)->setAddedByValue($this->realUserFinder->reveal());

        $this->projectRepository->save(Argument::exact($project))->shouldHaveBeenCalled();
        static::assertSame($project->getCurrentProjectStatusHistory()->getStatus(), $projectStatus->getStatus());
        static::assertSame($project->getCurrentProjectStatusHistory()->getAddedBy(), $projectStatus->getAddedBy());
    }
}
