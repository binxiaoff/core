<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ArrayIterator;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use KLS\CreditGuaranty\FEI\Repository\ReservationRepository;

class ReportingExtractor
{
    private ReservationRepository $reservationRepository;

    public function __construct(ReservationRepository $reservationRepository)
    {
        $this->reservationRepository = $reservationRepository;
    }

    /**
     * @throws Exception
     */
    public function extracts(
        ReportingTemplate $reportingTemplate,
        int $itemsPerPage,
        int $page,
        ?string $search
    ): Paginator {
        $fields  = $this->getOrderedFields($reportingTemplate);
        $filters = $this->generateFilters($fields, $search);

        return $this->reservationRepository->findByReportingFilters(
            $reportingTemplate->getProgram(),
            $filters['selects'] ?? [],
            $filters['joins'] ?? [],
            $filters['clauses'] ?? [],
            $itemsPerPage,
            $page
        );
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
     * @param array|Field[] $fields
     */
    private function generateFilters(array $fields, ?string $search): array
    {
        if (empty($fields)) {
            return [];
        }

        $selects = [];
        $joins   = [];
        $clauses = [];

        $searchExpressions = [];

        /** @var Field $field */
        foreach ($fields as $field) {
            foreach ($this->generateSelectByField($field) as $select) {
                $selects[] = $select;

                // generate search expressions
                if (
                    null !== $search
                    && ('string' === $field->getPropertyType() || 'ProgramChoiceOption' === $field->getPropertyType())
                ) {
                    $selectParts         = \explode(' AS ', $select);
                    $searchExpressions[] = $selectParts[0] . ' LIKE :search';
                }
            }

            foreach ($this->generateJoinByField($field) as $key => $join) {
                $joins[$key] = $join;
            }
        }

        if (null !== $search) {
            $clauses[] = [
                'expression' => \join(' OR ', $searchExpressions),
                'parameter'  => ['search', '%' . $search . '%'], // @todo be careful of special chars
            ];
        }

        return ['selects' => $selects, 'joins' => $joins, 'clauses' => $clauses];
    }

    private function generateSelectByField(Field $field): iterable
    {
        $fieldAlias = $field->getFieldAlias();

        if (\in_array($fieldAlias, FieldAlias::VIRTUAL_FIELDS, true)) {
            yield '\'\' AS ' . $fieldAlias;

            return;
        }

        if ('ProgramChoiceOption' === $field->getPropertyType()) {
            yield \sprintf(
                'pco_%s.description AS %s',
                $fieldAlias,
                $fieldAlias
            );

            return;
        }

        $fieldPropertyName = $field->getReservationPropertyName();

        if ('currentStatus' === $fieldPropertyName) {
            yield \sprintf('rs_%s.status AS %s', $fieldAlias, $fieldAlias);

            return;
        }

        $fieldPropertyPath = $field->getPropertyPath();

        $select = (empty($field->getObjectClass()) ? 'r.' : '') . $fieldPropertyName;

        if (false === empty($fieldPropertyPath)) {
            $select .= '.' . $fieldPropertyPath;
        }

        if (\in_array($field->getPropertyType(), ['MoneyInterface', 'Money', 'NullableMoney'])) {
            yield \sprintf('CONCAT(%s.amount, \' \', %s.currency) AS %s', $select, $select, $fieldAlias);

            return;
        }

        yield \sprintf('%s AS %s', $select, $fieldAlias);
    }

    private function generateJoinByField(Field $field): iterable
    {
        $fieldAlias        = $field->getFieldAlias();
        $fieldObjectClass  = $field->getObjectClass();
        $fieldPropertyName = $field->getReservationPropertyName();

        if (
            false === empty($fieldObjectClass)
            && FinancingObject::class !== $fieldObjectClass
            && Program::class !== $fieldObjectClass
        ) {
            yield $fieldObjectClass => ['r.' . $fieldPropertyName, $fieldPropertyName];
        }

        if ('ProgramChoiceOption' === $field->getPropertyType()) {
            $alias = \sprintf('pco_%s', $fieldAlias);

            yield $fieldAlias => [
                ProgramChoiceOption::class,
                $alias,
                Join::WITH,
                \sprintf('%s.id = %s.%s', $alias, $fieldPropertyName, $field->getPropertyPath()),
            ];
        }

        if ('currentStatus' === $fieldPropertyName) {
            $alias = \sprintf('rs_%s', $fieldAlias);

            yield $fieldAlias => [
                ReservationStatus::class,
                $alias,
                Join::WITH,
                \sprintf('%s.id = r.%s', $alias, $fieldPropertyName),
            ];
        }
    }
}
