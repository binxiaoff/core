<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker;


use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;


trait LenderChecker
{
    /**
     * @param Clients                  $client
     * @param \underlying_contract     $contract
     * @param ContractAttributeManager $contractAttributeManager
     *
     * @return bool
     * @throws \Exception
     */
    public function isEligibleForLenderType(Clients $client, \underlying_contract $contract, ContractAttributeManager $contractAttributeManager)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        $attrVars = $contractAttributeManager->getContractAttributesByType($contract, \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE);
        if (empty($attrVars)) {
            return true; // No limitation found!
        }

        return in_array($client->getType(), $attrVars);
    }
}
