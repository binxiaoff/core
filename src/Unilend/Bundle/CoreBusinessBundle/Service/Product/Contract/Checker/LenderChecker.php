<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;

trait LenderChecker
{
    /**
     * @param \lenders_accounts        $lender
     * @param \underlying_contract     $contract
     * @param EntityManager            $entityManager
     * @param ContractAttributeManager $contractAttributeManager
     *
     * @return bool
     */
    public function isEligibleForLenderType(\lenders_accounts $lender, \underlying_contract $contract, EntityManager $entityManager, ContractAttributeManager $contractAttributeManager)
    {
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        if (false === $client->get($lender->id_client_owner)) {
            throw new \InvalidArgumentException('The client id ' . $lender->id_client_owner . ' does not exist');
        }

        $attrVars = $contractAttributeManager->getContractAttributesByType($contract, \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE);
        if (empty($attrVars)) {
            return true; // No limitation found!
        }

        return in_array($client->type, $attrVars);
    }
}
