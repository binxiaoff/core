<?php

namespace Unilend\Service\Product\Contract\Checker;

use Unilend\Entity\{UnderlyingContract, UnderlyingContractAttributeType};
use Unilend\Service\Product\Contract\ContractAttributeManager;

trait AutoBidSettingsChecker
{
    /**
     * @param UnderlyingContract       $contract
     * @param ContractAttributeManager $contractAttributeManager
     *
     * @return bool
     */
    public function isEligibleForEligibility(UnderlyingContract $contract, ContractAttributeManager $contractAttributeManager)
    {
        $attrVars = $contractAttributeManager->getContractAttributesByType($contract, UnderlyingContractAttributeType::ELIGIBLE_AUTOBID);
        if (empty($attrVars)) {
            return true; // No limitation found!
        }

        return (bool) $attrVars[0];
    }
}
