<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\Unit\MessageHandler\Agency;

use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Message\Agency\AgencyPublished;
use KLS\Syndication\Agency\MessageHandler\Agency\AgencyPublishedHandler;
use KLS\Syndication\Agency\Repository\ProjectRepository;
use KLS\Syndication\Agency\Service\Notifier\Agency\AgencyPublishedNotifier;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use KLS\Test\Syndication\Agency\Unit\Traits\AgencyProjectTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\Syndication\Agency\MessageHandler\Agency\AgencyPublishedHandler
 *
 * @internal
 */
class AgencyPublishedHandlerTest extends TestCase
{
    use AgencyProjectTrait;
    use UserStaffTrait;
    use PropertyValueTrait;

    /** @var ProjectRepository|ObjectProphecy */
    private $projectRepository;

    /** @var AgencyPublishedNotifier|ObjectProphecy */
    private $agencyPublishedNotifier;

    protected function setUp(): void
    {
        $this->projectRepository       = $this->prophesize(ProjectRepository::class);
        $this->agencyPublishedNotifier = $this->prophesize(AgencyPublishedNotifier::class);
    }

    protected function tearDown(): void
    {
        $this->projectRepository       = null;
        $this->agencyPublishedNotifier = null;
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeWithoutProjectFound(): void
    {
        $staff   = $this->createStaff();
        $project = $this->createAgencyProject($staff);
        $this->forcePropertyValue($project, 'id', 1);

        $agencyPublished = new AgencyPublished($project, 2);

        $this->projectRepository->find(1)->shouldBeCalledOnce()->willReturn(null);

        $this->agencyPublishedNotifier->notify(Argument::type(Project::class))->shouldNotBeCalled();

        $this->createTestObject()($agencyPublished);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeWithWrongProjectStatus(): void
    {
        $staff   = $this->createStaff();
        $project = $this->createAgencyProject($staff);
        $this->forcePropertyValue($project, 'id', 1);

        $agencyPublished = new AgencyPublished($project, Project::STATUS_ARCHIVED);

        $this->projectRepository->find(1)->shouldBeCalledOnce()->willReturn($project);
        $this->agencyPublishedNotifier->notify(Argument::type(Project::class))->shouldNotBeCalled();

        $this->createTestObject()($agencyPublished);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeNotifyProjectHasChangedStatus(): void
    {
        $staff   = $this->createStaff();
        $project = $this->createAgencyProject($staff);
        $this->forcePropertyValue($project, 'id', 1);

        $agencyPublished = new AgencyPublished($project, Project::STATUS_PUBLISHED);

        $this->projectRepository->find(1)->shouldBeCalledOnce()->willReturn($project);
        $this->agencyPublishedNotifier->notify(Argument::type(Project::class))->shouldBeCalledOnce();

        $this->createTestObject()($agencyPublished);
    }

    private function createTestObject(): AgencyPublishedHandler
    {
        return new AgencyPublishedHandler(
            $this->projectRepository->reveal(),
            $this->agencyPublishedNotifier->reveal()
        );
    }
}
