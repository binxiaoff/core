<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use KLS\CreditGuaranty\FEI\Repository\ReservationRepository;
use KLS\CreditGuaranty\FEI\Service\ReportingExtractor;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReportingTemplateTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\ReportingExtractor
 *
 * @internal
 */
class ReportingExtractorTest extends TestCase
{
    use ReportingTemplateTrait;
    use ProphecyTrait;

    /** @var ReservationRepository|ObjectProphecy */
    private $reservationRepository;

    protected function setUp(): void
    {
        $this->reservationRepository = $this->prophesize(ReservationRepository::class);
    }

    protected function tearDown(): void
    {
        $this->reservationRepository = null;
    }

    /**
     * @covers ::extracts
     */
    public function testExtracts(): void
    {
        $reportingTemplate = $this->createReportingTemplate('Template test');
        $this->withMultipleReportingTemplateFields($reportingTemplate);

        $paginator = $this->prophesize(Paginator::class);

        $this->reservationRepository->findByReportingFilters(Argument::type('array'), Argument::type('array'), 100, 1)->shouldBeCalledOnce()->willReturn($paginator);

        $reportingExtractor = $this->createTestObject();
        $result             = $reportingExtractor->extracts($reportingTemplate, 100, 1);

        static::assertInstanceOf(Paginator::class, $result);
    }

    private function createTestObject(): ReportingExtractor
    {
        return new ReportingExtractor($this->reservationRepository->reveal());
    }
}
