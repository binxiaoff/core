<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service\Reporting;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\DateFilterInterface;
use KLS\Core\Entity\Constant\MathOperator;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Constant\ReportingFilter;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Service\Reporting\ReportingQueryHelper;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReportingTemplateTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\Reporting\ReportingQueryHelper
 *
 * @internal
 */
class ReportingQueryHelperTest extends TestCase
{
    use ProphecyTrait;
    use ReportingTemplateTrait;

    /** @var FieldRepository|ObjectProphecy */
    private $fieldRepository;

    protected function setUp(): void
    {
        $this->fieldRepository = $this->prophesize(FieldRepository::class);
    }

    protected function tearDown(): void
    {
        $this->fieldRepository = null;
    }

    public function reportingTemplateFieldsProvider(): iterable
    {
        foreach ($this->createFieldsForReportingTemplate() as $field) {
            yield $field->getFieldAlias() => [$field];
        }
    }

    /**
     * @covers ::getMappingOperatorsByFilterKey
     *
     * @dataProvider reportingTemplateFieldsProvider
     */
    public function testGetMappingOperatorsByFilterKey(Field $field): void
    {
        $fieldMappings = $this->getFieldAliasQueryMapping();
        $fieldAlias    = $field->getFieldAlias();
        $expected      = $fieldMappings[$fieldAlias]['mappingOperators'];

        $reportingQueryGenerator = $this->createTestObject();
        static::assertSame($expected, $reportingQueryGenerator->getMappingOperatorsByFilterKey($fieldAlias));
    }

    /**
     * @covers ::getPropertyPath
     *
     * @dataProvider reportingTemplateFieldsProvider
     */
    public function testGetPropertyPathFormatted(Field $field): void
    {
        $fieldMappings = $this->getFieldAliasQueryMapping();
        $expected      = $fieldMappings[$field->getFieldAlias()]['propertyPathFormatted'];

        $reportingQueryGenerator = $this->createTestObject();
        static::assertSame($expected, $reportingQueryGenerator->getPropertyPath($field, true));
    }

    /**
     * @covers ::getPropertyPath
     *
     * @dataProvider reportingTemplateFieldsProvider
     */
    public function testGetPropertyPathNotFormatted(Field $field): void
    {
        $fieldMappings = $this->getFieldAliasQueryMapping();
        $expected      = $fieldMappings[$field->getFieldAlias()]['propertyPathNotFormatted'];

        $reportingQueryGenerator = $this->createTestObject();
        static::assertSame($expected, $reportingQueryGenerator->getPropertyPath($field, false));
    }

    /**
     * @covers ::generateSearchExpressionByField
     *
     * @dataProvider reportingTemplateFieldsProvider
     */
    public function testGenerateSearchExpressionByField(Field $field): void
    {
        $fieldMappings = $this->getFieldAliasQueryMapping();
        $mapping       = $fieldMappings[$field->getFieldAlias()];
        $expected      = $mapping['searchable'] ? $mapping['propertyPathNotFormatted'] . ' LIKE :search' : null;

        $reportingQueryGenerator = $this->createTestObject();
        static::assertSame($expected, $reportingQueryGenerator->generateSearchExpressionByField($field));
    }

    /**
     * @covers ::generateJoinByField
     *
     * @dataProvider reportingTemplateFieldsProvider
     */
    public function testGenerateJoinByField(Field $field): void
    {
        $fieldMappings = $this->getFieldAliasQueryMapping();
        $expected      = $fieldMappings[$field->getFieldAlias()]['joins'];

        $reportingQueryGenerator = $this->createTestObject();
        static::assertSame($expected, \iterator_to_array($reportingQueryGenerator->generateJoinByField($field)));
    }

