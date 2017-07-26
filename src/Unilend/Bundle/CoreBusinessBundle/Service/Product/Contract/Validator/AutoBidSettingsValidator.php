<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Validator;

use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker\AutoBidSettingsChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractAttributeManager;

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
