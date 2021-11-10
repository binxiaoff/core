<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\Reporting;

use Doctrine\ORM\Query\Expr\Join;
use KLS\Core\Entity\NafNace;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;

class ReportingQueryHelper
{
    private const ALIAS_FORMAT_PROGRAM_CHOICE_OPTION = 'pco_%s';
    private const ALIAS_FORMAT_NAF_NACE              = 'pco_naf_nace_%s';
    private const ALIAS_FORMAT_RESERVATION_STATUS    = 'rs_%s';

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
}