    /**
     * @covers ::generateClauseByFilter
     *
     * @dataProvider validFilterFieldAliasProvider
     *
     * @param string|array $filter
     */
    public function testGenerateClauseByFilter(string $key, $filter, ?Field $field): void
    {
        if ($field instanceof Field) {
            $this->fieldRepository->findOneBy(['fieldAlias' => $field->getFieldAlias()])
                ->shouldBeCalledOnce()
                ->willReturn($field)
            ;
        } else {
            $this->fieldRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        }

        $reportingQueryGenerator = $this->createTestObject();
        static::assertSame(
            $this->getFilterClauseByKey($key),
            $reportingQueryGenerator->generateClauseByFilter($key, $filter)
        );
    }

    public function validFilterFieldAliasProvider(): iterable
    {
        yield ReportingFilter::FILTER_REPORTING_DATES => [
            ReportingFilter::FILTER_REPORTING_DATES,
            'null',
            null,
        ];
        yield FieldAlias::FIRST_RELEASE_DATE => [
            FieldAlias::FIRST_RELEASE_DATE,
            [DateFilterInterface::PARAMETER_STRICTLY_AFTER => '2021-01-01'],
            $this->createFirstReleaseDateField(),
        ];
        yield FieldAlias::RESERVATION_SIGNING_DATE => [
            FieldAlias::RESERVATION_SIGNING_DATE,
            [MathOperator::SUPERIOR_OR_EQUAL => '12'],
            $this->createReservationSigningDateField(),
        ];
        yield FieldAlias::RESERVATION_EXCLUSION_DATE => [
            FieldAlias::RESERVATION_EXCLUSION_DATE,
            [DateFilterInterface::PARAMETER_BEFORE => '2021-01-01'],
            null,
        ];
        yield FieldAlias::LOAN_REMAINING_CAPITAL => [
            FieldAlias::LOAN_REMAINING_CAPITAL,
            '42',
            $this->createLoanRemainingCapitalField(),
        ];
    }

    /**
     * @covers ::generateClauseByFilter
     *
     * @dataProvider invalidFilterFieldAliasProvider
     *
     * @param string|array $filter
     */
    public function testGenerateClauseByFilterEmpty(string $key, $filter): void
    {
        $this->fieldRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        $reportingQueryGenerator = $this->createTestObject();
        static::assertEmpty($reportingQueryGenerator->generateClauseByFilter($key, $filter));
    }

    public function invalidFilterFieldAliasProvider(): iterable
    {
        yield ReportingFilter::FILTER_REPORTING_DATES => [
            ReportingFilter::FILTER_REPORTING_DATES,
            [DateFilterInterface::PARAMETER_AFTER => 'null'],
        ];
        yield FieldAlias::FIRST_RELEASE_DATE => [
            FieldAlias::FIRST_RELEASE_DATE,
            [MathOperator::SUPERIOR => '2021-01-01'],
        ];
        yield FieldAlias::RESERVATION_SIGNING_DATE => [
            FieldAlias::RESERVATION_SIGNING_DATE,
            [DateFilterInterface::PARAMETER_STRICTLY_BEFORE => '2021-01-01'],
        ];
        yield FieldAlias::RESERVATION_EXCLUSION_DATE => [
            FieldAlias::RESERVATION_EXCLUSION_DATE,
            '2021-01-01',
        ];
        yield FieldAlias::LOAN_REMAINING_CAPITAL => [
            FieldAlias::LOAN_REMAINING_CAPITAL,
            ['42'],
        ];
    }

    /**
     * @covers ::isFieldAliasFilterValid
     *
     * @dataProvider validFiltersProvider
     *
     * @param string|array $filter
     */
    public function testIsFieldAliasFilterValid(string $key, $filter): void
    {
        $reportingQueryGenerator = $this->createTestObject();
        static::assertTrue($reportingQueryGenerator->isFieldAliasFilterValid($key, $filter));
    }

