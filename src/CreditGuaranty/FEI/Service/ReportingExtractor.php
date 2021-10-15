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
    public function extracts(ReportingTemplate $reportingTemplate, int $itemsPerPage, int $page): Paginator
    {
        $fields  = $this->getOrderedFields($reportingTemplate);
        $filters = $this->generateFilters($fields);

        return $this->reservationRepository->findByReportingFilters(
            $filters['selects'] ?? [],
            $filters['joins'] ?? [],
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
    private function generateFilters(array $fields): array
    {
        if (empty($fields)) {
            return [];
        }

        $selects = [];
        $joins   = [];

        /** @var Field $field */
        foreach ($fields as $field) {
            foreach ($this->generateSelectByField($field) as $select) {
                $selects[] = $select;
            }

            $fieldObjectClass  = $field->getObjectClass();
            $fieldPropertyName = $field->getReservationPropertyName();
            $fieldPropertyPath = $field->getPropertyPath();

            if (
                false === empty($fieldObjectClass)
                && false === \array_key_exists($fieldObjectClass, $joins)
                && FinancingObject::class !== $fieldObjectClass
            ) {
                $joins[$fieldObjectClass] = ['r.' . $fieldPropertyName, $fieldPropertyName];
            }

            if ('ProgramChoiceOption' === $field->getPropertyType()) {
                $alias = \sprintf('pco_%s_%s', $fieldPropertyName, $fieldPropertyPath);

                $joins[$field->getFieldAlias()] = [ProgramChoiceOption::class, $alias, Join::WITH, \sprintf('%s.id = %s.%s', $alias, $fieldPropertyName, $fieldPropertyPath)];
            }

            if ('currentStatus' === $fieldPropertyName) {
                $alias = \sprintf('rs_%s', $fieldPropertyName);

                $joins[$field->getFieldAlias()] = [ReservationStatus::class, $alias, Join::WITH, \sprintf('%s.id = r.%s', $alias, $fieldPropertyName)];
            }
        }

        return ['selects' => $selects, 'joins' => $joins];
    }

    private function generateSelectByField(Field $field): iterable
    {
        $alias = ' AS ' . $field->getFieldAlias();

        if (\in_array($field->getFieldAlias(), FieldAlias::VIRTUAL_FIELDS, true)) {
            yield '\'\'' . $alias;

            return;
        }

        if ('ProgramChoiceOption' === $field->getPropertyType()) {
            yield \sprintf('pco_%s_%s.%s %s', $field->getReservationPropertyName(), $field->getPropertyPath(), 'description', $alias);

            return;
        }

        if ('currentStatus' === $field->getReservationPropertyName()) {
            yield \sprintf('rs_%s.%s %s', $field->getReservationPropertyName(), 'status', $alias);

            return;
        }

        $select = (empty($field->getObjectClass()) ? 'r.' : '') . $field->getReservationPropertyName();

        if (false === empty($field->getPropertyPath())) {
            $select .= '.' . $field->getPropertyPath();
        }

        if (\in_array($field->getPropertyType(), ['MoneyInterface', 'Money', 'NullableMoney'])) {
            yield \sprintf('CONCAT(%s.amount, \' \', %s.currency) %s', $select, $select, $alias);

            return;
        }

        yield $select . $alias;
    }
}
