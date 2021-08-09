<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Controller;

use KLS\CreditGuaranty\Entity\Request\Eligibility;
use KLS\CreditGuaranty\Service\EligibilityChecker;

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
