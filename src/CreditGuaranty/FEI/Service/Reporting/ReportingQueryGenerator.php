<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\Reporting;

use ArrayIterator;
use Exception;
use KLS\Core\Entity\Constant\MathOperator;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;

class ReportingQueryGenerator
{
    private const FILTER_SEARCH          = 'search';
    private const FILTER_ORDER           = 'order';
    private const FILTER_REPORTING_DATES = 'reporting_dates';

    private const DATE_FILTER_KEYS = [
        self::FILTER_REPORTING_DATES,
        FieldAlias::FIRST_RELEASE_DATE,
        FieldAlias::RESERVATION_EXCLUSION_DATE,
    ];

    private const DURATION_FILTER_KEYS = [
        FieldAlias::RESERVATION_SIGNING_DATE,
    ];

    private const ALLOWED_FILTER_KEYS = [
        self::FILTER_SEARCH,
        ...self::DATE_FILTER_KEYS,
        ...self::DURATION_FILTER_KEYS,
        self::FILTER_ORDER,
    ];

    private const ALLOWED_ORDER_VALUES = [
        'asc',
        'desc',
    ];

    private const MAPPING_OPERATORS = [
        MathOperator::SUPERIOR          => '>',
        MathOperator::SUPERIOR_OR_EQUAL => '>=',
        MathOperator::INFERIOR          => '<',
        MathOperator::INFERIOR_OR_EQUAL => '<=',
        MathOperator::EQUAL             => '=',
    ];

    private FieldRepository $fieldRepository;
    private ReportingQueryHelper $reportingQueryHelper;

    public function __construct(FieldRepository $fieldRepository, ReportingQueryHelper $reportingQueryHelper)
    {
        $this->fieldRepository      = $fieldRepository;
        $this->reportingQueryHelper = $reportingQueryHelper;
    }

    /**
     * @throws Exception
     */
    public function generate(array $filters, ?ReportingTemplate $reportingTemplate = null): array
    {
        $searchableFields = ($reportingTemplate instanceof ReportingTemplate)
            ? $this->getOrderedFields($reportingTemplate)
            : $this->fieldRepository->findAll() // we retrieve all fields to be able to search or to filter
        ;

        $queryFilters = $this->generateFilters($searchableFields, $filters);

        $selects = [];
        $joins   = $queryFilters['joins']   ?? [];
        $clauses = $queryFilters['clauses'] ?? [];

        if ($reportingTemplate instanceof ReportingTemplate) {
            $selects = [
                'DATE_FORMAT(financingObjects.reportingFirstDate, \'%Y-%m-%d\') AS reporting_first_date',
                'DATE_FORMAT(financingObjects.reportingLastDate, \'%Y-%m-%d\') AS reporting_last_date',
                'DATE_FORMAT(financingObjects.reportingValidationDate, \'%Y-%m-%d\') AS reporting_validation_date',
            ];

            /** @var Field $field */
            foreach ($searchableFields as $field) {
                $selects[] = \sprintf(
                    '%s AS %s',
                    $this->reportingQueryHelper->getPropertyPath($field),
                    $field->getFieldAlias()
                );

                foreach ($this->reportingQueryHelper->generateJoinByField($field) as $key => $join) {
                    $joins[$key] = $join;
                }
            }
        }

        return [
            'selects' => $selects,
            'joins'   => $joins,
            'clauses' => $clauses,
            'orders'  => $queryFilters['orders'] ?? [],
        ];
    }

    private function generateFilters(array $fields, array $filters): array
    {
        $this->cleanFilters($fields, $filters);

        $joins   = [];
        $clauses = [];

        foreach ($filters as $filterKey => $filter) {
            // we exclude order filters because they can already be passed as they are to query
            if (self::FILTER_ORDER === $filterKey) {
                continue;
            }

            if (self::FILTER_SEARCH === $filterKey) {
                $searchExpressions = [];

                foreach ($fields as $field) {
                    $searchExpression = $this->generateSearchExpressionByField($field);

                    if (null !== $searchExpression) {
                        $searchExpressions[] = $searchExpression;

                        foreach ($this->reportingQueryHelper->generateJoinByField($field) as $key => $join) {
                            $joins[$key] = $join;
                        }
                    }
                }

                if (false === empty($searchExpressions)) {
                    $clauses[] = [
                        'expression' => \implode(' OR ', $searchExpressions),
                        'parameter'  => ['search', '%' . $filters['search'] . '%'], // @todo be careful of special chars
                    ];
                }

                continue;
            }

            // TODO generate filters clauses
        }

        return [
            'joins'   => $joins,
            'clauses' => $clauses,
            'orders'  => $filters['order'] ?? [],
        ];
    }

