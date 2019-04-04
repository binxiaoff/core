<?php

namespace Unilend\Service\Product\Contract\Validator;

use Unilend\Entity\{UnderlyingContract, UnderlyingContractAttributeType};
use Unilend\Service\Product\Contract\Checker\AutoBidSettingsChecker;
use Unilend\Service\Product\Contract\ContractAttributeManager;

class AutoBidSettingsValidator
{
    use AutoBidSettingsChecker;

    /** @var ContractAttributeManager */
    private $contractAttributeManager;

    public function __construct(ContractAttributeManager $contractAttributeManager)
    {
        $this->contractAttributeManager = $contractAttributeManager;
    }

    /**
     * @param UnderlyingContract $contract
     *
     * @return array
     */
    public function validate($contract)
    {
        $violations = [];

        if (false === $this->isEligibleForEligibility($contract, $this->contractAttributeManager)) {
            $violations[] = UnderlyingContractAttributeType::ELIGIBLE_AUTOBID;
        }

        return $violations;
    }
}
