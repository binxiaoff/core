<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ArrayIterator;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use KLS\Core\Entity\NafNace;
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
        array $orders,
        ?string $search
    ): Paginator {
        $fields  = $this->getOrderedFields($reportingTemplate);
        $filters = $this->generateFilters($fields, $search);

        return $this->reservationRepository->findByReportingFilters(
            $reportingTemplate->getProgram(),
            $filters['selects'] ?? [],
            $filters['joins'] ?? [],
            $filters['clauses'] ?? [],
            $orders,
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
            $select    = $this->generateSelectByField($field);
            $selects[] = $select;

            if (null !== $search) {
                $searchExpression = $this->generateSearchExpression($field, $select);

                if (null !== $searchExpression) {
                    $searchExpressions[] = $searchExpression;
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

    /**
     * We need to generate the select for each reporting template field to respect its position whatever the field type.
     */
    private function generateSelectByField(Field $field): string
    {
        $fieldAlias        = $field->getFieldAlias();
        $fieldPropertyPath = $field->getPropertyPath();

        // Virtual fields are fields which values are accessed by dynamic getters,
        // and investment_thematic field was hard to concatenate in sql all at once
        // (maybe re-try in sql to avoid to do it in normalizer and to be able to order by this field)
        // that's why we define here an empty string as the value of these fields
        // which will be defined in ReportingNormalizer.
        if (
            \in_array($fieldAlias, FieldAlias::VIRTUAL_FIELDS, true)
            || 'investmentThematics' === $fieldPropertyPath
        ) {
            return '\'\' AS ' . $fieldAlias;
        }

        if ('ProgramChoiceOption' === $field->getPropertyType()) {
            // we only need nace code because CASA needs it in reporting to send to FEI
            if (\array_key_exists($fieldAlias, FieldAlias::NAF_NACE_FIELDS)) {
                return \sprintf(
                    'pco_naf_nace_%s.naceCode AS %s',
                    $fieldAlias,
                    FieldAlias::NAF_NACE_FIELDS[$fieldAlias]
                );
            }

            return \sprintf(
                'pco_%s.description AS %s',
                $fieldAlias,
                $fieldAlias
            );
        }

        $fieldPropertyName = $field->getReservationPropertyName();

        if ('currentStatus' === $fieldPropertyName) {
            return \sprintf('rs_%s.status AS %s', $fieldAlias, $fieldAlias);
        }

        $select = (empty($field->getObjectClass()) ? 'r.' : '') . $fieldPropertyName;
        $select .= (false === empty($fieldPropertyPath)) ? '.' . $fieldPropertyPath : '';

        if (\in_array($field->getPropertyType(), ['MoneyInterface', 'Money', 'NullableMoney'])) {
            return \sprintf('CONCAT(%s.amount, \' \', %s.currency) AS %s', $select, $select, $fieldAlias);
        }

        if (\in_array($fieldAlias, FieldAlias::DATE_FIELDS)) {
            $select = \sprintf('DATE_FORMAT(%s, %s)', $select, '\'%Y-%m-%d\'');
        }

        return \sprintf('%s AS %s', $select, $fieldAlias);
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

            if (\array_key_exists($fieldAlias, FieldAlias::NAF_NACE_FIELDS)) {
                $aliasNafNace = \sprintf('pco_naf_nace_%s', $fieldAlias);

                yield FieldAlias::NAF_NACE_FIELDS[$fieldAlias] => [
                    NafNace::class,
                    $aliasNafNace,
                    Join::WITH,
                    \sprintf('%s.description = %s.nafCode', $alias, $aliasNafNace),
                ];
            }
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

    private function generateSearchExpression(Field $field, string $select): ?string
    {
        if (false === \in_array($field->getPropertyType(), ['string', 'ProgramChoiceOption'], true)) {
            return null;
        }

        \preg_match('/^(.+) AS \w+/', $select, $matches);
        $selectPropertyPath = $matches[1] ?? '';

        // we cannot search on some fields because these fields values are defined in ReportingNormalizer
        // so we have to ignore selects having a ''
        if ('' === $selectPropertyPath) {
            return null;
        }

        return $selectPropertyPath . ' LIKE :search';
    }
}
