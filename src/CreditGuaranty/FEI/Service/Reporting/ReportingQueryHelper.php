<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\Reporting;

use DateTime;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use KLS\Core\Entity\NafNace;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Constant\ReportingFilter;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;

class ReportingQueryHelper
{
    private const ALIAS_FORMAT_PROGRAM_CHOICE_OPTION = 'pco_%s';
    private const ALIAS_FORMAT_NAF_NACE              = 'pco_naf_nace_%s';
    private const ALIAS_FORMAT_RESERVATION_STATUS    = 'rs_%s';

    private FieldRepository $fieldRepository;

    public function __construct(FieldRepository $fieldRepository)
    {
        $this->fieldRepository = $fieldRepository;
    }

    public function getMappingOperatorsByFilterKey(string $key): array
    {
        if (\in_array($key, ReportingFilter::DATE_FILTER_KEYS, true)) {
            return ReportingFilter::MAPPING_DATE_OPERATORS;
        }

        if (\in_array($key, ReportingFilter::DURATION_FILTER_KEYS, true)) {
            return ReportingFilter::MAPPING_RANGE_OPERATORS;
        }

        return [];
    }

    public function getPropertyPath(Field $field): string
    {
        $fieldAlias        = $field->getFieldAlias();
        $fieldPropertyPath = $field->getPropertyPath();

        // We return void property path for virtual fields and investment_thematic field
        // because virtual fields values are accessed only by dynamic getters
        // and investment_thematic field was hard to concatenate in sql all at once
        // (problem on using GROUP_CONCAT with a lot of joins).
        // These void property paths are overridden in ReportingNormalizer.
        if (
            \in_array($fieldAlias, FieldAlias::VIRTUAL_FIELDS, true)
            || FieldAlias::INVESTMENT_THEMATIC === $fieldAlias
        ) {
            return '\'\'';
        }

        if ('ProgramChoiceOption' === $field->getPropertyType()) {
            // we only need nace code because CASA needs it in reporting to send to FEI
            if (\array_key_exists($fieldAlias, FieldAlias::NAF_NACE_FIELDS)) {
                return \sprintf(self::ALIAS_FORMAT_NAF_NACE . '.naceCode', $fieldAlias);
            }

            return \sprintf(self::ALIAS_FORMAT_PROGRAM_CHOICE_OPTION . '.description', $fieldAlias);
        }

        $fieldPropertyName = $field->getReservationPropertyName();

        if ('currentStatus' === $fieldPropertyName) {
            return \sprintf(self::ALIAS_FORMAT_RESERVATION_STATUS . '.status', $fieldAlias);
        }

        $propertyPath = (empty($field->getObjectClass()) ? 'r.' : '') . $fieldPropertyName;
        $propertyPath .= (false === empty($fieldPropertyPath)) ? '.' . $fieldPropertyPath : '';

        if (\in_array($field->getPropertyType(), ['MoneyInterface', 'Money', 'NullableMoney'])) {
            return \sprintf('CONCAT(%s.amount, \' \', %s.currency)', $propertyPath, $propertyPath);
        }

        if (\in_array($fieldAlias, FieldAlias::DATE_FIELDS)) {
            $propertyPath = \sprintf('DATE_FORMAT(%s, %s)', $propertyPath, '\'%Y-%m-%d\'');
        }

        return $propertyPath;
    }

    public function generateSearchExpressionByField(Field $field): ?string
    {
        // we search only on textual fields
        if (false === \in_array($field->getPropertyType(), ['string', 'ProgramChoiceOption'], true)) {
            return null;
        }

        return \sprintf('%s LIKE :search', $this->getPropertyPath($field));
    }

    public function generateJoinByField(Field $field): iterable
    {
        $fieldAlias        = $field->getFieldAlias();
        $fieldObjectClass  = $field->getObjectClass();
        $fieldPropertyName = $field->getReservationPropertyName();

        // we do not need to generate join for FinancingObject nor Program because they already in query by default
        // cf ReservationRepository::findByReportingFilters
        if (
            false === empty($fieldObjectClass)
            && FinancingObject::class !== $fieldObjectClass
            && Program::class !== $fieldObjectClass
        ) {
            yield $fieldObjectClass => ['r.' . $fieldPropertyName, $fieldPropertyName];
        }

        if ('ProgramChoiceOption' === $field->getPropertyType()) {
            $alias = \sprintf(self::ALIAS_FORMAT_PROGRAM_CHOICE_OPTION, $fieldAlias);

            yield $fieldAlias => [
                ProgramChoiceOption::class,
                $alias,
                Join::WITH,
                \sprintf('%s.id = %s.%s', $alias, $fieldPropertyName, $field->getPropertyPath()),
            ];

            if (\array_key_exists($fieldAlias, FieldAlias::NAF_NACE_FIELDS)) {
                $aliasNafNace = \sprintf(self::ALIAS_FORMAT_NAF_NACE, $fieldAlias);

                yield FieldAlias::NAF_NACE_FIELDS[$fieldAlias] => [
                    NafNace::class,
                    $aliasNafNace,
                    Join::WITH,
                    \sprintf('%s.description = %s.nafCode', $alias, $aliasNafNace),
                ];
            }
        }

        if ('reservation' === $field->getCategory() && 'currentStatus' === $fieldPropertyName) {
            $alias = \sprintf(self::ALIAS_FORMAT_RESERVATION_STATUS, $fieldAlias);

            yield $fieldAlias => [
                ReservationStatus::class,
                $alias,
                Join::WITH,
                \sprintf('%s.id = r.%s', $alias, $fieldPropertyName),
            ];
        }
    }

