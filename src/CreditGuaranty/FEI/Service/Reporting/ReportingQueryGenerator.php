<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\Reporting;

use Exception;
use KLS\Core\DTO\Query;
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
    public function generate(array $filters, ?ReportingTemplate $reportingTemplate = null): Query
    {
        $searchableFields = ($reportingTemplate instanceof ReportingTemplate)
            ? $reportingTemplate->getOrderedFields()
            : $this->fieldRepository->findAll() // we retrieve all fields to be able to search or to filter
        ;

        $query = new Query();

        if ($reportingTemplate instanceof ReportingTemplate) {
            if (0 === $reportingTemplate->getReportingTemplateFields()->count()) {
                return $query;
            }

            foreach (FieldAlias::MAPPING_REPORTING_DATES as $fieldAlias => $property) {
                $query->addSelect(
                    \sprintf('DATE_FORMAT(financingObjects.%s, %s) AS %s', $property, '\'%Y-%m-%d\'', $fieldAlias)
                );
            }

            /** @var Field $field */
            foreach ($searchableFields as $field) {
                $query->addSelect(\sprintf(
                    '%s AS %s',
                    $this->reportingQueryHelper->getPropertyPath($field, true),
                    $field->getFieldAlias()
                ));

                foreach ($this->reportingQueryHelper->generateJoinByField($field) as $key => $join) {
                    $query->addJoin([$key => $join]);
                }
            }
        }

        $this->generateFilters($query, $searchableFields, $filters);

        return $query;
    }

    /**
     * @throws Exception
     */
    private function generateFilters(Query $query, array $fields, array $filters): void
    {
        $this->cleanFilters($fields, $filters);

        foreach ($filters as $filterKey => $filter) {
            if (ReportingFilter::FILTER_ID === $filterKey) {
                $query->addClause([
                    'expression' => 'financingObjects.id IN (:financingObjectIds)',
                    'parameter'  => ['financingObjectIds', \is_string($filter) ? [$filter] : $filter],
                ]);

                continue;
            }

            if (ReportingFilter::FILTER_SEARCH === $filterKey) {
                $searchExpressions = [];

                foreach ($fields as $field) {
                    $searchExpression = $this->reportingQueryHelper->generateSearchExpressionByField($field);

                    if (null !== $searchExpression) {
                        $searchExpressions[] = $searchExpression;

                        foreach ($this->reportingQueryHelper->generateJoinByField($field) as $key => $join) {
                            $query->addJoin([$key => $join]);
                        }
                    }
                }

                if (false === empty($searchExpressions)) {
                    $query->addClause([
                        'expression' => \implode(' OR ', $searchExpressions),
                        'parameter'  => ['search', '%' . $filters['search'] . '%'],
                    ]);
                }

                continue;
            }

            if (ReportingFilter::FILTER_ORDER === $filterKey) {
                $query->addOrder($filter);

                continue;
            }

            $clause = $this->reportingQueryHelper->generateClauseByFilter($filterKey, $filter);
            $query->addClause($clause);

            // we do not need to generate joins for these filters like the search filter (from line 129)
            // because the joins of these filters belonging to Reservation or FinancingObject
            // already are in the query by default
            // we should generate them if we add a new filter which the field do not belong to any of these entities
        }
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

            if (ReportingFilter::FILTER_ID === $filterKey) {
                if (\is_string($filterValue) && false === \is_numeric($filterValue)) {
                    unset($filters[$filterKey]);

                    continue;
                }

                if (\is_array($filterValue)) {
                    if (empty($filterValue)) {
                        unset($filters[$filterKey]);

                        continue;
                    }

                    foreach ($filterValue as $value) {
                        if (false === \is_numeric($value)) {
                            unset($filters[$filterKey]);
                        }
                    }
                }

                continue;
            }

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
                        false === \array_key_exists($filterFieldAlias, FieldAlias::MAPPING_REPORTING_DATES)
                        && false === \in_array($filterFieldAlias, $fieldAliases, true)
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
