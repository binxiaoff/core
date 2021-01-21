<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

class PortfolioGradeAllocation
{
    private Portfolio $portfolio;
    private string $grade;
    private string $maxAllocationRate;
}