    public function validFiltersProvider(): iterable
    {
        $first  = ' 1';
        $second = ' 2';

        yield ReportingFilter::FILTER_REPORTING_DATES . $first => [
            ReportingFilter::FILTER_REPORTING_DATES,
            'null',
        ];
        yield ReportingFilter::FILTER_REPORTING_DATES . $second => [
            ReportingFilter::FILTER_REPORTING_DATES,
            [DateFilterInterface::PARAMETER_AFTER => '2021-01-01'],
        ];
        yield FieldAlias::FIRST_RELEASE_DATE . $first => [
            FieldAlias::FIRST_RELEASE_DATE,
            '2021-01-01',
        ];
        yield FieldAlias::FIRST_RELEASE_DATE . $second => [
            FieldAlias::FIRST_RELEASE_DATE,
            [DateFilterInterface::PARAMETER_STRICTLY_AFTER => '2021-01-01'],
        ];
        yield FieldAlias::RESERVATION_SIGNING_DATE . $first => [
            FieldAlias::RESERVATION_SIGNING_DATE,
            '12',
        ];
        yield FieldAlias::RESERVATION_SIGNING_DATE . $second => [
            FieldAlias::RESERVATION_SIGNING_DATE,
            [MathOperator::SUPERIOR_OR_EQUAL => '12'],
        ];
        yield FieldAlias::RESERVATION_EXCLUSION_DATE . $first => [
            FieldAlias::RESERVATION_EXCLUSION_DATE,
            '2021-01-01',
        ];
        yield FieldAlias::RESERVATION_EXCLUSION_DATE . $second => [
            FieldAlias::RESERVATION_EXCLUSION_DATE,
            [DateFilterInterface::PARAMETER_BEFORE => '2021-01-01'],
        ];
        yield FieldAlias::LOAN_REMAINING_CAPITAL . $second => [
            FieldAlias::LOAN_REMAINING_CAPITAL,
            '42',
        ];
        yield FieldAlias::LOAN_REMAINING_CAPITAL . $first => [
            FieldAlias::LOAN_REMAINING_CAPITAL,
            [MathOperator::INFERIOR => '42'],
        ];
    }

    /**
     * @covers ::isFieldAliasFilterValid
     *
     * @dataProvider invalidFiltersProvider
     *
     * @param string|array $filter
     */
    public function testIsFieldAliasFilterInvalid(string $key, $filter): void
    {
        $reportingQueryGenerator = $this->createTestObject();
        static::assertFalse($reportingQueryGenerator->isFieldAliasFilterValid($key, $filter));
    }

    public function invalidFiltersProvider(): iterable
    {
        $first  = ' 1';
        $second = ' 2';

        yield ReportingFilter::FILTER_REPORTING_DATES . $first => [
            ReportingFilter::FILTER_REPORTING_DATES,
            '42',
        ];
        yield ReportingFilter::FILTER_REPORTING_DATES . $second => [
            ReportingFilter::FILTER_REPORTING_DATES,
            [DateFilterInterface::PARAMETER_AFTER => '01-01'],
        ];
        yield FieldAlias::FIRST_RELEASE_DATE . $first => [
            FieldAlias::FIRST_RELEASE_DATE,
            '42',
        ];
        yield FieldAlias::FIRST_RELEASE_DATE . $second => [
            FieldAlias::FIRST_RELEASE_DATE,
            [MathOperator::SUPERIOR_OR_EQUAL => '2021-01-01'],
        ];
        yield FieldAlias::RESERVATION_SIGNING_DATE . $first => [
            FieldAlias::RESERVATION_SIGNING_DATE,
            '01-01-2021',
        ];
        yield FieldAlias::RESERVATION_SIGNING_DATE . $second => [
            FieldAlias::RESERVATION_SIGNING_DATE,
            [DateFilterInterface::PARAMETER_STRICTLY_BEFORE => '2021-01-01'],
        ];
        yield FieldAlias::LOAN_REMAINING_CAPITAL . $second => [
            FieldAlias::LOAN_REMAINING_CAPITAL,
            '2021-01-01',
        ];
        yield FieldAlias::LOAN_REMAINING_CAPITAL . $first => [
            FieldAlias::LOAN_REMAINING_CAPITAL,
            [DateFilterInterface::PARAMETER_STRICTLY_AFTER => '42'],
        ];
    }

    private function createTestObject(): ReportingQueryHelper
    {
        return new ReportingQueryHelper($this->fieldRepository->reveal());
    }
}
