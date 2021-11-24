<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service\Reporting;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\DateFilterInterface;
use KLS\Core\Entity\Constant\MathOperator;
use KLS\CreditGuaranty\FEI\DTO\Query;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Constant\ReportingFilter;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplateField;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Service\Reporting\ReportingQueryGenerator;
use KLS\CreditGuaranty\FEI\Service\Reporting\ReportingQueryHelper;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReportingTemplateTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\Reporting\ReportingQueryGenerator
 *
 * @internal
 */
class ReportingQueryGeneratorTest extends TestCase
{
    use ProphecyTrait;
    use ReportingTemplateTrait;

    /** @var FieldRepository|ObjectProphecy */
    private $fieldRepository;
    /** @var ReportingQueryHelper|ObjectProphecy */
    private $reportingQueryHelper;

    protected function setUp(): void
    {
        $this->fieldRepository      = $this->prophesize(FieldRepository::class);
        $this->reportingQueryHelper = $this->prophesize(ReportingQueryHelper::class);
    }

    protected function tearDown(): void
    {
        $this->fieldRepository      = null;
        $this->reportingQueryHelper = null;
    }

    /**
     * @covers ::generate
     *
     * @dataProvider dataProvider
     */
    public function testGenerate(
        array $dataFilters,
        Query $expected,
        ?ReportingTemplate $reportingTemplate = null
    ): void {
        $fieldMappings = $this->getFieldAliasQueryMapping();

        $filters = [];
        foreach ($dataFilters as $filterKey => $data) {
            $filters[$filterKey] = $data['fieldAliasFilter'] ?? $data['filter'];
        }

        if ($reportingTemplate instanceof ReportingTemplate) {
            /** @var Field[]|array $fields */
            $fields = $reportingTemplate->getReportingTemplateFields()
                ->map(static fn (ReportingTemplateField $rtf) => $rtf->getField())
            ;
            $this->fieldRepository->findAll()->shouldNotBeCalled();

            // selects
            foreach ($fields as $field) {
                $mapping = $fieldMappings[$field->getFieldAlias()];

                $this->reportingQueryHelper->getPropertyPath($field, true)
                    ->shouldBeCalledOnce()
                    ->willReturn($mapping['propertyPathFormatted'])
                ;
                $this->reportingQueryHelper->generateJoinByField($field)
                    ->shouldBeCalled()
                    ->willReturn($mapping['joins'])
                ;
            }
        } else {
            /** @var Field[]|array $fields */
            $fields = $this->createFieldsForReportingTemplate();
            $this->fieldRepository->findAll()->shouldBeCalledOnce()->willReturn($fields);
        }

        // filters
        foreach ($dataFilters as $filterKey => $data) {
            if (false === empty($data['fieldAliasFilter'])) {
                // clean filters
                $this->reportingQueryHelper->isFieldAliasFilterValid($filterKey, $data['fieldAliasFilter'])
                    ->shouldBeCalledOnce()
                    ->willReturn($data['filterValid'])
                ;
                // clauses
                if ($data['filterValid']) {
                    $this->reportingQueryHelper->generateClauseByFilter($filterKey, $data['fieldAliasFilter'])
                        ->shouldBeCalledOnce()
                        ->willReturn($this->getFilterClauseByKey($filterKey))
                    ;
                } else {
                    $this->reportingQueryHelper->generateClauseByFilter($filterKey, $data['fieldAliasFilter'])
                        ->shouldNotBeCalled()
                    ;
                }
            }
            // search
            if (ReportingFilter::FILTER_SEARCH === $filterKey) {
                if ($data['filterValid']) {
                    foreach ($fields as $field) {
                        $mapping = $fieldMappings[$field->getFieldAlias()];

                        $searchExpression = $mapping['searchable']
                            ? $mapping['propertyPathNotFormatted'] . ' LIKE :search'
                            : null;
                        $this->reportingQueryHelper->generateSearchExpressionByField($field)
                            ->shouldBeCalledOnce()
                            ->willReturn($searchExpression)
                        ;
                    }
                } else {
                    $this->reportingQueryHelper->generateSearchExpressionByField(Argument::any())->shouldNotBeCalled();
                }
            }
        }

        $reportingQueryGenerator = $this->createTestObject();
        $result                  = $reportingQueryGenerator->generate($filters, $reportingTemplate);

        static::assertInstanceOf(Query::class, $result);
        static::assertSame($expected->getSelects(), $result->getSelects());
        static::assertSame($expected->getJoins(), $result->getJoins());
        static::assertSame($expected->getClauses(), $result->getClauses());
        static::assertSame($expected->getOrders(), $result->getOrders());
    }