    private function generateSearchExpressionByField(Field $field): ?string
    {
        // we search only on textual fields
        if (false === \in_array($field->getPropertyType(), ['string', 'ProgramChoiceOption'], true)) {
            return null;
        }

        return \sprintf('%s LIKE :search', $this->reportingQueryHelper->getPropertyPath($field));
    }

    /**
     * @throws Exception
     *
     * @return array|Field[]
     */
    private function getOrderedFields(ReportingTemplate $reportingTemplate): array
    {
        if ($reportingTemplate->getReportingTemplateFields()->isEmpty()) {
            return [];
        }

        // sort reportingTemplateFields by position
        /** @var ArrayIterator $iterator */
        $iterator = $reportingTemplate->getReportingTemplateFields()->getIterator();
        $iterator->uasort(fn ($a, $b) => $a->getPosition() > $b->getPosition() ? 1 : -1);

        // retrieve only field
        $fields = $iterator->getArrayCopy();
        \array_walk_recursive($fields, fn (&$item) => $item = $item->getField());

        return $fields;
    }

    /**
     * @param Field[]|array $fields
     */
    private function cleanFilters(array $fields, array &$filters): void
    {
        // field aliases of reportingTemplateFields
        $fieldAliases = \array_map(static fn ($item) => $item->getFieldAlias(), $fields);

        foreach ($filters as $filterKey => $filterValue) {
            // we ignore non-existent filters like API Platform
            if (false === \in_array($filterKey, self::ALLOWED_FILTER_KEYS)) {
                unset($filters[$filterKey]);
            }

            // we ignore invalid values of existent filters like API Platform

            if (self::FILTER_ORDER === $filterKey) {
                if (false === \is_array($filterValue)) {
                    unset($filters[$filterKey]);
                }

                foreach ($filterValue as $filterFieldAlias => $filterFieldValue) {
                    // we remove the order filter item
                    // if field_alias does not belong to reportingTemplateFields aliases and to reporting dates aliases
                    // or if value is invalid
                    if (
                        false === \in_array($filterFieldAlias, $fieldAliases)
                        && false === \in_array($filterFieldAlias, FieldAlias::REPORTING_DATE_FIELDS)
                    ) {
                        unset($filters[$filterKey][$filterFieldAlias]);
                    }
                    if (false === \in_array(\mb_strtolower($filterFieldValue), self::ALLOWED_ORDER_VALUES)) {
                        unset($filters[$filterKey][$filterFieldAlias]);
                    }
                }
            }

            if (\in_array($filterKey, self::DATE_FILTER_KEYS)) {
                if (false === \is_array($filterValue)) {
                    unset($filters[$filterKey]);
                }

                foreach ($filterValue as $filterOperator => $filterOperatorValue) {
                    if (empty(self::MAPPING_OPERATORS[$filterOperator])) {
                        unset($filters[$filterKey]);
                    }

                    if (0 === \preg_match('/^(\d{4}\-\d{2}\-\d{2}|null)$/', $filterOperatorValue)) {
                        unset($filters[$filterKey]);
                    }
                }
            }

            if (\in_array($filterKey, self::DURATION_FILTER_KEYS)) {
                if (false === \is_array($filterValue)) {
                    unset($filters[$filterKey]);
                }

                foreach ($filterValue as $filterOperator => $filterOperatorValue) {
                    if (empty(self::MAPPING_OPERATORS[$filterOperator])) {
                        unset($filters[$filterKey]);
                    }

                    if (0 === \preg_match('/^(\d+|null)$/', $filterOperatorValue)) {
                        unset($filters[$filterKey]);
                    }
                }
            }
        }
    }
}
