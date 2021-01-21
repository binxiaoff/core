<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

class PortfolioBorrowerTypeAllocation
{
    private Portfolio $portfolio;
    private ProgramBorrowerType $programBorrowerType;
    private string $maxAllocationRate;
}
