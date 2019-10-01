<?php

declare(strict_types=1);

namespace Unilend\Test\unitTest\Service\Staff;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Unilend\Entity\MarketSegment;
use Unilend\Repository\{ClientsRepository, CompaniesRepository, StaffRepository};
use Unilend\Service\{Company\CompanyManager, Staff\StaffManager};

/**
 * @internal
 *
 * @coversDefaultClass \Unilend\Service\Staff\StaffManager
 */
class StaffManagerTest extends TestCase
{
    /** @var ObjectProphecy */
    private $companyManager;
    /** @var ObjectProphecy */
    private $clientsRepository;
    /** @var ObjectProphecy */
    private $companiesRepository;
    /** @var ObjectProphecy */
    private $staffRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->companyManager      = $this->prophesize(CompanyManager::class);
        $this->clientsRepository   = $this->prophesize(ClientsRepository::class);
        $this->companiesRepository = $this->prophesize(CompaniesRepository::class);
        $this->staffRepository     = $this->prophesize(StaffRepository::class);
    }

    /**
     * @covers ::getConcernedStaff
     */
    public function testGetConcernedRoles(): void
    {
        $marketSegmentLabel = 'real_estate_development';
        $marketSegment      = new MarketSegment();
        $marketSegment->setLabel($marketSegmentLabel);
        $role = 'ROLE_STAFF_MARKET_' . mb_strtoupper($marketSegmentLabel);

        $concernedRoles = $this->createTestObject()->getConcernedRoles($marketSegment);

        static::assertSame([$role], $concernedRoles);
    }

    /**
     * @return StaffManager
     */
    private function createTestObject(): StaffManager
    {
        return new StaffManager(
            $this->companyManager->reveal(),
            $this->clientsRepository->reveal(),
            $this->companiesRepository->reveal(),
            $this->staffRepository->reveal()
        );
    }
}
