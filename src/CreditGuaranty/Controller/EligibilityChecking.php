<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Controller;

use Unilend\CreditGuaranty\Entity\Request\Eligibility;

class EligibilityChecking
{
    public function __invoke(Eligibility $data): Eligibility
    {
        return $data;
    }
}
