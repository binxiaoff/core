<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Controller;

use Unilend\CreditGuaranty\Entity\Request\Eligibility;
use Unilend\CreditGuaranty\Service\EligibilityChecker;

class EligibilityChecking
{
    private EligibilityChecker $eligibilityChecker;

    public function __construct(EligibilityChecker $eligibilityChecker)
    {
        $this->eligibilityChecker = $eligibilityChecker;
    }

    public function __invoke(Eligibility $data): Eligibility
    {
        $data->ineligibles = $this->eligibilityChecker->check($data->reservation, $data->withConditions, $data->category);

        return $data;
    }
}
