<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\Unit\MessageHandler;

use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Message\ProjectCreated;
use KLS\Syndication\Agency\MessageHandler\ProjectCreatedHandler;
use KLS\Syndication\Agency\Notifier\ProjectCreatedNotifier;
use KLS\Syndication\Agency\Repository\ProjectRepository;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use KLS\Test\Syndication\Agency\Unit\Traits\AgencyProjectTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\Syndication\Agency\MessageHandler\ProjectCreatedHandler
 *
 * @internal
 */
class ProjectCreatedHandlerTest extends TestCase
{
    use AgencyProjectTrait;
    use UserStaffTrait;
    use PropertyValueTrait;
    use ProphecyTrait;

    /** @var ProjectRepository|ObjectProphecy */
    private $projectRepository;

    /** @var ProjectCreatedNotifier|ObjectProphecy */
    private $agencyCreatedNotifier;

    protected function setUp(): void
    {
        $this->projectRepository     = $this->prophesize(ProjectRepository::class);
        $this->agencyCreatedNotifier = $this->prophesize(ProjectCreatedNotifier::class);
    }

    protected function tearDown(): void
    {
        $this->projectRepository     = null;
        $this->agencyCreatedNotifier = null;
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeWithoutProjectFound(): void
    {
        $staff   = $this->createStaff();
        $project = $this->createAgencyProject($staff);
        $this->forcePropertyValue($project, 'id', 1);

        $agencyCreated = new ProjectCreated($project);

        $this->projectRepository->find(1)->shouldBeCalledOnce()->willReturn(null);

        $this->agencyCreatedNotifier->notify(Argument::type(Project::class))->shouldNotBeCalled();

        $this->createTestObject()($agencyCreated);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeNotifyProjectIsCreated(): void
    {
        $staff   = $this->createStaff();
        $project = $this->createAgencyProject($staff);
        $this->forcePropertyValue($project, 'id', 1);

        $agencyCreated = new ProjectCreated($project);

        $this->projectRepository->find(1)->shouldBeCalledOnce()->willReturn($project);
        $this->agencyCreatedNotifier->notify(Argument::type(Project::class))->shouldBeCalledOnce();

        $this->createTestObject()($agencyCreated);
    }

    private function createTestObject(): ProjectCreatedHandler
    {
        return new ProjectCreatedHandler(
            $this->projectRepository->reveal(),
            $this->agencyCreatedNotifier->reveal()
        );
    }
}
