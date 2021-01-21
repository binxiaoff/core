<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

class PortfolioEligibilityItemConfiguration
{
    private const OPERATION_EQUAL_TO = '=';
    private const OPERATION_GREATER_THAN = '>';
    private const OPERATION_GREATER_OR_EQUAL_THAN = '>=';
    private const OPERATION_LESS_THAN = '<';
    private const OPERATION_LESS_OR_EQUAL_THAN = '<=';

    private const DATA_TYPE_RATE = 'rate';
    private const DATA_TYPE_VALUE = 'value';

    private string $leftOperand;
    private string $rightOperand;
    private string $operation;
    private string $valueType;
    private string $data;
}
