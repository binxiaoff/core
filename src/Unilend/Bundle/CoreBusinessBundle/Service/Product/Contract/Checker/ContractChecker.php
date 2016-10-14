<?php


namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker;


use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;

trait ContractChecker
{

    /**
     * @param \underlying_contract $contract
     * @param ContractAttributeManager $contractAttributeManager
     *
     * @return bool
     */
    public function isContractAutobidEligible(\underlying_contract $contract, ContractAttributeManager $contractAttributeManager)
    {
        $attrVars = $contractAttributeManager->getContractAttributesByType($contract, \underlying_contract_attribute_type::ELIGIBLE_AUTOBID);
        if (empty($attrVars)) {
            return true; // No limitation found!
        }

        return in_array(\underlying_contract_attribute::ELIGIBLE_AUTOBID_TRUE_VALUE, $attrVars);
    }

    /**
     * @param \underlying_contract $contract
     * @param ContractAttributeManager $contractAttributeManager
     *
     * @return null|int
     */
    public function getMaxEligibleAmount(\underlying_contract $contract, ContractAttributeManager $contractAttributeManager)
    {
        $attrVars = $contractAttributeManager->getContractAttributesByType($contract, \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);

        if (empty($attrVars)) {
            return null; // No limitation found!
        }

        return $attrVars[0];
    }

}