    public function dataProvider(): iterable
    {
        $reportingTemplate = $this->createReportingTemplate('Test');
        $fields            = $this->createFieldsForReportingTemplate();
        $this->withMultipleReportingTemplateFields($reportingTemplate, $fields);

        yield 'reportingTemplate and valid filters' => [
            [
                'filter' => [
                    'filter' => 'value',
                ],
                ReportingFilter::FILTER_SEARCH => [
                    'filter'      => 'search',
                    'filterValid' => true,
                ],
                ReportingFilter::FILTER_REPORTING_DATES => [
                    'fieldAliasFilter' => 'null',
                    'filterValid'      => true,
                ],
                FieldAlias::FIRST_RELEASE_DATE => [
                    'fieldAliasFilter' => [DateFilterInterface::PARAMETER_STRICTLY_AFTER => '2021-01-01'],
                    'filterValid'      => true,
                ],
                FieldAlias::RESERVATION_EXCLUSION_DATE => [
                    'fieldAliasFilter' => [DateFilterInterface::PARAMETER_STRICTLY_BEFORE => '2021-01-01'],
                    'filterValid'      => true,
                ],
                FieldAlias::RESERVATION_SIGNING_DATE => [
                    'fieldAliasFilter' => [MathOperator::SUPERIOR_OR_EQUAL => '12'],
                    'filterValid'      => true,
                ],
                FieldAlias::LOAN_REMAINING_CAPITAL => [
                    'fieldAliasFilter' => '42',
                    'filterValid'      => true,
                ],
                FieldAlias::BORROWER_TYPE => [
                    'filter'      => [DateFilterInterface::PARAMETER_AFTER => 'type'],
                    'filterValid' => false,
                ],
                'order' => [
                    'filter' => [
                        FieldAlias::BENEFICIARY_NAME => 'desc',
                        FieldAlias::LOAN_NAF_CODE    => 'ASC',
                    ],
                ],
            ],
            $this->createQuery(
                $fields,
                [
                    $this->getFilterClauseByKey(ReportingFilter::FILTER_SEARCH),
                    $this->getFilterClauseByKey(ReportingFilter::FILTER_REPORTING_DATES),
                    $this->getFilterClauseByKey(FieldAlias::FIRST_RELEASE_DATE),
                    $this->getFilterClauseByKey(FieldAlias::RESERVATION_EXCLUSION_DATE),
                    $this->getFilterClauseByKey(FieldAlias::RESERVATION_SIGNING_DATE),
                    $this->getFilterClauseByKey(FieldAlias::LOAN_REMAINING_CAPITAL),
                ],
                [
                    FieldAlias::BENEFICIARY_NAME => 'desc',
                    FieldAlias::LOAN_NAF_CODE    => 'ASC',
                ],
                true
            ),
            $reportingTemplate,
        ];

        yield 'reportingTemplate and invalid filters' => [
            [
                'searchh' => [
                    'filter' => 'search',
                ],
                ReportingFilter::FILTER_SEARCH => [
                    'filter'      => ['search'],
                    'filterValid' => false,
                ],
                ReportingFilter::FILTER_REPORTING_DATES => [
                    'fieldAliasFilter' => ['null'],
                    'filterValid'      => false,
                ],
                FieldAlias::FIRST_RELEASE_DATE => [
                    'fieldAliasFilter' => [MathOperator::SUPERIOR => '2021-01-01'],
                    'filterValid'      => false,
                ],
                FieldAlias::RESERVATION_EXCLUSION_DATE => [
                    'fieldAliasFilter' => '01-01',
                    'filterValid'      => false,
                ],
                FieldAlias::RESERVATION_SIGNING_DATE => [
                    'fieldAliasFilter' => [MathOperator::SUPERIOR_OR_EQUAL => '2021-01-01'],
                    'filterValid'      => false,
                ],
                FieldAlias::LOAN_REMAINING_CAPITAL => [
                    'fieldAliasFilter' => [MathOperator::EQUAL => 'null'],
                    'filterValid'      => false,
                ],
                'orders' => [
                    'filter'      => [],
                    'filterValid' => false,
                ],
                'order' => [
                    'filter' => [
                        'non_existent_field_alias'   => 'ascendant',
                        FieldAlias::BENEFICIARY_NAME => 'descendant',
                        FieldAlias::LOAN_NAF_CODE    => ['ASC'],
                    ],
                ],
            ],
            $this->createQuery($fields, [], [], true),
            $reportingTemplate,
        ];

        yield 'no reportingTemplate and no filters' => [
            [],
            $this->createQuery($fields, [], []),
            null,
        ];
    }

    /**
     * @param Field[]|array $fields
     */
    private function createQuery(
        array $fields,
        array $clauses = [],
        array $orders = [],
        bool $haReportingTemplate = false
    ): Query {
        $fieldMappings = $this->getFieldAliasQueryMapping();

        $query = new Query();

        if ($haReportingTemplate) {
            $initSelects = [
                FieldAlias::REPORTING_FIRST_DATE      => 'financingObjects.reportingFirstDate',
                FieldAlias::REPORTING_LAST_DATE       => 'financingObjects.reportingLastDate',
                FieldAlias::REPORTING_VALIDATION_DATE => 'financingObjects.reportingValidationDate',
            ];

            foreach ($initSelects as $fieldAlias => $propertyPath) {
                $query->addSelect(\sprintf('DATE_FORMAT(%s, %s) AS %s', $propertyPath, '\'%Y-%m-%d\'', $fieldAlias));
            }

            foreach ($fields as $field) {
                $fieldAlias = $field->getFieldAlias();
                $mapping    = $fieldMappings[$fieldAlias];

                $query->addSelect($mapping['propertyPathFormatted'] . ' AS ' . $fieldAlias);

                foreach ($mapping['joins'] as $key => $join) {
                    $query->addJoin([$key => $join]);
                }
            }
        }

        foreach ($clauses as $clause) {
            $query->addClause($clause);
        }

        foreach ($orders as $key => $order) {
            $query->addOrder([$key => $order]);
        }

        return $query;
    }

    private function createTestObject(): ReportingQueryGenerator
    {
        return new ReportingQueryGenerator(
            $this->fieldRepository->reveal(),
            $this->reportingQueryHelper->reveal()
        );
    }
}
