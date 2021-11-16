<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\Reporting;

use ArrayIterator;
use Exception;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Constant\ReportingFilter;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;

class ReportingQueryGenerator
{
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

    /**
     * @throws Exception
     */
    private function generateFilters(array $fields, array $filters): array
    {
        $this->cleanFilters($fields, $filters);

        $joins   = [];
        $clauses = [];

        foreach ($filters as $filterKey => $filter) {
            // we exclude order filters because they can already be passed as they are to query
            if (ReportingFilter::FILTER_ORDER === $filterKey) {
                continue;
            }

            if (ReportingFilter::FILTER_SEARCH === $filterKey) {
                $searchExpressions = [];

                foreach ($fields as $field) {
                    $searchExpression = $this->reportingQueryHelper->generateSearchExpressionByField($field);

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

            $clause = $this->reportingQueryHelper->generateClauseByFilter($filterKey, $filter);

            if (false === empty($clause)) {
                $clauses[] = $clause;

                // we do not need to generate joins for these filters like the search filter (from line 129)
                // because the joins of these filters belonging to Reservation or FinancingObject
                // already are in the query by default
                // we should generate them if we add a new filter which the field do not belong to any of these entities
            }
        }

        return [
            'joins'   => $joins,
            'clauses' => $clauses,
            'orders'  => $filters['order'] ?? [],
        ];
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
            if (false === \in_array($filterKey, ReportingFilter::ALLOWED_FILTER_KEYS, true)) {
                unset($filters[$filterKey]);

                continue;
            }

            // we ignore invalid format of existent filters like API Platform

            if (ReportingFilter::FILTER_SEARCH === $filterKey) {
                if (false === \is_string($filterValue)) {
                    unset($filters[$filterKey]);
                }

                continue;
            }

            if (ReportingFilter::FILTER_ORDER === $filterKey) {
                if (false === \is_array($filterValue)) {
                    unset($filters[$filterKey]);
                }
                if (empty($filterValue)) {
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
                    if (
                        false === \is_string($filterFieldValue)
                        || false === \in_array(
                            \mb_strtolower($filterFieldValue),
                            ReportingFilter::ALLOWED_ORDER_VALUES,
                            true
                        )
                    ) {
                        unset($filters[$filterKey][$filterFieldAlias]);
                    }
                }

                continue;
            }

            // we ignore field_alias filter if it does not respect the good format
            if (false === $this->reportingQueryHelper->isFieldAliasFilterValid($filterKey, $filterValue)) {
                unset($filters[$filterKey]);
            }
        }
    }
}
