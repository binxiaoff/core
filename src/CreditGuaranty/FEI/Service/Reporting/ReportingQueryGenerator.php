<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\Reporting;

use ArrayIterator;
use Exception;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;

class ReportingQueryGenerator
{
    private const FILTER_SEARCH = 'search';
    private const FILTER_ORDER  = 'order';

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
}