    /**
     * @param string|array $filter
     *
     * @throws Exception
     */
    public function generateClauseByFilter(string $fieldAlias, $filter): array
    {
        if (false === $this->isFieldAliasFilterValid($fieldAlias, $filter)) {
            return [];
        }

        // we do not return clause for filter[reservation_exclusion_date]
        // because exclusion ReservationStatus is not created yet
        // TODO remove this condition once implemented
        if (FieldAlias::RESERVATION_EXCLUSION_DATE === $fieldAlias) {
            return [];
        }

        $mappingOperators = $this->getMappingOperatorsByFilterKey($fieldAlias);
        $filterOperator   = (\is_string($filter)) ? '=' : \array_keys($filter)[0];
        $operator         = $mappingOperators[$filterOperator] ?? $filterOperator;
        $value            = (\is_string($filter)) ? $filter : $filter[$filterOperator];
        $parameterName    = \sprintf('%s_value', $fieldAlias);

        if (
            \in_array($fieldAlias, ReportingFilter::DATE_FILTER_KEYS, true)
            || \in_array($fieldAlias, ReportingFilter::DURATION_FILTER_KEYS, true)
        ) {
            if ('null' !== $value) {
                $value = (\in_array($fieldAlias, ReportingFilter::DURATION_FILTER_KEYS, true))
                    ? new DateTime(\sprintf('-%s MONTH', $value))
                    : new DateTime($value);
                $value = $value->format('Y-m-d');
            }
        }

        $expressions = [];

        foreach ($this->getFilterPropertyPaths($fieldAlias) as $propertyPath) {
            if ('=' === $operator && 'null' === $value) {
                $expressions[] = $propertyPath . ' IS NULL';

                continue;
            }

            $expressions[] = \sprintf(
                '%s %s :%s',
                $propertyPath,
                $operator,
                $parameterName
            );
        }

        return [
            'expression' => \implode(' OR ', $expressions),
            'parameter'  => ('null' === $value) ? [] : [$parameterName, $value],
        ];
    }

    /**
     * Filter should respect some format to be valid.
     * We black-hole >> mirror API Platform filters following these :
     *     - ?field_alias[self::MAPPING_DATE_OPERATORS]=value
     *     - ?field_alias[self::MAPPING_RANGE_OPERATORS]=value
     *     - ?field_alias=value.
     *
     * @param string|array $value
     */
    public function isFieldAliasFilterValid(string $fieldAlias, $value): bool
    {
        if (false === \in_array($fieldAlias, ReportingFilter::FIELD_ALIAS_FILTER_KEYS, true)) {
            return false;
        }

        if (\is_string($value) && false === $this->isFilterValueValid($fieldAlias, $value, true)) {
            return false;
        }

        if (\is_array($value)) {
            $mappingOperators = $this->getMappingOperatorsByFilterKey($fieldAlias);

            if (empty($mappingOperators)) {
                return false;
            }

            foreach ($value as $filterOperator => $filterOperatorValue) {
                if (empty($mappingOperators[$filterOperator])) {
                    return false;
                }

                if (false === $this->isFilterValueValid($fieldAlias, $filterOperatorValue)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function getFilterPropertyPaths(string $fieldAlias): array
    {
        $propertyPaths = [];

        if (ReportingFilter::FILTER_REPORTING_DATES === $fieldAlias) {
            return [
                'DATE_FORMAT(financingObjects.reportingFirstDate, \'%Y-%m-%d\')',
                'DATE_FORMAT(financingObjects.reportingLastDate, \'%Y-%m-%d\')',
                'DATE_FORMAT(financingObjects.reportingValidationDate, \'%Y-%m-%d\')',
            ];
        }

        $field = $this->fieldRepository->findOneBy(['fieldAlias' => $fieldAlias]);

        if ($field instanceof Field) {
            $propertyPaths[] = $this->getPropertyPath($field);
        }

        return $propertyPaths;
    }

    private function isFilterValueValid(string $key, string $value, bool $nullable = false): bool
    {
        $dateFormat  = ($nullable) ? '/^(\d{4}\-\d{2}\-\d{2}|null)$/' : '/^\d{4}\-\d{2}\-\d{2}$/';
        $digitFormat = ($nullable) ? '/^(\d+|null)$/' : '/^\d+$/';

        if (
            \in_array($key, ReportingFilter::DATE_FILTER_KEYS, true)
            && 1 === \preg_match($dateFormat, $value)
        ) {
            return true;
        }

        if (
            \in_array($key, ReportingFilter::DURATION_FILTER_KEYS, true)
            && 1 === \preg_match($digitFormat, $value)
        ) {
            return true;
        }

        return false;
    }
}
