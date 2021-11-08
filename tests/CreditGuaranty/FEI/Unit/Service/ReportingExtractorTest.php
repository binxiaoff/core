<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\Project;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use KLS\CreditGuaranty\FEI\Repository\ReservationRepository;
use KLS\CreditGuaranty\FEI\Service\ReportingExtractor;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReportingTemplateTrait;
use PHPUnit\Framework\TestCase;
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

        $filters   = $this->getFilters();
        $paginator = $this->prophesize(Paginator::class);

        $this->reservationRepository->findByReportingFilters(
            $reportingTemplate->getProgram(),
            $filters['selects'],
            $filters['joins'],
            $filters['clauses'],
            [],
            100,
            1
        )
            ->shouldBeCalledOnce()
            ->willReturn($paginator)
        ;

        $reportingExtractor = $this->createTestObject();
        $result             = $reportingExtractor->extracts($reportingTemplate, 100, 1, [], null);

        static::assertInstanceOf(Paginator::class, $result);
    }

    /**
     * @covers ::extracts
     */
    public function testExtractsWithSearch(): void
    {
        $reportingTemplate = $this->createReportingTemplate('Template test');
        $this->withMultipleReportingTemplateFields($reportingTemplate);

        $filters   = $this->getFilters();
        $paginator = $this->prophesize(Paginator::class);

        $filters['clauses'][] = $this->getSearchClause();

        $this->reservationRepository->findByReportingFilters(
            $reportingTemplate->getProgram(),
            $filters['selects'],
            $filters['joins'],
            $filters['clauses'],
            [],
            100,
            1
        )
            ->shouldBeCalledOnce()
            ->willReturn($paginator)
        ;

        $reportingExtractor = $this->createTestObject();
        $result             = $reportingExtractor->extracts($reportingTemplate, 100, 1, [], 'search');

        static::assertInstanceOf(Paginator::class, $result);
    }

    /**
     * @covers ::extracts
     */
    public function testExtractsWithOrders(): void
    {
        $reportingTemplate = $this->createReportingTemplate('Template test');
        $this->withMultipleReportingTemplateFields($reportingTemplate);

        $orders    = ['borrower_type' => 'asc'];
        $filters   = $this->getFilters();
        $paginator = $this->prophesize(Paginator::class);

        $this->reservationRepository->findByReportingFilters(
            $reportingTemplate->getProgram(),
            $filters['selects'],
            $filters['joins'],
            $filters['clauses'],
            $orders,
            100,
            1
        )
            ->shouldBeCalledOnce()
            ->willReturn($paginator)
        ;

        $reportingExtractor = $this->createTestObject();
        $result             = $reportingExtractor->extracts($reportingTemplate, 100, 1, $orders, null);

        static::assertInstanceOf(Paginator::class, $result);
    }

    private function getFilters(): array
    {
        return [
            'selects' => [
                'program.guarantyDuration AS guaranty_duration',
                'rs_reservation_status.status AS reservation_status',
                'pco_borrower_type.description AS borrower_type',
                'CONCAT(project.fundingMoney.amount, \' \', project.fundingMoney.currency) AS project_total_amount',
                'financingObjects.supportingGenerationsRenewal AS supporting_generations_renewal',
            ],
            'joins' => [
                'reservation_status' => [
                    ReservationStatus::class,
                    'rs_reservation_status',
                    'WITH',
                    'rs_reservation_status.id = r.currentStatus',
                ],
                Borrower::class => [
                    'r.borrower',
                    'borrower',
                ],
                'borrower_type' => [
                    ProgramChoiceOption::class,
                    'pco_borrower_type',
                    'WITH',
                    'pco_borrower_type.id = borrower.borrowerType',
                ],
                Project::class => [
                    'r.project',
                    'project',
                ],
            ],
            'clauses' => [],
        ];
    }

    private function getSearchClause(): array
    {
        return [
            'expression' => 'pco_borrower_type.description LIKE :search',
            'parameter'  => [
                'search',
                '%search%',
            ],
        ];
    }

    private function createTestObject(): ReportingExtractor
    {
        return new ReportingExtractor($this->reservationRepository->reveal());
    }
}
