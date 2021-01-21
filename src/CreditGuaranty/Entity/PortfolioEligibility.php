<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Unilend\CreditGuaranty\Entity\ConstantList\EligibilityCondition;

class PortfolioEligibility
{
    private Portfolio $portfolio;

    private EligibilityCondition $eligibilityCondition;

    // when the eligibility type is data or bool.
    private ?string $data;
}
