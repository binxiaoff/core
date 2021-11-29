<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity\Constant;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\DateFilterInterface;
use KLS\Core\Entity\Constant\AbstractEnum;
use KLS\Core\Entity\Constant\MathOperator;

class ReportingFilter extends AbstractEnum
{
    //
    // Keys
    //

    public const FILTER_SEARCH = 'search';
    public const FILTER_ORDER  = 'order';

    public const FILTER_REPORTING_DATES = 'reporting_dates';

    public const DATE_FILTER_KEYS = [
        self::FILTER_REPORTING_DATES,
        FieldAlias::FIRST_RELEASE_DATE,
        FieldAlias::RESERVATION_EXCLUSION_DATE,
    ];

    public const DURATION_FILTER_KEYS = [
        FieldAlias::RESERVATION_SIGNING_DATE,
    ];

    public const AMOUNT_FILTER_KEYS = [
        FieldAlias::LOAN_REMAINING_CAPITAL,
    ];

    public const FIELD_ALIAS_FILTER_KEYS = [
        ...self::DATE_FILTER_KEYS,
        ...self::DURATION_FILTER_KEYS,
        ...self::AMOUNT_FILTER_KEYS,
    ];

    public const ALLOWED_FILTER_KEYS = [
        self::FILTER_SEARCH,
        ...self::FIELD_ALIAS_FILTER_KEYS,
        self::FILTER_ORDER,
    ];

    //
    // Operators
    //

    public const MAPPING_DATE_OPERATORS = [
        DateFilterInterface::PARAMETER_BEFORE          => '<=',
        DateFilterInterface::PARAMETER_STRICTLY_BEFORE => '<',
        DateFilterInterface::PARAMETER_AFTER           => '>=',
        DateFilterInterface::PARAMETER_STRICTLY_AFTER  => '>',
    ];

    public const MAPPING_RANGE_OPERATORS = [
        MathOperator::INFERIOR          => '<',
        MathOperator::INFERIOR_OR_EQUAL => '<=',
        MathOperator::SUPERIOR          => '>',
        MathOperator::SUPERIOR_OR_EQUAL => '>=',
    ];

    //
    // Values
    //

    public const ALLOWED_ORDER_VALUES = [
        'asc',
        'desc',
    ];
}
