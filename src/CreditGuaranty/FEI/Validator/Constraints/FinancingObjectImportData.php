<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class FinancingObjectImportData extends Constraint
{
    public const INVALID_DATA_ERROR = '305b94fe-68bc-11ec-90d6-0242ac120003';
}
